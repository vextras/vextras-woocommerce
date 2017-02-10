=== vextras-woocommerce ===
Contributors: ryanhungate
Tags: ecommerce,email,workflows,mailchimp,highrise,xero,accounting,google,analytics,woocommerce,order updates
Donate link: https://vextras.com
Requires at least: 4.3
Tested up to: 4.4.2
Stable tag: 4.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send targeted e-mails based on customer behavior like abandoned carts, customer reviews, rewards and more.
Sync your store with software you already use with great cloud apps including MailChimp, Xero and Slack in just a few clicks.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wordpress.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

##_Step 1_
####Enable the WooCommerce API
If it’s not already been activated, enable the WooCommerce API. This will allow us to transfer order/product/customer data from your store and send it to any app or initiate a Vextras workflow.

1. Login to your WooCommerce powered store
2. Enable the REST API by following these instructions

##_Step 2_
####Add your WooCommerce store to Vextras
Once the API is enabled, you will be able to connect your WooCommerce store with Vextras.

1. Login to your Vextras account
2. Click ‘+ Add New’ on the bottom left
3. Add a new store, choose WooCommerce
4. Install the Vextras plugin
5. Activate the plugin

####_Plugin notes_

* If you already have an account with us, login with your existing e-mail address and password.
* If you don’t have a Vextras account, we will create a new account for you on the fly inside of WordPress.
* After activation, apply plugin updates as you normal.
* All configuration of apps and workflows will occur in your Vextras dashboard.

== Screenshots ==

== Changelog ==

= 2.0 =
* A complete rewrite of the version 1 plugin.

== Upgrade Notice ==

= 2.0 =
This upgrade is required due to Vextras 7.0 release
