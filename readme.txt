=== Syndicate Press ===
Contributors: hranchFundi 
Donate link: http://syndicatepress.henryranch.net/donate/
Tags: RSS,RDF,Atom,feed,syndicate,syndication,news,aggregator,aggregation,plugin,active,maintained,custom,widget,post,plugin,posts,admin,sidebar,theme,comments,images,twitter,page,google,links
Requires at least: 2.8
Tested up to: 3.8.1
Stable tag: 1.0.30

Syndicate Press lets you include RSS, RDF or Atom feeds directly in your Wordpress posts, pages, widgets or theme. 

== Description ==

Syndicate Press lets you include RSS, RDF or Atom feeds directly in your Wordpress Posts, Pages, Widgets or anywhere in your theme. Syndicate Press features an easy to use admin page and includes great features such as feed caching, filters and numerous display options.

Unlike a number of other news syndication plugins for Wordpress, Syndicate Press does not force arbitrary formatting or CSS styling on the feed contents.  This allows the feed items to be displayed in your site like they are a fully integrated part of your content.

Syndicate Press is actively maintained and regularly updated with new features and enhancements. The Syndicate Press development team at <a href="http://henryranch.net/software/syndicate-press/">henryranch.net</a> has focused on ease of use, performance, stability and functionality to bring you a great plugin that will help keep your Wordpress site up to date with the latest in news feeds from every corner of the world.

== Installation ==

This section describes how to install the plugin and get it working.  For more details please see http://syndicatepress.henryranch.net/documentation/install/

1. Install the plugin
   - Via th Wordpress plugin repository: Click 'Install' and then 'Activate' after installation completes.
   - via the Wordpress 'Upload' feature: SImply select the SyndicatePress.zip file and upload via the Wordpress interface
   - Via FTP: Upload the unzipped syndicate-press directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Follow the usage instructions at the bottom of the admin page

== Frequently Asked Questions ==

See http://syndicatepress.henryranch.net/documentation/faq/

== Screenshots ==

For detailed screenshots of the admin control panel, please visit the Syndicate Press documentation page: http://syndicatepress.henryranch.net/screenshots/

Please see the following pages for examples of the syndicated news feeds on a Wordpress blog:<br>

http://syndicatepress.henryranch.net/feed-tests/simple-wordpress-feeds/ <br>

http://syndicatepress.henryranch.net/feed-tests/amazon-affiliate-feeds/ <br>

http://syndicatepress.henryranch.net/feed-tests/feedburner-feeds/ <br>

== Changelog ==

http://syndicatepress.henryranch.net/documentation/changelog/

v1.0.30: Some fixes to the lightbox popup and html special character handling
- Lightbox: Ensure it displays in the center of the page when the page is scrolled.  Also, fixed multiple vertical scroll bar issue in lightbox.
- HTML special char: fixed issue from forum where & and then various chars such as reg will show the html special char

v1.0.29: Quick fix for permissions message when there was no perms issue

v1.0.28: Lightbox popup for article links
- Added new lightbox feature with tab on SP Admin Control Panel
- Fixed issue with 'hide articles after X number of articles' where styling was off and content would jump out of theme div's.
- Cleaned up some dead code from the parser
- Re-arranged the Donations tab to highlight the donors website link 
- Cleaned up some warnings visible in php/wordpress debug mode related to uninitialized vars.

v1.0.27: Custom shortcode parameter: replaceStringInTitle
- Added a new parameter to the shortcode which allows the admin to replace one or more strings in the article title with another string.

v1.0.26: Small update
- Updated support for advertising code between feeds.

1.0.25: New feature release - Lots of features...<br>
- Custom formatting now supports:<br>
--- Timestamp<br>
--- Author<br>
--- Copyright<br>
--- Price<br>
--- Image<br>
- Basic namespace support for 'pm' namespace<br>
- Updated 'Custom Formatting' tab in admin panel to make it easier to use<br>
- Updated documentation and disclaimers in the admin panel<br>
- GUID from article is placed into html comments in the rendered page<br>
- Added subtitle for article to parsed output<br>


1.0.24: New feature release<br>
- Added the ability to remove the CDATA tags from the feed.  The content is still retained that was within the CDATA tags.  This feature can be accessed on the 'Display Settings' tab.<br>


1.0.23: New feature release<br>
- Show the first X number of articles from a feed and then hide the rest.  Allow the website viewer to click a link to show the hidden articles.<br>


1.0.22: New feature release<br>
- Custom cache directory:<br>
--- The admin can now set a custom cache root directory in the "Cache" tab.  This may be useful for Wordpress multi-site configurations.<br>
--- The "Cache" tab now shows the cache root, input and output directories along with their current permissions and how many cache files are in each dir.<br>
- Bug fix:<br>
--- Fixed a bug in which the clear cache buttons were not working as expected.<br>

1.0.21: New feature release<br>
This release contains multiple new features...<br>
- Custom Formatting:<br> 
--- Feed and article title custom formatting now supports full html parameters such as the css style param and the javascript param<br>
--- Added custom formatting to the article body<br>
- Article identification:<br>
--- Added div id's to the articles.  This div surrounds the article tite, timestamp and body content.<br>
- Support:<br>
--- Added a feature on the support tab which will show the internal, global settings for SP so that an admin can include the settings in a support request email.<br>
- TinyHttpClient class:<br>
--- Changed User-Agent to Mozilla to help more servers accept the client.<br>
- Documentation:<br>
--- Added a reminder on the RSS Feeds tab that the site admin is fully responsible for following a feed publishers Copyright and Terms of Use.<br>



1.0.20: New feature release<br>
Added a new short-code parameter, truncateTitleAtWord, which allows you to set a word, which, if detected in the title will truncate the title just prior to that word.  The result is that the word, and all words following it will not be shown on the page.  This is especially useful for affiliate rss feeds where product meta information is included in the article title, but is not actually important. Also updated docs on the help tab.

1.0.19: Bug fix release<br>
Fixed a bug in the length truncation code which would truncate the article or the article headline in the middle of a word.  With this fix, the article or headline will now be truncated on whole word boundaries, where a word is defined by any string of characters, separated by a space.  This change applies to the "Limit article to _____ characters" setting and the the "Limit article headline to _____ characters" setting on the "Display Settings" tab of the SP Admin panel.

1.0.18: New feature release<br>
Added a new short-code parameter, limitArticles, which allows you to override the global article limit for the short-code that the param is included in.  Use the new param as follows: [sp# feedList=feedName limitArticles=5].  Change 5 to whatever you want the max number of articles to be.

1.0.17: New feature release<br>
Added new SEO feature which allows the admin to add the rel=nofollow tag to the article links.

1.0.16: New feature release and bug fixes<br>
Added a new feature that allows the user to customize the format of the timestamp.<br>
Fixed a bug in the shortcode definition of the feednames to include in a page.  SP now allows the user to define the feeds to include as follows: feedList=feedname <br>
Updated the help tab with the new feedList example<br>
Added directory path display when the cache permissions are incorrect.<br>

1.0.15: Small performance update<br>
Removed an external server call which was slowing down the admin page load.<br>

1.0.14: Tiny bugfix release<br>
Fixed spelling error in Syndicate Press linkback text<br>

1.0.13: Documentation release<br>
Clarified some documentation around the file permissions check<br>

1.0.12: Bug fix release.<br>
Put exception handling around the file permissions check.<br>

1.0.11: This is a new feature release.<br>
Added support for bbcodes in the text widget.<br>
Added a new check to make sure that the cache directories have the correct permissions.<br>
Updated some of the descriptions in the admin page to make them clearer.<br>

1.0.10: This is a bug-fix release.  Fixed the following issues: <br>
Failure to show feed items when "Show item description only when the viewer hovers over the item link." is selected.<br>
Error upon extra spaces and commas in the filter fields.<br>

1.0.9: Added the showImages parameter to the bbcode to enable display of feed images for all feeds included by that bbcode snippet.  i.e. [sp# feedname showImages=true]<br>

1.0.8: Added ability to define inclusive and exclusive filters within the bbcode i.e. [sp# feedname include=keyword exclude=keyword]<br>
Moved the custom formatting config out of the Display Options tab and into the new Custom Formatting tab<br>

1.0.7: Fixed an image display bug.  Placed the cache control buttons at the top of the admin UI.<br>

1.0.6: Major admin page UI refactoring to utilize tabs.  Tabs make managing the plugin much easier!<br>
Implemented first attempt at solving the go-daddy permanent redirect for rss feeds.<br>

1.0.5: Removed an extra line break after the article title and prior to the article timestamp.  <br>
Now the look of the title and timestamp is more controllable by the user.  The article title still defaults to a 
header2 level, but this can be removed in the plugin admin page.

1.0.4: Ignoring empty lines in the the feed url box.  <br>

1.0.3: Fixed a quote and slash escaping issue in the custom feed separator html and in the feed not found custom html.<br>

1.0.2: Added an article link to the '...' when an article is truncated by Syndicate Press.  Automatically replacing the feed:// protocol designator with http://<br>

1.0.1: Added an 'Update Settings' button to the top of the Admin Page.  Fixed a bug where a space between the | character and the feed URL (when using a custom feed name) resulted in the feed URL not being found.<br>

1.0: Initial release to the Wordpress plugin repository<br>

== Arbitrary section ==

Please see the following pages for examples of the syndicated news feeds on a Wordpress blog:<br>

Usage information: http://syndicatepress.henryranch.net/documentation/usage/ <br>


