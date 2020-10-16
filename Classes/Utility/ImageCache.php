<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageCache
{
    /**
     * How many days till check if new thumbnail
     *
     * @var int
     */
    protected $daysToKeep = 60;

    /**
     * Cache image
     *
     * @param string $url : url to the image
     * @param string $location : Where to store the image
     * @param string $fileExtension : Type of the image (gif, jpg, png)
     * @return string
     */
    public function get(string $url, string $location, string $fileExtension): string
    {
        $fileUrl = GeneralUtility::getFileAbsFileName($location . md5($url) . $fileExtension);

        $image = '';
        if (file_exists($fileUrl)) {
            // check age
            $diff = (time() - filemtime($fileUrl)) / 60 / 60 / 24;
            if ($diff > $this->daysToKeep) {
                unlink($fileUrl);
            } else {
                $image = $fileUrl;
            }
        }

        if (!file_exists($fileUrl)) {
            $buff = GeneralUtility::getURL($url, $includeHeader = 0);
            if ($buff !== '') {
                GeneralUtility::writeFile($fileUrl, $buff);
                $image = $fileUrl;
            }
        }
        return $image;
    }
}
