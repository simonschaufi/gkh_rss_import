<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['gkh_rss_import_pi1'] = 'layout,select_key,pages,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['gkh_rss_import_pi1'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'gkh_rss_import_pi1',
        'FILE:EXT:gkh_rss_import/Configuration/FlexForm/flexform.xml'
    );
});
