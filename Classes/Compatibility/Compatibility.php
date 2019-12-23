<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Compatibility;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class Compatibility
{
    /**
     * Returns true if the current TYPO3 version is at least 8.7.0
     *
     * @return bool|int
     */
    public static function isAtLeastVersion8Dot7Dot0()
    {
        return version_compare(VersionNumberUtility::getNumericTypo3Version(), '8.7.0', '>=');
    }
}
