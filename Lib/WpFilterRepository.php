<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Entity\TypeOffre;
use AcMarche\Pivot\Spec\UrnList;
use Doctrine\ORM\NonUniqueResultException;
use VisitMarche\ThemeTail\Inc\Theme;

class WpFilterRepository
{
    public const PIVOT_REFRUBRIQUE = 'pivot_refrubrique';
    public const PIVOT_REFOFFERS = 'pivot_ref_offers';

    /**
     * @param int $categoryId
     * @param bool $flatWithChildren
     * @param bool $removeFilterEmpty
     * @param bool $unsetParent
     * @param bool $onlyPivot
     * @return FilterStd[]
     */
    public function getCategoryFilters(
        int $categoryId,
        bool $flatWithChildren = false,
        bool $removeFilterEmpty = true,
        bool $unsetParent = false,
        bool $onlyPivot = false
    ): array {

        $typesOffre = array_map(fn(TypeOffre $typeOffre) => FilterStd::createFromTypeOffre($typeOffre),
            $this->getTypesOffreByCategoryId(
                $categoryId,
                $flatWithChildren,
                $removeFilterEmpty,
                $unsetParent
            ));

        $children = [];

        if (!$onlyPivot) {
            $wpRepository = new WpRepository();
            $children = array_map(fn(\WP_Term $category) => FilterStd::createFromCategory($category),
                $wpRepository->getChildrenOfCategory($categoryId));
            $children = RouterPivot::setRoutesToFilters($children, $categoryId);
        }

        $filters = [...$typesOffre, ...$children];
        RouterPivot::setRoutesToFilters($filters, $categoryId);

        return $filters;
    }


    /**
     * @param int $categoryWpId
     * @param bool $flatWithChildren
     * @param bool $removeFilterEmpty
     * @param bool $unsetParent
     * @return TypeOffre[]
     * @throws NonUniqueResultException
     */
    public function getTypesOffreByCategoryId(
        int $categoryWpId,
        bool $flatWithChildren = false,
        bool $removeFilterEmpty = true,
        bool $unsetParent = false
    ): array {
        if (in_array($categoryWpId, Theme::CATEGORIES_HEBERGEMENT)) {
            return self::getChildrenHebergements($removeFilterEmpty);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_AGENDA)) {
            return self::getChildrenEvents($removeFilterEmpty);
        }
        if (in_array($categoryWpId, Theme::CATEGORIES_RESTAURATION)) {
            return self::getChildrenRestauration($removeFilterEmpty);
        }

        $categoryUrns = self::getMetaPivotTypesOffre($categoryWpId);
        $typeOffreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $allFiltres = [];

        foreach ($categoryUrns as $categoryUrn) {

            if (!isset($categoryUrn['urn'])) {
                continue;
            }

            try {
                $typeOffre = $typeOffreRepository->findOneByUrn($categoryUrn['urn']);
                if (!$typeOffre) {
                    continue;
                }
            } catch (NonUniqueResultException $e) {
                continue;
            }

            //bug parent is a proxy
            if ($unsetParent) {
                if ($typeOffre->parent) {
                    $typeOffre->parent = $typeOffreRepository->find($typeOffre->parent->id);
                }
            }
            $typeOffre->withChildren = $categoryUrn['withChildren'];
            $allFiltres[] = $typeOffre;

            /**
             * Force à ne pas prendre les enfants
             */
            if ($flatWithChildren) {
                continue;
            }

            if ($categoryUrn['withChildren']) {
                $children = $typeOffreRepository->findByParent($typeOffre->id, $removeFilterEmpty);
                foreach ($children as $typeOffreChild) {
                    //bug parent is a proxy
                    if ($typeOffreChild->parent) {
                        $typeOffreChild->parent = $typeOffreRepository->find($typeOffreChild->parent->id);
                    }
                    $allFiltres[] = $typeOffreChild;
                }
            }
        }

        return $allFiltres;
    }

    /**
     * @param int $categoryId
     * @return string[]
     */
    public function getCodesCgtByCategoryId(int $categoryId): array
    {
        return self::getMetaPivotCodesCgtOffres($categoryId);
    }

    /**
     * @return TypeOffre[]
     * @throws \Exception
     */
    public static function getChildrenEvents(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);

        return $filtreRepository->findByUrnLike(UrnList::CATEGORIE_EVENT->value.':');
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException|\Exception
     */
    public static function getChildrenRestauration(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $barVin = $filtreRepository->findOneByUrn(UrnList::BAR_VIN->value);

        return $filtreRepository->findByParent($barVin->parent->id, $removeFilterEmpty);
    }

    /**
     * @return TypeOffre[]
     * @throws NonUniqueResultException
     */
    public static function getChildrenHebergements(bool $removeFilterEmpty): array
    {
        $filtreRepository = PivotContainer::getTypeOffreRepository(WP_DEBUG);
        $filtre = $filtreRepository->findOneByUrn(UrnList::HERGEMENT->value);

        $filters = $filtreRepository->findByParent($filtre->id, $removeFilterEmpty);
        foreach ($filters as $key => $filter) {
            if ($filter->urn == 'urn:val:typeheb:chbhot') {
                unset($filters[$key]);
            }
            if ($filter->id == 188) {
                unset($filters[$key]);
            }
        }

        return array_values($filters);
    }

    public static function getMetaPivotTypesOffre(int $wpCategoryId): array
    {
        $filtres = get_term_meta($wpCategoryId, self::PIVOT_REFRUBRIQUE, true);
        if (!is_array($filtres)) {
            return [];
        }

        return $filtres;
    }

    public static function getMetaPivotCodesCgtOffres(int $wpCategoryId): array
    {
        $offers = get_term_meta($wpCategoryId, self::PIVOT_REFOFFERS, true);
        if (!is_array($offers)) {
            return [];
        }

        return $offers;
    }
}