<?php

defined('TYPO3') or die();

use GertKaaeHansen\GkhRssImport\Cache\Backend\Typo3TempSimpleFileBackend;
use GertKaaeHansen\GkhRssImport\Cache\Frontend\ImageFrontend;
use GertKaaeHansen\GkhRssImport\Controller\RssImportController;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function () {
    // Add default rendering for pi_layout plugin
    ExtensionManagementUtility::addTypoScript(
        'gkh_rss_import',
        'setup',
        'tt_content.list.20.gkh_rss_import_pi1 =< plugin.tx_gkhrssimport_pi1',
        'defaultContentRendering'
    );

    ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:gkh_rss_import/Configuration/TSconfig/Page/Mod/Wizards/NewContentElement.typoscript">'
    );

    // Cache configuration
    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][RssImportController::CACHE_IDENTIFIER])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][RssImportController::CACHE_IDENTIFIER] = [
            'frontend' => ImageFrontend::class,
            'backend' => Typo3TempSimpleFileBackend::class,
            'groups' => [
                'all',
                'gkh_rss_import'
            ]
        ];
    }

    if (!Environment::isComposerMode()) {
        require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/lastRSS.php');
        require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/smarttrim.php');
    }
})();
