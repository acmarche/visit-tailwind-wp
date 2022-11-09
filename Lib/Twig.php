<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Dotenv\Dotenv;
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
    public static function LoadTwig(?string $path = null): Environment
    {
        //todo get instance
        if (!$path) {
            $path = get_template_directory().'/templates';
        }

        $loader = new FilesystemLoader($path);
        (new Dotenv())
            ->bootEnv(ABSPATH.'.env');
        $environment = new Environment(
            $loader,
            [
                'cache' => $_ENV['APP_CACHE_DIR'],
                'debug' => WP_DEBUG,
            ]
        );

        $loader->addPath(ABSPATH.'wp-content/themes/visittail/templates/', 'VisitTail');

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

        return $environment;
    }

    public static function rendPage(string $templatePath, array $variables = []): void
    {
        $twig = self::LoadTwig();
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

    public function contentPage(string $templatePath, array $variables = []): string
    {
        $twig = self::LoadTwig();
        try {
            return $twig->render(
                $templatePath,
                $variables,
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            return $twig->render(
                '@VisitTail/errors/500.html.twig',
                [
                    'message' => $e->getMessage(),
                    'title' => "La page n'a pas pu être chargée",
                    'tags' => [],
                    'relations' => [],
                ]
            );
        }
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
}
