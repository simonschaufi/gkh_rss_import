<?php

defined('TYPO3') || die();

use GertKaaeHansen\GkhRssImport\Cache\Backend\Typo3TempSimpleFileBackend;
use GertKaaeHansen\GkhRssImport\Cache\Frontend\ImageFrontend;
use GertKaaeHansen\GkhRssImport\Controller\RssImportController;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    // Add default rendering for pi_layout plugin. Similar like:
    /** @see \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin */
    $pluginSignature = 'gkh_rss_import_pi1';
    $pluginContent = trim('
tt_content.' . $pluginSignature . ' =< lib.contentElement
tt_content.' . $pluginSignature . ' {
    templateName = Generic
    20 =< plugin.tx_gkhrssimport_pi1
}');

    ExtensionManagementUtility::addTypoScript(
        'gkh_rss_import',
        'setup',
        $pluginContent,
        'defaultContentRendering'
    );

    // Cache configuration
    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][RssImportController::CACHE_IDENTIFIER] ?? null)) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][RssImportController::CACHE_IDENTIFIER] = [
            'frontend' => ImageFrontend::class,
            'backend' => Typo3TempSimpleFileBackend::class,
            'groups' => [
                'all',
                'gkh_rss_import',
            ],
        ];
    }

    if (!Environment::isComposerMode()) {
        $extPath = ExtensionManagementUtility::extPath('gkh_rss_import');
        require_once($extPath . 'Resources/PHP/lastRSS.php');
        require_once($extPath . 'Resources/PHP/smarttrim.php');
    }
})();
