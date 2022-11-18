<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\DependencyInjection\PivotContainer;
use AcMarche\Pivot\Utils\LocalSwitcherPivot;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorBagInterface;

class LocaleHelper
{
    private LocalSwitcherPivot $localeSwitcher;

    public function __construct()
    {
        $this->localeSwitcher = PivotContainer::getLocalSwitcherPivot();
    }

    public static function getSelectedLanguage(): string
    {
        if (defined(ICL_LANGUAGE_CODE)) {
            return ICL_LANGUAGE_CODE;
        }

        return 'fr';
    }

    public function getCurrentLanguageSf(): string
    {
        return $this->localeSwitcher->getLocale();
    }

    public function setCurrentLanguageSf(string $locale): void
    {
        $this->localeSwitcher->setLocale($locale);
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
