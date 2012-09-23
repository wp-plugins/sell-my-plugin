=== Sell My Plugin ===
Contributors: Rob Landry
Donate link: http://www.landry.me
Tags: plugins, self-hosted, repository
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 0.9.3

This plugin allows you to host your own plugins on your wordpress installation.

== Description ==

I was working on a plugin that had a pro (paid) version. This plugin had 2 code bases and was hosted on an external site. This site charged upwards of 50% of what I was charging. 

So I became interested in finding a way to provide updates and use only one code base. I also wanted to minimize the cost to sell the plugin so that the money was not going into someone elses pocket. With Sell My Plugin, you host your own plugins. You use PayPal to charge for the purchases. There is a transactions list so that you can keep track of your sales. If you sell 10 copies of your plugin in a month at **$10/ea**, this plugin will save you **$360** per year vs PlugPress and **$600** vs CodeCanyon.

This plugin also uses the familiar domain.com/extend/plugins/ layout just like wordpress.

= Free Features =

* Your.Domain.com/extend/plugins/ to list all of your plugins just like wordpress.
* Your.Domain.com/extend/plugins/plugin-slug to display the plugin info.
* A custom search on the plugins page to search for available plugins.
* Number of downloads tracked to see which plugins are HOT!!
* News updates to provide you with up-to-date info on Sell My Plugin.
* Shortcodes are provided to display the plugin where ever you want.
* A [Forum](http://redmine.landry.me/projects/sell-my-plugin/boards) and [Bug Tracker](http://redmine.landry.me/projects/sell-my-plugin/issues) to get help.

= Pro Features =

* If the plugin is a paid plugin, ?transaction=<transaction id> is added to:
* Your.Domain.com/extend/plugins/plugin-slug/version/ to allow downloads.
* Your.Domain.com/extend/plugins/plugin-slug/update/ to display the update json to allow for plugin updates.
* Your.Domain.com/extend/plugins/plugin-slug/update/ to display the update json to allow for plugin updates.
* [sell-my-plugin] Displays the plugins list.
* [sell-my-plugin slug=the-slug] Displays a small info box about the plugin.
* [sell-my-plugin slug=the-slug format=full] Displays the full plugin info.
* [sell-my-plugin slug=the-slug version=1.0 format=full] Displays the full plugin info for a particular version.
* Transactions list for book keeping and tracking.
* PayPal express checkout for purchase!
* Downloads instantly activated upon completion of purchase.
* UPDATES!! provided to plugins once a new version zip is uploaded!
* PayPal Sandbox to test plugin purchases without mixing with REAL purchases.
* Custom pricing of plugins in backend.
* Ability to give paid plugins for free providing a FREE KEY!
* Custom email following purchase including shortcodes to allow for injection of PayPal data!

* And many more!...

== Installation ==

1. Install Sell My Plugin like you would any other plugin.

== Frequently Asked Questions ==

= How do I test the PayPal features? =
If this is the paid version:
You must sign up for a sandbox account with PayPal. Enter your credentials in Sell My Plugin Settings. Then navigate to http://yourdomain.com/extend/plugins and select your plugin. If you have set a price on the plugin page, you must click on the purchase button.

= How do I provide updates for the plugins I sell with this Plugin? =
If this is the paid version:
This is done automatically with a little setup from you. When uploading a plugin using Sell My Plugin, this is automatically added to the plugin file, and the update script is included in a folder plugin-updates/.
`
#------------------------------------------------------------------------------
# Check for Updates
# Since: 1.0
# Check for Updates on personal server
#------------------------------------------------------------------------------
require_once('plugin-updates/class-plugin-updates.php');
$key=get_option('YOUR-PLUGIN-SLUG_key'); 

$update_link = "http://YOUR.DOMAIN.COM/extend/plugins/YOUR-PLUGIN-SLUG/update/?transaction=$key";
$update = new plugin_check_for_updates(
	$update_link, 
	__FILE__, $slug
);
# End Check for Updates `

As you can see, there is a transaction id required for updates. This only applies to paid plugins. You will also need to capture the transaction id. The user will need to navigate back to their plugin settings page, and paste ?transaction=<THEIR TID>. You will have to provide a means of capturing this TID.

`
if (isset($_GET['transaction'])) update_option('YOUR-PLUGIN-SLUG_key', $_GET['transaction']);
`

= Is all of this required for plugins I just want to host and not sell? =
Yes and no. You do not need to capture a transaction id if there is no purchase. You do need to however include the update check and file which is automatically added.

= I updated the plugin and all of the options were missing. Even worse, none of my plugins work anymore. What gives? =
I have run into this issue a few times, however the fix is easy. When the plugin is first installed, a secret folder name is created, and the name saved to your database. This way, the plugin knows where the zips are located. When the options are somehow deleted, the files are still in tact as well as the databases. If this issue becomes a hot topic, I can create a means of doing it from within the plugin, but for now here is the fix.

Using your favorite ftp program or cpanel, navigate to the wp-content/uploads/ folder. Look for the 2 folders with random characters. Browse each of them and find which is empty. Copy the folder name. Delete the folder. Change the name of the folder with the zips to the name that was copied. Tada! Problem solved.

= Why is the plugin zip folder random characters? =
This is done to prevent hot linking to the plugin zips. For instance. A user knows where the files are located by knowing the folder name. All they would have to do is type in the location of the zip in the address bar, and the download would start. This is why the folder name is hashed to a random name so that the possibility of stumbling onto the plugin is less likely.

= What happens when I select PayPal Sandbox and vice versa? =
* When PayPal Sandbox is selected: 
	The credentials must be for sandbox.
	The address your payment system uses changes to allow for testing.
	PayPal Sandbox notices are enabled to let you and others know this is only for testing.
	The downloads increment, but only for sandbox downloads, it does not count towards real downloads.
	Transactions are saved, but indicating they are sandbox transactions. They do not show when not using sandbox.
* When PayPal Sandbox is turned off:
	The credentials are your real credentials.
	The payment gateway address is the real one.
	All Sandbox notices are turned off.
	The real downloads increment.
	Transactions are saved without the Sandbox flag.
== Screenshots ==

1. The Plugins Admin Page.
2. The Settings Admin Page.
3. The Transactions Admin Page.
4. Displaying a plugin with full content.
5. Displaying a plugin in short content.

== Changelog ==

= 0.9.0 =

* Initial Checkin.

== Requirements ==

There are a few things that you need to do to make sure that you can use this plugin.  

= PHP 5 >= 5.2.0, PECL zip >= 1.5.0 =

This plugin makes use of the ZipArchive class, introduced in PHP 5.2.0.  You must be running at least this version to use the plugin.

= Valid readme.txt and plugin headers = 

Like plugins hosted on wordpress.org, you must include a valid readme.txt file and the proper plugin headers in your main plugin file.  Check out a [sample readme.txt file](http://wordpress.org/extend/plugins/about/readme.txt) and use the [readme validator](http://wordpress.org/extend/plugins/about/validator/).  The Sell My Plugin plugin uses the readme.txt file and the plugin headers to render information about the plugin.
