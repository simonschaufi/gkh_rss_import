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

namespace GertKaaeHansen\GkhRssImport\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3TempSimpleFileBackend extends SimpleFileBackend
{
    /**
     * @throws Exception
     */
    public function initializeObject(): void
    {
        $this->setCacheDirectory(Environment::getPublicPath() . '/typo3temp/assets/images/');

        if (!@is_dir(Environment::getPublicPath() . '/typo3temp/assets/images/')) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
        }
    }
}
