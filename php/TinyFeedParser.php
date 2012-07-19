<?php
/*
File: TinyFeedParser.php
Date: 7/18/2012
Version 1.9.6
Author: HenryRanch LLC

LICENSE:
============
Copyright (c) 2009-2012, Henry Ranch LLC. All rights reserved. http://www.henryranch.net


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
    var $title;
    var $subtitle;
    //description: typically contains the syndicated article content
    var $description;
    //content: not always provided.  Can contain html
    var $content;
    //headline: a copy of the description data, but with html tags stripped out
    var $headline;
    var $image;
    var $copyright;
}

class TinyFeedParser
{
    var $articles = array();
    
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
    var $exclusiveKeywordList = "";
    var $inclusiveKeywordList = "";
    
    var $showArticlePublishTimestamp = true;
    
    var $useCustomFeednameAsChannelTitle = false;
    var $customFeedName = "";
        
    var $feedTitleHTMLCodePre = '<h2>';
    var $feedTitleHTMLCodePost = '</h2>';
    var $articleTitleHTMLCodePre = '<h3>';
    var $articleTitleHTMLCodePost = '</h3>';
    
    var $numArticles = 0;
    
	var $useCustomTimestampFormat = true;
	var $defaultTimestampFormatString = 'l F jS, Y h:i:s A';
	var $timestampFormatString = 'l F jS, Y h:i:s A';
	
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
        if(strripos($xmlString, 'rdf') !== false)
        {
            $xmlString = str_replace("rdf:", "rdf_", $xmlString);
            $xmlString = str_replace("dc:", "dc_", $xmlString);
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
            $article->link = (string)$channel->link;
            $article->description = (string)$channel->description;    
            $article->pubDateStr = (string)$channel->lastBuildDate;
            $article->pubTimeStamp = strtotime($channel->lastBuildDate);
            $article->image = $channel->image;
            $article->copyright = $channel->copyright;
                    
            $this->addArticle($article);
            
            foreach($channel->item as $item)
            {
                if($this->maxNumArticlesReached())
                {
                    return;
                }
                
                $article = new Article();
                if($item->pubDate)
                {
                    $article->pubDateStr = (string)$item->pubDate;
                }            
                else
                {
                    $article->pubDateStr = (string)$item->pubDate;
                }
                
                $article->pubTimeStamp = strtotime($article->pubDateStr);
                $article->link = (string)$item->link;
                $article->title = (string)$item->title;
                $article->description = (string)$item->description;
                                
                $this->addArticle($article);
            }
        }
    }
    
    function parseRdfFeed($xmlDocObj)
    {
        $article = new Article();
        $article->title = (string)$xmlDocObj->channel->title;
        $article->link = (string)$xmlDocObj->channel->link;
        $article->description = (string)$xmlDocObj->channel->description;      
        $article->pubDateStr = (string)$xmlDocObj->channel->dc_date;
        $article->pubTimeStamp = strtotime($xmlDocObj->channel->dc_date);
        $article->image = $xmlDocObj->channel->image;
        $article->copyright = $xmlDocObj->channel->dc_rights;
                 
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
            $article->pubDateStr = (string)$item->dc_date;
            $article->pubTimeStamp = strtotime($item->dc_date);
            $article->image = (string)$item->image;
            $article->copyright = (string)$item->dc_rights;
                       
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
        
        $article->title = (string)$this->removeAllHtmlMarkup($article->title);
        $article->title = $this->truncateToLength($article->title, $this->maxHeadlineLength, 'NO_LINK');
                
        $article->subtitle = (string)$this->removeAllHtmlMarkup($article->subtitle);
        
        if($article->copyright)
        {
            $article->copyright = (string)$this->removeAllHtmlMarkup($article->copyright);
        }

        return $article;
    }
    
    function truncateToLength($text, $length, $urlLink="")
    {
        if($length != -1 && strlen($text) > $length)
        {
            $text = substr($text, 0, $length);
            if($urlLink != "" && $urlLink != 'NO_LINK')
            {
                $text .= " <a href=\"$urlLink\" target=\"_blank\" title=\"Open article in a new window\"";				
				if($this->addNoFollowTag == 'true')
				{
					$text .= ' rel="nofollow"';
				}
				$text .= ">...</a>";
            }
            else
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
    
    function getHtml()
    {
        $html = "";
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
            $html .= "<p>\r\n";
            if($article->image)
            {
                $html .= '<a href="'.$article->image->link.'"';
				if($this->addNoFollowTag == 'true')
				{
					$html .= ' rel="nofollow"';
				}
				$html .= '><img src="'.$article->image->url.'"></a>';//."\r\n";     
            }
            $html .= $headerHtmlPre;
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
            $html .= ' target=_blank>'.$article->title.'</a>'.$headerHtmlPost."\r\n";
            if($article->subtitle != '')
            {
                $html = $this->addBrIfNeeded($html);
                $html .= $article->subtitle;
            }
            if($this->showArticlePublishTimestamp == 'true' && $currentArticleIndex != 1)
            {
                if($article->pubDateStr)
                {
                    //$html = $this->addBrIfNeeded($html);
					if($this->useCustomTimestampFormat)
					{
						$html .= '<font size=-3>'.date($this->timestampFormatString, $article->pubTimeStamp).'</font>'."\r\n";
					}
					else
					{
						$html .= '<font size=-3>'.$article->pubDateStr.'</font>'."\r\n";
					}
                }
                else
                {
                    //$html = $this->addBrIfNeeded($html);
                    $html .= '<font size=-3>No timestamp info...</font>'."\r\n";
                }
            }
        
            $html = $this->addBrIfNeeded($html);
            if(($this->showContentOnlyInLinkTitle == 'false'))
            {
                if($article->description != "")
                {
                    $html .= $article->description."\r\n";
                }
                if($article->content != "")
                {
                    $html .= $article->content."\r\n";
                }
            }
            if($article->copyright)
            {
                $html = $this->addBrIfNeeded($html);
                $html .= '<font size=-3>'.$article->copyright."</font>";
            }
            if(($this->showFeedMetrics == 'true') && ($this->feedUpdateTime) && ($currentArticleIndex == 1))
            {
                $html = $this->addBrIfNeeded($html);
                $html .= '<font size=-4>Last feed update: '.$this->feedUpdateTime.'</font>'."\r\n";
            }
            $html .= "</p>\r\n";
        }
        return $html;
    }
}
?>
