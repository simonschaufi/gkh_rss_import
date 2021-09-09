<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * (c) Gert Kaae Hansen, Simon Schaufelberger
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function () {
    ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:gkh_rss_import/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
            'gkh_rss_import_pi1'
        ],
        ExtensionUtility::PLUGIN_TYPE_PLUGIN,
        'gkh_rss_import'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['gkh_rss_import_pi1'] = 'layout,select_key,pages,recursive';
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['gkh_rss_import_pi1'] = 'pi_flexform';

    ExtensionManagementUtility::addPiFlexFormValue(
        'gkh_rss_import_pi1',
        'FILE:EXT:gkh_rss_import/Configuration/FlexForm/flexform.xml'
    );
})();
