<?php

namespace VisitMarche\ThemeTail\Lib;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;

class LocaleHelper
{
    //todo remove
    const ICL_LANGUAGE_CODE = null;
    public static function getSelectedLanguage(): string
    {
        if (self::ICL_LANGUAGE_CODE !== null) {
            return self::ICL_LANGUAGE_CODE;
        }

        return 'fr';
    }

    public static function getSelectedLanguage22(): string
    {
        if (ICL_LANGUAGE_CODE) {
            return ICL_LANGUAGE_CODE;
        }

        return 'fr';
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
