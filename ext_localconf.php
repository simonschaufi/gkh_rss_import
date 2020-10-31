<?php

defined('TYPO3_MODE') or die();

call_user_func(static function () {
    // Add default rendering for pi_layout plugin
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'gkh_rss_import',
        'setup',
        'tt_content.list.20.gkh_rss_import_pi1 =< plugin.tx_gkhrssimport_pi1',
        'defaultContentRendering'
    );

    $icons = [
        'ext-gkhrssimport-wizard-icon' => 'Extension.svg',
    ];
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($icons as $identifier => $path) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:gkh_rss_import/Resources/Public/Icons/' . $path]
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:gkh_rss_import/Configuration/TSconfig/Page/Mod/Wizards/NewContentElement.typoscript">'
    );

    // Cache configuration
    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\GertKaaeHansen\GkhRssImport\Controller\RssImportController::CACHE_IDENTIFIER])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\GertKaaeHansen\GkhRssImport\Controller\RssImportController::CACHE_IDENTIFIER] = [
            'frontend' => \GertKaaeHansen\GkhRssImport\Cache\Frontend\ImageFrontend::class,
            'backend' => \GertKaaeHansen\GkhRssImport\Cache\Backend\Typo3TempSimpleFileBackend::class,
            'groups' => [
                'all',
                'gkh_rss_import'
            ]
        ];
    }
});
