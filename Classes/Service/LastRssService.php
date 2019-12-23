<?php
declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Service;

use lastRSS;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LastRssService
{
    protected const CACHE_DIR = 'typo3temp/var/lastRSS/';

    /**
     * @var lastRSS
     */
    protected $rss;

    /**
     * @var string
     */
    protected $url;

    public function __construct()
    {
        $this->rss = new lastRSS();
        $this->rss->CDATA = 'content';

        $path = PATH_site . self::CACHE_DIR;
        // we check for existence of our targetDirectory
        if (!is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }
        $this->rss->cache_dir = $path;
    }

    /**
     * @return bool|string
     */
    public function getFeed()
    {
        if (empty($this->url)) {
            throw new RuntimeException('Feed URL is not set.', 1526816720);
        }
        return $this->rss->Get($this->url);
    }

    /**
     * @param string $url
     * @return LastRssService
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param string $cacheTime
     * @return LastRssService
     */
    public function setCacheTime(string $cacheTime): self
    {
        $this->rss->cache_time = $cacheTime;
        return $this;
    }

    /**
     * @param string $cp
     * @return LastRssService
     */
    public function setCP(string $cp): self
    {
        $this->rss->cp = $cp;
        return $this;
    }

    /**
     * @param int $limit
     * @return LastRssService
     */
    public function setItemsLimit(int $limit): self
    {
        $this->rss->items_limit = $limit;
        return $this;
    }

    /**
     * @param bool $stripHTML
     * @return LastRssService
     */
    public function setStripHTML(bool $stripHTML): self
    {
        $this->rss->stripHTML = $stripHTML;
        return $this;
    }

    /**
     * @param string $dateFormat
     * @return LastRssService
     */
    public function setDateFormat(string $dateFormat): self
    {
        $this->rss->date_format = $dateFormat;
        return $this;
    }

    /**
     * @param string $string
     * @return string
     */
    public function unHtmlEntities(string $string): string
    {
        return $this->rss->unhtmlentities($string);
    }
}
