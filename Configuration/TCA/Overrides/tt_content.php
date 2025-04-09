<?php

declare(strict_types=1);

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

defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:gkh_rss_import/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
        'gkh_rss_import_pi1',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    'gkh_rss_import'
);
ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', 'gkh_rss_import_pi1', 'after:subheader');

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:gkh_rss_import/Configuration/FlexForm/flexform.xml',
    'gkh_rss_import_pi1'
);
