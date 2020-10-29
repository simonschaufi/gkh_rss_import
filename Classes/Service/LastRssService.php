<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Service;

use lastRSS;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LastRssService
{
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

        $path = Environment::getVarPath() . '/lastRSS';
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

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Cache time in seconds
     *
     * @param int $cacheTime
     * @return $this
     */
    public function setCacheTime(int $cacheTime): self
    {
        $this->rss->cache_time = $cacheTime;
        return $this;
    }

    public function setCP(string $cp): self
    {
        $this->rss->cp = $cp;
        return $this;
    }

    public function setItemsLimit(int $limit): self
    {
        $this->rss->items_limit = $limit;
        return $this;
    }

    public function setStripHTML(bool $stripHTML): self
    {
        $this->rss->stripHTML = $stripHTML;
        return $this;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->rss->date_format = $dateFormat;
        return $this;
    }

    public function unHtmlEntities(string $string): string
    {
        return $this->rss->unhtmlentities($string);
    }
}
