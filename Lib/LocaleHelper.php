<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Utils\LocalSwitcherPivot;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;

class LocaleHelper
{
    private static ?LocalSwitcherPivot $localeSwitcher = null;

    private static function init()
    {
        if (!self::$localeSwitcher) {
            self::$localeSwitcher = PivotContainer::getLocalSwitcherPivot();
        }
    }

    public static function getSelectedLanguage(): string
    {
        $current_lang = apply_filters('wpml_current_language', null);
        if (!$current_lang) {
            $current_lang = 'fr';
        }
        self::setCurrentLanguageSf($current_lang);

        return $current_lang;
    }

    public static function getCurrentLanguageSf(): string
    {
        self::init();

        return self::$localeSwitcher->getLocale();
    }

    public static function setCurrentLanguageSf(string $locale): void
    {
        self::init();
        self::$localeSwitcher->setLocale($locale);
    }

    public static function iniTranslator(): TranslatorBagInterface
    {
        $yamlLoader = new YamlFileLoader();

        $translator = new Translator(self::getSelectedLanguage());
        $translator->addLoader('yaml', $yamlLoader);
        $translator->addResource('yaml', get_template_directory().'/translations/messages.fr.yaml', 'fr');
        $translator->addResource('yaml', get_template_directory().'/translations/messages.en.yaml', 'en');
        $translator->addResource('yaml', get_template_directory().'/translations/messages.nl.yaml', 'nl');

        return $translator;
    }

    public static function translate(string $text): string
    {
        $translator = self::iniTranslator();
        $language = self::getSelectedLanguage();

        return $translator->trans($text, [], null, $language);
    }
}
