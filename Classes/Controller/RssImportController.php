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

namespace GertKaaeHansen\GkhRssImport\Controller;

use GertKaaeHansen\GkhRssImport\Cache\Backend\Typo3TempSimpleFileBackend;
use GertKaaeHansen\GkhRssImport\Service\LastRssService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Exception;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

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
     * Holds the template for FE rendering
     *
     * @var string
     */
    protected string $template;

    protected CacheManager $cacheManager;

    protected LastRssService $rssService;

    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null)
    {
        parent::__construct($_, $frontendController);

        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->rssService = GeneralUtility::makeInstance(LastRssService::class);
    }

    /**
     * The main method of the PlugIn
     *
     * @param string $content The PlugIn content
     * @param array $conf The PlugIn configuration
     * @return string The content that is displayed on the website
     * @throws Exception
     * @throws NoSuchCacheException|\ErrorException
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

        if (($this->conf['flexCache'] ?? null) !== null) {
            $this->rssService->setCacheTime((int)$this->conf['flexCache']);
        }

        if ((bool)($this->conf['stripHTML'] ?? false) === true) {
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
        if (str_starts_with($templateFile, 'EXT:')) {
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

    /**
     * @throws NoSuchCacheException
     */
    protected function render(): string
    {
        $markerArray['###BOX###'] = $this->pi_classParam('rss_box');

        // Try to load and parse RSS file
        $rss = $this->rssService->getFeed();
        if (is_array($rss)) {
            $rss['title'] = strip_tags($this->rssService->unHtmlEntities(strip_tags($rss['title'])));
            if (isset($rss['description'])) {
                $rss['description'] = strip_tags($this->rssService->unHtmlEntities(strip_tags($rss['description'])));
            } else {
                $rss['description'] = '';
            }

            $target = $this->getTarget();

            // Show website logo (if presented)
            $markerArray['###IMAGE###'] = $this->getImage($rss, $target);

            // title
            $markerArray['###CLASS_RSS_TITLE###'] = $this->pi_classParam('rss_title');
            $markerArray['###URL###'] = $this->removeDoubleHTTP($rss['link']);
            $markerArray['###TARGET###'] = $target;
            // TODO: htmlspecialchars?
            $markerArray['###RSS_TITLE###'] = $rss['title'];
            // description
            $markerArray['###CLASS_DESCRIPTION###'] = $this->pi_classParam('description');
            // TODO: htmlspecialchars?
            $markerArray['###DESCRIPTION###'] = smart_trim($rss['description'], $this->conf['headerLength']);

            $subPart = $this->getSubPart($this->template, '###RSSIMPORT_TEMPLATE###');
            $itemSubpart = $this->getSubPart($subPart, '###ITEM###');

            $contentItem = '';
            foreach ($rss['items'] as $item) {
                $contentItem .= $this->renderItem($item, $itemSubpart, $target);
            }
            $subPartArray['###ITEM###'] = $contentItem;

            // @extensionScannerIgnoreLine
            $content = $this->substituteMarkerArrayCached($subPart, $markerArray, $subPartArray);
        } else {
            // If feed is not found show this message
            if (isset($this->conf['errorMessage.'])) {
                $errorMessage = $this->cObj->stdWrap('', $this->conf['errorMessage.']);
            } else {
                $errorMessage = $this->conf['errorMessage'] ?? '';
            }
            $content = '<div class="rss_box">' . htmlspecialchars($errorMessage) . '</div>';
        }
        if (isset($this->conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $this->conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * Get the channel image. Image url, title and link are required.
     *
     * @throws NoSuchCacheException|\RuntimeException
     */
    protected function getImage(array $rss, string $target): string
    {
        if (isset($rss['image_url'], $rss['image_title'], $rss['image_link']) && $rss['image_url'] !== '') {
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
                    'maxW' => $this->conf['logoWidth'] ?? 0,
                ],
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
     * @throws NoSuchCacheException
     */
    protected function getCachedImageLocation(string $imageUrl): string
    {
        $imageCache = $this->cacheManager->getCache(self::CACHE_IDENTIFIER);

        $fileExtension = '.' . $this->getFileExtensionFromUrl($imageUrl);
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

    protected function getFileExtensionFromUrl(string $url): string
    {
        $urlParts = parse_url($url);
        return pathinfo($urlParts['path'], PATHINFO_EXTENSION);
    }

    protected function getSubPart(string $template, string $marker): string
    {
        return $this->templateService->getSubpart($template, $marker);
    }

    protected function renderItem(array $item, string $itemSubpart, string $target): string
    {
        // for UserFunction fixRssURLs
        $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LINK'] = $item['link'];

        // Get item header
        $markerArray['###CLASS_HEADER###'] = $this->pi_classParam('header');
        $markerArray['###HEADER_URL###'] = $this->removeDoubleHTTP($item['link']);
        $markerArray['###HEADER_TARGET###'] = $target;
        // TODO: htmlspecialchars?
        $markerArray['###HEADER###'] = smart_trim($item['title'], $this->conf['headerLength']);

        // Get published date, author and category
        $markerArray['###CLASS_PUBBOX###'] = $this->pi_classParam('pubbox');
        if ($item['pubDate'] !== '01/01/1970') {
            $markerArray['###CLASS_RSS_DATE###'] = $this->pi_classParam('date');

            $date = \DateTimeImmutable::createFromFormat('U', (string)strtotime($item['pubDate']));
            $markerArray['###RSS_DATE###'] = htmlentities(
                $date->format($this->getDateFormat()),
                ENT_QUOTES,
                'utf-8'
            );
        }
        $markerArray['###CLASS_AUTHOR###'] = $this->pi_classParam('author');
        // TODO: htmlspecialchars?
        $markerArray['###AUTHOR###'] = $item['author'] ?? '';
        $markerArray['###CLASS_CATEGORY###'] = $this->pi_classParam('category');
        // TODO: htmlspecialchars?
        $markerArray['###CATEGORY###'] = htmlentities($item['category'] ?? '');

        // Get item content/home/simon/Code/github/simonschaufi/gkh_rss_import/.Build/bin/phpcs
        $markerArray['###CLASS_SUMMARY###'] = $this->pi_classParam('content');
        $itemSummary = $item['description'];

        // for UserFunction smart_trim
        $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LENGTH'] = (int)$this->conf['itemLength'];
        if (isset($this->conf['itemSummary_stdWrap.'])) {
            $itemSummary = $this->cObj->stdWrap($itemSummary, $this->conf['itemSummary_stdWrap.']);
        }
        $itemSummary = smart_trim($itemSummary, (int)$this->conf['itemLength']);
        // no htmlspecialchars as this might contain html which should be rendered
        $markerArray['###SUMMARY###'] = $itemSummary;

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

        // @extensionScannerIgnoreLine
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
        switch ($this->conf['target'] ?? null) {
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
        switch ($this->conf['dateFormat'] ?? null) {
            case 1:
                return 'l, d. F Y';
            case 2:
                return 'd. F Y';
            case 3:
                return 'j/m - Y';
            default:
                if (!empty($this->conf['dateFormat'])) {
                    return $this->conf['dateFormat'];
                }
                return 'j/m - Y';
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
            $flex['itemLength'] = (int)$this->flexFormValue('length', 'rssFeed');
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
     */
    protected function flexFormValue(string $variable, string $sheet): ?string
    {
        return $this->pi_getFFvalue($this->cObj->data['pi_flexform'] ?? null, $variable, $sheet);
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
     */
    public function fixRssURLs(string $attribute, HtmlParser $htmlParser): string
    {
        $imgURL = parse_url($attribute);
        if ($imgURL['scheme'] && $imgURL['host']) {
            return $attribute;
        }

        $linkURL = parse_url($this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LINK']);

        return $linkURL['scheme'] . '://' . $linkURL['host'] . $linkURL['port'] . $imgURL['path'] . $imgURL['query']
            . $imgURL['fragment'];
    }

    public function cropHTML(string $text, array $conf): string
    {
        $itemLength = $this->getTypoScriptFrontendController()->register['RSS_IMPORT_ITEM_LENGTH'];
        if ($itemLength === 0) {
            return $text;
        }
        return $this->cObj->stdWrap_cropHTML($text, ['cropHTML' => $itemLength . '|...|1']);
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
