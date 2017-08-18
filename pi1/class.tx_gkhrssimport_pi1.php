<?php
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/lastRSS.php');
require_once(ExtensionManagementUtility::extPath('gkh_rss_import') . 'Resources/PHP/smarttrim.php');

/**
 * Plugin 'gkh RSS import' for the 'gkh_rss_import' extension.
 *
 * @author    Gert Kaae Hansen <gertkh@gmail.com>
 * @package    TYPO3
 * @subpackage    tx_gkhrssimport
 */
class tx_gkhrssimport_pi1 extends AbstractPlugin
{
    var $prefixId = 'tx_gkhrssimport_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.tx_gkhrssimport_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey = 'gkh_rss_import';    // The extension key.
    var $pi_checkCHash = true;
    var $display = 20;
    var $title = 'Test';

    /**
     * The main method of the PlugIn
     *
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return   string The content that is displayed on the website
     */
    function main($content, $conf)
    {
        // Create lastRSS object
        $this->pi_initPIflexForm();
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        // Get data from flexform
        $flex_rssfeed = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "rssfeed", "rssFeed"));
        $flex_ErrorMessage = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "errorMessage", "rssFeed"));
        $flex_maxitems = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "display", "rssFeed"));
        $flex_headerlength = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "hlenght", "rssFeed"));
        $flex_itemlength = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "lenght", "rssFeed"));
        $flex_taget_val = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "target", "rssFeed");
        $flex_ca = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "flexcache", "rssSettings");
        //if ($flex_ca <= 3599) {
        //	$flex_ca = 3600;
        //}
        // Change data from flexform to HTML values
        switch ($flex_taget_val) {
            case 1:
                $flex_target = '_top';
                break;
            default:
                $flex_target = '_blank';
                break;
        }
        $flex_logowidth = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "logowidth", "rssFeed"));
        $rss = new lastRSS();
        if ($flex_ca <> 0) {
            $rss->cache_dir = 'uploads/tx_gkhrssimport/';
        }
        $rss->cache_time = $flex_ca;
        //$rss->cp = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'],"cp","rssSettings"));
        $rss->cp = $GLOBALS['TSFE']->renderCharset; //Thor Solli <thor@linkfactory.dk>
        $rss->items_limit = htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "display", "rssFeed"));
        #$rss->CDATA = content;
        $stripHTML = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "striphtml", "rssSettings");
        switch ($stripHTML) {
            case 1:
                $rss->stripHTML = true;
                break;
            default:
                $rss->stripHTML = false;
                break;
        }
        $rss->date_format = 'm/d/Y';

        $rss_dateFormat = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "dateformat", "rssSettings");
        // Date format select
        switch ($rss_dateFormat) {
            case 1:
                $dateFormat = '%A, %d. %B %Y';
                break;
            case 2:
                $dateFormat = '%d. %B %Y';
                break;
            case 3:
                $dateFormat = ' %e/%m - %Y';
                break;
            default:
                if (!empty($this->conf['dateFormat'])) {
                    $dateFormat = $this->conf['dateFormat'];
                } else {
                    $dateFormat = ' %e/%m - %Y';
                }
                break;
        }
        // Get template file
        $templateFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "template", "templateS");
        if (!$templateFile) {
            $templateFile = $this->conf['templateFile'];
        } else {
            $templateFile = 'uploads/tx_gkhrssimport/' . $templateFile;
        }
        if (!is_file($templateFile)) {
            $templateFile = 'EXT:' . $this->extKey . '/template/tmpl_rssimport.htm';
        }

        $templateHtml = $this->cObj->fileResource($templateFile);

        // Extract subparts from the template
        $subparts['template'] = $this->cObj->getSubpart($templateHtml, '###RSSIMPORT_TEMPLATE###');
        $subparts['item'] = $this->cObj->getSubpart($subparts['template'], '###ITEM###');
        $markerArray['###BOX###'] = $this->pi_classParam('rss_box');

        // Try to load and parse RSS file
        if ($rs = $rss->get($this->pi_getFFvalue($this->cObj->data['pi_flexform'], "rssfeed", "rssFeed"))) {
            $rs['title'] = strip_tags($rss->unhtmlentities(strip_tags($rs['title'])));
            $rs['description'] = strip_tags($rss->unhtmlentities(strip_tags($rs['description'])));
            // Show website logo (if presented)
            if ($rs['image_url'] != '') {
                $imgT = substr(get_filename($rs['image_url']), -4);
                $imageLoaction = 'uploads/tx_gkhrssimport/';
                $location = cache_img($rs['image_url'], $imageLoaction, $imgT);
                $imgTSConf['altText'] = $rs['image_title'];
                $imgTSConf['titleText'] = $rs['image_title'];
                $imgTSConf['file.']['maxW'] = $flex_logowidth;
                $imgTSConf['file'] = $location;
                // Pass the combination of TS-defined values and php processing through
                // the IMAGE cobject function:
                $imgOutput = $this->cObj->IMAGE($imgTSConf);
                $RSSImage = '<div' . $this->pi_classParam('RSS_h_image') . '><a href="' . removeHTTP($rs['image_link']) . '" target="' . $flex_target . '">' . $imgOutput . '</a></div><br />';
            } else {
                $RSSImage = '';
            }

            $markerArray['###IMAGE###'] = $RSSImage;

            // Getclickable website title
            $markerArray['###CLASS_RSS_TITLE###'] = $this->pi_classParam('rss_title');
            $markerArray['###URL###'] = removeHTTP($rs['link']);
            $markerArray['###TARGET###'] = $flex_target;
            $markerArray['###RSS_TITLE###'] = $rs['title'];
            // Get website description
            $markerArray['###CLASS_DESCRIPTION###'] = $this->pi_classParam('description');
            $markerArray['###DESCRIPTION###'] = smart_trim($rs['description'], $flex_headerlength);

            $contentItem = '';
            foreach ($rs['items'] as $item) {
                //$subparts['item'] = $this->cObj->getSubpart($subparts['template'], '###ITEM###');
                $GLOBALS['TSFE']->register['RSS_IMPORT_ITEM_LINK'] = $item['link'];  // for Userfunc fixRssURLs
                // Get item header
                $markerArray['###CLASS_HEADER###'] = $this->pi_classParam('header');
                $markerArray['###HEADER_URL###'] = removeHTTP($item['link']);
                $markerArray['###HEADER_TARGET###'] = $flex_target;
                $markerArray['###HEADER###'] = smart_trim($item['title'], $flex_headerlength);

                // Get Published date, Author and Category
                $markerArray['###CLASS_PUBBOX###'] = $this->pi_classParam('pubbox');
                if ($item['pubDate'] <> '01/01/1970') {
                    $markerArray['###CLASS_RSS_DATE###'] = $this->pi_classParam('date');
                    $markerArray['###RSS_DATE###'] = htmlentities(strftime($dateFormat, strtotime($item['pubDate'])), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);//'UTF-8'
                }
                $markerArray['###CLASS_AUTHOR###'] = $this->pi_classParam('author');
                $markerArray['###AUTHOR###'] = $item['author'];
                $markerArray['###CLASS_CATEGORY###'] = $this->pi_classParam('category');
                $markerArray['###CATEGORY###'] = htmlentities($item['category']);

                // Get Item content
                $markerArray['###CLASS_SUMMARY###'] = $this->pi_classParam('content');
                $itemSummary = $item['description'];
                $GLOBALS['TSFE']->register['RSS_IMPORT_ITEM_LENGTH'] = $flex_itemlength;  // for Userfunc smart_trim
                if (isset($this->conf['itemSummary_stdWrap.'])) {
                    $itemSummary = $this->cObj->stdWrap($itemSummary, $this->conf['itemSummary_stdWrap.']);
                }
                $itemSummary = smart_trim($itemSummary, $flex_itemlength);
                $markerArray['###SUMMARY###'] = $itemSummary;

                if ($item['enclosure']['prop']['url'] != '') {
                    $markerArray['###CLASS_DOWNLOAD###'] = $this->pi_classParam('download');
                    $markerArray['###DOWNLOAD###'] = '<a href="' . $item['enclosure']['prop']['url'] . '">' . $this->pi_getLL('Download') . ' (' . round($item['enclosure']['prop']['length'] / (1024 * 1024), 1) . ' MB)</a>';
                } else {
                    $markerArray['###CLASS_DOWNLOAD###'] = $this->pi_classParam('download');
                    $markerArray['###DOWNLOAD###'] = '';
                }
                // Get Item content
                $contentSubpart = $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray);
                if (isset($this->conf['item_stdWrap.'])) {
                    $contentSubpart = $this->cObj->stdWrap($contentSubpart, $this->conf['item_stdWrap.']);
                }
                $contentItem .= $contentSubpart;
            }
            $subpartArray['###ITEM###'] = $contentItem;
            $content = $this->cObj->substituteMarkerArrayCached($subparts['template'], $markerArray, $subpartArray);
        } else {
            // If feed is not found show this message
            $content = '<div class="rss_box">' . $flex_ErrorMessage . '</div>';
        }
        //$content= $content . '</div>';
        if (isset($this->conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $this->conf['stdWrap.']);
        }

        return $this->pi_wrapInBaseClass($content);
    }

    /*
     * fixRssURLs is called by HTMLparser to check and fix incomplete image-src-attributes in the description
     * example:
	 * <item>
	 *		<title>...</title>
	 *		<link>http://www.example.com/1234</link>
	 *		<description><![CDATA[<img src="/item.jpg"/>long and boring description</description>
	 * </item>
	 * In this case the img-src is relative to the remote domain http://www.example.com. If they're not fixed,
	 * they would point to the local domain.
	 */
    function fixRssURLs($attrib, $conf)
    {
        $imgURL = parse_url($attrib);
        if ($imgURL['scheme'] && $imgURL['host']) {
            return $attrib;
        }

        $linkURL = parse_url($GLOBALS['TSFE']->register['RSS_IMPORT_ITEM_LINK']);

        $url = $linkURL['scheme'] . '://' . $linkURL['host'] . $linkURL['port'] . $imgURL['path'] . $imgURL['query'] . $imgURL['fragment'];

        return $url;
    }

    function smart_trim($text, $conf)
    {
        $flex_itemlength = $GLOBALS['TSFE']->register['RSS_IMPORT_ITEM_LENGTH'];
        if ($flex_itemlength == 0) {
            return $text;
        }

        return smart_trim($text, $flex_itemlength);
    }
}

/**
 * Get filename from url
 *
 * @param    string $url : url to the file
 */
function get_filename($url)
{
    $parts = explode("/", $url);
    return ($parts[count($parts) - 1] == "") ? $parts[count($parts) - 2] : $parts[count($parts) - 1];
}

/**
 * Cache image
 *
 * @param  string $url : url to the image
 * @param  string $location : Where to store the image
 * @param  string $imgT : Type of the image (gif, jpg,png)
 * @return string
 */
function cache_img($url, $location, $imgT)
{
    //EDITABLE PARAMETERS
    $days_to_keep = 60;              //How many days till check if new thumbnail
    $fname = md5($url) . $imgT;
    $full_img_path = $location;
    $fileUrl = $full_img_path . $fname;

    if (file_exists($fileUrl)) {
        //check age
        $diff = (time() - filemtime($full_img_path . $fname)) / 60 / 60 / 24;
        if ($diff > $days_to_keep) {
            unlink($full_img_path . $fname);
        } else {
            $return_img = $full_img_path . $fname;
        }
    }

    if (!file_exists($fileUrl)) {
        $buff = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($url, $includeHeader = 0);
        if ($buff != '') {
            \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($full_img_path . $fname, $buff);
            $return_img = $full_img_path . $fname;
        }
    }

    return $return_img;
}

/**
 * Remove double http://
 *
 * @param string $fn : url
 * @return string return url with one http://
 */
function removeHTTP($fn)
{
    $fnCheck2 = substr($fn, 14, 3);
    $fnLEN = strlen($fn);
    if ($fnCheck2 == 'www') {
        $fnURL = 'http://' . substr($fn, 14, $fnLEN);
    } else {
        $fnURL = $fn;
    }

    return $fnURL;
}
