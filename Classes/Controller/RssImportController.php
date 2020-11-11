<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Gert Kaae Hansen <gertkh@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use GertKaaeHansen\GkhRssImport\Cache\Backend\Typo3TempSimpleFileBackend;
use GertKaaeHansen\GkhRssImport\Service\LastRssService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Exception;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/lastRSS.php');
require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/smarttrim.php');

/**
 * Plugin 'gkh RSS import' for the 'gkh_rss_import' extension.
 *
 * @author Gert Kaae Hansen <gertkh@gmail.com>
 */
class RssImportController extends AbstractPlugin
{
    public const CACHE_IDENTIFIER = 'gkh_rss_import_image';

    /**
     * Used for CSS classes, variables
     *
     * @var string
     */
    public $prefixId = 'tx_gkhrssimport_pi1';

    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'gkh_rss_import';

    /**
     * @var bool
     */
    public $pi_checkCHash = true;

    /**
     * Holds the template for FE rendering
     *
     * @var string
     */
    protected $template;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var LastRssService
     */
    protected $rssService;

    /**
     * Class Constructor
     *
     * @param null $_
     * @param TypoScriptFrontendController|null $frontendController
     */
    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null)
    {
        parent::__construct($_, $frontendController);

        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->rssService = GeneralUtility::makeInstance(LastRssService::class);
    }

    /**
     * The main method of the PlugIn
     *
     * @param string $content : The PlugIn content
     * @param array $conf : The PlugIn configuration
     * @return string The content that is displayed on the website
     * @throws Exception
     */
    public function main(string $content, array $conf): string
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL('EXT:gkh_rss_import/Resources/Private/Language/locallang.xlf');
        $this->pi_initPIflexForm();
        $this->mergeFlexFormValuesIntoConf();

        if (empty($this->conf['rssFeed'])) {
            throw new \ErrorException('No feed URL set', 1595545177);
        }

        $this->rssService
            ->setUrl($this->conf['rssFeed'])
            ->setCP('utf-8')
            ->setItemsLimit((int)($this->conf['itemsLimit'] ?? 10))
            ->setDateFormat('m/d/Y');

        if ($this->conf['flexCache'] !== null) {
            $this->rssService->setCacheTime((int)$this->conf['flexCache']);
        }

        if ($this->conf['stripHTML'] == 1) {
            $this->rssService->setStripHTML(true);
        }

        $this->template = $this->getTemplate();

        return $this->pi_wrapInBaseClass($this->render());
    }

    /**
     * Get the template from configuration or default template provided by extension
     *
     * @throws Exception
     */
    protected function getTemplate()
    {
        $templateFile = $this->conf['templateFile'];

        // Check if template file is set via TypoScript
        if (strpos($templateFile, 'EXT:') === 0) {
            $template = GeneralUtility::getFileAbsFileName($templateFile);
            if ($template === '' || !file_exists($template)) {
                throw new Exception(sprintf('Template "%s" not found', $template), 1572458728);
            }
            return file_get_contents($template);
        }

        // Check if template is given via flex form
        $uid = $this->cObj->data['uid'];

        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $references = $fileRepository->findByRelation('tt_content', 'template', $uid);

        if (!empty($references)) {
            /* @var FileReference $fileReference */
            $fileReference = reset($references);
            $templateFile = $fileReference->getForLocalProcessing(false);
            if (!file_exists($templateFile)) {
                throw new Exception(sprintf('Template "%s" not found', $templateFile), 1572458728);
            }
            return file_get_contents($templateFile);
        }

        // Fallback if no template is set
        $template = GeneralUtility::getFileAbsFileName('EXT:gkh_rss_import/Resources/Private/Templates/RssImport.html');
        if ($template === '' || !file_exists($template)) {
            throw new Exception(sprintf('Template "%s" not found', $template), 1572458728);
        }
        return file_get_contents($template);
    }

    protected function render(): string
    {
        $markerArray['###BOX###'] = $this->pi_classParam('rss_box');

        // Try to load and parse RSS file
        $rss = $this->rssService->getFeed();
        if (is_array($rss)) {
            $rss['title'] = strip_tags($this->rssService->unHtmlEntities(strip_tags($rss['title'])));
            $rss['description'] = strip_tags($this->rssService->unHtmlEntities(strip_tags($rss['description'])));

            $target = $this->getTarget();

            // Show website logo (if presented)
            $markerArray['###IMAGE###'] = $this->getImage($rss, $target);

            // title
            $markerArray['###CLASS_RSS_TITLE###'] = $this->pi_classParam('rss_title');
            $markerArray['###URL###'] = $this->removeDoubleHTTP($rss['link']);
            $markerArray['###TARGET###'] = $target;
            $markerArray['###RSS_TITLE###'] = $rss['title']; // TODO: htmlspecialchars?
            // description
            $markerArray['###CLASS_DESCRIPTION###'] = $this->pi_classParam('description');
            $markerArray['###DESCRIPTION###'] = smart_trim($rss['description'], $this->conf['headerLength']); // TODO: htmlspecialchars?

            $subPart = $this->getSubPart($this->template, '###RSSIMPORT_TEMPLATE###');
            $itemSubpart = $this->getSubPart($subPart, '###ITEM###');

            $contentItem = '';
            foreach ($rss['items'] as $item) {
                $contentItem .= $this->renderItem($item, $itemSubpart, $target);
            }
            $subPartArray['###ITEM###'] = $contentItem;

            $content = $this->substituteMarkerArrayCached($subPart, $markerArray, $subPartArray);
        } else {
            // If feed is not found show this message
            $content = '<div class="rss_box">' . htmlspecialchars($this->conf['errorMessage']) . '</div>';
        }
        if (isset($this->conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $this->conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * @param array $rss
     * @param string $target
     * @return string
     * @throws NoSuchCacheException
     */
    protected function getImage(array $rss, string $target): string
    {
        if (isset($rss['image_url'], $rss['image_link']) && $rss['image_url'] !== '') {
            $location = $this->getCachedImageLocation($rss['image_url']);

            if (!file_exists($location)) {
                throw new \RuntimeException(sprintf('File %s could not be found!', $location));
            }

            // Pass the combination of TS-defined values and php processing through the IMAGE cObject function
            $imgOutput = $this->cObj->cObjGetSingle('IMAGE', [
                'altText' => $rss['image_title'],
                'titleText' => $rss['image_title'],
                'file' => $location,
                'file.' => [
                    'maxW' => $this->conf['logoWidth']
                ]
            ]);
            return sprintf(
                '<div%s><a href="%s" target="%s">%s</a></div><br />',
                $this->pi_classParam('RSS_h_image'),
                $this->removeDoubleHTTP($rss['image_link']),
                $target,
                $imgOutput
            );
        }

        return '';
    }

    /**
     * @param string $imageUrl
     * @return string
     * @throws NoSuchCacheException
     */
    protected function getCachedImageLocation(string $imageUrl): string
    {
        $imageCache = $this->cacheManager->getCache(self::CACHE_IDENTIFIER);

        $fileExtension = substr($this->getFileName($imageUrl), -4);
        $cacheIdentifier = sha1($imageUrl . '_' . $fileExtension) . $fileExtension;
        if (!$imageCache->has($cacheIdentifier)) {
            $buff = GeneralUtility::getURL($imageUrl);
            if ($buff !== false) {
                $imageCache->set($cacheIdentifier, $buff);
            }
        }

        /** @var Typo3TempSimpleFileBackend $imageCacheBackend */
        $imageCacheBackend = $imageCache->getBackend();
        return $imageCacheBackend->getCacheDirectory() . $cacheIdentifier;
    }

    protected function getSubPart(string $template, string $marker): string
    {
        return $this->templateService->getSubpart($template, $marker);
    }

    protected function renderItem(array $item, string $itemSubpart, string $target): string
    {
        $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LINK'] = $item['link']; // for Userfunction fixRssURLs

        // Get item header
        $markerArray['###CLASS_HEADER###'] = $this->pi_classParam('header');
        $markerArray['###HEADER_URL###'] = $this->removeDoubleHTTP($item['link']);
        $markerArray['###HEADER_TARGET###'] = $target;
        $markerArray['###HEADER###'] = smart_trim($item['title'], $this->conf['headerLength']); // TODO: htmlspecialchars?

        // Get published date, author and category
        $markerArray['###CLASS_PUBBOX###'] = $this->pi_classParam('pubbox');
        if ($item['pubDate'] !== '01/01/1970') {
            $markerArray['###CLASS_RSS_DATE###'] = $this->pi_classParam('date');
            $markerArray['###RSS_DATE###'] = htmlentities(
                strftime($this->getDateFormat(), strtotime($item['pubDate'])),
                ENT_QUOTES,
                'utf-8'
            );
        }
        $markerArray['###CLASS_AUTHOR###'] = $this->pi_classParam('author');
        $markerArray['###AUTHOR###'] = $item['author'] ?? ''; // TODO: htmlspecialchars?
        $markerArray['###CLASS_CATEGORY###'] = $this->pi_classParam('category');
        $markerArray['###CATEGORY###'] = htmlentities($item['category'] ?? ''); // TODO: htmlspecialchars?

        // Get item content/home/simon/Code/github/simonschaufi/gkh_rss_import/.Build/bin/phpcs
        $markerArray['###CLASS_SUMMARY###'] = $this->pi_classParam('content');
        $itemSummary = $item['description'];
        $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LENGTH'] = $this->conf['itemLength']; // for Userfunc smart_trim
        if (isset($this->conf['itemSummary_stdWrap.'])) {
            $itemSummary = $this->cObj->stdWrap($itemSummary, $this->conf['itemSummary_stdWrap.']);
        }
        $itemSummary = smart_trim($itemSummary, $this->conf['itemLength']);
        $markerArray['###SUMMARY###'] = $itemSummary; // no htmlspecialchars as this might contain html which should be rendered

        $markerArray['###CLASS_DOWNLOAD###'] = $this->pi_classParam('download');
        if (isset($item['enclosure']['prop']['url']) && $item['enclosure']['prop']['url'] !== '') {
            $download = $this->pi_getLL('Download');
            if (isset($item['enclosure']['prop']['length'])) {
                $download .= ' (' . round((float)$item['enclosure']['prop']['length'] / (1024 * 1024), 1) . ' MB)';
            }
            $markerArray['###DOWNLOAD###'] = sprintf(
                '<a href="%s">%s</a>',
                htmlspecialchars($item['enclosure']['prop']['url']),
                htmlspecialchars($download)
            );
        } else {
            $markerArray['###DOWNLOAD###'] = '';
        }

        $contentSubPart = $this->substituteMarkerArrayCached($itemSubpart, $markerArray);

        if (isset($this->conf['item_stdWrap.'])) {
            $contentSubPart = $this->cObj->stdWrap($contentSubPart, $this->conf['item_stdWrap.']);
        }
        return $contentSubPart;
    }

    protected function substituteMarkerArrayCached(
        string $subPart,
        array $markerArray,
        ?array $subPartArray = []
    ): string {
        return $this->templateService->substituteMarkerArrayCached($subPart, $markerArray, $subPartArray);
    }

    protected function getTarget(): string
    {
        switch ($this->conf['target']) {
            case 1:
                return '_top';
            case 3:
                return '_self';
            case 2:
            default:
                return '_blank';
        }
    }

    protected function getDateFormat(): string
    {
        switch ($this->conf['dateFormat']) {
            case 1:
                return '%A, %d. %B %Y';
            case 2:
                return '%d. %B %Y';
            case 3:
                return '%e/%m - %Y';
            default:
                if (!empty($this->conf['dateFormat'])) {
                    return $this->conf['dateFormat'];
                }

                return '%e/%m - %Y';
        }
    }

    /**
     * Reads flexform configuration and merge it with $this->conf
     */
    protected function mergeFlexFormValuesIntoConf(): void
    {
        $flex = [];
        // rssFeed
        if ($this->flexFormValue('rssfeed', 'rssFeed')) {
            $flex['rssFeed'] = $this->flexFormValue('rssfeed', 'rssFeed');
        }
        if ($this->flexFormValue('display', 'rssFeed')) {
            $flex['itemsLimit'] = $this->flexFormValue('display', 'rssFeed');
        }
        if ($this->flexFormValue('length', 'rssFeed')) {
            $flex['itemLength'] = $this->flexFormValue('length', 'rssFeed');
        }
        if ($this->flexFormValue('hlength', 'rssFeed')) {
            $flex['headerLength'] = $this->flexFormValue('hlength', 'rssFeed');
        }
        if ($this->flexFormValue('target', 'rssFeed')) {
            $flex['target'] = $this->flexFormValue('target', 'rssFeed');
        }
        if ($this->flexFormValue('logowidth', 'rssFeed')) {
            $flex['logoWidth'] = $this->flexFormValue('logowidth', 'rssFeed');
        }
        if ($this->flexFormValue('errorMessage', 'rssFeed')) {
            $flex['errorMessage'] = $this->flexFormValue('errorMessage', 'rssFeed');
        }

        // rssSettings
        if ($this->flexFormValue('dateformat', 'rssSettings')) {
            $flex['dateFormat'] = $this->flexFormValue('dateformat', 'rssSettings');
        }
        if ($this->flexFormValue('striphtml', 'rssSettings')) {
            $flex['stripHTML'] = $this->flexFormValue('striphtml', 'rssSettings');
        }
        if ($this->flexFormValue('flexcache', 'rssSettings')) {
            $flex['flexCache'] = $this->flexFormValue('flexcache', 'rssSettings');
        }

        // templateS
        if ($this->flexFormValue('template', 'templateS')) {
            $flex['templateFile'] = $this->flexFormValue('template', 'templateS');
        }

        $this->conf = array_merge($this->conf, $flex);
    }

    /**
     * Loads a variable from the flexform
     *
     * @param string $var Name of variable
     * @param string $sheet Name of sheet
     * @return string|null Value of var
     */
    protected function flexFormValue($var, $sheet): ?string
    {
        return $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $var, $sheet);
    }

    /**
     * fixRssURLs is called by HTMLparser to check and fix incomplete image-src-attributes in the description
     * example:
     * <item>
     *        <title>...</title>
     *        <link>http://www.example.com/1234</link>
     *        <description><![CDATA[<img src="/item.jpg"/>long and boring description</description>
     * </item>
     * In this case the img-src is relative to the remote domain http://www.example.com. If they're not fixed,
     * they would point to the local domain.
     *
     * @param string $attrib
     * @param HtmlParser $htmlParser
     * @return string
     */
    public function fixRssURLs(string $attrib, HtmlParser $htmlParser): string
    {
        $imgURL = parse_url($attrib);
        if ($imgURL['scheme'] && $imgURL['host']) {
            return $attrib;
        }

        $linkURL = parse_url($this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LINK']);

        return $linkURL['scheme'] . '://' . $linkURL['host'] . $linkURL['port'] . $imgURL['path'] . $imgURL['query']
            . $imgURL['fragment'];
    }

    /**
     * User function cropHTML
     *
     * @param string $text
     * @param array $conf
     * @return string
     */
    public function cropHTML(string $text, $conf): string
    {
        $itemLength = $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LENGTH'];
        if ($itemLength == 0) {
            return $text;
        }
        return $this->cObj->cropHTML($text, $itemLength . '|...|1');
    }

    /**
     * Get filename from url
     *
     * @param string $url : url to the file
     *
     * @return string
     */
    protected function getFileName(string $url): string
    {
        $parts = explode('/', $url);
        return ($parts[count($parts) - 1] === '') ? $parts[count($parts) - 2] : $parts[count($parts) - 1];
    }

    /**
     * Remove double http://
     *
     * @param string $url
     * @return string return url with one http://
     */
    protected function removeDoubleHTTP(string $url): string
    {
        if (substr($url, 14, 3) === 'www') {
            $url = 'http://' . substr($url, 14, strlen($url));
        }
        return $url;
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
