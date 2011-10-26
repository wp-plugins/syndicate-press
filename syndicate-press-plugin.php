<?php
/* 
Plugin Name: Syndicate Press
Plugin URI: http://www.henryranch.net/software/syndicate-press/
Description: This plugin provides a high performance, highly configurable and easy to use news syndication aggregator which supports RSS, RDF and ATOM feeds.
Author: HenryRanch LLC (henryranch.net)
Version: 1.0.4
Author URI: http://henryranch.net/
License: GPL2
*/

/*

LICENSE:
============
Copyright (c) 2009-2011, Shaun Henry, Henry Ranch LLC. All rights reserved. http://www.henryranch.net
Author email: s <at> henryranch.net

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
 
 
 Copyright 2009-2011  Shaun Henry, HenryRanch LLC  (email : s <at> henryranch.net)

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
        var $version = "1.0.4";
        var $homepageURL = "http://henryranch.net/software/syndicate-press/";
        
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
			$adminOptions = array('enable' => 'true',
            'enableFeedCache' => 'true', 
            'enableOutputCache' => 'true', 
            'useDownloadClient' => 'true', 
            'displayImages' => 'false',
            'allowMarkupInDescription' => 'false',
            'showContentOnlyInLinkTitle' => 'false', 
            'showSyndicatePressLinkback' => 'true',
            'showProcessingMetrics' => 'true',
            'showFeedChannelTitle' => 'true',
            'useCustomFeednameAsChannelTitle' => 'false',
            'showArticlePublishTimestamp' => 'true',
            'limitFeedItemsToDisplay' => -1, 
            'limitFeedDescriptionCharsToDisplay' => -1, 
            'maxHeadlineLength' => -1,
            'feedUrlList' => '',
            'inclusiveKeywordFilter' => '',
            'exclusiveKeywordFilter' => '',
            'cacheTimeoutSeconds' => 3600,
            'feedTitleHTMLCodePre' => '<h2>',
            'feedTitleHTMLCodePost' => '</h2>',
            'articleTitleHTMLCodePre' => '<h3>',
            'articleTitleHTMLCodePost' => '</h3>',
            'feedSeparationHTMLCode' => '<hr>',
            'feedNotAvailableHTMLCode' => 'Sorry, the {feedname} feed is not available at this time.'
            );
			$configOptions = get_option($this->adminOptionsName);
			if (!empty($configOptions)) {
				foreach ($configOptions as $key => $option)
					$adminOptions[$key] = $option;
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
                        * @param    string    $content    the post/page content
                        * @return   string     the post/page content with relevant RSS feeds embedded in place of syndicate press bbcodes
                        */
        function sp_filterCallback($matches)
        {
            $startTime = $this->sp_getCurrentTime();
            $configOptions = $this->sp_getConfigOptions();
            $enabled = $configOptions['enable'];
            if($enabled == 'false')
                return;
                                
            $availableFeeds = explode("\n", $configOptions['feedUrlList']);
            
            $content = '';
            
            $enableOutputCache = $configOptions['enableOutputCache'];
            $pageFeedReference = implode(",", $matches);
            $outputCacheFilename = $this->sp_getOutputCacheFilename($pageFeedReference);
            //print "enableOutputCache: $enableOutputCache <br>";
            if(($enableOutputCache == 'true') && !$this->sp_incomingFeedCacheExpired($url) && file_exists($outputCacheFilename))
            {
                //print "Using content from output cache file: $outputCacheFilename<br>";
                $content = file_get_contents($outputCacheFilename);
            }
            else
            {
                //print "Formatting content...<br>";
                foreach($matches as $feedNameReference)
                {
                    $feedNameReference = trim($feedNameReference);                    
                    foreach($availableFeeds as $availableFeed)
                    {
                        $availableFeed = trim($availableFeed);
                        if($availableFeed == "")
                        {
                            continue;
                        }
                        //split the reference string on ',' (comma).  this is the feed reference list provided in the bbcode: [sp# feed1,feed2,feed3,etc...]
                        $feedNameList = explode(',', $feedNameReference);
                        foreach($feedNameList as $feedName)
                        {
                            if(strpos($availableFeed, $feedName) !== false || (strtolower($feedName) == "all"))
                            {    
                                //print "Found requested feed: $availableFeed <br>"; 
                                //this allows naming of the feeds in the feed list as follows: [[<name><pipechar>]<feedUrl>]*
                                if(strpos($availableFeed, $this->feedListCustomNameDelimiter) !== false)
                                {
                                    $availableFeedName = explode($this->feedListCustomNameDelimiter, $availableFeed);
                                    $availableFeed = trim($availableFeedName[1]);
                                    //print "feed URL: $availableFeed <br>"; 
                                }
                                $content .= $this->sp_getFormattedRssContent($availableFeed);
                                if($configOptions['feedSeparationHTMLCode'] != "")
                                {
                                    $content .= $this->sp_unescapeString($configOptions['feedSeparationHTMLCode']);
                                }
                            }
                        }
                    }    
                }
                if($configOptions['showSyndicatePressLinkback'] == 'true')
                {
                    $content .= '<br><br><br><br>Feed aggragation powered by <a href='.$this->homepageURL.' title="Syndicate Press: A smarter feed aggregator" target=_blank>Syndicate Press</a>.';
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
        
        function sp_unescapeString($str)
        {
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
            $pluginDir = dirname(__FILE__);
            $cacheFile = $pluginDir.'/'.$this->inputFeedCacheDir . '/' . md5($url); 
            
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
            $pluginDir = dirname(__FILE__);
            if(!is_dir($pluginDir.'/'.$this->cacheDir))
            {
                mkdir($pluginDir.'/'.$this->cacheDir);
            }
            if(!is_dir($pluginDir.'/'.$this->inputFeedCacheDir))
            {
                mkdir($pluginDir.'/'.$this->inputFeedCacheDir);
            }
            if(!is_dir($pluginDir.'/'.$this->formattedOutputCacheDir))
            {
                mkdir($pluginDir.'/'.$this->formattedOutputCacheDir);
            }
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
            $cacheFile = dirname(__FILE__) . $this->inputFeedCacheDir .'/'. md5($url); 
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
                        $fromEmail = 'syndicatePress@'.$this->sp_getServerHostname();
                        $postData = "";
                        $filename = $cacheFile;
                        //print "host: $host<br>post: $port<br>remoteFile: $remoteFile<br>fromEmail: $fromEmail<br>filename: $filename<br>";
                        $tinyHttpClient = new TinyHttpClient();    
                        //$tinyHttpClient->debug = true;
                        $retVal = $tinyHttpClient->getRemoteFile($host, $port, $remoteFile, $basicAuthUsernameColonPassword, $bufferSize, $mode, $fromEmail, $postData, $filename);
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
            return dirname(__FILE__) . $this->formattedOutputCacheDir .'/'. md5($feedNameReference); 
        }
        
        /* Get the filename of the input cache file for the given feed url
                        * @package WordPress
                        * @since version 2.8.4
                        * @param    string    $url    the url of the incoming feed
                        * @return   string     the locally available path to the cache file.
                        */
        function sp_getInputCacheFilename($url)
        {
            return dirname(__FILE__) . $this->inputFeedCacheDir .'/'. md5($url); 
        }
          
        /* write the given content to the given filename
                        * @package WordPress
                        * @since version 2.8.4
                        * @param    string    $fileName    the local path to the file to be written
                        * @param    string    $content    the content to be writting into the file
                        */
        function sp_writeFile($fileName, $content)
        {
            $fp = fopen($fileName, 'w'); 
            fwrite($fp, $content); 
            fclose($fp);
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
                if($fileInDir)
                {
                    foreach($filesInDir as $filename)
                    {
                        unlink($filename);
                    }
                }
            } catch(Exception $e)
            {}
        }
        
        /* Delete the incoming feed cache files
                        * @package WordPress
                        * @since version 2.8.4
                        */
        function sp_clearIncomingFeedCache()
        {
            $this->sp_deleteFilesInDir(dirname(__FILE__) . $this->inputFeedCacheDir .'/');
        }
        
        /* Delete the formatted output cache files
                        * @package WordPress
                        * @since version 2.8.4
                        */
        function sp_clearFormattedOutputCache()
        {
            $this->sp_deleteFilesInDir(dirname(__FILE__) . $this->formattedOutputCacheDir .'/');
        }
        
        /* Get the html formatted rss feed.
                        * @package WordPress
                        * @since version 2.8.4
                        * @param    string    $url    the rss url
                        * @return   string     html formatted rss feed content from the given url.
                        */
        function sp_getFormattedRssContent($url)
        {
            include_once "php/TinyFeedParser.php";
            
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
                $parser->showContentOnlyInLinkTitle = $configOptions['showContentOnlyInLinkTitle'];
                $parser->maxNumArticlesToDisplay = $configOptions['limitFeedItemsToDisplay'];
                $parser->exclusiveKeywordList = $configOptions['exclusiveKeywordFilter'];
                $parser->inclusiveKeywordList = $configOptions['inclusiveKeywordFilter'];
                $parser->maxDescriptionLength = $configOptions['limitFeedDescriptionCharsToDisplay'];
                $parser->showFeedChannelTitle = $configOptions['showFeedChannelTitle'];
                $parser->useCustomFeednameAsChannelTitle = $configOptions['useCustomFeednameAsChannelTitle'];
                $parser->feedTitleHTMLCodePre = $configOptions['feedTitleHTMLCodePre'];
                $parser->feedTitleHTMLCodePost = $configOptions['feedTitleHTMLCodePost'];
                $parser->articleTitleHTMLCodePre = $configOptions['articleTitleHTMLCodePre'];
                $parser->articleTitleHTMLCodePost = $configOptions['articleTitleHTMLCodePost'];
                $parser->showFeedMetrics = $configOptions['showProcessingMetrics'];
                $parser->showArticlePublishTimestamp = $configOptions['showArticlePublishTimestamp'];
                $parser->allowMarkupInDescription = $configOptions['allowMarkupInDescription'];
                $parser->customFeedName = $this->sp_getCustomFeednameForUrl($url);
                if($parser->customFeedName == "")
                {
                    $parser->customFeedName = $url;
                }
                $parser->maxHeadlineLength = $configOptions['maxHeadlineLength'];
                $parser->allowImagesInDescription = $configOptions['displayImages'];
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
            $url = "http://henryranch.net/feed/";
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
                $parser->maxNumArticlesToDisplay = 3;
                $parser->exclusiveKeywordList = "";
                $parser->inclusiveKeywordList = 'Syndicate Press';
                $parser->maxDescriptionLength = 300;
                $parser->showFeedChannelTitle = 'false';
                $parser->useCustomFeednameAsChannelTitle = 'false';
                $parser->showArticlePublishTimestamp = 'true';
                $parser->feedTitleHTMLCodePre = "<h2>";
                $parser->feedTitleHTMLCodePost = "</h2>";
                $parser->articleTitleHTMLCodePre = "<h4>";
                $parser->articleTitleHTMLCodePost = "</h4>";
                $parser->showFeedMetrics = 'false';
                $parser->customFeedName == "";
                $parser->maxHeadlineLength = 128;
                $parser->allowImagesInDescription = 'false';
                $parser->allowMarkupInDescription = 'false';
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
				if (isset($_POST['syndicatePressLimitFeedDescriptionCharsToDisplay'])) {
					$configOptions['limitFeedDescriptionCharsToDisplay'] = $_POST['syndicatePressLimitFeedDescriptionCharsToDisplay'];
				}	         
				if (isset($_POST['syndicatePressMaxHeadlineLength'])) {
					$configOptions['maxHeadlineLength'] = $_POST['syndicatePressMaxHeadlineLength'];
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
				if (isset($_POST['syndicatePressAllowMarkup'])) {
					$configOptions['allowMarkupInDescription'] = $_POST['syndicatePressAllowMarkup'];
				}
				if (isset($_POST['syndicatePressFeedUrlList'])) {
					$configOptions['feedUrlList'] = apply_filters('feedUrlList_save_pre', $_POST['syndicatePressFeedUrlList']);
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
					$configOptions['feedTitleHTMLCodePre'] = apply_filters('feedTitleHTMLCodePre_save_pre', $_POST['syndicatePressFeedTitleHTMLCodePre']);
                }
				if (isset($_POST['syndicatePressFeedTitleHTMLCodePost'])) {
					$configOptions['feedTitleHTMLCodePost'] = apply_filters('feedTitleHTMLCodePost_save_pre', $_POST['syndicatePressFeedTitleHTMLCodePost']);
                }
				if (isset($_POST['syndicatePressArticleTitleHTMLCodePre'])) {
					$configOptions['articleTitleHTMLCodePre'] = apply_filters('articleTitleHTMLCodePre_save_pre', $_POST['syndicatePressArticleTitleHTMLCodePre']);
                }
				if (isset($_POST['syndicatePressArticleTitleHTMLCodePost'])) {
					$configOptions['articleTitleHTMLCodePost'] = apply_filters('articleTitleHTMLCodePost_save_pre', $_POST['syndicatePressArticleTitleHTMLCodePost']);
                }
				if (isset($_POST['syndicatePressFeedSeparationHTMLCode'])) {
					$configOptions['feedSeparationHTMLCode'] = apply_filters('feedSeparationHTMLCode_save_pre', $_POST['syndicatePressFeedSeparationHTMLCode']);
                    $configOptions['feedSeparationHTMLCode'] = mysql_real_escape_string($configOptions['feedSeparationHTMLCode']);
				}
				if (isset($_POST['syndicatePressFeedNotAvailableHTMLCode'])) {
					$configOptions['feedNotAvailableHTMLCode'] = apply_filters('feedNotAvailableHTMLCode_save_pre', $_POST['syndicatePressFeedNotAvailableHTMLCode']);
				    $configOptions['feedNotAvailableHTMLCode'] = mysql_real_escape_string($configOptions['feedNotAvailableHTMLCode']);
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

<table border=1>
<tr>
<td valign=top>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

<div id="formDiv">

<input type="submit" name="update_SyndicatePressPluginSettings" value="<?php _e('Update Settings', 'SyndicatePressPlugin') ?>" />

<h3>Output aggregated feed content?</h3>
<div style="padding-left: 20px;">
<label for="syndicatePressEnable_yes"><input type="radio" id="syndicatePressEnable_yes" name="syndicatePressEnable" value="true" <?php if ($configOptions['enable'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - show content</label><br>
<label for="syndicatePressEnable_no"><input type="radio" id="syndicatePressEnable_no" name="syndicatePressEnable" value="false" <?php if ($configOptions['enable'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - do not show content</label>
</div>

<br>&nbsp<br>
<h3>List each RSS feed on a single line</h3>
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

<br>&nbsp<br>
<h3>Inclusive keyword filtering</h3>
<div style="padding-left: 20px;">
Only allow feed items that contain any of the following words.<br>
If a feed item contains one or more of the words in this list, the item <em>will</em> be displayed.<br>
<em>Inclusive filtering will be applied before the exclusive filters.</em><br>
Enter a comma separated list of keywords:<br>
<textarea name="syndicatePressInclusiveKeywordFilter" style="width: 95%; height: 50px;"><?php _e(apply_filters('format_to_edit',$configOptions['inclusiveKeywordFilter']), 'SyndicatePressPlugin') ?></textarea>
</div>

<br>&nbsp<br>
<h3>Exclusive keyword filtering</h3>
<div style="padding-left: 20px;">
Filter <em>out</em> feed items that contain any of the following words.<br>
If a feed item contains one or more of the words in this list, the item will <em>not</em> be displayed.<br>
<em>Exclusive filtering will be applied after the inclusive filters.</em><br>
Enter a comma separated list of keywords:<br>
<textarea name="syndicatePressExclusiveKeywordFilter" style="width: 95%; height: 50px;"><?php _e(apply_filters('format_to_edit',$configOptions['exclusiveKeywordFilter']), 'SyndicatePressPlugin') ?></textarea>
</div>

<br>&nbsp<br>
<h3>Input feed caching</h3>
<div style="padding-left: 20px;">
<label for="syndicatePressEnableFeedCache_yes"><input type="radio" id="syndicatePressEnableFeedCache_yes" name="syndicatePressEnableFeedCache" value="true" <?php if ($configOptions['enableFeedCache'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - Cache the incoming feeds.</label><br>
<div style="padding-left: 20px;">
Cached feed expires after <input name="syndicatePressCacheTimeoutSeconds" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['cacheTimeoutSeconds']), 'SyndicatePressPlugin') ?>"> seconds. (1 hour = 3600 seconds)<br>
</div>
<label for="syndicatePressEnableFeedCache_no"><input type="radio" id="syndicatePressEnableFeedCache_no" name="syndicatePressEnableFeedCache" value="false" <?php if ($configOptions['enableFeedCache'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - Request the feed for every view of the Syndicate Press page.  <em>This is NOT recommended!</em></label><br>
Feed download mode:<br>
<div style="padding-left: 20px;">
<label for="syndicatePressUseDownloadClient_yes"><input type="radio" id="syndicatePressUseDownloadClient_yes" name="syndicatePressUseDownloadClient" value="true" <?php if ($configOptions['useDownloadClient'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Use download client.  <em>Recommended when the web host disables file_get_contents() functionality.</em></label><br>
<label for="syndicatePressUseDownloadClient_no"><input type="radio" id="syndicatePressUseDownloadClient_no" name="syndicatePressUseDownloadClient" value="false" <?php if ($configOptions['useDownloadClient'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Use direct download.  <em>May not work on all web hosts.</em></label><br>
</div>
</div>

<br>&nbsp<br>
<h3>Formatted output caching</h3>
<div style="padding-left: 20px;">
<label for="syndicatePressEnableOutputCache_yes"><input type="radio" id="syndicatePressEnableOutputCache_yes" name="syndicatePressEnableOutputCache" value="true" <?php if ($configOptions['enableOutputCache'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Enable - Cache the formatted output.</label><br>
<label for="syndicatePressEnableOutputCache_no"><input type="radio" id="syndicatePressEnableOutputCache_no" name="syndicatePressEnableOutputCache" value="false" <?php if ($configOptions['enableOutputCache'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Disable - Parse and format the feed every time the page/post is requested.  <em>This is NOT recommended!</em></label>
</div>

<br>&nbsp<br>
<h3>Display</h3>
<div style="padding-left: 20px;">
Limit articles in a feed to <input name="syndicatePressLimitFeedItemsToDisplay" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['limitFeedItemsToDisplay']), 'SyndicatePressPlugin') ?>"> items. (-1 to display all items in feed)<br>
Limit article to <input name="syndicatePressLimitFeedDescriptionCharsToDisplay" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['limitFeedDescriptionCharsToDisplay']), 'SyndicatePressPlugin') ?>"> characters. (-1 to display complete article description)<br>
Limit article headline to <input name="syndicatePressMaxHeadlineLength" size="10" value="<?php _e(apply_filters('format_to_edit',$configOptions['maxHeadlineLength']), 'SyndicatePressPlugin') ?>"> characters. (-1 to display complete article headline)<br>
Show item description:<br>
<div style="padding-left: 20px;">
<label for="syndicatePressShowContentOnlyInLinkTitle_yes"><input type="radio" id="syndicatePressShowContentOnlyInLinkTitle_yes" name="syndicatePressShowContentOnlyInLinkTitle" value="true" <?php if ($configOptions['showContentOnlyInLinkTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> only when the viewer hovers over the item link.</label><br>
<label for="syndicatePressShowContentOnlyInLinkTitle_no"><input type="radio" id="syndicatePressShowContentOnlyInLinkTitle_no" name="syndicatePressShowContentOnlyInLinkTitle" value="false" <?php if ($configOptions['showContentOnlyInLinkTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> below the item link.</label><br>
</div>
Item publication timestamp:<br>
<div style="padding-left: 20px;">
<label for="syndicatePressshowArticlePublishTimestamp_yes"><input type="radio" id="syndicatePressshowArticlePublishTimestamp_yes" name="syndicatePressshowArticlePublishTimestamp" value="true" <?php if ($configOptions['showArticlePublishTimestamp'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show timestamp.</label><br>
<label for="syndicatePressshowArticlePublishTimestamp_no"><input type="radio" id="syndicatePressshowArticlePublishTimestamp_no" name="syndicatePressshowArticlePublishTimestamp" value="false" <?php if ($configOptions['showArticlePublishTimestamp'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Hide timestamp.</label><br>
</div>
Display HTML formatting in article:<br>
<div style="padding-left: 20px;">
<em>NOTE: Displaying HTML content in the articles will disable article length limitation</em><br>
<label for="syndicatePressAllowMarkup_yes"><input type="radio" id="syndicatePressAllowMarkup_yes" name="syndicatePressAllowMarkup" value="true" <?php if ($configOptions['allowMarkupInDescription'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show HTML formatting.</label><br>
<label for="syndicatePressAllowMarkup_no"><input type="radio" id="syndicatePressAllowMarkup_no" name="syndicatePressAllowMarkup" value="false" <?php if ($configOptions['allowMarkupInDescription'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Strip HTML formatting, leaving only the article text.</label><br>
</div>
Syndicate Press link:<br>
<div style="padding-left: 20px;">
<label for="syndicatePressShowSyndicatePressLinkback_yes"><input type="radio" id="syndicatePressShowSyndicatePressLinkback_yes" name="syndicatePressShowSyndicatePressLinkback" value="true" <?php if ($configOptions['showSyndicatePressLinkback'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show 'Powered by <a href="<?php echo $this->homepageURL; ?>" target=_blank>Syndicate Press</a>' at the end of the aggregated feed content.</label><br>
<div style="padding-left: 20px;">
<em>If you have not donated to Syndicate Press, a link back to the Syndicate Press site is requested.<br>
You may use this automated linkback, or you may place the link in the footer of your site.</em><br>
</div>
<label for="syndicatePressShowSyndicatePressLinkback_no"><input type="radio" id="syndicatePressShowSyndicatePressLinkback_no" name="syndicatePressShowSyndicatePressLinkback" value="false" <?php if ($configOptions['showSyndicatePressLinkback'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show the Syndicate Press link.</label><br>
</div>
Processing and feed metrics:<br>
<div style="padding-left: 20px;">
<label for="syndicatePressShowProcessingMetrics_yes"><input type="radio" id="syndicatePressShowProcessingMetrics_yes" name="syndicatePressShowProcessingMetrics" value="true" <?php if ($configOptions['showProcessingMetrics'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show.</label><br>
<label for="syndicatePressShowProcessingMetrics_no"><input type="radio" id="syndicatePressShowProcessingMetrics_no" name="syndicatePressShowProcessingMetrics" value="false" <?php if ($configOptions['showProcessingMetrics'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show.</label><br>
</div>
Feed name (title):<br>
<div style="padding-left: 20px;">
<label for="syndicatePressUseCustomFeednameAsChannelTitle_yes"><input type="radio" id="syndicatePressUseCustomFeednameAsChannelTitle_yes" name="syndicatePressUseCustomFeednameAsChannelTitle" value="true" <?php if ($configOptions['useCustomFeednameAsChannelTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Use custom feedname as feed title.</label><br>
<label for="syndicatePressUseCustomFeednameAsChannelTitle_no"><input type="radio" id="syndicatePressUseCustomFeednameAsChannelTitle_no" name="syndicatePressUseCustomFeednameAsChannelTitle" value="false" <?php if ($configOptions['useCustomFeednameAsChannelTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Use publisher's title (including link and image if available).</label><br>
<div style="padding-left: 20px;">
<label for="syndicatePressShowFeedChannelTitle_yes"><input type="radio" id="syndicatePressShowFeedChannelTitle_yes" name="syndicatePressShowFeedChannelTitle" value="true" <?php if ($configOptions['showFeedChannelTitle'] == "true") { _e('checked="checked"', "SyndicatePressPlugin"); }?> /> Show.</label><br>
<label for="syndicatePressShowFeedChannelTitle_no"><input type="radio" id="syndicatePressShowFeedChannelTitle_no" name="syndicatePressShowFeedChannelTitle" value="false" <?php if ($configOptions['showFeedChannelTitle'] == "false") { _e('checked="checked"', "SyndicatePressPlugin"); }?>/> Do not show.</label><br>
</div>
</div>
Title formatting:<br>
<div style="padding-left: 20px;">
<em>You can use html tags to format the feed and article titles... i.e. &lt;h2&gt;title&lt;/h2&gt;</em><br>
<input name="syndicatePressFeedTitleHTMLCodePre" size="20" value="<?php _e(apply_filters('format_to_edit',$configOptions['feedTitleHTMLCodePre']), 'SyndicatePressPlugin') ?>">Feed title<input name="syndicatePressFeedTitleHTMLCodePost" size="20" value="<?php _e(apply_filters('format_to_edit',$configOptions['feedTitleHTMLCodePost']), 'SyndicatePressPlugin') ?>"><br>
<input name="syndicatePressArticleTitleHTMLCodePre" size="20" value="<?php _e(apply_filters('format_to_edit',$configOptions['articleTitleHTMLCodePre']), 'SyndicatePressPlugin') ?>">Article title<input name="syndicatePressArticleTitleHTMLCodePost" size="20" value="<?php _e(apply_filters('format_to_edit',$configOptions['articleTitleHTMLCodePost']), 'SyndicatePressPlugin') ?>"><br>
</div>
Custom feed separation code:<br>
<div style="padding-left: 20px;">
<em>You can insert any html content between feeds (including advertising code)<br>
<div style="padding-left: 20px;">
i.e. To insert a horizontal line: &lt;hr&gt;</em><br>
</div>
<textarea name="syndicatePressFeedSeparationHTMLCode" style="width: 95%; height: 100px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedSeparationHTMLCode'])), 'SyndicatePressPlugin') ?></textarea>
</div>
Custom content to show when a feed is unavailable:<br>
<div style="padding-left: 20px;">
<em>You can insert custom html content when a feed is not available.<br>
To include the name of the unavailable feed, use {feedname} in the code below and it will be replaced with the name of the feed.<br>
To show nothing when a feed is not available, simply delete all of the content from this field.</em>
<div style="padding-left: 20px;">
</div>
<textarea name="syndicatePressFeedNotAvailableHTMLCode" style="width: 95%; height: 100px;"><?php _e($this->sp_unescapeString(apply_filters('format_to_edit',$configOptions['feedNotAvailableHTMLCode'])), 'SyndicatePressPlugin') ?></textarea>
</div>
</div>
</div>

<input name="synPress-update_settings" type="hidden" value="<?php echo wp_create_nonce('synPress-update_settings'); ?>" />
<div class="submit">
<table>
<tr><td>
<input type="submit" name="update_SyndicatePressPluginSettings" value="<?php _e('Update Settings', 'SyndicatePressPlugin') ?>" />
</form>
</td><td></td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td>
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
</td></tr>
</table>
</div>

<br>&nbsp<br>
<h3>Quick Start...</h3>
To insert feed contents into a Page or Post, use the following syntax:<br>
<div style="padding-left: 20px;">
[sp# all] - insert all of the feeds in the feed list<br>
[sp# feedname] - insert only the feed with the given name<br>
[sp# feedname1,feedname2,etc...] - insert the feeds with the given names<br>
&lt;?php sp_getFeedContent("feedname");?&gt; - inserts the feed(s) into a theme location<br>
</div>
<a href="<?php echo $this->homepageURL; ?>" target=_blank title="Click for the Syndicate Press homepage...">Help and documentation...</a><br>


<br>&nbsp<br>
<em>Version <?php print $this->version;?></em>
</td>

<!-- right side content -->
<td valign=top width=30%>
<div style='background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
<h3 style="text-align:center">Help support this plugin!</h3>
<p>
A donation is a great way to show your support for this plugin.  Donations help offset the cost of maintenance, development and hosting.<br><br>
There is no minimum donation amount.  If you like this plugin and find that it has saved you time or effort, you can be the judge of how much that is worth to you.<br><br>
Thank you!
</p>
<p align="center">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="8983567">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</p>
</div>

<br>&nbsp<br>
<div style='background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
<h3 style="text-align:center">Syndicate Press news</h3>
<p>
<?php print $this->sp_getSPNews(); ?>
</p>
</div>

<br>&nbsp<br>
<div style='background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
<h3 style="text-align:center">Other ways to support this plugin</h3>
<p>
In addition to direct donations, you can also support Syndicate Press by following one of the Amazon book links below and buying a book.
</p>
<p align="center">

<br>&nbsp<br>
<table style="margin-left: auto; margin-right: auto">
<tr>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470592745&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470937815&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
</tr>
<tr>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=0470560541&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=1849514100&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe>
</td>
</tr>
<tr>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=B00168NGGU&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
<td style="padding: 10px;"><iframe src="http://rcm.amazon.com/e/cm?t=henrantecandl-20&o=1&p=8&l=as1&asins=B004DNWI8W&ref=qf_sp_asin_til&fc1=000000&IS2=1&lt1=_blank&m=amazon&lc1=0000FF&bc1=000000&bg1=FFFFFF&f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></td>
</tr>
</table>

</p>
</div>

</td>
</tr>
</table>

 </div>
 <!-- Completely anonymous page view counter. -->
 <img src="http://hitcount.henryranch.net/gethitcount.php?cid=501">
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
		if (!isset($syndicatePressPluginObjectRef)) {
			return;
		}
		if (function_exists('add_options_page')) {
            add_options_page('Syndicate Press', 'Syndicate Press', 9, basename(__FILE__), array(&$syndicatePressPluginObjectRef, 'sp_printAdminPage'));
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