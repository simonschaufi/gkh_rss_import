<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3TempSimpleFileBackend extends SimpleFileBackend
{
    /**
     * @throws \TYPO3\CMS\Core\Cache\Exception
     */
    public function initializeObject(): void
    {
        $this->setCacheDirectory(Environment::getPublicPath() . '/typo3temp/assets/images/');

        if (!@is_dir(Environment::getPublicPath() . '/typo3temp/assets/images/')) {
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/images/');
        }
    }
}
