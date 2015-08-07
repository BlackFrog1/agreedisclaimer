<?php
/**
 * ownCloud - agreedisclaimer
 *
 * This file is licensed under the MIT License. See the COPYING file.
 *
 * @author Josef Meile <technosoftgratis@okidoki.com.co>
 * @copyright Josef Meile 2015
 */

namespace OCA\AgreeDisclaimer\AppInfo;

use \OCP\AppFramework\App;
use \OCA\AgreeDisclaimer\Hooks\UserHooks;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCA\AgreeDisclaimer\Controller\SettingsController;

/**
 * Main application class where all the services, controllers, hooks, and
 * application settings are registered
 */
class Application extends App {
    /**
     * Creates an Application object and registers its related services,
     * user hooks, and settings
     */
    public function __construct(array $urlParams=array()) {
        parent::__construct('agreedisclaimer', $urlParams);
        $container = $this->getContainer();
        // register parameters
        $container->registerParameter('fileSizeLimit', 3);
        $container->registerParameter('filePrefix', 'disclaimer');
        $container->registerParameter('appPath', $this->buildPath(__DIR__, '..'));
        $container->registerParameter('pdfPath', $this->buildPath($container->query('appPath'), 'pdf'));
        $container->registerParameter('txtPath', $this->buildPath($container->query('appPath'), 'txt'));

        $this->registerHooks($container);
        $this->registerSettings($container);
    }

    private function buildPath(...$parts) {
        return join(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Registers all the application user hooks
     */
    private function registerHooks() {
        $this->getContainer()->query('OCA\AgreeDisclaimer\Hooks\UserHooks')->register();
    }

    /**
     * Enables the application in the admin settings
     */
    private function registerSettings($container) {
        \OCP\App::registerAdmin($container->query('appName'), 'admin');
    }

    /**
     * Renders the template for the login page
     *
     * @return OCP\AppFramework\Http\TemplateResponse   The response for the
     *         login template
     */
    public function getDisclaimerForm(){
        $appId = self::APP_ID;
        $data = [
            'appId' => $appId,
        ];
        $templateResponse = new TemplateResponse($appId, 'login', $data,
            'blank');
        return $templateResponse;
    }

    /**
     * Gets all the available languages
     *
     * @param   string  $defaultLang    Current used language
     *
     * @return array    An array of the form:
     *     ['languages'       => <languages>,
     *      'commonlanguages' => <common_languages>,
     *      'activelanguage'  => <active_language>]
     *     where:
     *     - languages is an array with all the ownCloud languages (except the
     *       common ones) of the form:
     *          [['code' => <lang_code>, 'name' => <translated_lang_name], ...]
     *     - commonlanguages is an array with the common ownCloud languages
     *     - active_language is the current used language
     *
     * @remarks: This code was taken from:
     *       * <ownCloudRoot>/settings/personal.php
     *       Unfortunatelly there isn't an utility for this at the
     *       moment of writting
     */
    public static function getAvailableLanguages($defaultLang)
    {
        $config = \OC::$server->getConfig();
        $userLang = $defaultLang;
        $languageCodes = \OC_L10N::findAvailableLanguages();

        // array of common languages
        $commonlangcodes = array(
            'en', 'es', 'fr', 'de', 'de_DE', 'ja', 'ar', 'ru', 'nl', 'it',
            'pt_BR', 'pt_PT', 'da', 'fi_FI', 'nb_NO', 'sv', 'tr', 'zh_CN', 'ko'
        );

        $languageNames = include \OC::$SERVERROOT .
            '/settings/languageCodes.php';
        $languages = array();
        $commonlanguages = array();
        foreach($languageCodes as $lang) {
            $l = \OC::$server->getL10N('settings', $lang);
            if ( substr($l->t('__language_name__'), 0, 1) !== '_') {
                //first check if the language name is in the translation file
                $ln = array(
                    'code' => $lang,
                    'name' => (string)$l->t('__language_name__')
                );
            } elseif(isset($languageNames[$lang])) {
                $ln=array('code' => $lang, 'name' => $languageNames[$lang]);
                } else { //fallback to language code
                $ln=array('code'=>$lang, 'name'=>$lang);
            }

            // put apropriate languages into apropriate arrays, to print them
            // sorted used language -> common languages -> divider -> other
            //languages
            if ($lang === $userLang) {
                $userLang = $ln;
            } elseif (in_array($lang, $commonlangcodes)) {
                $commonlanguages[array_search($lang, $commonlangcodes)]=$ln;
            } else {
                $languages[]=$ln;
            }
        }

        ksort($commonlanguages);

        // sort now by displayed language not the iso-code
        usort($languages, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return array(
            'languages' => $languages,
            'commonlanguages' => $commonlanguages,
            'activelanguage' => $userLang,
        );
    }

}
