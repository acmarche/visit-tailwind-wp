<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entities\Specification\SpecData;
use AcMarche\Pivot\Spec\SpecTypeEnum;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\String\UnicodeString;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Twig
{
    public static ?Environment $instanceObject = null;

    public static function LoadTwig(?string $path = null): Environment
    {
        if (self::$instanceObject) {
            return self::$instanceObject;
        }

        if (!$path) {
            $path = get_template_directory().'/templates';
        }

        $loader = new FilesystemLoader($path);
        (new Dotenv())
            ->bootEnv(ABSPATH.'.env');

        $environment = new Environment(
            $loader,
            [
                'cache' => $_ENV['APP_CACHE_DIR'] ?? Cache::getPathCache(),
                'debug' => WP_DEBUG,
            ]
        );

        $loader->addPath(get_template_directory().'/templates/', 'VisitTail');
        $environment->addExtension(new DebugExtension());

        $translator = LocaleHelper::iniTranslator();
        $environment->addExtension(new TranslationExtension($translator));
        $environment->addExtension(new StringExtension());
        $environment->addExtension(new IntlExtension());

        $environment->addGlobal('template_directory', get_template_directory_uri());
        $environment->addGlobal('locale', LocaleHelper::getSelectedLanguage());
        $environment->addFilter(self::categoryLink());
        $environment->addFilter(self::translation());
        $environment->addFilter(self::autoLink());
        $environment->addFilter(self::makeClikable());
        $environment->addFunction(self::showTemplate());
        $environment->addFunction(self::currentUrl());
        $environment->addFunction(self::templateUri());
        $environment->addFunction(self::isExternalUrl());
        $environment->addFilter(self::removeHtml());
        $environment->addFilter(self::renderValuePivot());
        $environment->addFilter(self::checkDisplay());

        self::$instanceObject = $environment;

        return self::$instanceObject;
    }

    public static function rendPage(string $templatePath, array $variables = []): void
    {
        $twig = self::LoadTwig();
        //force
        $variables['language'] = LocaleHelper::getSelectedLanguage();
        try {
            echo $twig->render(
                $templatePath,
                $variables,
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            echo $twig->render(
                '@VisitTail/errors/500.html.twig',
                [
                    'message' => $e->getMessage()." ligne ".$e->getLine()." file ".$e->getFile(),
                    'error' => $e,
                    'title' => "La page n'a pas pu être chargée",
                    'tags' => [],
                    'relations' => [],
                ]
            );
            $url = RouterPivot::getCurrentUrl();
            Mailer::sendError('Error page: '.$templatePath, $url.' \n '.$e->getMessage());
        }
    }

    public static function rend500Page(): void
    {
        $twig = self::LoadTwig();

        echo $twig->render(
            '@VisitTail/errors/500.html.twig',
            [
                'excerpt' => null,
                'image' => get_template_directory_uri().'/assets/images/error500.jpg',
                'urlBack' => '/',
                'categoryName' => 'Accueil',
                'nameBack' => 'Acceuil',
            ]
        );
    }

    public static function rend404Page(): void
    {
        $twig = self::LoadTwig();

        echo $twig->render(
            '@VisitTail/errors/404.html.twig',
            [
                'excerpt' => null,
                'title' => null,
                'message' => null,
                'image' => get_template_directory_uri().'/assets/images/error404.jpg',
                'url' => RouterPivot::getCurrentUrl(),
                'urlBack' => '/',
                'categoryName' => 'Accueil',
                'nameBack' => 'Acceuil',
            ]
        );
    }

    /**
     * For sharing pages.
     */
    public static function currentUrl(): TwigFunction
    {
        return new TwigFunction(
            'currentUrl',
            fn(): string => RouterPivot::getCurrentUrl()
        );
    }

    protected static function categoryLink(): TwigFilter
    {
        return new TwigFilter(
            'category_link',
            fn(int $categoryId): ?string => get_category_link($categoryId)
        );
    }

    protected static function translation(): TwigFilter
    {
        return new TwigFilter(
            'translationjf',
            function ($x, Offre $offre, string $property): ?string {
                $selectedLanguage = LocaleHelper::getSelectedLanguage();

                return $offre->{$property}->languages[$selectedLanguage];
            }
        );
    }

    protected static function showTemplate(): TwigFunction
    {
        return new TwigFunction(
            'showTemplate',
            function (): string {
                if (true === WP_DEBUG) {
                    global $template;

                    return 'template: '.$template;
                }

                return '';
            }
        );
    }

    protected static function isExternalUrl(): TwigFunction
    {
        return new TwigFunction(
            'isExternalUrl',
            function (string $url): bool {
                if (preg_match('#http#', $url)) {
                    return !preg_match('#https://visitmarche.be#', $url);
                }

                return false;
            }
        );
    }

    private static function autoLink(): TwigFilter
    {
        return new TwigFilter(
            'auto_link',
            fn(string $text, string $type): string => match ($type) {
                'url' => '<a href="'.$text.'">'.$text.'</a>',
                'mail' => '<a href="mailto:'.$text.'">'.$text.'</a>',
                'tel' => '<a href="tel:'.$text.'">'.$text.'</a>',
                default => $text,
            }
        );
    }

    private static function templateUri(): TwigFunction
    {
        return new TwigFunction(
            'template_uri',
            fn(): string => get_template_directory_uri()
        );
    }

    private static function makeClikable(): TwigFilter
    {
        return new TwigFilter(
            'make_clikable',
            fn(string $text): string => make_clickable($text)
        );
    }

    private static function removeHtml(): TwigFilter
    {
        return new TwigFilter(
            'remove_html',
            function (?string $text): ?string {
                if (!$text) {
                    return $text;
                }

                return strip_tags($text);
            },
            [
                'is_safe' => ['html'],
            ]
        );
    }

    private static function renderValuePivot(): TwigFilter
    {
        return new TwigFilter(
            'format_pivot_value',
            function (SpecData $specData): ?string {
                $return_value = match ($specData->type) {
                    SpecTypeEnum::BOOLEAN->value => '',
                    SpecTypeEnum::CHOICE->value => '',//todo
                    SpecTypeEnum::TEXTML->value => $specData->value,
                    SpecTypeEnum::STRINGML->value => $specData->value,
                    SpecTypeEnum::CURRENCY->value => $specData->value.' €',
                    SpecTypeEnum::DATE->value => $specData->value,
                    SpecTypeEnum::PHONE->value, SpecTypeEnum::GSM->value => '<a href="tel:'.$specData->value.'">'.$specData->value.'</a>',
                    SpecTypeEnum::EMAIL->value => '<a href="mailto:'.$specData->value.'">'.$specData->value.'</a>',
                    SpecTypeEnum::URL->value, SpecTypeEnum::URL_FACEBOOK->value, SpecTypeEnum::URL_TRIPADVISOR->value => '<a href="'.$specData->value.'">'.$specData->value.'</a>',
                    default => $specData->value
                };

                return $return_value;
            }, [
                'is_safe' => ['html'],
            ]
        );
    }

    /**
     * N'affiche pas en fr si urn commence par en ou nl
     * en:urn:fld:orc
     * @return TwigFilter
     */
    private static function checkDisplay(): TwigFilter
    {
        return new TwigFilter(
            'pivot_check_display',
            function (SpecData $specData): bool {
                if ($specData->type == SpecTypeEnum::CHOICE->value) {
                    return false;
                }
                $useless = array(
                    'urn:val:class:michstar:nc',
                    'urn:val:class:michfour:nc',
                    'urn:val:class:gaultmiltoq:nc',
                    'urn:fld:attestincendie:dateech',
                    'urn:fld:idautor',
                    'urn:fld:dateech',
                );
                if (in_array($specData->urn, $useless)) {
                    return false;
                }
                $language = LocaleHelper::getSelectedLanguage();
                $text = (new UnicodeString($specData->urn));
                if ($language == 'fr' && $text->startsWith('urn')) {
                    return true;
                }
                if ($text->slice(0, 2) == $language) {
                    return true;
                }

                return false;
            }
        );
    }

}
