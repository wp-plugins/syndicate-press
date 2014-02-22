<?php
/* 
Plugin Name: Syndicate Press
Plugin URI: http://syndicatepress.henryranch.net/
Description: This plugin provides a high performance, highly configurable and easy to use news syndication aggregator which supports RSS, RDF and ATOM feeds.
Author: HenryRanch LLC (henryranch.net)
Version: 1.0.30
Author URI: http://syndicatepress.henryranch.net/
License: GPL2
*/

/*

LICENSE:
============
Copyright (c) 2009-2013 Henry Ranch LLC. All rights reserved. http://syndicatepress.henryranch.net/

By downloading or using this software,  you agree to all the following: 

Syndicate Press is released under the GPLv2 license and is additionally governed by the following 
agreement:  

THE SYNDICATE PRESS PLUGIN IS COMPRISED OF MULTIPLE FILES WHICH ARE 
CONTAINED WITHIN A DISTRIBUTION OF THE SYNDICATE PRESS PLUGIN.  NO 
PORTION OF THE SYNDICATEPRESS DISTRIBUTION IS LICENCED FOR USE 
OUTSIDE OF THE SYNDICATEPRESS WORDPRESS PLUGIN.  IF YOU WOULD LIKE 
TO USE A PORTION OF THE SYNDICATEPRESS PLUGIN IN YOUR OWN APPLICATION, 
YOU MAY REQUEST A LICENSE TO DO SO FROM THE AUTHOR.
 
 YOU AGREE THAT YOU WILL NOT USE THIS SOFTWARE TO ACCESS, DISPLAY
 OR AGGREGATE CONTENT IN A MANNER THAT VIOLATES THE COPYRIGHT, 
 INTELLECTUAL PROPERTY RIGHTS OR TRADEMARK OF ANY ENTITY.  
 
 THE AUTHOR AND HENRYRANCH LLC SHALL NOT BE LIABLE FOR ANY COPYRIGHT 
 INFRINGEMENT CLAIMS MADE THROUGH YOUR USE OF THIS SOFTWARE.  YOU 
 AGREE TO INDEMNIFY, PROTECT AND SHIELD THE AUTHOR AND HENRYRANCH LLC 
 FROM ALL LEGAL JUDGEMENTS, FEES,  COSTS AND/OR ANY ASSOCIATED FEES 
 THAT MAY RESULT OUT OF YOUR USE OF THIS SOFTWARE.  YOU AGREE THAT YOU 
 ARE SOLELY RESPONSIBLE FOR YOUR USE OF THIS  SOFTWARE AND SHALL 
 FOREVER HOLD THE AUTHOR AND HENRYRANCH LLC HARMLESS IN ALL MATTERS.
 
 ANY INSTALLATION OR USE OF THIS SOFTWARE MEANS THAT YOU ACCEPT AND AGREE 
 TO ABIDE BY ALL OF THE TERMS OF THIS LICENSE AGREEMENT.
 
 
 Copyright 2009-2013  HenryRanch LLC  

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



if (!class_exists("SyndicatePressPlugin")) {
  class SyndicatePressPlugin {
        var $version = "1.0.30";
        var $homepageURL = "http://syndicatepress.henryranch.net/";
        
        var $cacheDir = "/cache";
        var $inputFeedCacheDir = "/cache/input";
        var $formattedOutputCacheDir = "/cache/output";
        var $linkBackImagesDir = "/images";
        var $adminOptionsName = "SyndicatePressPluginAdminOptions";
        
        var $feedListCustomNameDelimiter = '|';
        function SyndicatePressPlugin() {}
        function init() {
          $this->sp_getConfigOptions();
        }
        //Returns an array of admin options
        function sp_getConfigOptions() {
          $adminOptions = array(
            'enable' => 'true',
            'enableFeedCache' => 'true', 
            'enableOutputCache' => 'true', 
            'customCacheDirectory' => '',
            'useDownloadClient' => 'true', 
            'displayImages' => 'false',
            'allowMarkupInDescription' => 'false',
            'stripCDataTags' => 'false',
            'showContentOnlyInLinkTitle' => 'false', 
            'showSyndicatePressLinkback' => 'true',
            'showProcessingMetrics' => 'true',
            'showFeedChannelTitle' => 'true',
            'useCustomFeednameAsChannelTitle' => 'false',
            'showArticlePublishTimestamp' => 'true',
            'limitFeedItemsToDisplay' => -1, 
            'hideArticlesAfterArticleNumber' => -1,
            'limitFeedDescriptionCharsToDisplay' => -1, 
            'maxHeadlineLength' => -1,
            'feedUrlList' => '',
            'inclusiveKeywordFilter' => '',
            'exclusiveKeywordFilter' => '',
            'timestampFormat' => 'l F jS, Y h:i:s A',
            'cacheTimeoutSeconds' => 3600,
            'feedTitleHTMLCodePre' => '<h2>',
            'feedTitleHTMLCodePost' => '</h2>',
            'articleTimestampHTMLCodePre' => '<i>',
            'articleTimestampHTMLCodePost' => '</i>',
            'articleAuthorHTMLCodePre' => '<i>',
            'articleAuthorHTMLCodePost' => '</i>',
            'articleCopyrightHTMLCodePre' => '<i>',
            'articleCopyrightHTMLCodePost' => '</i>',
            'articlePriceHTMLCodePre' => '<i>',
            'articlePriceHTMLCodePost' => '</i>',
            'articleImageHTMLCodePre' => '<div><br>',
            'articleImageHTMLCodePost' => '</div>',
            'articleTitleHTMLCodePre' => '<h3>',
            'articleTitleHTMLCodePost' => '</h3>',
            'articleBodyHTMLCodePre' => '<div>',
            'articleBodyHTMLCodePost' => '</div><br>',
            'feedSeparationHTMLCode' => '<hr>',
            'addNoFollowTag' => 'true',
            'openArticleInLightbox' => 'false',            
            'lightboxHTMLCode' => "<div id=\"lightbox-external\" class=\"lightbox_content\">\r\n".
			                         "<a href=\"javascript:void(0)\" onclick=\"document.getElementById('lightbox-external').style.display='none';document.getElementById('body').style.display='none'\" title=\"click to close the lightbox\">X</a><br>\r\n".
		                            "<iframe id=\"external-content-iframe\" name=\"external-content-iframe\" frameborder=0 width=\"100%\" height=\"100%\" scrolling=\"yes\">Hello world!</iframe>\r\n".
			                         "</div>\r\n",
            'feedNotAvailableHTMLCode' => 'Sorry, the {feedname} feed is not available at this time.'
            );
            $configOptions = get_option($this->adminOptionsName);
            if (!empty($configOptions)) 
            {
              foreach ($configOptions as $key => $option)
              {
                $adminOptions[$key] = $option;
              }
            }        
              update_option($this->adminOptionsName, $adminOptions);
              return $adminOptions;
          }
    
         /* Find and match the bbcodes that are related to syndicate press in pages and posts.
         * Will match syndicate press bbcodes in pages/posts and replace the bbcode with the referenced RSS feed content
         * <!--syn-press#name--> or <!--sp#name--> or [sp# name] 
         *     This syntax allows you to reference an RSS feed URL that has been defined in the rss feed url list.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $content    the post/page content
         * @return   string     the post/page content with relevant RSS feeds embedded in place of syndicate press bbcodes
         */
        function sp_ContentFilter($content) 
        {
            global $syndicatePressPluginObjectRef;
            if(isset($syndicatePressPluginObjectRef))
            {  
                $content=preg_replace_callback(array("/<!--syn-press#(.*)-->/","/<!--sp#(.*)-->/","/\[sp#(.*)\]/"),array(&$syndicatePressPluginObjectRef,'sp_filterCallback'),$content);
                return $content;
            }
            else
            {
                return $content;
            }
        }
        
        /* get the current time
         * @package WordPress
         * @since version 2.8.4
         * @return   string     the current time
         */
        function sp_getCurrentTime()
        {
            $time = microtime();
            $timeArray = explode(" ", $time);
            $time = $timeArray[1] + $timeArray[0];
            return $time;
        }
        
        /* given the start time, calculate the elapsed time based on the current time
         * @package WordPress
         * @since version 2.8.4
         * @return   string     the elaspsed time with 5 decimal place resolution
         */
        function sp_getTotalProcessTime($startTime)
        {
            $currentTime = $this->sp_getCurrentTime();
            $elapsedTime = $currentTime - $startTime;
            $elapsedTime = round($elapsedTime,5);
            return $elapsedTime;
        }
        
        function sp_getFilePermissions($filepath)
        {
            try
            {
            	 $permissionString = '';
                $filePermissions = @fileperms($filepath);
                // Owner
                $permissionString .= (($filePermissions & 0x0100) ? 'r' : '-');
                $permissionString .= (($filePermissions & 0x0080) ? 'w' : '-');
                $permissionString .= (($filePermissions & 0x0040) ? (($filePermissions & 0x0800) ? 's' : 'x' ) : (($filePermissions & 0x0800) ? 'S' : '-'));
                // Group
                $permissionString .= (($filePermissions & 0x0020) ? 'r' : '-');
                $permissionString .= (($filePermissions & 0x0010) ? 'w' : '-');
                $permissionString .= (($filePermissions & 0x0008) ? (($filePermissions & 0x0400) ? 's' : 'x' ) : (($filePermissions & 0x0400) ? 'S' : '-'));
                // Public
                $permissionString .= (($filePermissions & 0x0004) ? 'r' : '-');
                $permissionString .= (($filePermissions & 0x0002) ? 'w' : '-');
                $permissionString .= (($filePermissions & 0x0001) ? (($filePermissions & 0x0200) ? 't' : 'x' ) : (($filePermissions & 0x0200) ? 'T' : '-'));
                return $permissionString;
            } catch(Exception $e)
            {
                return "";
            }
        }
        
        /* get the custom feedname for the given url.
         * @package WordPress
         * @since version 2.8.4
         * @return   string     the custom feedname if one exists, else ""
         */
        function sp_getCustomFeednameForUrl($url)
        {
            $configOptions = $this->sp_getConfigOptions();
            $availableFeeds = explode("\n", $configOptions['feedUrlList']);
            foreach($availableFeeds as $availableFeed)
            {
                $delimiterPosition = strpos($availableFeed, $this->feedListCustomNameDelimiter);
                if($delimiterPosition !== false)
                {
                    //only want to treat the delimiter char as a custom feedname and URL delimiter IFF the delimiter char is before the "http://" string in the URL
                    $httpPosition = stripos($availableFeed, "http://");
                    if($delimiterPosition < $httpPosition)
                    {
                        $availableFeedNameArray = explode($this->feedListCustomNameDelimiter, $availableFeed);
                        $availableFeedUrl = trim($availableFeedNameArray[1]);
                        if($availableFeedUrl == $url)
                        {
                            return trim($availableFeedNameArray[0]);
                        }
                    }
                }
            }
            return "";
        }

        /* Filter callback which actually does the main work of the plugin.
         * Will match syndicate press bbcodes in pages/posts and replace the bbcode with the referenced RSS feed content
         * <!--syn-press#name--> or <!--sp#name--> or [sp# name] 
         *     This syntax allows you to reference an RSS feed URL that has been defined in the rss feed url list.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $bbCodeTagArray    the values passed in from the bbcode: [sp# match1,match2,match3]
         * @return   string     the post/page content with relevant RSS feeds embedded in place of syndicate press bbcodes
         */
        function sp_filterCallback($bbCodeTagArray)
        {
            $startTime = $this->sp_getCurrentTime();
            $configOptions = $this->sp_getConfigOptions();
            $enabled = $configOptions['enable'];
            if($enabled == 'false')
                return;
                                
            $availableFeeds = explode("\n", $configOptions['feedUrlList']);
            
            $content = '';            
       
            $enableOutputCache = $configOptions['enableOutputCache'];
            $pageFeedReference = implode(",", $bbCodeTagArray);
            //echo "page feed reference: $pageFeedReference<br>";
            $pageFeedReferenceArray = split(" ", $pageFeedReference);
            $outputCacheFilename = $this->sp_getOutputCacheFilename($pageFeedReference);
            if(($enableOutputCache == 'true') && !$this->sp_incomingFeedCacheExpired($url) && file_exists($outputCacheFilename))
            {
                $content = file_get_contents($outputCacheFilename);
            }
            else
            {
                //print "Formatting content...<br>";
                foreach($bbCodeTagArray as $feedNameReference)
                {  
                    $feedNameReference = trim($feedNameReference);//,"\x7f..\xff\x0..\x1f"); 
          //echo 'pre exploded feed name ref: \''.$feedNameReference.'\'<br>';
                    //ignore array element s that are just the bbcode text.  we don't need the bbcode text b/c the following text is the bbcode parameters as extracted by the sp bbcode filter
                    if(strpos($feedNameReference, '[') !== false)
                    {
                        continue;
                    }
                    $bbcodeParams = explode(' ', $feedNameReference);
                    $feedNameReference = $bbcodeParams[0];
                    //extract any known params
                    foreach($bbcodeParams as $param)
                    {
                        if(strpos($param, 'include') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['includeFilterList'] = $list[1];
                        }
                        else if(strpos($param, 'exclude') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['excludeFilterList'] = $list[1];
                        }
                        else if(strpos($param, 'showImages') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['showImages'] = $list[1];
                        }
                        else if(strpos($param, 'limitArticles') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['limitArticles'] = $list[1];
                        }
                        else if(strpos($param, 'truncateTitleAtWord') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['truncateTitleAtWord'] = $list[1];
                        }                        
                        else if(strpos($param, 'replaceStringInTitle') !== false)
                        {
                            $list = explode('=', $param);
                            $customConfigOverrides['replaceStringInTitle'] = $list[1];
                        }
                        else if(strpos($param, 'feedList') !== false)
                        {
                            $list = explode('=', $param);
                            $feedNameReference = $list[1];
                        }
                    }
                    $feedIndex = 0;
                    foreach($availableFeeds as $availableFeed)
                    {
                        $availableFeed = trim($availableFeed);
                        if($availableFeed == "")
                        {
                            continue;
                        }
                        //split the reference string on ',' (comma).  this is the feed reference list provided in the bbcode: [sp# feed1,feed2,feed3,etc...]
                        //echo 'feed name ref: '.$feedNameReference.'<br>';
                        $feedNameList = explode(',', $feedNameReference);
                        foreach($feedNameList as $feedName)
                        {
                            $feedName = trim($feedName);
                            //print "Checking feedname: '$feedName' against available feed: '$availableFeed'<br>"; 
                            if(strpos($availableFeed, $feedName) !== false || ($feedName == 'all'))
                            {    
                                //print "Found requested feed: $availableFeed <br>"; 
                                //this allows naming of the feeds in the feed list as follows: [[<name><pipechar>]<feedUrl>]*
                                if(strpos($availableFeed, $this->feedListCustomNameDelimiter) !== false)
                                {
                                    $availableFeedName = explode($this->feedListCustomNameDelimiter, $availableFeed);
                                    $availableFeed = trim($availableFeedName[1]);
                                    //print "feed URL: $availableFeed <br>"; 
                                }
                                $content .= $this->sp_getFormattedRssContent($feedIndex, $availableFeed, $customConfigOverrides);
                                $feedIndex += 1;
                                if($configOptions['feedSeparationHTMLCode'] != '')
                                {
                                    $content .= $this->sp_unescapeString($configOptions['feedSeparationHTMLCode']);
                                }
                            }
                        }
                    }    
                }
                if($configOptions['showSyndicatePressLinkback'] == 'true')
                {
                    $content .= '<br><br><br><br>Feed aggregation powered by <a href='.$this->homepageURL.' title="Syndicate Press: A smarter feed aggregator" target=_blank>Syndicate Press</a>.';
                }
                //print "writing content to output cache file: $outputCacheFilename<br>";
                $this->sp_writeFile($outputCacheFilename, $content);
            }    
            if($configOptions['showProcessingMetrics'] == 'true')
            {
                $elapsedTime = $this->sp_getTotalProcessTime($startTime);
                $content .= '<br><font size=-4>Processed request in '.$elapsedTime.' seconds.</font><br>';
            }
            return $content;
        }
        
        function sp_stripCDataTags($cacheFilename)
        {            
            //echo "stripping CDATA tag from file: $cacheFilename<br>";
            $content = file_get_contents($cacheFilename);
            $content = str_replace('<![CDATA[', '', $content);
            $content = str_replace(']]>', '', $content);
            $this->sp_writeFile($cacheFilename, $content);
        }

        function sp_escapeString($str)
        {
            return $str;
        }

        function sp_unescapeString($str, $replaceDoubleQuotesWithSingleQuotesForTagParams=false)
        {
            if($replaceDoubleQuotesWithSingleQuotesForTagParams)
            {
                //$str = str_replace('"', '\'', $str);
            }
            return stripslashes(stripslashes($str));
        }
        
        /* Get the time when the given url was last cached.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     time stamp of when the feed was last cached.
         */
        function sp_getFeedCacheTime($url)
        {
            $cacheFile = $this->sp_getInputFeedCacheDir() . '/' . md5($url); 
            
            if(file_exists($cacheFile))
            {
                return date("F d Y H:i:s.", filemtime($cacheFile));
            }
            else
            {
                return "recent...";
            }
        }
        
        /* Make sure that the cache dirs are ready to be used.
         * Initialize the cache dirs.  If they don't exist, create them, else do nothing.
         * @package WordPress
         * @since version 2.8.4
         */
        function sp_prepCache()
        {
            $cacheRootDir = $this->sp_getRootCacheDir();
            if(!is_dir($cacheRootDir))
            {
                mkdir($cacheRootDir);
            }
            if(!is_dir($this->sp_getInputFeedCacheDir()))
            {
                mkdir($this->sp_getInputFeedCacheDir());
            }
            if(!is_dir($this->sp_getOutputFeedCacheDir()))
            {
                mkdir($this->sp_getOutputFeedCacheDir());
            }
        }
        
        function sp_getNumInputCacheFiles()
        {
            return $this->sp_countFilesInDir($this->sp_getInputFeedCacheDir() . '/');
        }

        function sp_getNumOutputCacheFiles()
        {
            return $this->sp_countFilesInDir($this->sp_getOutputFeedCacheDir() . '/');
        }

        function sp_getInputFeedCacheDir()
        {
            return $this->sp_getRootCacheDir().'/input';
        }

        function sp_getOutputFeedCacheDir()
        {
            return $this->sp_getRootCacheDir().'/output';
        }

        function sp_getCacheDefaultRootDir()
        {
            $pluginDir = dirname(__FILE__);
            $rootCacheDir = $pluginDir.$this->cacheDir;
            return $rootCacheDir;
        }

        function sp_getRootCacheDir()
        {            
            $configOptions = $this->sp_getConfigOptions();
            if($configOptions['customCacheDirectory'] != '')
            {
                return $configOptions['customCacheDirectory'];
            }
            else
            {
                return $this->sp_getCacheDefaultRootDir();
            }
        }

        function sp_checkCachePermissions()
        {
            $pluginDir = dirname(__FILE__);
            $mainCacheDir = $this->sp_getRootCacheDir();
            $inputCacheDir = $this->sp_getInputFeedCacheDir();
            $outputCacheDir = $this->sp_getOutputFeedCacheDir();
            $mainCacheDirPerm = $this->sp_getFilePermissions($mainCacheDir);
            $inputCacheDirPerm = $this->sp_getFilePermissions($inputCacheDir);
            $outputCacheDirPerm = $this->sp_getFilePermissions($outputCacheDir);
            $permProblem = '';
            if($mainCacheDirPerm != "rwxr-xr-x" && $mainCacheDirPerm != "---------")
            {
                $permProblem .= "Main cache: $mainCacheDirPerm<br>";
            }
            if($inputCacheDirPerm != "rwxr-xr-x" && $inputCacheDirPerm != "---------")
            {
                $permProblem .= "Input cache: $inputCacheDirPerm<br>";
            }
            if($outputCacheDirPerm != "rwxr-xr-x" && $outputCacheDirPerm != "---------")
            {
                $permProblem .= "Output cache: $outputCacheDirPerm<br>";
            }
            if($permProblem != '')
            {
                $permProblem = "There may be a problem with your cache permissions:<br>$permProblem<br>Please set your cache permissions to rwxr-xr-x.<br>Your Syndicate Press cache directory is located here: ".$mainCacheDir;
            }
            return $permProblem;
        }
    
        /* Get the domain name from the given url.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     domain name or ip address.
         */
        function sp_getDomainFromUrl($url)
        {
            preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $urlArray);
            return $urlArray[2];
        }
        
        /* Get the file path from the given url.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     file path from the url
         */
        function sp_getFilePathFromUrl($url)
        {
            $domain = $this->sp_getDomainFromUrl($url);
            $domain = 'http://'.$domain;
            return str_replace($domain, '', $url);
        }
        
        /* Get the local servers hostname that this plugin is running on.
         * @package WordPress
         * @since version 2.8.4
         * @return   string     domain name of the server that this wordpress installation is running on.
         */
        function sp_getServerHostname()
        {
            $hostname = $_ENV["HOSTNAME"];
            if(!$hostname) $hostname = $_SERVER['SERVER_NAME'];            
            return $hostname;
        }
    
        /* Determine if the cache for the given feed URL has expired and needs to be refreshed.
         * @package WordPress
         * @since version 2.8.4
         * @return   boolean - true if the cache is expired else false.
         */
        function sp_incomingFeedCacheExpired($url)
        {
            $configOptions = $this->sp_getConfigOptions();
            $cacheTimeoutSeconds = $configOptions['cacheTimeoutSeconds'];
            $enableFeedCache = $configOptions['enableFeedCache'];
            $cacheFile = $this->sp_getInputCacheFilename($url);
            if($enableFeedCache == "false" || !file_exists($cacheFile) || ((time() - filemtime($cacheFile)) > $cacheTimeoutSeconds)) 
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    
        /* Cache the given rss feed if needed
         * Cache the contents of the given rss feed if it is not already cached. 
         * If already cached and the timeout period has expired, re-download the feed and cache it.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     the locally available path to the cache file.
         */
        function sp_cacheIncomingRssFeed($url)
        {
            $configOptions = $this->sp_getConfigOptions();
            $cacheTimeoutSeconds = $configOptions['cacheTimeoutSeconds'];
            $enableFeedCache = $configOptions['enableFeedCache'];            
            $useDownloadClient = $configOptions['useDownloadClient'];              
            $this->sp_prepCache();
            $cacheFile = $this->sp_getInputFeedCacheDir() .'/'. md5($url); 
            if($this->sp_incomingFeedCacheExpired($url))
            {    
                if($useDownloadClient == "true")
                {
                    include_once "php/TinyHttpClient.php";                    
                    if(class_exists("TinyHttpClient")) 
                    {                       
                        $host = $this->sp_getDomainFromUrl($url);
                        $port = 80;
                        $remoteFile = $this->sp_getFilePathFromUrl($url);
                        $basicAuthUsernameColonPassword = "";
                        $bufferSize = 4096;
                        $mode = "get";
                        //$mode = "post";
                        $fromEmail = 'syndicatePress@'.$this->sp_getServerHostname();
                        $postData = "";
                        $filename = $cacheFile;
                        //print "host: $host<br>post: $port<br>remoteFile: $remoteFile<br>fromEmail: $fromEmail<br>filename: $filename<br>";
                        $tinyHttpClient = new TinyHttpClient();    
                        //$tinyHttpClient->debug = true;
                        $retVal = $tinyHttpClient->getRemoteFile($host, $port, $remoteFile, $basicAuthUsernameColonPassword, $bufferSize, $mode, $fromEmail, $postData, $filename);
                        if(strpos($retVal, "HTTP-301_MOVED_TO:") !== false)
                        {
                            //print "Received $retVal when requesting $remoteFile<br>";
                            $remoteFile = str_replace("HTTP-301_MOVED_TO:", $retVal, "");
                            $retVal = $tinyHttpClient->getRemoteFile($host, $port, $remoteFile, $basicAuthUsernameColonPassword, $bufferSize, $mode, $fromEmail, $postData, $filename);
                        }
                        //print $retVal;
                    }
                    else
                    {
                        print "<font color=red>ERROR: Could not locate TinyHttpClient class</font><br>";
                    }
                }
                else
                {
                    //this approach is disabled on many php 5 based shared web hosts...
                    if($content = file_get_contents($url)) 
                    { 
                        $this->sp_writeFile($cacheFile, $content);
                    } 
                }
            } 
            if($this->stripCDataTags == 'true')
            {
                //print "cache(): need to strip CDATA tags from $cacheFile<br>";
                $this->sp_stripCDataTags($cacheFile);
            }
            return $cacheFile;
        }  
        
        /* Cache the parsed and "rendered" html feed information
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     the locally available path to the cache file.
         */
        function sp_cacheRenderedHtml($feedReference, $htmlContent)
        {  
            $cacheFile = $this->sp_getOutputCacheFilename($feedReference); 
            $this->sp_writeFile($cacheFile, $content);
        }
        
        /* Get the filename of the output cache file for the given feed reference  name
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $feedNameReference    comma separated list of feeds that make up the formatted output for the bbcode that this bbcode instance refers to
         * @return   string     the locally available path to the cache file.
         */
        function sp_getOutputCacheFilename($feedNameReference)
        {
            return $this->sp_getOutputFeedCacheDir() .'/'. md5($feedNameReference); 
        }
        
        /* Get the filename of the input cache file for the given feed url
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the url of the incoming feed
         * @return   string     the locally available path to the cache file.
         */
        function sp_getInputCacheFilename($url)
        {
            return $this->sp_getInputFeedCacheDir() .'/'. md5($url); 
        }
          
        /* write the given content to the given filename
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $fileName    the local path to the file to be written
         * @param    string    $content    the content to be writting into the file
         */
        function sp_writeFile($fileName, $content)
        {
        		//echo "writing to file $fileName : <br> $content<br>";
            $fp = fopen($fileName, 'w'); 
            fwrite($fp, $content); 
            fclose($fp);
        }

        function sp_readFile($filename)
        {
        	  return file_get_contents($filename, true);
        }

        function sp_countFilesInDir($dir)
        {
          try
            {
                $filesInDir = glob($dir.'*');
                if($filesInDir)
                {
                    return count($filesInDir);
                }
                else
                {
                    return 0;
                }
            } catch(Exception $e)
            {
                return -2;
            }
        }

        /* Delete all of the files in the given directory
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $dir    the local path to the dir from which the files will be deleted
         */
        function sp_deleteFilesInDir($dir)
        {
            try
            {
                $filesInDir = glob($dir.'*');
                if($filesInDir)
                {
                    foreach($filesInDir as $filename)
                    {
                        unlink($filename);
                    }
                }
            } catch(Exception $e)
            {}
        }
        
        function sp_clearCache()
        {
            //echo "clearing both caches...<br>";
            $this->sp_clearIncomingFeedCache();
            $this->sp_clearFormattedOutputCache();
        }

        /* Delete the incoming feed cache files
         * @package WordPress
         * @since version 2.8.4
         */
        function sp_clearIncomingFeedCache()
        {
            $this->sp_deleteFilesInDir($this->sp_getInputFeedCacheDir() .'/');
        }
        
        /* Delete the formatted output cache files
         * @package WordPress
         * @since version 2.8.4
         */
        function sp_clearFormattedOutputCache()
        {
            $this->sp_deleteFilesInDir($this->sp_getOutputFeedCacheDir() .'/');
        }
        
        /* Get the html formatted rss feed.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $url    the rss url
         * @return   string     html formatted rss feed content from the given url.
         */
        function sp_getFormattedRssContent($feedIndex, $url, $customConfigOverrides = NULL)
        {
            include_once "php/TinyFeedParser.php";
            if(isset($customConfigOverrides))
            {
                $customConfigExclusiveKeywords = $customConfigOverrides['excludeFilterList'];
                $customConfigInclusiveKeywords = $customConfigOverrides['includeFilterList'];
                $customConfigShowImages = $customConfigOverrides['showImages'];
                $customConfigLimitArticles = $customConfigOverrides['limitArticles'];
                $customConfigTruncateTitleAtWord = $customConfigOverrides['truncateTitleAtWord'];
                $customConfigReplaceStringInTitle = $customConfigOverrides['replaceStringInTitle'];                
            }
            
            //make sure there are no whitespaces leading or trailing the URL string
            $url = trim($url);
            
            try
            {
                $cachedInputFeedFile = $this->sp_cacheIncomingRssFeed($url);
            }
            catch(Exception $e)
            {
                return 'Unable to download content from '.$url;
            }
            try
            {
                if(!file_exists($cachedInputFeedFile) || filesize($cachedInputFeedFile) == 0)
                {
                    return "Failed to get content from '".$url."'";
                }
                $configOptions = $this->sp_getConfigOptions();
                $parser = new TinyFeedParser($cachedInputFeedFile);
                $parser->feedIndex = $feedIndex;
                //echo "feedIndex: $feedIndex<br>";
                $parser->showContentOnlyInLinkTitle = $configOptions['showContentOnlyInLinkTitle'];
                $parser->maxNumArticlesToDisplay = $configOptions['limitFeedItemsToDisplay'];
                if(isset($customConfigLimitArticles))
                {
                  $parser->maxNumArticlesToDisplay = $customConfigLimitArticles;
                }
                
                $parser->exclusiveKeywordList = $configOptions['exclusiveKeywordFilter'] . ',' . $customConfigExclusiveKeywords;
                $parser->exclusiveKeywordList = trim($parser->exclusiveKeywordList, ',');
                $parser->inclusiveKeywordList = $configOptions['inclusiveKeywordFilter'] . ',' . $customConfigInclusiveKeywords;
                $parser->inclusiveKeywordList = trim($parser->inclusiveKeywordList, ',');                
                
                $parser->maxDescriptionLength = $configOptions['limitFeedDescriptionCharsToDisplay'];
                $parser->showFeedChannelTitle = $configOptions['showFeedChannelTitle'];
                $parser->useCustomFeednameAsChannelTitle = $configOptions['useCustomFeednameAsChannelTitle'];
                $parser->feedTitleHTMLCodePre = $this->sp_unescapeString($configOptions['feedTitleHTMLCodePre']);
                $parser->feedTitleHTMLCodePost = $this->sp_unescapeString($configOptions['feedTitleHTMLCodePost']);
                $parser->articleTitleHTMLCodePre = $this->sp_unescapeString($configOptions['articleTitleHTMLCodePre']);
                $parser->articleTitleHTMLCodePost = $this->sp_unescapeString($configOptions['articleTitleHTMLCodePost']);
                $parser->articleBodyHTMLCodePre = $this->sp_unescapeString($configOptions['articleBodyHTMLCodePre']);
                $parser->articleBodyHTMLCodePost = $this->sp_unescapeString($configOptions['articleBodyHTMLCodePost']);
                $parser->articleAuthorHTMLCodePre = $this->sp_unescapeString($configOptions['articleAuthorHTMLCodePre']);
                $parser->articleAuthorHTMLCodePost = $this->sp_unescapeString($configOptions['articleAuthorHTMLCodePost']);
                $parser->articleCopyrightHTMLCodePre = $this->sp_unescapeString($configOptions['articleCopyrightHTMLCodePre']);
                $parser->articleCopyrightHTMLCodePost = $this->sp_unescapeString($configOptions['articleCopyrightHTMLCodePost']);
                $parser->articlePriceHTMLCodePre = $this->sp_unescapeString($configOptions['articlePriceHTMLCodePre']);
                $parser->articlePriceHTMLCodePost = $this->sp_unescapeString($configOptions['articlePriceHTMLCodePost']);
                $parser->articleTimestampHTMLCodePre = $this->sp_unescapeString($configOptions['articleTimestampHTMLCodePre']);
                $parser->articleTimestampHTMLCodePost = $this->sp_unescapeString($configOptions['articleTimestampHTMLCodePost']);
                $parser->articleImageHTMLCodePre = $this->sp_unescapeString($configOptions['articleImageHTMLCodePre']);
                $parser->articleImageHTMLCodePost = $this->sp_unescapeString($configOptions['articleImageHTMLCodePost']);
                $parser->showFeedMetrics = $configOptions['showProcessingMetrics'];
                $parser->showArticlePublishTimestamp = $configOptions['showArticlePublishTimestamp'];
                $parser->allowMarkupInDescription = $configOptions['allowMarkupInDescription'];
                $parser->addNoFollowTag = $configOptions['addNoFollowTag'];
                $parser->hideArticlesAfterArticleNumber = $configOptions['hideArticlesAfterArticleNumber'];
                $parser->openArticleInLightbox = $configOptions['openArticleInLightbox'];
                $parser->lightboxHTMLCode = $this->sp_unescapeString($configOptions['lightboxHTMLCode']);
                if($configOptions['timestampFormat']  != '')
                {
                  $parser->useCustomTimestampFormat = true;
                  $parser->timestampFormatString = $configOptions['timestampFormat'];
                  $parser->timestampFormatString = $this->sp_unescapeString($parser->timestampFormatString);
                }
                $parser->customFeedName = $this->sp_getCustomFeednameForUrl($url);
                if($parser->customFeedName == "")
                {
                    $parser->customFeedName = $url;
                }
                $parser->maxHeadlineLength = $configOptions['maxHeadlineLength'];
                $parser->allowImagesInDescription = $configOptions['displayImages'];
                if(isset($customConfigShowImages))
                {
                    $parser->allowImagesInDescription = $customConfigShowImages;
                    $parser->allowMarkupInDescription = 'true';
                    $parser->showContentOnlyInLinkTitle = 'false';
                }
                if(isset($customConfigTruncateTitleAtWord))
                {
                     $parser->truncateTitleAtWord = $customConfigTruncateTitleAtWord;
                }
                if(isset($customConfigReplaceStringInTitle))
                {
                	   $parser->replaceStringInTitle = $customConfigReplaceStringInTitle;
                }
                if($parser->showContentOnlyInLinkTitle == 'true')
                {
                    $parser->allowImagesInDescription = 'false';
                    $parser->allowMarkupInDescription = 'false';
                }
                $parser->parseFeed($cachedInputFeedFile);
                $content = $parser->getHtml();
                //$this->sp_postContent($content);
                //$content = '<h2>Feed '.$url.'</h2>'.$content;
                return $content;
            }
            catch(Exception $e)
            {
                return str_replace("{feedname}", $url, $this->sp_unescapeString($configOptions['feedNotAvailableHTMLCode']));
                //return 'Error parsing content from '.$url.':<br>'.$e->getMessage();
            }
        }
    
    
    
        /* Get the list of link back images 
         *  Not currently used.                      
         * @package WordPress
         * @since version 2.8.4
         * @return   string     an array of file paths to the link back images.
         */
        function sp_getLinkbackImages()
        {
            $imagesArray = array();
            $dir = dirname(__FILE__) . $this->linkBackImagesDir;
            if ($handle = opendir($dir)) 
            {
                while (false !== ($file = readdir($handle))) 
                {
                    if($file != '.' && $file != '..')
                    {
                        $file = "http://".$_SERVER['HTTP_HOST'].dirname(__FILE__)  . $this->linkBackImagesDir .'/'. $file;                        
                        array_push($imagesArray, $file);
                    }
                }
                closedir($handle);
            }
            return $imagesArray;
        }
        
        /* Determine if the given string ends with the gven end string.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $str the string to check
         * @param    string    $end the string to see if $str ends with 
         * @return   boolean true if the tring ends wit the given end, else false.
         */
        function sp_endsWith($str, $end)
        {
            $len = strlen($end);
            $strEnd = substr($str, strlen($str) - $len);
            return $strEnd == $end;
        }
    
        /* Add the given content to a new wordpress post.
         * @package WordPress
         * @since version 2.8.4
         * @param    string    $content the content to add as a new post
         */
        function sp_postContent($content)
        {
            include_once('wp-admin/includes/taxonomy.php');
            $dynamicNewsCategoryId = wp_create_category('Latest news');

            $postObj = array();
            $postObj['post_title'] = 'News';
            $postObj['post_content'] = $content;
            $postObj['post_status'] = 'publish';
            $postObj['post_author'] = 1;
            $postObj['post_category'] = $dynamicNewsCategoryId;

            wp_insert_post($postObj);
        }

        /* Verify the given nonce or kill the script.  this helps prevent nefarious evil doers from making direct URL calls into the plugin configuration page
         * @package WordPress
         * @since version 2.8.4
         */
        function verifyNonceOrDie($nonceName)
        {
            if(!isset($_POST[$nonceName]))
            {
                die("Invalid credentials");
            } 
            else
            {
                if(!wp_verify_nonce($_POST[$nonceName],$nonceName)) 
                {
                    die("Invalid credentials");
                }
            }
        }
        
        function sp_getSPNews()
        {
            //$url = "http://henryranch.net/feed/";
            $url = "http://syndicatepress.henryranch.net/feed/";
            //return $this->sp_getFormattedRssContent($url);
            
            include_once "php/TinyFeedParser.php";
            
            try
            {
                $cachedInputFeedFile = $this->sp_cacheIncomingRssFeed($url);
            }
            catch(Exception $e)
            {
                return 'Unable to download content from '.$url;
            }
            try
            {
                if(!file_exists($cachedInputFeedFile) || filesize($cachedInputFeedFile) == 0)
                {
                    return "Failed to get content from '".$url."'";
                }
                $configOptions = $this->sp_getConfigOptions();
                $parser = new TinyFeedParser($cachedInputFeedFile);
                $parser->showContentOnlyInLinkTitle = 'false';
                $parser->maxNumArticlesToDisplay = 5;
                $parser->exclusiveKeywordList = "";
                $parser->inclusiveKeywordList = 'Syndicate Press';
                $parser->maxDescriptionLength = -1;
                $parser->showFeedChannelTitle = 'false';
                $parser->useCustomFeednameAsChannelTitle = 'false';
                $parser->showArticlePublishTimestamp = 'true';
                $parser->feedTitleHTMLCodePre = "<h2>";
                $parser->feedTitleHTMLCodePost = "</h2>";
                $parser->articleTitleHTMLCodePre = "<h4>";
                $parser->articleTitleHTMLCodePost = "</h4>";
                $parser->articleBodyHTMLCodePre = "<div>";
                $parser->articleBodyHTMLCodePost = "</div>";
                $parser->showFeedMetrics = 'false';
                $parser->customFeedName == "";
                $parser->maxHeadlineLength = 128;
                $parser->allowImagesInDescription = 'false';
                $parser->allowMarkupInDescription = 'true';
                $parser->showArticlesInLightbox = false;
                $parser->parseFeed($cachedInputFeedFile);
                $content = $parser->getHtml();
                return $content;
            }
            catch(Exception $e)
            {
                
                return 'Error parsing content from '.$url.':<br>'.$e->getMessage();
            }
        }
        
    /* Display the plugin admin page
         * @package WordPress
         * @since version 2.8.4
         */
    function sp_printAdminPage() 
        {
      $configOptions = $this->sp_getConfigOptions();
                        
      if (isset($_POST['update_SyndicatePressPluginSettings'])) 
            { 
                //nonce security check...
                $this->verifyNonceOrDie('synPress-update_settings');
            

        if (isset($_POST['syndicatePressCustomCacheDirectory'])) {
          $configOptions['customCacheDirectory'] = trim(mysql_real_escape_string($_POST['syndicatePressCustomCacheDirectory']));
        }             
        if (isset($_POST['syndicatePressEnableFeedCache'])) {
          $configOptions['enableFeedCache'] = $_POST['syndicatePressEnableFeedCache'];
        }  
        if (isset($_POST['syndicatePressEnableOutputCache'])) {
          $configOptions['enableOutputCache'] = $_POST['syndicatePressEnableOutputCache'];
        }  
        if (isset($_POST['syndicatePressCacheTimeoutSeconds'])) {
          $configOptions['cacheTimeoutSeconds'] = $_POST['syndicatePressCacheTimeoutSeconds'];
        }  
        if (isset($_POST['syndicatePressLimitFeedItemsToDisplay'])) {
          $configOptions['limitFeedItemsToDisplay'] = $_POST['syndicatePressLimitFeedItemsToDisplay'];
        }  
        if (isset($_POST['syndicatePressHideArticlesAfterArticleNumber'])) {
          $configOptions['hideArticlesAfterArticleNumber'] = $_POST['syndicatePressHideArticlesAfterArticleNumber'];
        }  
        if (isset($_POST['syndicatePressLimitFeedDescriptionCharsToDisplay'])) {
          $configOptions['limitFeedDescriptionCharsToDisplay'] = $_POST['syndicatePressLimitFeedDescriptionCharsToDisplay'];
        }           
        if (isset($_POST['syndicatePressMaxHeadlineLength'])) {
          $configOptions['maxHeadlineLength'] = $_POST['syndicatePressMaxHeadlineLength'];
        }             
        if (isset($_POST['syndicatePressTimestampFormat'])) {
          $configOptions['timestampFormat'] = trim(mysql_real_escape_string($_POST['syndicatePressTimestampFormat']));
        }             
        if (isset($_POST['syndicatePressUseDownloadClient'])) {
          $configOptions['useDownloadClient'] = $_POST['syndicatePressUseDownloadClient'];
        }                         
        if (isset($_POST['syndicatePressshowArticlePublishTimestamp'])) {
          $configOptions['showArticlePublishTimestamp'] = $_POST['syndicatePressshowArticlePublishTimestamp'];
        }
        if (isset($_POST['syndicatePressEnable'])) {
          $configOptions['enable'] = $_POST['syndicatePressEnable'];
        }  
        if (isset($_POST['syndicatePressShowContentOnlyInLinkTitle'])) {
          $configOptions['showContentOnlyInLinkTitle'] = $_POST['syndicatePressShowContentOnlyInLinkTitle'];
        }    
        if (isset($_POST['syndicatePressAddNoFollowTag'])) {
          $configOptions['addNoFollowTag'] = $_POST['syndicatePressAddNoFollowTag'];
        }
        if (isset($_POST['syndicatePressShowSyndicatePressLinkback'])) {
          $configOptions['showSyndicatePressLinkback'] = $_POST['syndicatePressShowSyndicatePressLinkback'];
        }  
        if (isset($_POST['syndicatePressShowProcessingMetrics'])) {
          $configOptions['showProcessingMetrics'] = $_POST['syndicatePressShowProcessingMetrics'];
        }  
        if (isset($_POST['syndicatePressShowFeedChannelTitle'])) {
          $configOptions['showFeedChannelTitle'] = $_POST['syndicatePressShowFeedChannelTitle'];
        }  
        if (isset($_POST['syndicatePressUseCustomFeednameAsChannelTitle'])) {
          $configOptions['useCustomFeednameAsChannelTitle'] = $_POST['syndicatePressUseCustomFeednameAsChannelTitle'];
        }  
        if (isset($_POST['syndicatePressDisplayImages'])) {
          $configOptions['displayImages'] = $_POST['syndicatePressDisplayImages'];
        }    
        if (isset($_POST['syndicatePressStripCdataTags'])) {
          $configOptions['stripCDataTags'] = $_POST['syndicatePressStripCdataTags'];
          $this->sp_clearCache();
        }           
        if (isset($_POST['syndicatePressOpenArticleInLightbox'])) {
          $configOptions['openArticleInLightbox'] = $_POST['syndicatePressOpenArticleInLightbox'];
        }
        if (isset($_POST['syndicatePressAllowMarkup'])) {
          $configOptions['allowMarkupInDescription'] = $_POST['syndicatePressAllowMarkup'];
        }
        if (isset($_POST['syndicatePressFeedUrlList'])) {
          //$configOptions['feedUrlList'] = apply_filters('feedUrlList_save_pre', $_POST['syndicatePressFeedUrlList']);
          $configOptions['feedUrlList'] = htmlentities($_POST['syndicatePressFeedUrlList']);
          //replace any occurrances of feed:// with http://
          $configOptions['feedUrlList'] = str_replace("feed://", "http://", $configOptions['feedUrlList']);
          $configOptions['feedUrlList'] = trim($configOptions['feedUrlList']);
        }
        if (isset($_POST['syndicatePressExclusiveKeywordFilter'])) {
          $configOptions['exclusiveKeywordFilter'] = apply_filters('exclusiveKeywordFilter_save_pre', $_POST['syndicatePressExclusiveKeywordFilter']);
        }
        if (isset($_POST['syndicatePressInclusiveKeywordFilter'])) {
          $configOptions['inclusiveKeywordFilter'] = apply_filters('inclusiveKeywordFilter_save_pre', $_POST['syndicatePressInclusiveKeywordFilter']);
        }
        if (isset($_POST['syndicatePressFeedTitleHTMLCodePre'])) {
          $configOptions['feedTitleHTMLCodePre'] = $this->sp_escapeString(apply_filters('feedTitleHTMLCodePre_save_pre', $_POST['syndicatePressFeedTitleHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressFeedTitleHTMLCodePost'])) {
          $configOptions['feedTitleHTMLCodePost'] = $this->sp_escapeString(apply_filters('feedTitleHTMLCodePost_save_pre', $_POST['syndicatePressFeedTitleHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleTitleHTMLCodePre'])) {
          $configOptions['articleTitleHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleTitleHTMLCodePre_save_pre', $_POST['syndicatePressArticleTitleHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleTitleHTMLCodePost'])) {
          $configOptions['articleTitleHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleTitleHTMLCodePost_save_pre', $_POST['syndicatePressArticleTitleHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleAuthorHTMLCodePre'])) {
          $configOptions['articleAuthorHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleAuthorHTMLCodePre_save_pre', $_POST['syndicatePressArticleAuthorHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleAuthorHTMLCodePost'])) {
          $configOptions['articleAuthorHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleAuthorHTMLCodePost_save_pre', $_POST['syndicatePressArticleAuthorHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleCopyrightHTMLCodePre'])) {
          $configOptions['articleCopyrightHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleCopyrightHTMLCodePre_save_pre', $_POST['syndicatePressArticleCopyrightHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleCopyrightHTMLCodePost'])) {
          $configOptions['articleCopyrightHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleCopyrightHTMLCodePost_save_pre', $_POST['syndicatePressArticleCopyrightHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticlePriceHTMLCodePre'])) {
          $configOptions['articlePriceHTMLCodePre'] = $this->sp_escapeString(apply_filters('articlePriceHTMLCodePre_save_pre', $_POST['syndicatePressArticlePriceHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticlePriceHTMLCodePost'])) {
          $configOptions['articlePriceHTMLCodePost'] = $this->sp_escapeString(apply_filters('articlePriceHTMLCodePost_save_pre', $_POST['syndicatePressArticlePriceHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleImageHTMLCodePre'])) {
          $configOptions['articleImageHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleImageHTMLCodePre_save_pre', $_POST['syndicatePressArticleImageHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleImageHTMLCodePost'])) {
          $configOptions['articleImageHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleImageHTMLCodePost_save_pre', $_POST['syndicatePressArticleImageHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleTimestampHTMLCodePre'])) {
          $configOptions['articleTimestampHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleTimestampHTMLCodePre_save_pre', $_POST['syndicatePressArticleTimestampHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleTimestampHTMLCodePost'])) {
          $configOptions['articleTimestampHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleTimestampHTMLCodePost_save_pre', $_POST['syndicatePressArticleTimestampHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressArticleBodyHTMLCodePre'])) {
          $configOptions['articleBodyHTMLCodePre'] = $this->sp_escapeString(apply_filters('articleBodyHTMLCodePre_save_pre', $_POST['syndicatePressArticleBodyHTMLCodePre']));
        }
        if (isset($_POST['syndicatePressArticleBodyHTMLCodePost'])) {
          $configOptions['articleBodyHTMLCodePost'] = $this->sp_escapeString(apply_filters('articleBodyHTMLCodePost_save_pre', $_POST['syndicatePressArticleBodyHTMLCodePost']));
        }
        if (isset($_POST['syndicatePressFeedSeparationHTMLCode'])) {
          $configOptions['feedSeparationHTMLCode'] = $this->sp_escapeString(apply_filters('feedSeparationHTMLCode_save_pre', $_POST['syndicatePressFeedSeparationHTMLCode']));
        }
        if (isset($_POST['syndicatePressFeedNotAvailableHTMLCode'])) {
          $configOptions['feedNotAvailableHTMLCode'] = $this->sp_escapeString(apply_filters('feedNotAvailableHTMLCode_save_pre', $_POST['syndicatePressFeedNotAvailableHTMLCode']));
        }
        if (isset($_POST['syndicatePressLightboxCSSTextArea'])) {
          $this->sp_writeFile(dirname(__FILE__).'/css/TinyLightbox.css', $this->sp_escapeString(apply_filters('lightboxCSSCode_save_pre', $_POST['syndicatePressLightboxCSSTextArea'])));
        }
        if (isset($_POST['syndicatePressLightboxHTMLTextArea'])) {
          $configOptions['lightboxHTMLCode'] = $this->sp_escapeString(apply_filters('lightboxHTMLCode_save_pre', $_POST['syndicatePressLightboxHTMLTextArea']));
        }
        
                
        update_option($this->adminOptionsName, $configOptions);
        $this->sp_clearFormattedOutputCache();
?>
            <div class="updated"><p><strong><?php _e("Settings Updated.", "SyndicatePressPlugin");?></strong></p></div>              
<?php   
        } 
        else if (isset($_POST['synPress-clearInputFeedCacheSubmit'])) 
        {
            //nonce security check...
            $this->verifyNonceOrDie('synPress-clearInputFeedCache');
            $this->sp_clearIncomingFeedCache();
?>
            <div class="updated"><p><strong><?php _e("Input feed cache cleared.", "SyndicatePressPlugin");?></strong></p></div>    
<?php            
        }
        else if (isset($_POST['synPress-clearOutputCacheSubmit'])) 
        {
            //nonce security check...
            $this->verifyNonceOrDie('synPress-clearOutputCache');
            $this->sp_clearFormattedOutputCache();
?>
            <div class="updated"><p><strong><?php _e("Output cache cleared.", "SyndicatePressPlugin");?></strong></p></div>    
<?php
        }
        if($configOptions['enable'] != 'true')
        {
?>
            <div id="notice" class="error"><p><strong><?php _e("Syndicate Press output is currently disabled.", "SyndicatePressPlugin");?></strong></p></div>              
<?php
        }
?>        
        
        
       
<div class=wrap>
<h2><a href="<?php echo $this->homepageURL; ?>" target=_blank title="Click for the Syndicate Press homepage...">Syndicate Press</a></h2>
<em>Version <?php print $this->version;?></em><br>

<?php
 $permMessage = $this->sp_checkCachePermissions();
 if($permMessage)
 {
    echo '<div class="updated"><p><strong>';
    _e($permMessage, "SyndicatePressPlugin");
    echo '</strong></p></div>';
 }
?>

<table>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr>
<td>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="WNRCV8LST3ALA">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</td>
<td>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<input name="synPress-clearInputFeedCache" type="hidden" value="<?php echo wp_create_nonce('synPress-clearInputFeedCache'); ?>" />
<input type="submit" name="synPress-clearInputFeedCacheSubmit" value="<?php _e('Clear input feed cache', 'SyndicatePressPlugin') ?>" />
</form>
</td>
<td>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<input name="synPress-clearOutputCache" type="hidden" value="<?php echo wp_create_nonce('synPress-clearOutputCache'); ?>" />
<input type="submit" name="synPress-clearOutputCacheSubmit" value="<?php _e('Clear output cache', 'SyndicatePressPlugin') ?>" />
</form>
</td>
<td>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<input type="submit" name="update_SyndicatePressPluginSettings" value="<?php _e('Update Settings', 'SyndicatePressPlugin') ?>" />
<input name="synPress-update_settings" type="hidden" value="<?php echo wp_create_nonce('synPress-update_settings'); ?>" />
<!--  Must leave this form element open, because it is the form across all the tabs-->
</td>
</tr>
</table>



<div id="formDiv">

<div class="tabber">
     <div class="tabbertab">
        <h2>News</h2>
        <div style="padding-left: 20px;">
        <p>
        <?php print $this->sp_getSPNews(); ?>
        </p>
        </div>
     </div>
     <div class="tabbertab">
        <h2>Output</h2>
        <b><u>Output aggregated feed content?</u></b>
        <div style="padding-left: 20px;">
        <label for="syndicatePressEnable_yes"><input type="radio" id="syndicatePressEnable_yes" name="syndicatePressEnable" value="true" <?php if ($configOptions['enable'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - show content</label><br>
        <label for="syndicatePressEnable_no"><input type="radio" id="syndicatePressEnable_no" name="syndicatePressEnable" value="false" <?php if ($configOptions['enable'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - do not show content</label>
        </div>
     </div>
     <div class="tabbertab">
        <h2>RSS Feeds</h2>
        <b>IMPORTANT: As the site admin, you are fully responsible for adhering to all of the Copyright and Terms of Use restrictions for each feed that you syndicate.  You should check with the feed publisher to verify that you can legally syndicate their feed on your website.</b><br><br>
        <b><u>List each RSS feed on a single line</u></b>
        <div style="padding-left: 20px;">
        Enter a feed URL on each line<br>
        Feeds without names: Simply enter 1 feed URL per line.<br>
        Feeds with custom names (can be shown as the feed title).<br>
        <div style="padding-left: 20px;">
        Enter 1 name/feed pair per line.  Separate the name and URL by a pipe character: |.<br>
        </div>
        You may mix and match feeds with names or without names.<br>
        <textarea name="syndicatePressFeedUrlList" style="width: 95%; height: 200px;"><?php _e(apply_filters('format_to_edit',$configOptions['feedUrlList']), 'SyndicatePressPlugin') ?></textarea>
        </div>
     </div>
     <div class="tabbertab">
        <h2>Filters</h2>
        <b><u>Inclusive keyword filtering</u></b>
        <div style="padding-left: 20px;">
        Only allow feed items that contain any of the following words.<br>
        If a feed item contains one or more of the words in this list, the item <em>will</em> be displayed.<br>
        <em>Inclusive filtering will be applied before the exclusive filters.</em><br>
        Enter a comma separated list of keywords:<br>
        <textarea name="syndicatePressInclusiveKeywordFilter" style="width: 95%; height: 50px;"><?php _e(apply_filters('format_to_edit',$configOptions['inclusiveKeywordFilter']), 'SyndicatePressPlugin') ?></textarea>
        </div>
        <br>&nbsp;<br>
        <b><u>Exclusive keyword filtering</u></b>
        <div style="padding-left: 20px;">
        Filter <em>out</em> feed items that contain any of the following words.<br>
        If a feed item contains one or more of the words in this list, the item will <em>not</em> be displayed.<br>
        <em>Exclusive filtering will be applied after the inclusive filters.</em><br>
        Enter a comma separated list of keywords:<br>
        <textarea name="syndicatePressExclusiveKeywordFilter" style="width: 95%; height: 50px;"><?php _e(apply_filters('format_to_edit',$configOptions['exclusiveKeywordFilter']), 'SyndicatePressPlugin') ?></textarea>
        </div>
     </div>
     <div class="tabbertab">
        <h2>Cache</h2>
        <b><u>Input feed caching</u></b>
        <div style="padding-left: 20px;">
        <label for="syndicatePressEnableFeedCache_yes"><input type="radio" id="syndicatePressEnableFeedCache_yes" name="syndicatePressEnableFeedCache" value="true" <?php if ($configOptions['enableFeedCache'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - Cache the incoming feeds.</label><br>
        <div style="padding-left: 20px;">
        Cached feed expires after <input name="syndicatePressCacheTimeoutSeconds" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['cacheTimeoutSeconds']), 'SyndicatePressPlugin') ?>"> seconds. (1 hour = 3600 seconds)<br>
        </div>
        <label for="syndicatePressEnableFeedCache_no"><input type="radio" id="syndicatePressEnableFeedCache_no" name="syndicatePressEnableFeedCache" value="false" <?php if ($configOptions['enableFeedCache'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - Request the feed for every view of the Syndicate Press page.  <em>This is NOT recommended and may result in your server IP being banned by the publisher!</em></label><br>
        Feed download mode:<br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressUseDownloadClient_yes"><input type="radio" id="syndicatePressUseDownloadClient_yes" name="syndicatePressUseDownloadClient" value="true" <?php if ($configOptions['useDownloadClient'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Use download client.  <em>Recommended when the web host disables file_get_contents() functionality.</em></label><br>
        <label for="syndicatePressUseDownloadClient_no"><input type="radio" id="syndicatePressUseDownloadClient_no" name="syndicatePressUseDownloadClient" value="false" <?php if ($configOptions['useDownloadClient'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Use direct download.  <em>May not work on all web hosts.</em></label><br>
        </div>
        </div>
        <br>&nbsp;<br>
        <b><u>Formatted output caching</u></b>
        <div style="padding-left: 20px;">
        <label for="syndicatePressEnableOutputCache_yes"><input type="radio" id="syndicatePressEnableOutputCache_yes" name="syndicatePressEnableOutputCache" value="true" <?php if ($configOptions['enableOutputCache'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - Cache the formatted output.</label><br>
        <label for="syndicatePressEnableOutputCache_no"><input type="radio" id="syndicatePressEnableOutputCache_no" name="syndicatePressEnableOutputCache" value="false" <?php if ($configOptions['enableOutputCache'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - Parse and format the feed every time the page/post is requested.  <em>This is NOT recommended!</em></label>
        </div>
        <br>&nbsp;<br>
        <b><u>Cache directory</u></b>
        <div style="padding-left: 20px;">
        <b>Current cache root directory:</b><br>
        <div style="padding-left: 20px;">
        <?php print $this->sp_getRootCacheDir(); ?><br>Permissions: <?php print $this->sp_getFilePermissions($this->sp_getRootCacheDir())?><br>
        </div>
        <b>Current input cache directory:</b><br>
        <div style="padding-left: 20px;">
        <?php print $this->sp_getInputFeedCacheDir(); ?><br>Permissions: <?php print $this->sp_getFilePermissions($this->sp_getInputFeedCacheDir())?><br>
        <?php print $this->sp_getNumInputCacheFiles(); ?> cached files.<br>
        </div>
        <b>Current output cache directory:</b><br>
        <div style="padding-left: 20px;">
        <?php print $this->sp_getOutputFeedCacheDir(); ?><br>Permissions: <?php print $this->sp_getFilePermissions($this->sp_getOutputFeedCacheDir())?><br>
        <?php print $this->sp_getNumOutputCacheFiles(); ?> cached files.<br>
        </div>
        <b>Syndicate Press default cache directory:</b><br>
        <div style="padding-left: 20px;">
        <?php print $this->sp_getCacheDefaultRootDir(); ?><br>Permissions: <?php print $this->sp_getFilePermissions($this->sp_getCacheDefaultRootDir())?><br>
        </div>
        <b>Custom cache root directory</b> (Leave blank to use the default plugin cache directory):<br>
        <div style="padding-left: 20px;">
        <input name="syndicatePressCustomCacheDirectory" size="100" value="<?php _e(apply_filters('format_to_edit',$configOptions['customCacheDirectory']), 'SyndicatePressPlugin') ?>"><br>
        <i>If you set a custom cache directory, make sure that your server has read and write access to it.</i>
        </div>
        </div>
     </div>
     <div class="tabbertab">
        <h2>Display Settings</h2>
        <div style="padding-left: 20px;">
        <b><u>Limit the number of articles</u></b> in a feed to <input name="syndicatePressLimitFeedItemsToDisplay" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['limitFeedItemsToDisplay']), 'SyndicatePressPlugin') ?>"> items. Depending on the publishers settings, this will typically limit the displayed feeds to the most recent feeds.  For example, limit feed articles to the 4 most recent items.  Set this value to -1 to display all items in feed.<br>&nbsp;<br>
        <b><u>Hide articles after article number</u></b>: <input name="syndicatePressHideArticlesAfterArticleNumber" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['hideArticlesAfterArticleNumber']), 'SyndicatePressPlugin') ?>">. For feeds with articles greater than this number, hide those articles and place a 'show more articles' link for the user to click that will show the articles. Set this value to -1 to display all items in feed.<br>&nbsp;<br>
        <b><u>Limit article to</u></b> <input name="syndicatePressLimitFeedDescriptionCharsToDisplay" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['limitFeedDescriptionCharsToDisplay']), 'SyndicatePressPlugin') ?>"> characters. (-1 to display complete article description)<br>&nbsp;<br>
        <b><u>Limit article headline</u></b> to <input name="syndicatePressMaxHeadlineLength" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['maxHeadlineLength']), 'SyndicatePressPlugin') ?>"> characters. (-1 to display complete article headline)<br>&nbsp;<br>
        <b><u>Show item description:</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressShowContentOnlyInLinkTitle_yes"><input type="radio" id="syndicatePressShowContentOnlyInLinkTitle_yes" name="syndicatePressShowContentOnlyInLinkTitle" value="true" <?php if ($configOptions['showContentOnlyInLinkTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> only when the viewer hovers over the item link.</label><br>
        <label for="syndicatePressShowContentOnlyInLinkTitle_no"><input type="radio" id="syndicatePressShowContentOnlyInLinkTitle_no" name="syndicatePressShowContentOnlyInLinkTitle" value="false" <?php if ($configOptions['showContentOnlyInLinkTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> below the item link.</label><br>
        </div><br>&nbsp;<br>
        <b><u>Item publication timestamp:</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressshowArticlePublishTimestamp_yes"><input type="radio" id="syndicatePressshowArticlePublishTimestamp_yes" name="syndicatePressshowArticlePublishTimestamp" value="true" <?php if ($configOptions['showArticlePublishTimestamp'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show timestamp.</label><br>
      <div style="padding-left: 20px;">
      Timestamp Format: <input name="syndicatePressTimestampFormat" size="50" value="<?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['timestampFormat'])), 'SyndicatePressPlugin') ?>"> &nbsp;&nbsp;Default: <?php $tfp = new TinyFeedParser(); echo '<b><i>'.$tfp->getDefaultTimestampFormatString().'</b></i>'; ?> <br>Example: The default format string create a timestamp like this: <i>Friday June 29th, 2012 04:12:01 AM</i> <br>For help with custom timestamp formatting, see <a href="http://php.net/manual/en/function.date.php" target="_blank">this documentation</a>.<br>
      </div>
        <label for="syndicatePressshowArticlePublishTimestamp_no"><input type="radio" id="syndicatePressshowArticlePublishTimestamp_no" name="syndicatePressshowArticlePublishTimestamp" value="false" <?php if ($configOptions['showArticlePublishTimestamp'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Hide timestamp.</label><br>
        </div><br>&nbsp;<br>
        <b><u>Display HTML formatting in article:</u></b><br>
        <div style="padding-left: 20px;">
        <em>NOTE: Displaying HTML content in the articles will disable article length limitation</em><br>
        <label for="syndicatePressAllowMarkup_yes"><input type="radio" id="syndicatePressAllowMarkup_yes" name="syndicatePressAllowMarkup" value="true" <?php if ($configOptions['allowMarkupInDescription'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show HTML formatting.</label><br>
        <label for="syndicatePressAllowMarkup_no"><input type="radio" id="syndicatePressAllowMarkup_no" name="syndicatePressAllowMarkup" value="false" <?php if ($configOptions['allowMarkupInDescription'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Strip HTML formatting, leaving only the article text.</label><br>
        </div><br>&nbsp;<br>
        <b><u>Display images in article:</u></b><br>
        <div style="padding-left: 20px;">
        <em>NOTE: If HTML formatting is stripped (see above setting), images will NOT be shown.</em><br>
        <label for="syndicatePressDisplayImages_yes"><input type="radio" id="syndicatePressDisplayImages_yes" name="syndicatePressDisplayImages" value="true" <?php if ($configOptions['displayImages'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show images.</label><br>
        <label for="syndicatePressDisplayImages_no"><input type="radio" id="syndicatePressDisplayImages_no" name="syndicatePressDisplayImages" value="false" <?php if ($configOptions['displayImages'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Strip images.</label><br>
        </div><br>
        <div style="padding-left: 20px;">
        <u>Strip CDATA XML tags:</u><br>
        <div style="padding-left: 20px;">
        <em>If images are not being shown, even though DisplayImages=Yes and DisplayHTML=Yes, set this to Yes.  It may be that the feed publisher is placing their content within CDATA tags.  Stripping the CDATA tag delimiters might allow images through.  If not, check with the feed publisher to verify that images are being published.</em><br>
        <label for="syndicatePressStripCdataTags_yes"><input type="radio" id="syndicatePressStripCdataTags_yes" name="syndicatePressStripCdataTags" value="true" <?php if ($configOptions['stripCDataTags'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Strip CDATA tags.</label><br>
        <label for="syndicatePressStripCdataTags_no"><input type="radio" id="syndicatePressStripCdataTags_no" name="syndicatePressStripCdataTags" value="false" <?php if ($configOptions['stripCDataTags'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Allow CDATA tags.</label><br>
        </div></div><br>&nbsp;<br>
        <b><u>Syndicate Press link:</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressShowSyndicatePressLinkback_yes"><input type="radio" id="syndicatePressShowSyndicatePressLinkback_yes" name="syndicatePressShowSyndicatePressLinkback" value="true" <?php if ($configOptions['showSyndicatePressLinkback'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show 'Powered by <a href="<?php echo $this->homepageURL; ?>" target=_blank>Syndicate Press</a>' at the end of the aggregated feed content.</label><br>
        <div style="padding-left: 20px;">
        <em>If you have not donated to Syndicate Press, a link back to the Syndicate Press site is requested.<br>
        You may use this automated linkback, or you may place the link in the footer of your site.</em><br>
        </div>
        <label for="syndicatePressShowSyndicatePressLinkback_no"><input type="radio" id="syndicatePressShowSyndicatePressLinkback_no" name="syndicatePressShowSyndicatePressLinkback" value="false" <?php if ($configOptions['showSyndicatePressLinkback'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show the Syndicate Press link.</label><br>
        </div><br>&nbsp;<br>
        <b><u>Processing and feed metrics:</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressShowProcessingMetrics_yes"><input type="radio" id="syndicatePressShowProcessingMetrics_yes" name="syndicatePressShowProcessingMetrics" value="true" <?php if ($configOptions['showProcessingMetrics'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show.</label><br>
        <label for="syndicatePressShowProcessingMetrics_no"><input type="radio" id="syndicatePressShowProcessingMetrics_no" name="syndicatePressShowProcessingMetrics" value="false" <?php if ($configOptions['showProcessingMetrics'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show.</label><br>
        </div><br>&nbsp;<br>
        <b><u>Feed name (title):</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressUseCustomFeednameAsChannelTitle_yes"><input type="radio" id="syndicatePressUseCustomFeednameAsChannelTitle_yes" name="syndicatePressUseCustomFeednameAsChannelTitle" value="true" <?php if ($configOptions['useCustomFeednameAsChannelTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Use custom feedname as feed title.</label><br>
        <label for="syndicatePressUseCustomFeednameAsChannelTitle_no"><input type="radio" id="syndicatePressUseCustomFeednameAsChannelTitle_no" name="syndicatePressUseCustomFeednameAsChannelTitle" value="false" <?php if ($configOptions['useCustomFeednameAsChannelTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Use publisher's title (including link and image if available).</label><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressShowFeedChannelTitle_yes"><input type="radio" id="syndicatePressShowFeedChannelTitle_yes" name="syndicatePressShowFeedChannelTitle" value="true" <?php if ($configOptions['showFeedChannelTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show.</label><br>
        <label for="syndicatePressShowFeedChannelTitle_no"><input type="radio" id="syndicatePressShowFeedChannelTitle_no" name="syndicatePressShowFeedChannelTitle" value="false" <?php if ($configOptions['showFeedChannelTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show.</label><br>
        </div>
        </div>
     </div>
     </div>     
     <div class="tabbertab">
        <h2>Custom Formatting</h2>
        <b><u>Feed title formatting:</u></b><br>
        <div style="padding-left: 20px;">
        <em>You can use html tags (including the css style parameter and the script parameter) to format the feed title... i.e. &lt;h2&gt;title&lt;/h2&gt;</em><br>
        <textarea name="syndicatePressFeedTitleHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedTitleHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>title<textarea name="syndicatePressFeedTitleHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedTitleHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        </div><br>&nbsp;<br>
        <b><u>Article formatting:</u></b><br>
        <div style="padding-left: 20px;">
        <em>You can use html tags (including the css style parameter and the script parameter) to format the article title, author, timestamp, copyright, price, image and body... i.e. &lt;p&gt;body text&lt;/p&gt;</em>
        <br>The following elements are defined by the feed structure XML and do not apply to content within the body of the feed.
        <br>Some feeds may not have all of the following elements.  If a feed does not have one of the elements, nothing will be shown for that element.<br>
        <textarea name="syndicatePressArticleTitleHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleTitleHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>title<textarea name="syndicatePressArticleTitleHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleTitleHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticleAuthorHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleAuthorHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>author<textarea name="syndicatePressArticleAuthorHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleAuthorHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticleCopyrightHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleCopyrightHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>copyright<textarea name="syndicatePressArticleCopyrightHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleCopyrightHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticlePriceHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articlePriceHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>price<textarea name="syndicatePressArticlePriceHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articlePriceHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticleImageHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleImageHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>image<textarea name="syndicatePressArticleImageHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleImageHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticleTimestampHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleTimestampHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>timestamp<textarea name="syndicatePressArticleTimestampHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleTimestampHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        <textarea name="syndicatePressArticleBodyHTMLCodePre" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleBodyHTMLCodePre']), true), 'SyndicatePressPlugin') ?></textarea>body (content)<textarea name="syndicatePressArticleBodyHTMLCodePost" style="width: 40%; height: 50px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['articleBodyHTMLCodePost'])), 'SyndicatePressPlugin') ?></textarea><br>
        </div><br>&nbsp;<br>
        <b><u>Custom feed separation code:</u></b><br>
        <div style="padding-left: 20px;">
        <em>You can insert any html content between feeds (including advertising code)<br>
        <div style="padding-left: 20px;">
        i.e. To insert a horizontal line: &lt;hr&gt;</em><br>
        </div>
        <textarea name="syndicatePressFeedSeparationHTMLCode" style="width: 95%; height: 100px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedSeparationHTMLCode']), true), 'SyndicatePressPlugin') ?></textarea>
        </div><br>&nbsp;<br>
        <b><u>Custom content to show when a feed is unavailable:</u></b><br>
        <div style="padding-left: 20px;">
        <em>You can insert custom html content when a feed is not available.<br>
        To include the name of the unavailable feed, use {feedname} in the code below and it will be replaced with the name of the feed.<br>
        To show nothing when a feed is not available, simply delete all of the content from this field.</em>
        <textarea name="syndicatePressFeedNotAvailableHTMLCode" style="width: 95%; height: 100px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedNotAvailableHTMLCode']), true), 'SyndicatePressPlugin') ?></textarea>
        </div>
     </div>
   <div class="tabbertab">
        <h2>SEO</h2>
        <b><u>No-follow directive:</u></b><br>
        <div style="padding-left: 20px;">
        <label for="syndicatePressAddNoFollowTag_yes"><input type="radio" id="syndicatePressAddNoFollowTag_yes" name="syndicatePressAddNoFollowTag" value="true" <?php if ($configOptions['addNoFollowTag'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Add no-follow tag to article URL's.</label><br>
        <label for="syndicatePressAddNoFollowTag_no"><input type="radio" id="syndicatePressAddNoFollowTag_no" name="syndicatePressAddNoFollowTag" value="false" <?php if ($configOptions['addNoFollowTag'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not add the no-follow tag to URL's.</label><br>
        </div>
     </div>
     <div class="tabbertab">
        <h2>Lightbox</h2>
        <b><u>You may configure Syndicate Press to show article sources in a popup lightbox.</u></b><br>
        <div style="padding-left: 20px;">
        This feature was sponsored by <a href="http://www.collectorsbluebook.com/" target="_blank">CollectorsBlueBook.com</a>.<br><br>
        </div>
        <b><u>Open article in lightbox instead of new window:</u></b><br>
        <p>
        <div style="padding-left: 20px;">
        <label for="syndicatePressOpenArticleInLightbox_yes"><input type="radio" id="syndicatePressOpenArticleInLightbox_yes" name="syndicatePressOpenArticleInLightbox" value="true" <?php if ($configOptions['openArticleInLightbox'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Open the article in a popup lightbox.</label><br>
        <label for="syndicatePressOpenArticleInLightbox_no"><input type="radio" id="syndicatePressOpenArticleInLightbox_no" name="syndicatePressOpenArticleInLightbox" value="false" <?php if ($configOptions['openArticleInLightbox'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Open the article in a new tab/window.</label><br>
        </div>
        </p>
        <p>
        <div style="padding-left: 20px;">
        Edit the CSS of the Lightbox here (<i>Class names must match the CSS classes referenced in the HTML code </i>):<br>
        <textarea name="syndicatePressLightboxCSSTextArea"  style="width: 95%; height: 300px;"><?php echo $this->sp_readFile(dirname(__FILE__).'/css/TinyLightbox.css'); ?></textarea>
        </div>
        <div style="padding-left: 20px;">
        <br>Edit the HTML of the Lightbox here (<i>CSS style class names must match the CSS classes defined in the CSS code </i>):<br>
        <textarea name="syndicatePressLightboxHTMLTextArea"  style="width: 95%; height: 150px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['lightboxHTMLCode']), true), 'SyndicatePressPlugin') ?></textarea>
        </div>        
        </p>
     </div>
     
</form>
     <div class="tabbertab">
        <h2>Help</h2>
        <b><u>Inserting feed content into a Wordpress page or post...</u></b>
        <p>
        To insert feed contents into a Page, Post or Text Widget, use the following syntax:<br>
        <div style="padding-left: 20px;">
        [sp# feedList=all] - insert all of the feeds in the feed list<br>
    <i>In the following examples, <b>feedname</b> will match the name of a feed, or any word within the feed url</i><br>
        [sp# feedList=feedname] - insert only the feed with the given name<br>
        [sp# feedList=feedname limitArticles=maxNumArticles] - limit the number of articles from the given feedname(s).  Overrides the global article limit.<br>
        [sp# feedList=feedname1,feedname2,etc...] - insert the feeds with the given names<br>
        [sp# feedList=feedname1,feedname2 include=keyword1,keyword2] - insert the feeds with the given names and the given inclusive keyword filters<br>
        [sp# feedList=feedname1,feedname2 exclude=keyword1,keyword2] - insert the feeds with the given names and the given exclusive keyword filters<br>
        [sp# feedList=feedname include=keyword exclude=keyword] - insert the feeds with the given name and the given inclusive and exclusive keyword filters<br>
        </p>
        </div>   
        <b><u>Shortcode parameters</u></b>
        <div style="padding-left: 20px;">
        <p>
        The following case-sensitive parameters may be used within shortcodes and will override corrosponding global settings from the admin panel:<br><br>
        <i>feedList</i> - a comma separated list of keywords that are contained in either the feed name or in the feed url<br>
        <i>exclude</i> - a comma separated list of words that, if found in the article, will result in the article not being shown<br>
        <i>include</i> - a comma separated list of words that, if found in the article, will show the article.  Articles without one of the words listed, will not be shown<br>
        <i>showImages</i> - true/false.  show the image for the article, if it was included in the article feed from the publisher
        <i>limitArticles</i> - set to the maximum number of articles to show for each of the feeds matched by this shortcode<br>
        <i>truncateTitleAtWord</i> - truncate the feed and article titles if they contain this word.  
        <br>&nbsp;&nbsp;&nbsp;&nbsp; This will cut off the title at this word, and not include the word or any words following<br>
        <i>replaceStringInTitle</i> - replace a string ith another string in the title of an article within the feeds of the given shortcode.
        <br>&nbsp;&nbsp;&nbsp;&nbsp; Format: replaceStringInTitle=strToReplace1:replacementstr1,strToReplace2:replacementstr2,etc...        
        </p>
        </div>     
        <b><u>Inserting feed content into a Wordpress theme...</u></b>
        <div style="padding-left: 20px;">
        <p>
        To insert feed contents into the php code of a theme:<br>
        &lt;?php sp_getFeedContent("feedname");?&gt; - inserts the feed(s) into a theme location
        </p>
        </div>
        <b><u>Respecting publishers terms of use</u></b>
        <div style="padding-left: 20px;">
        <p>
        By using Syndicate Press you accept full responsibility and liability for adherance to the terms of service of each feed you syndicate.  Please respect the copyright of feed publishers.
        </p>
        </div>
        <b><u>Credits</u></b>
        <div style="padding-left: 20px;">
        Syndicate Press is designed, developed, published and maintained by HenryRanchLLC.  No warranties of any kind are made or implied regarding the operation of Syndicate Press.  
        The Syndicate Press Wordpress plugin file is licensed to you under the GPL2.0.  Other files included within the Syndicate Press plugin package are licensed according to the license described in those files.<br><br>
        Admin panel tab library provided by <a href="http://www.barelyfitz.com/projects/tabber/" target=_blank>tabber</a>
        </div><br><br>
        <p>
        <a href="<?php echo $this->homepageURL; ?>" target=_blank title="Click for the Syndicate Press homepage...">More Help and documentation...</a><br>
        </p>
     </div>         
     <div class="tabbertab">
        <h2>Support</h2>
        <b><u>Usage Help</u></b>
        <p style="padding-left: 20px;">
        For simple usage instructions, see the Help tab.
        </p>
        <b><u>Detailed documentation</u></b>
        <p style="padding-left: 20px;">
        For more detailed documentation, you can visit the Syndicate Press homepage at <a href="http://syndicatepress.henryranch.net" target=_blank>http://syndicatepress.henryranch.net</a>.
        </p>
        <b><u>Community forum help</u></b>
        <p style="padding-left: 20px;">
        With over 24,000 downloads of Syndicate Press across the world, we are starting to get a fairly active forum where you can ask questions.  
        The Syndicate Press developers and testers regularly read the forum questions and respond with ideas and help.          
        </p>
        <b><u>New feature idea???</u></b>
        <p style="padding-left: 20px;">
        Do you have a great idea for a new feature?  If so, please send it directly to the support email below.<br>
        <i>Since new features take a bit of work, a donation is appreciated and will help push <b>your</b> feature to the top of the list!</i>
        </p>  
        <b><u>Personalized support</u></b>
        <p style="padding-left: 20px;">
        If you would like personalized support from the Syndicate Press developers, you may contact us directly at <b>sp@henryranch.net.</b><br>
        <i>We request a donation to Syndicate Press for personalized support.</i>
        </p>        
        <b><u>Export Settings</u></b>
        <p style="padding-left: 20px;">
        Click <a id="displayText" href="javascript:toggle('exportSettingsDiv');">here</a> to see all of your current Syndicate Press settings.  Copy the text and then include it in your support email.</b><br>

<script language="javascript"> 
function toggle(elementId) {
  var ele = document.getElementById(elementId);
  var text = document.getElementById("displayText");
  if(ele.style.display == "block") {
        ele.style.display = "none";
    text.innerHTML = "show";
    }
  else {
    ele.style.display = "block";
    text.innerHTML = "hide";
  }
} 
</script>

         <div id="exportSettingsDiv" style="display: none">
         <h3>Your current Syndicate Press settings</h3>
         <textarea style="width: 95%; height: 300px;">
            <?php 
                 foreach($configOptions as $key=>$value)
                 {
                   print "$key = $value\r\n";
                 }
            ?>
         </textarea>
         </div>
        </p>
     </div>
     <div class="tabbertab">
        <h2>Donations</h2>
        <table>
        <tr><td width="70%">
        <b><u>Help support this plugin!</u></b>
        <p>
        A donation is a great way to show your support for this plugin.  Donations help offset the cost of maintenance, development and hosting.<br><br>
        Donations also help keep the developer motivated to add new features.  :-)<br><br>
    There is no minimum donation amount.  If you like this plugin and find that it has saved you time or effort, you can be the judge of how much that is worth to you.<br><br>
        Thank you!
        </p>
        <p align="center">
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="hosted_button_id" value="G3XU76VEAWT4Y">
    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form>
    <br>Donations are securely processed by Paypal.<br>
        </p>
        <p>
        <b><u>Other ways to support this plugin</u></b>
        <p>
        In addition to direct donations, you can also support Syndicate Press by following one of the Amazon book links below and buying a book.
        </p>
        <br>&nbsp<br>
        <table style="margin-left: auto; margin-right: auto">
        <tr>
        <td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470592745&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
        <td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470937815&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
        <td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470560541&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
        <td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=1849514100&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
        </tr>
        <tr><td colspan="6"><font size=-12>Syndicate Press Plugin is a participant in the Amazon Services LLC Associates Program, an affiliate advertising program designed to provide a means for sites to earn advertising fees by advertising and linking to amazon.com.</font></td></tr>
        </table>
        </p>
        <td width="30%" style="vertical-align: top;">
        <div style='background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
        <b><u>Thank you to our supporters:</u></b><br><br>
			<a href="http://www.collectorsbluebook.com/">Collectors Blue Book</a><br>
			<a href="http://boltonstudios.com/">Bolton Studios Web Design and Development</a><br>
        <a href="http://dennisholloway.com/">Dennis Holloway Photography</a><br>
			<a href="http://www.pushentertainment.com/">Push Entertainment Ltd</a><br>
			<a href="http://rolandarblog.com">Roland Artist Relations Blog</a><br>
			<a href="http://www.dah.org.il/">Site Building Simple</a><br>
			Anonymous<br>
			Anonymous<br>
			Anonymous<br>
			<a href="http://www.saillwhite.com/">Saill White</a><br>
			Anonymous<br>
			Anonymous<br>
			Anonymous<br>
			Lead Foot Media LLC<br>
        </div>
        </td></tr>
        </table>
     </div>   
</div>

</div>

 </div>
          <?php
        }  
  }
} //End Class SyndicatePressPlugin

//Create the object instance of the class...
if (class_exists("SyndicatePressPlugin")) {
  $syndicatePressPluginObjectRef = new SyndicatePressPlugin();
}

//Init the admin panel by adding it to the WP settings menu...
if (!function_exists("SyndicatePressPlugin_ap")) {
  function SyndicatePressPlugin_ap() {
    global $syndicatePressPluginObjectRef;
        global $adminPageHook;
    if (!isset($syndicatePressPluginObjectRef)) {
      return;
    }
    if (function_exists('add_options_page')) {
            $adminPageHook = add_options_page('Syndicate Press', 'Syndicate Press', 9, basename(__FILE__), array(&$syndicatePressPluginObjectRef, 'sp_printAdminPage'));
            add_action( 'admin_enqueue_scripts', 'my_admin_enqueue_scripts' );
    }
  }  
}

//Actions and filters are registered with WP here.  
if (isset($syndicatePressPluginObjectRef)) {
  //Actions...
  add_action('admin_menu', 'SyndicatePressPlugin_ap');
  add_action('activate_syndicatePress-plugin/syndicatePress-plugin.php',  array(&$syndicatePressPluginObjectRef, 'init'));
        
  //Filter...
  add_filter('the_content', array(&$syndicatePressPluginObjectRef,'sp_ContentFilter')); 
  add_filter('widget_text', array(&$syndicatePressPluginObjectRef,'sp_ContentFilter'));
  
  //enqueing scripts and styles for the main site (not admin)
  add_action( 'wp_enqueue_scripts', 'my_enqueue_scripts' );
}

function my_enqueue_scripts($hook_suffix) {
    wp_enqueue_style('sp_printPage_lightbox', plugins_url('syndicate-press/css/TinyLightbox.css'), false, '1.00', false);
    //wp_enqueue_script('sp_printPage_TAB', plugins_url('syndicate-press/js/tabber-minimized.js'), false, '2.50', false);
}

function my_admin_enqueue_scripts($hook_suffix) {
    global $adminPageHook;
    if ( $adminPageHook == $hook_suffix )
    {
    wp_enqueue_style('sp_printAdminPage_TAB', plugins_url('syndicate-press/css/tabber.css'), false, '2.50', false);
    wp_enqueue_script('sp_printAdminPage_TAB', plugins_url('syndicate-press/js/tabber-minimized.js'), false, '2.50', false);
    }        
}

/* 
* To call the function from anywhere within the Wordpress application, simple paste the following where you want the feed content to appear:
*     <?php sp_getFeedContent("bbc");?>
* 
* @package WordPress
* @since version 2.8.4
* @param string $commaSeparatedListOfFeedNames - the feednames that will be included in the output...
* @return   string     the elaspsed time with 5 decimal place resolution
*/
function sp_getFeedContent($commaSeparatedListOfFeedNames)
{
    global $syndicatePressPluginObjectRef;
    if (!isset($syndicatePressPluginObjectRef)) {
        return;
    }
    $feednameArray = explode(',', $commaSeparatedListOfFeedNames);
    print $syndicatePressPluginObjectRef->sp_filterCallback($feednameArray);
}
?>
