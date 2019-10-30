<?php
/*
 ======================================================================
 lastRSS 0.9.1

 Simple yet powerfull PHP class to parse RSS files.

 by Vojtech Semecky, webmaster @ oslab . net

 Latest version, features, manual and examples:
 	http://lastrss.oslab.net/

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
 * lastRSS
 * Simple yet powerfull PHP class to parse RSS files.
 */
class lastRSS
{
    // -------------------------------------------------------------------
    // Public properties
    // -------------------------------------------------------------------
    public $default_cp = 'UTF-8';
    public $CDATA = 'nochange';
    public $cp = '';
    public $items_limit = 0;
    public $stripHTML = false;
    public $date_format = '';
    public $cache_dir = '';
    public $cache_time = '';
    public $rsscp;

    // -------------------------------------------------------------------
    // Private variables
    // -------------------------------------------------------------------
    protected $channeltags = ['title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs'];
    protected $itemtags = ['title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source'];
    protected $xmlproptags = ['enclosure' => ['url', 'length', 'type']];
    protected $imagetags = ['title', 'url', 'link', 'width', 'height'];
    protected $textinputtags = ['title', 'description', 'name', 'link'];

    // -------------------------------------------------------------------
    // Parse RSS file and returns associative array.
    // -------------------------------------------------------------------
    public function Get($rss_url)
    {
        // If CACHE ENABLED
        if ($this->cache_dir != '') {
            $cache_file = $this->cache_dir . '/rsscache_' . $this->items_limit . '_' . md5($rss_url);
            $timedif = @(time() - filemtime($cache_file));
            if ($timedif < $this->cache_time) {
                // cached file is fresh enough, return cached array
                $result = unserialize(join('', file($cache_file)));
                // set 'cached' to 1 only if cached file is correct
                if ($result) {
                    $result['cached'] = 1;
                }
            } else {
                // cached file is too old, create new
                $result = $this->Parse($rss_url);
                $serialized = serialize($result);
                if ($f = @fopen($cache_file, 'w')) {
                    fwrite($f, $serialized, strlen($serialized));
                    fclose($f);
                }
                if ($result) {
                    $result['cached'] = 0;
                }
            }
        } else {
            // If CACHE DISABLED >> load and parse the file directly
            $result = $this->Parse($rss_url);
            if ($result) {
                $result['cached'] = 0;
            }
        }
        // return result
        return $result;
    }

    // -------------------------------------------------------------------
    // Modification of preg_match(); return trimed field with index 1
    // from 'classic' preg_match() array output
    // -------------------------------------------------------------------
    public function my_preg_match($pattern, $subject)
    {
        // start regular expression
        preg_match($pattern, $subject, $out);

        // if there is some result... process it and return it
        if (isset($out[1])) {
            // Process CDATA (if present)
            if ($this->CDATA == 'content') { // Get CDATA content (without CDATA tag)
                if (substr($out[1], 0, 9) == '<![CDATA[') {
                    $out[1] = strtr($out[1], ['<![CDATA['=>'', ']]>'=>'']);
                } else {
                    $out[1] = html_entity_decode($out[1], ENT_QUOTES, $this->rsscp);
                }
            } elseif ($this->CDATA == 'strip') { // Strip CDATA
                $out[1] = strtr($out[1], ['<![CDATA['=>'', ']]>'=>'']);
            }

            // If code page is set convert character encoding to required
            if ($this->cp != '') {
                //$out[1] = $this->MyConvertEncoding($this->rsscp, $this->cp, $out[1]);
                $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);
            }
            // Return result
            return trim($out[1]);
        }

        // if there is NO result, return empty string
        return '';
    }

    // -------------------------------------------------------------------
    // Replace HTML entities &something; by real characters
    // -------------------------------------------------------------------
    public function unhtmlentities($string)
    {
        return html_entity_decode($string, ENT_QUOTES, $this->cp);
    }

    // -------------------------------------------------------------------
    // Parse() is private method used by Get() to load and parse RSS file.
    // Don't use Parse() in your scripts - use Get($rss_file) instead.
    // -------------------------------------------------------------------
    public function Parse($rss_url)
    {
        // Open and load RSS file
        $report = null;
        $rss_httpmsg = GeneralUtility::getURL($rss_url, 1, false, $report);
        // Check for "301 Moved"
        while ($report['http_code'] == '301') {
            if ($rss_httpmsg) {
                preg_match('/^(.+?\r\n)\r\n(.*)$/ms', $rss_httpmsg, $http_parts);
                preg_match('/Location: (.*)/', $http_parts[1], $location);
                if (isset($location[1])) {
                    $rss_httpmsg = GeneralUtility::getUrl($location[1], 1, false, $report);
                }
            }
        }
        if ($rss_httpmsg) {
            preg_match('/^(.+?\r\n)\r\n(.*)$/ms', $rss_httpmsg, $http_parts);
            preg_match('/\r\nContent-Encoding:\s*(.+)\r\n/', $http_parts[1], $http_encoding);
            if ($http_encoding[1] == 'gzip') {
                $rss_content = gzinflate(substr($http_parts[2], 10));
            } elseif (!$http_encoding[1]) {
                $rss_content = $http_parts[2];
            }
        }
        //$rss_content = t3lib_div::getURL( $rss_url );

        if ($rss_content) {
            // Parse document encoding
            $result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
            // if document codepage is specified, use it
            if ($result['encoding'] != '') {
                $this->rsscp = $result['encoding'];
            } // This is used in my_preg_match()
            // otherwise use the default codepage
            else {
                $this->rsscp = $this->default_cp;
            } // This is used in my_preg_match()

            // Parse CHANNEL info
            preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
            foreach ($this->channeltags as $channeltag) {
                $temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
                if ($temp != '') {
                    $result[$channeltag] = $temp;
                } // Set only if not empty
            }
            // If date_format is specified and lastBuildDate is valid
            if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {
                // convert lastBuildDate to specified date format
                $result['lastBuildDate'] = date($this->date_format, $timestamp);
            }

            // Parse TEXTINPUT info
            preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
            // This a little strange regexp means:
            // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
            if (isset($out_textinfo[2])) {
                foreach ($this->textinputtags as $textinputtag) {
                    $temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
                    if ($temp != '') {
                        $result['textinput_'.$textinputtag] = $temp;
                    } // Set only if not empty
                }
            }
            // Parse IMAGE info
            preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach ($this->imagetags as $imagetag) {
                    $temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
                    if ($temp != '') {
                        $result['image_'.$imagetag] = $temp;
                    } // Set only if not empty
                }
            }
            // Parse ITEMS
            preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
            $rss_items = $items[2];
            $i = 0;
            $result['items'] = array(); // create array even if there are no items
            foreach ($rss_items as $rss_item) {
                // If number of items is lower then limit: Parse one item
                if ($i < $this->items_limit || $this->items_limit == 0) {
                    foreach ($this->itemtags as $itemtag) {
                        $temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
                        if ($temp != '') {
                            $result['items'][$i][$itemtag] = $temp;
                        } // Set only if not empty
                    }

                    foreach ($this->xmlproptags as $xmlpropkey => $xmlproperties) {
                        $temp = $this->my_preg_match("'<$xmlpropkey(.*?)/>'si", $rss_item);
                        if ($temp != '') {
                            foreach ($xmlproperties as $xmlproperty) {
                                $tempprop = $this->my_preg_match("'$xmlproperty=\"(.*?)\"'si", $temp);
                                if ($tempprop != '') {
                                    $result['items'][$i][$xmlpropkey]['prop'][$xmlproperty] = $tempprop;
                                } // Set only if not empty
                            }
                        }
                    }

                    // Strip HTML tags and other bullshit from DESCRIPTION
                    if ($this->stripHTML && $result['items'][$i]['description']) {
                        $result['items'][$i]['description'] = strip_tags($result['items'][$i]['description']);
                    }
                    // Strip HTML tags and other bullshit from TITLE
                    if ($this->stripHTML && $result['items'][$i]['title']) {
                        $result['items'][$i]['title'] = strip_tags($result['items'][$i]['title']);
                    }
                    // If date_format is specified and pubDate is valid
                    if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
                        // convert pubDate to specified date format
                        $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                    }
                    // Item counter
                    $i++;
                }
            }

            $result['items_count'] = $i;
            return $result;
        }

        // Error in opening return False
        return false;
    }
}
