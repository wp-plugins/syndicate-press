<?php
/*
File: TinyFeedParser.php
Date: 7/06/2014
Version 1.9.15
Author: HenryRanch LLC

LICENSE:
============
Copyright (c) 2009-2014, Henry Ranch LLC. All rights reserved. http://www.henryranch.net


TinyFeedParser is governed by the following license and is not licensed for use outside of 
the SyndicatePress Wordpress plugin.  By downloading or using this software,  you
 agree to all the following: 
 
 YOU WILL NOT USE OR COPY THIS SOFTWARE OUTSIDE OF THE SYNDICATE PRESS
 WORDPRESS PLUGIN.

 THIS SOFTWARE IS PROVIDED BY HENRY RANCH LLC `AS IS' AND ANY EXPRESS
 OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 ARE DISCLAIMED.  IN NO EVENT SHALL HENRY RANCH LLC OR ANY OF THE AUTHORS
 BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT 
 OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  
 
 IF YOU WOULD LIKE TO USE THIS SOFTWARE IN YOUR OWN APPLICATION, 
YOU MAY REQUEST A LICENSE TO DO SO FROM THE AUTHOR.
 
 YOU AGREE THAT YOU WILL NOT USE THIS SOFTWARE TO ACCESS, DISPLAY
 OR AGGREGATE CONTENT IN A MANNER THAT VIOLATES THE COPYRIGHT, 
 INTELLECTUAL PROPERTY RIGHTS OR TRADEMARK OF ANY ENTITY.  
 
 HENRYRANCH LLC SHALL NOT BE LIABLE FOR ANY COPYRIGHT INFRINGEMENT CLAIMS
 MADE THROUGH YOUR USE OF THIS SOFTWARE.  YOU AGREE TO INDEMNIFY, PROTECT 
 AND SHIELD HENRYRANCH LLC FROM ALL LEGAL JUDGEMENTS, FEES, COSTS AND/OR 
 ANY ASSOCIATED FEES THAT MAY RESULT OUT OF YOUR USE OF THIS SOFTWARE.
 YOU AGREE THAT YOU ARE SOLELY RESPONSIBLE FOR YOUR USE OF THIS SOFTWARE
 AND SHALL FOREVER HOLD HENRYRANCH LLC HARMLESS IN ALL MATTERS.
 
 ANY INSTALLATION OR USE OF THIS SOFTWARE MEANS THAT YOU ACCEPT AND AGREE 
 TO ABIDE BY ALL OF THE TERMS OF THIS LICENSE AGREEMENT.
*/


class Article
{
    var $pubDateStr;
    var $pubTimeStamp;
    var $link;
    var $linkComments;
    var $linkSelf;
    var $linkAlternate;
    var $linkEdit;
    var $linkReplies;
    var $title;
    var $subtitle;
    //description: typically contains the syndicated article content
    var $description;
    //content: not always provided.  Can contain html
    var $content;
    //headline: a copy of the description data, but with html tags stripped out
    var $headline;
    var $image;
    var $imageCaptionAlt;
    var $copyright;
    var $author;
    var $guid;
    var $comments;
    var $price;
}

class TinyFeedParser
{
    var $articles = array();
    
    var $feedIndex = 0;

    var $feedUpdateTime = '';
    
    var $maxDescriptionLength = -1;
    var $maxHeadlineLength = 100;
    var $allowImagesInDescription = 'false';
    var $allowMarkupInDescription = 'false';
	
    var $addNoFollowTag = 'true';
    
    var $showContentOnlyInLinkTitle = false;
    var $showFeedChannelTitle = true;
    var $showFeedMetrics = true;
    var $maxNumArticlesToDisplay = 5;
    var $hideArticlesAfterArticleNumber = -1;
    var $exclusiveKeywordList = "";
    var $inclusiveKeywordList = "";
    
    var $showArticlePublishTimestamp = true;
    var $showComments = false;

    var $useCustomFeednameAsChannelTitle = false;
    var $customFeedName = "";
        
    var $feedTitleHTMLCodePre = '<h2>';
    var $feedTitleHTMLCodePost = '</h2>';
    var $articleTitleHTMLCodePre = '<h3>';
    var $articleTitleHTMLCodePost = '</h3>';
    var $articleBodyHTMLCodePre = '<div name="bodyHtmlCodePre">';
    var $articleBodyHTMLCodePost = '</div>';
    var $articleTimestampHTMLCodePre = '<i>';
    var $articleTimestampHTMLCodePost = '</i>';
    var $articleAuthorHTMLCodePre = '<i>';
    var $articleAuthorHTMLCodePost = '</i>';
    var $articleCopyrightHTMLCodePre = '<i>';
    var $articleCopyrightHTMLCodePost = '</i>';
    var $articlePriceHTMLCodePre = '<i>';
    var $articlePriceHTMLCodePost = '</i>';
    var $articleSubtitleHTMLCodePre = '<i>';
    var $articleSubtitleHTMLCodePost = '</i>';
    var $articleImageHTMLCodePre = '<div name="imgHtmlCodePre"><br>';
    var $articleImageHTMLCodePost ='</div>';

    var $numArticles = 0;
    
    var $targetDirective = '_blank';
    
    var $useCustomTimestampFormat = true;
    var $defaultTimestampFormatString = 'l F jS, Y h:i:s A';
    var $timestampFormatString = 'l F jS, Y h:i:s A';
    var $truncateTitleAtWord = '';
    var $replaceStringInTitle = '';
    var $openArticleInLightbox = false;
    var $lightboxHTMLCode = '';
	
    function TinyFeedParser() 
    {
    }
    
	function getDefaultTimestampFormatString()
	{
		return $this->defaultTimestampFormatString;
	}
	
    function getDomainFromUrl($url)
    {
        preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $urlArray);
        return $urlArray[2];
    }
        
    
    function getFilePathFromUrl($url)
    {
        $domain = $this->getDomainFromUrl($url);
        $domain = 'http://'.$domain;
        return str_replace($domain, '', $url);
    }
    
    /**
                param:
                 - contentArray: an array of content items to search for exclusive filter keywords in...
                 - filterKeywordArray: an array of filter keywords
                 - exclusive: true the filter is exclusive, false if the filter is inclusive
                return:
                 - true if the currently parsed item is allowed past the exclusive filter
                 - false else...                
           */
    function passFilter($contentArray, $filterKeywordList, $exclusive)
    {      
        if(count($contentArray) == 0)
            return false;
        
        if($filterKeywordList == "")
            return true;
                
        //print "filter keywords: '$filterKeywordList'<br>";
        $filterKeywordList = strtolower($filterKeywordList);
        $filterKeywordArray = explode(',', $filterKeywordList); 
        
        $smooshedContent = implode('+_+', $contentArray);
        $smooshedContent = strtolower($smooshedContent);
        
        $passFilter = $exclusive;
        foreach($filterKeywordArray as $filterKeyword)
        {
            $filterKeyword = trim($filterKeyword);
            if($filterKeyword == '')
            {
                continue;
            }
            if($exclusive)
            {
                //print "ekl: checking '$smooshedContent' for '$filterKeyword'<br>";
                if($smooshedContent && (stripos($smooshedContent, $filterKeyword) !== false))
                {
                    //print "ekl: filtered item '$smooshedContent' for '$filterKeyword'<br>";
                    return false;
                }
            }
            else //inclusive
            {
                //print "inl: checking '$smooshedContent' for '$filterKeyword'<br>";
                if($smooshedContent && stripos($smooshedContent, $filterKeyword) !== false)
                {
                    //print "inl: allowing item '$smooshedContent' for '$filterKeyword'<br>";
                    return true;
                }
            }
        }
        return $passFilter;
    }
        
    function parseFeed($url_or_file)
    {
        $this->feedUpdateTime = $this->getFileModificationTime($url_or_file);
		
        if(strpos($url_or_file, "http://") === false)
        {
            $xmlString = file_get_contents($url_or_file);
        }
        if(strripos($xmlString, 'rdf:') !== false)
        {
            $xmlString = str_replace("rdf:", "rdf_", $xmlString);
        }
        if(strripos($xmlString, 'dc:') !== false)
        {
            $xmlString = str_replace("dc:", "ns_", $xmlString);
        }
        if(strripos($xmlString, 'pm:') !== false)
        {
            $xmlString = str_replace("pm:", "ns_", $xmlString);
        }
        if(strripos($xmlString, '<html') !== false)
        {
            throw new Exception('Given URL reference ('.$this->customFeedName.') returned an HTML page.  Cannot parse HTML as RSS.');
        }
        try
        {
            @$xmlDocObj = simplexml_load_string($xmlString);
        }
        catch(Exception $e)
        {
            throw new Exception('Failed to parse given URL reference ('.$this->customFeedName.'):<br>'.$e->getMessage());
        }
        if($xmlDocObj === false)
        {
            throw new Exception('Failed to parse given URL reference ('.$this->customFeedName.').');
        }
        if(count($xmlDocObj) == 0)
            return "Unable to parse xml data...";

        //print "\r\n\r\n<!-- \r\n\r\n$xmlString\r\n\r\n -->\r\n\r\n";

        if($this->isRss($xmlDocObj))
        {
            $this->parseRSSFeed($xmlDocObj);
        }
        else if($this->isAtom($xmlDocObj))
        {
            $this->parseAtomFeed($xmlDocObj);
        }    
        else
        {            
            //print "\r\n\r\n<!-- \r\n\r\n$xmlString\r\n\r\n -->\r\n\r\n";
            $this->parseRdfFeed($xmlDocObj);
        }
    }    
    
    function isRss($xmlDocObj)
    {
        if($xmlDocObj->channel->item)
            return true;
        else 
            return false;
    }
    
    function isAtom($xmlDocObj)
    {
        if($xmlDocObj->entry)
            return true;
        else 
            return false;
    }
        
    function getAtomLinkAttributes($link)
    {
        $attributes = array();
        foreach($link->attributes() as $attribute => $value) 
        {
            $attributes[$attribute] = $value;
        }
        return $attributes;
    }
    
    function parseAtomFeed($xmlDocObj)
    {
        $article = new Article();
        $article->pubDateStr = (string)$xmlDocObj->updated;
        $article->pubTimeStamp = strtotime($xmlDocObj->updated);
        $article->link = (string)$xmlDocObj->link;
        if(!$article->link)
        {
            $attributes = $this->getAtomLinkAttributes($xmlDocObj->link);
            $article->link = $attributes['href'];
        }
        $article->title = (string)$xmlDocObj->title;
        $article->subtitle = (string)$xmlDocObj->subtitle;
        $article->description = (string)$xmlDocObj->summary;   
        $article->author = (string)$xmlDocObj->author;      
                  
        $this->addArticle($article);
            
        foreach($xmlDocObj->entry as $entry)
        {
            if($this->maxNumArticlesReached())
            {
                return;
            }
            
            $article = new Article();
            $article->pubDateStr = (string)$entry->updated;
            $article->pubTimeStamp = strtotime($entry->updated);
            $article->link = (string)$entry->link;
            if(!$article->link)
            {
                $attributes = $this->getAtomLinkAttributes($entry->link);
                $article->link = $attributes['href'];
            }
            $article->title = (string)$entry->title;
            $article->description = (string)$entry->summary;
            $article->content = (string)$entry->content;
                        
            $this->addArticle($article);
        }  
    }    
        
    function parseRSSFeed($xmlDocObj)
    {
        foreach($xmlDocObj->channel as $channel)
        {
            $article = new Article();
            $article->title = (string)$channel->title;
            $article->subtitle = (string)$channel->subtitle;
            $article->link = (string)$channel->link;
            $article->description = (string)$channel->description;    
            $article->pubDateStr = (string)$channel->lastBuildDate;
            $article->pubTimeStamp = strtotime($channel->lastBuildDate);
            $article->guid = $channel->guid;
            $article->image = $channel->image;
            $article->copyright = (string)$channel->copyright;
            $article->author = (string)$channel->author;
            if($channel->ns_creator)
            {
                $article->author = (string)$channel->ns_creator;
            }
            

            $this->addArticle($article);
            
            foreach($channel->item as $item)
            {
                if($this->maxNumArticlesReached())
                {
                    return;
                }
                
                $article = new Article();
                $article->pubDateStr = (string)$item->pubDate;
                                
                $article->pubTimeStamp = strtotime($article->pubDateStr);
                $article->link = (string)$item->link;
                $article->title = (string)$item->title;
                $article->subtitle = (string)$item->subtitle;
                $article->description = (string)$item->description;
                $article->guid = (string)$item->guid;
                $article->image = $item->image;
                $article->comments = (string)$item->comments;
                if($item->ns_Image)
                {
                    $article->image = (string)$item->ns_Image;
                }              
                if($item->ns_CurrentPrice)
                {
                    $article->price = (string)$item->ns_CurrentPrice;
                }   
                if($item->ns_Caption)
                {
                    $article->imageCaptionAlt = (string)$item->ns_Caption;
                }
                $article->copyright = (string)$item->copyright;
                $article->author = (string)$item->author;
                if($item->ns_creator)
                {
                    $article->author = (string)$item->ns_creator;
                }
                
                $this->addArticle($article);
            }
        }
    }
    
    function parseRdfFeed($xmlDocObj)
    {
        $article = new Article();
        $article->title = (string)$xmlDocObj->channel->title;
        $article->subtitle = (string)$xmlDocObj->channel->subtitle;
        $article->link = (string)$xmlDocObj->channel->link;
        $article->guid = (string)$xmlDocObj->channel->guid;
        $article->description = (string)$xmlDocObj->channel->description;      
        $article->pubDateStr = (string)$xmlDocObj->channel->ns_date;
        $article->pubTimeStamp = strtotime($xmlDocObj->channel->ns_date);
        $article->image = $xmlDocObj->channel->image;
        
        $article->author = (string)$xmlDocObj->channel->author;
        $article->copyright = (string)$xmlDocObj->channel->ns_rights;
                 
        $this->addArticle($article);
       
        foreach($xmlDocObj->item as $item)
        {
            if($this->maxNumArticlesReached())
            {
                return;
            }
            $article = new Article();
            $article->title = (string)$item->title;
            $article->link = (string)$item->link;
            $article->description = (string)$item->description;      
            $article->pubDateStr = (string)$item->ns_date;
            $article->pubTimeStamp = strtotime($item->ns_date);
            $article->image = (string)$item->image;
            if($xmlDocObj->channel->ns_image)
            {
                $article->image = $xmlDocObj->channel->ns_image;
            }
            $article->copyright = (string)$item->ns_rights;
                       
            $this->addArticle($article);
        } 
    }
    
    function getFileModificationTime($filename)
    {
        if(file_exists($filename))
        {
			if($this->useCustomTimestampFormat)
			{
				return date($this->timestampFormatString, filemtime($filename));
			}
			else
			{
				return date("F d Y H:i:s.", filemtime($filename));
			}
        }
    }
    
    function maxNumArticlesReached()
    {
        if(($this->maxNumArticlesToDisplay != -1) && ($this->numArticles > $this->maxNumArticlesToDisplay))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    function addArticle($article)
    {
        $article = $this->cleanupArticle($article);
        $objectVarArray = get_object_vars($article);
        if(($this->numArticles == 0) ||
          (($this->passFilter($objectVarArray, $this->inclusiveKeywordList, false)) &&
          ($this->passFilter($objectVarArray, $this->exclusiveKeywordList, true))))
        {
            $this->articles[] = $article;
            $this->numArticles++;
        }    
    }
    
    function cleanupArticle($article)
    {
        if($article->link)
        {
            $article->link = trim($article->link);
        }
        if($article->content)
        {
            if($this->allowMarkupInDescription == 'false')
            {
                $article->content = $this->removeAllHtmlMarkup($article->content);
                $article->content = $this->truncateToLength($article->content, $this->maxDescriptionLength, $article->link);
            }
        }
        
        if($this->showContentOnlyInLinkTitle == 'true')
        {
            $article->description = (string)$this->removeImages($article->description);
            $article->description = (string)$this->removeAllHtmlMarkup($article->description);
        }
        if($this->allowImagesInDescription == 'false')
        {
            $article->description = (string)$this->removeImages($article->description);
        }
        if($this->allowMarkupInDescription == 'false')
        {
            $article->description = (string)$this->removeAllHtmlMarkup($article->description);
            $truncateLink = $article->link;
            if($this->showContentOnlyInLinkTitle == 'true')
            {
                $truncateLink = 'NO_LINK';
            }
            $article->description = $this->truncateToLength($article->description, $this->maxDescriptionLength, $truncateLink);
        }
                
        $article->headline = $article->description;
        $article->headline = (string)$this->removeAllHtmlMarkup($article->headline);
        $article->headline = $this->truncateToLength($article->headline, $this->maxHeadlineLength, 'NO_LINK');
        if($this->truncateTitleAtWord != '')
        {
            $length = strpos($article->headline, $this->truncateTitleAtWord, 0) - 1;
            $article->headline = $this->truncateToLength($article->headline, $length, 'NO_LINK', false);
        }
        
        $article->title = (string)$this->removeAllHtmlMarkup($article->title);
        $article->title = $this->truncateToLength($article->title, $this->maxHeadlineLength, 'NO_LINK');
        if($this->truncateTitleAtWord != '')
        {
            $length = strpos($article->title, $this->truncateTitleAtWord, 0) - 1;
            $article->title = $this->truncateToLength($article->title, $length, 'NO_LINK', false);
        }
        if($this->replaceStringInTitle != '')
        {
        	 $keyValuePairArray = explode(',', $this->replaceStringInTitle);
        	 foreach($keyValuePairArray as $replacementPair)
        	 {        	   
        	   $replacementArray = explode(':', $replacementPair);
        	   $strToBeReplaced = $replacementArray[0];
        	   $replacementStr = $replacementArray[1];
        	   $article->title = str_replace($strToBeReplaced, $replacementStr, $article->title);
        	 }
        }
                
        $article->subtitle = (string)$this->removeAllHtmlMarkup($article->subtitle);
        
        if($article->copyright)
        {
            $article->copyright = (string)$this->removeAllHtmlMarkup($article->copyright);
        }

        return $article;
    }
    
    function truncateToLength($text, $length, $urlLink="", $elipsis=true)
    {
        if($length != -1 && strlen($text) > $length)
        {
            //find the end of the word, as denoted by a space char
            $length = strpos($text, ' ', $length);
            //now do the truncation
            $text = substr($text, 0, $length);
            if($urlLink != "" && $urlLink != 'NO_LINK')
            {
                $text .= " <a href=\"$urlLink\" target=\"".$this->targetDirective."\" title=\"Open article in a new window\"";				
                if($this->addNoFollowTag == 'true')
                {
                    $text .= ' rel="nofollow"';
                }
                $text .= ">";
                if($elipsis)
                {
                    $text .= '...';
                }
                $text .= "</a>";
            }
            else if($elipsis)
            {
                $text .= '...';
            }
            
        }
        return $text;
    }
    
    function removeImages($text)
    {
        return eregi_replace("<img[^>]*>", "", $text);
    }
    
    function removeAllHtmlMarkup($text)
    {
        return eregi_replace("<[^>]*>", "", $text);
    }
    
    function removeLinks($text)
    {
        return eregi_replace("</?a[^>]*>", "", $text);      
    }
    
    function removeNonTextElements($text)
    {
        $text = eregi_replace("^(<br[ ]?/>)*", "", $text);
        $text = eregi_replace("(<br[ ]?/>)*$", "", $text);    
        $text = eregi_replace("<br[^>]*>", "", $text);    
        $text = eregi_replace("(</?p>)*", "", $text);
        return $text;
    }
    
    /* Determine if the given string ends with the gven end string.
            * @package WordPress
            * @since version 2.8.4
            * @param    string    $str the string to check
            * @param    string    $end the string to see if $str ends with 
            * @return   boolean true if the tring ends wit the given end, else false.
            */
    function endsWith($str, $end)
    {
        $len = strlen($end);
        $strEnd = substr($str, strlen($str) - $len);
        return $strEnd == $end;
    }
    
    function addBrIfNeeded($str)
    {
        $str = trim($str);
        if(!$this->endsWith($str, "<br>") && !$this->endsWith($str, "<br/>") && !$this->endsWith($str, "<br />"))
        {
             $str .= "<br />\r\n";
        }
        return $str;
    }
    
    function getJS()
    {
        return '<script language="javascript" type="text/javascript">'.
               'function toggle(elementId) '.
               '{'.
               '  var ele = document.getElementById(elementId);'.
               '  var text = document.getElementById("displayText");'.
               '  if(ele.style.display == "block")'. 
               '  {'.
               '    ele.style.display = "none";'.
               '  }'.
               '  else '.
               '  {'.
               '    ele.style.display = "block";'.
               '  }'.
               '} '.
               'function loadLightbox(elementId, url) {'.           
               '  var request = new XMLHttpRequest();'.
               '  request.open("GET", url, false);'.
               '  request.send(null);'.
               '  var content = request.responseText;'.
               '  document.getElementById(elementId).innerHTML = content;'.
               '}'.                            
               '</script>';
    }
    

    function getHtml()
    {
        $html = "";

        if($this->hideArticlesAfterArticleNumber > 1)
        {
            $html .= $this->getJS();
        }
                
        if($this->openArticleInLightbox == 'true')
        {
            $html .= $this->getJS();
            $html .= $this->lightboxHTMLCode;
        }

        $articles = $this->articles;
        $currentArticleIndex = 0;
        foreach($articles as $article)
        {
            $currentArticleIndex++;
            
            if($currentArticleIndex == 1)
            {
                $headerHtmlPre = $this->feedTitleHTMLCodePre;
                $headerHtmlPost = $this->feedTitleHTMLCodePost;
            }
            else
            {
                if(($this->hideArticlesAfterArticleNumber > 1) && (($currentArticleIndex - 2) == $this->hideArticlesAfterArticleNumber))
                {
                    $hiddenDivId = 'hiddenArticleDiv-'.rand().'-feedIndex-'.$this->feedIndex;
                    $html .= "<br>\r\n<div id=\"showHideArticlesControlDiv\"><a id=\"displayText\" href=\"javascript:toggle('".$hiddenDivId."');\">Show / Hide more articles from this feed.</a><br></div><!-- end div showHideArticlesControlDiv-->";
                    $html .= "\r\n<div id=\"".$hiddenDivId."\" style=\"display: none\">\r\n";
                }
                $headerHtmlPre = $this->articleTitleHTMLCodePre;
                $headerHtmlPost = $this->articleTitleHTMLCodePost;
            }
            
            if($currentArticleIndex == 1) 
            {
                if(($this->useCustomFeednameAsChannelTitle == 'true') && ($this->customFeedName != ""))
                {
                    $html .= $headerHtmlPre.$this->customFeedName.$headerHtmlPost;
                    continue;
                }
                else if($this->showFeedChannelTitle == 'false')
                {
                    continue;
                }
            }
            $html .= "\r\n<div id=\"itemDiv-feed-".$this->feedIndex.'-article-'.($currentArticleIndex-1)."\" class=\"sp-feed-item\"><!-- Article GUID: ".$article->guid."-->\r\n";
            
            $html .= $headerHtmlPre;


            if($this->openArticleInLightbox == 'true')
            {
                $html .= ' <a href="javascript:void(0)" onclick="document.getElementById(\'external-content-iframe\').src=\''.$article->link.
                      '\';document.getElementById(\'lightbox-external\').style.display=\'block\';document.getElementById(\'main\').style.display=\'block\';">'.$article->title.'</a>'."\r\n";
            }			
				else 
				{            
              	$html .= '<a href="'.$article->link.'" ';
            	if($this->showContentOnlyInLinkTitle == 'true')
            	{
                	$html .= 'title="'.$article->description.'  Click to read the full article..."';
                	if($article->description == '')
                	{
                  	  continue;
               	}
            	}
            	else
            	{
                	$html .= 'title="Click to read article..."';
            	}
            	if($this->addNoFollowTag == 'true')
            	{
              	 	$html .= ' rel="nofollow"';
            	}
            	$html .= ' target='.$this->targetDirective.'>'.$article->title.'</a>';
 				}
				$html .= $headerHtmlPost."\r\n";            
            
            if($article->subtitle != '')
            {
                $html .= $this->articleSubtitleHTMLCodePre.$article->subtitle.$this->articleSubtitleHTMLCodePost."\r\n";
            }
            if($this->showArticlePublishTimestamp == 'true' && $currentArticleIndex != 1)
            {
                if($article->pubDateStr)
                {
                    if($this->useCustomTimestampFormat)
                    {
                       $html .= $this->articleTimestampHTMLCodePre.date($this->timestampFormatString, $article->pubTimeStamp).$this->articleTimestampHTMLCodePost."\r\n";
                    }
                    else
                    {
                      $html .= $this->articleTimestampHTMLCodePre.$article->pubDateStr.$this->articleTimestampHTMLCodePost."\r\n";
                    }
                }
                else
                {
                    $html .= $this->articleTimestampHTMLCodePre.'No timestamp info...'.$this->articleTimestampHTMLCodePost."\r\n";
                }
            }
        
            if($article->copyright)
            {
                $html .= $this->articleCopyrightHTMLCodePre.$article->copyright.$this->articleCopyrightHTMLCodePost."\r\n";
            }
            if($article->author)
            {
                $html .= $this->articleAuthorHTMLCodePre.$article->author.$this->articleAuthorHTMLCodePost."\r\n";
            }            
            if($article->price)
            {
                $html .= $this->articlePriceHTMLCodePre.$article->price.$this->articlePriceHTMLCodePost."\r\n";
            }

            if($article->image)
            {
                if($article->image->url)
                {
                    $imageUrl = $article->image->url;
                }
                else
                {
                    $imageUrl = (string)$article->image;
                }
                if($article->image->link)
                {
                    $articleLink = $article->image->link;
                }
                else
                {
                    $articleLink = $article->link;
                }
                $html .= $this->articleImageHTMLCodePre.'<a href="'.$articleLink.'"';
                if($this->addNoFollowTag == 'true')
                {
                    $html .= ' rel="nofollow"';
                }
                $html .= '><img src="'.$imageUrl.'" alt="'.$article->imageCaptionAlt.'"></a>'.$this->articleImageHTMLCodePost."\r\n";     
            }

            if(($this->showContentOnlyInLinkTitle == 'false'))
            {
                $html .= $this->articleBodyHTMLCodePre."\r\n";
                if($article->description != "")
                {
                    $html .= $article->description."\r\n";
                }
                if($article->content != "")
                {
                    $html .= $article->content."\r\n";
                }
                if($this->showComments && $article->comments != "")
                {
                    $html = $this->addBrIfNeeded($html);
                    $html .= "<a href=\"".$article->comments."\" target=\"".$this->targetDirective."\">View comments</a>\r\n";
                }
                $html .= $this->articleBodyHTMLCodePost."\r\n";
            }
            if(($this->showFeedMetrics == 'true') && ($this->feedUpdateTime) && ($currentArticleIndex == 1))
            {
                $html = $this->addBrIfNeeded($html);
                $html .= '<font size=-4>Last feed update: '.$this->feedUpdateTime.'</font>'."\r\n";
            }
            $html .= "\r\n</div><!-- end div itemDiv-feed-".$this->feedIndex.'-article-'.($currentArticleIndex-1)."-->\r\n";
        }
        if(($this->hideArticlesAfterArticleNumber > 1)  && (($currentArticleIndex - 2) >= $this->hideArticlesAfterArticleNumber))
        {
            $html .= "\r\n</div><!-- end div hidearticles - $hiddenDivId -->\r\n";
        }
        return $html;
    }
}
?>
