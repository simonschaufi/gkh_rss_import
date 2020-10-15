<?php
defined('TYPO3_MODE') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'gkh_rss_import',
        'Configuration/TypoScript/',
        'RSS Feed Import'
    );
});
