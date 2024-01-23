<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entity\TypeOffre;

class FilterStd
{
    const TYPE_PIVOT = 'pivot';
    const TYPE_WP = 'wp';

    public ?string $url = null;
    public ?string $urn = null;

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    public static function createFromTypeOffre(TypeOffre $typeOffre): FilterStd
    {
        $filter = new self($typeOffre->id, $typeOffre->name, self::TYPE_PIVOT);
        $filter->urn = $typeOffre->urn;

        return $filter;
    }

    public static function createFromCategory(\WP_Term $category): FilterStd
    {
        return new self($category->term_id, $category->name, self::TYPE_WP);
    }
}