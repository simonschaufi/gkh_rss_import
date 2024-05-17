<?php

declare(strict_types=1);

/*
 ======================================================================
 lastRSS 0.9.1

 Simple yet powerful PHP class to parse RSS files.

 by Vojtech Semecky, webmaster @ oslab . net

 Latest version, features, manual and examples:
 	https://github.com/MikeVister/lastrss

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Simple yet powerful PHP class to parse RSS files.
 */
class lastRSS
{
    public $default_cp = 'UTF-8';
    public $CDATA = 'nochange';
    public $cp = '';
    public $items_limit = 0;
    public $stripHTML = false;
    public $date_format = '';
    public $cache_dir = '';
    public $cache_time = 0;
    public $rsscp = '';

    // -------------------------------------------------------------------
    // Private variables
    // -------------------------------------------------------------------
    protected $channeltags = ['title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs'];
    protected $itemtags = ['title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source'];
    protected $xmlproptags = ['enclosure' => ['url', 'length', 'type']];
    protected $imagetags = ['title', 'url', 'link', 'width', 'height'];
    protected $textinputtags = ['title', 'description', 'name', 'link'];

    /**
     * Parse RSS file and returns associative array.
     * @return array|bool|mixed
     */
    public function Get(string $rssUrl): mixed
    {
        // if the cache is enabled
        if ($this->cache_dir !== '') {
            $cacheFile = $this->cache_dir . '/rsscache_' . $this->items_limit . '_' . md5($rssUrl);
            $timeDiff = @(time() - filemtime($cacheFile));
            if ($timeDiff < $this->cache_time) {
                // cached file is fresh enough, return cached array
                $result = unserialize(implode('', file($cacheFile)));
                // set 'cached' to 1 only if cached file is correct
                if ($result) {
                    $result['cached'] = 1;
                }
            } else {
                // cached file is too old, create new
                $result = $this->Parse($rssUrl);
                $serialized = serialize($result);
                if ($f = @fopen($cacheFile, 'wb')) {
                    fwrite($f, $serialized, strlen($serialized));
                    fclose($f);
                }
                if ($result) {
                    $result['cached'] = 0;
                }
            }
        } else {
            // if the cache is disabled, then load and parse the file directly
            $result = $this->Parse($rssUrl);
            if ($result) {
                $result['cached'] = 0;
            }
        }
        return $result;
    }

    /**
     * Modification of preg_match(); return trimmed field with index 1
     * from 'classic' preg_match() array output
     */
    public function my_preg_match(string $pattern, string $subject): string
    {
        preg_match($pattern, $subject, $out);

        // if there is some result... process it and return it
        if (isset($out[1])) {
            // Process CDATA (if present)
            if ($this->CDATA === 'content') { // Get CDATA content (without CDATA tag)
                if (str_starts_with($out[1], '<![CDATA[')) {
                    $out[1] = strtr($out[1], ['<![CDATA[' => '', ']]>' => '']);
                } else {
                    $out[1] = html_entity_decode($out[1], ENT_QUOTES, $this->rsscp);
                }
            } elseif ($this->CDATA === 'strip') { // Strip CDATA
                $out[1] = strtr($out[1], ['<![CDATA[' => '', ']]>' => '']);
            }

            // If code page is set, convert character encoding to required
            if ($this->cp !== '') {
                $out[1] = iconv((string) $this->rsscp, $this->cp . '//TRANSLIT', $out[1]);
            }
            return trim($out[1]);
        }

        return '';
    }

    /**
     * Replace HTML entities by real characters
     */
    public function unhtmlentities(string $string): string
    {
        return html_entity_decode($string, ENT_QUOTES, $this->cp);
    }

    /**
     * Parse() is a private method used by Get() to load and parse RSS file.
     * Don't use Parse() in your scripts - use Get($rss_file) instead.
     * @internal
     */
    public function Parse(string $rssUrl): bool|array
    {
        $content = GeneralUtility::getURL($rssUrl);

        if (!$content) {
            // Error in opening return False
            return false;
        }

        // Parse document encoding
        $result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $content);
        // if document codepage is specified, use it
        $this->rsscp = $result['encoding'] !== '' ? $result['encoding'] : $this->default_cp;

        // Parse CHANNEL info
        preg_match("'<channel.*?>(.*?)</channel>'si", $content, $out_channel);
        foreach ($this->channeltags as $channelTag) {
            $temp = $this->my_preg_match("'<$channelTag.*?>(.*?)</$channelTag>'si", $out_channel[1]);
            if ($temp !== '') {
                $result[$channelTag] = $temp;
            }
        }
        // If date_format is specified and lastBuildDate is valid
        if ($this->date_format !== '' && ($timestamp = strtotime($result['lastBuildDate'])) !== -1) {
            // convert lastBuildDate to specified date format
            $result['lastBuildDate'] = date($this->date_format, $timestamp);
        }

        // Parse TEXTINPUT info
        preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $content, $outTextInfo);
        // This a little strange regexp means:
        // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
        if (isset($outTextInfo[2])) {
            foreach ($this->textinputtags as $textInputTag) {
                $temp = $this->my_preg_match("'<$textInputTag.*?>(.*?)</$textInputTag>'si", $outTextInfo[2]);
                if ($temp !== '') {
                    $result['textinput_' . $textInputTag] = $temp;
                }
            }
        }
        // Parse IMAGE info
        preg_match("'<image.*?>(.*?)</image>'si", $content, $outImageInfo);
        if (isset($outImageInfo[1])) {
            foreach ($this->imagetags as $imagetag) {
                $temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $outImageInfo[1]);
                if ($temp !== '') {
                    $result['image_' . $imagetag] = $temp;
                }
            }
        }
        // Parse ITEMS
        preg_match_all("'<item(| .*?)>(.*?)</item>'si", $content, $items);
        $rssItems = $items[2];
        $i = 0;
        $result['items'] = []; // create array even if there are no items
        foreach ($rssItems as $rssItem) {
            // If number of items is lower then limit: Parse one item
            if ($i < $this->items_limit || $this->items_limit == 0) {
                foreach ($this->itemtags as $itemTag) {
                    $temp = $this->my_preg_match("'<$itemTag.*?>(.*?)</$itemTag>'si", $rssItem);
                    if ($temp !== '') {
                        $result['items'][$i][$itemTag] = $temp;
                    }
                }

                foreach ($this->xmlproptags as $xmlPropKey => $xmlProperties) {
                    $temp = $this->my_preg_match("'<$xmlPropKey(.*?)/>'si", $rssItem);
                    if ($temp !== '') {
                        foreach ($xmlProperties as $xmlProperty) {
                            $tempProp = $this->my_preg_match("'$xmlProperty=\"(.*?)\"'si", $temp);
                            if ($tempProp !== '') {
                                $result['items'][$i][$xmlPropKey]['prop'][$xmlProperty] = $tempProp;
                            }
                        }
                    }
                }

                // Strip HTML tags from DESCRIPTION
                if ($this->stripHTML && $result['items'][$i]['description']) {
                    $result['items'][$i]['description'] = strip_tags((string) $result['items'][$i]['description']);
                }
                // Strip HTML tags from TITLE
                if ($this->stripHTML && $result['items'][$i]['title']) {
                    $result['items'][$i]['title'] = strip_tags((string) $result['items'][$i]['title']);
                }
                // If date_format is specified and pubDate is valid
                if ($this->date_format !== '' && ($timestamp = strtotime((string) $result['items'][$i]['pubDate'])) !==-1) {
                    // convert pubDate to specified date format
                    $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                }
                $i++;
            }
        }

        $result['items_count'] = $i;
        return $result;
    }
}
