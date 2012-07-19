=== Syndicate Press ===
Contributors: hranchFundi 
Donate link: http://henryranch.net/software/syndicate-press/
Tags: RSS,RDF,Atom,feed,syndicate,syndication,news,aggregator,aggregation,plugin,active,maintained,custom,widget,post,plugin,posts,admin,sidebar,theme,comments,images,twitter,page,google,links
Requires at least: 2.8
Tested up to: 3.4.1
Stable tag: 1.0.17

Syndicate Press lets you include RSS, RDF or Atom feeds directly in your Wordpress posts, pages, widgets or theme. 

== Description ==

Syndicate Press lets you include RSS, RDF or Atom feeds directly in your Wordpress Posts, Pages, Widgets or anywhere in your theme. Syndicate Press features an easy to use admin page and includes great features such as feed caching, filters and numerous display options.

Unlike a number of other news syndication plugins for Wordpress, Syndicate Press does not force arbitrary formatting or CSS styling on the feed contents.  This allows the feed items to be displayed in your site like they are a fully integrated part of your content.

Syndicate Press is actively maintained and regularly updated with new features and enhancements. The Syndicate Press development team at <a href="http://henryranch.net/software/syndicate-press/">henryranch.net</a> has focused on ease of use, performance, stability and functionality to bring you a great plugin that will help keep your Wordpress site up to date with the latest in news feeds from every corner of the world.

== Installation ==

This section describes how to install the plugin and get it working.

1. Install the plugin
   - Via th Wordpress plugin repository: Click 'Install' and then 'Activate' after installation completes.
   - via the Wordpress 'Upload' feature: SImply select the SyndicatePress.zip file and upload via the Wordpress interface
   - Via FTP: Upload the unzipped syndicate-press directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Follow the usage instructions at the bottom of the admin page

== Frequently Asked Questions ==

See http://henryranch.net/software/syndicate-press/

== Screenshots ==

For detailed screenshots of the admin control panel, please visit the Syndicate Press documentation page: http://henryranch.net/software/syndicate-press/

Please see the following pages for examples of the syndicated news feeds on a Wordpress blog:<br>

http://henryranch.net/news/ <br>

http://henryranch.net/news/real-time-earthquake-news/ <br>

http://henryranch.net/news/science-technology/ <br>

== Changelog ==

http://henryranch.net/software/syndicate-press/syndicate-press-releases/

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

http://henryranch.net/news/ <br>

http://henryranch.net/news/real-time-earthquake-news/ <br>

http://henryranch.net/news/science-technology/ <br>
