<?php
defined('TYPO3_MODE') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'GertKaaeHansen.GkhRssImport',
        'setup',
        '
plugin.tx_gkhrssimport_pi1 = USER
plugin.tx_gkhrssimport_pi1 {
	userFunc = ' . \GertKaaeHansen\GkhRssImport\Controller\RssImportController::class . '->main
}

# Setting gkh_rss_import plugin TypoScript
tt_content.list.20.gkh_rss_import_pi1 = < plugin.tx_gkhrssimport_pi1
',
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
});
