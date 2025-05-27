=== Contact Congress ===
Contributors: rsauers
Donate link: https://www.paypal.com/donate/?business=QZKCZCYZCEU22&no_recurring=0&item_name=Thank+you+for+supporting+my+goal+to+develop+free+applications+to+create+positive+change%21&currency_code=USD
Tags: congress
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 8.1
Recommended: PHP 8.1+
Required Extensions: zip, xml, gid, mbstring
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin for visitors of your site to contact members of congress with pre-filled emails.

== Description ==

A plugin for visitors of your site to contact members of congress with pre-filled emails.

Features:
* Drag and Drop a block in the WordPress page editor to easily add a contact congress form to your pages!
* Create campaigns and track how many emails have been sent!
* Pre-filled email templates make it a breeze for your readers to send well-crafted emails to their representatives.
* Readers can use an Autocomplete address to quickly find their representatives.
* Support for state and federal legislature!
* Representatives are automatically synced with state/federal websites so you don't have to worry about plugin maintenance.

== Installation ==

= Install and Activate =
You can install and activate the plugin on the WordPress Plugins dashboard. You can find instructions [here](https://learn.wordpress.org/lesson/choosing-and-installing-a-plugin-copy/).

= Essential Setup =
After activating the plugin, follow the next steps to set up your plugin:
1. Select "Congress" from the left admin panel.
1. Add a 'Google API Key' by following these [instructions](https://support.google.com/googleapi/answer/6158862?hl=en).
Please note that you are responsible for knowing [Google's Places API pricing](https://developers.google.com/maps/documentation/places/web-service/usage-and-billing).
The plugin uses "Autocomplete Session Usage" and "Place Details Essentials" for every user of your site that sends an email.
There are expected to be 12 and 1 requests for each user, but that is not always the case.
I recommend setting up [Quotas](https://cloud.google.com/api-keys/docs/quotas).
Finally, you are also responsible for [Securing your API key](https://support.google.com/googleapi/answer/6310037?sjid=12161290377665172779-NC).
1. Add a 'Congress.gov API Key' [here](https://gpo.congress.gov/sign-up/).
1. Set up Google reCAPTCHA keys [here](https://www.google.com/recaptcha/admin/create).
You should use Score Based (v3) as the captcha type, and you should specify your site's domain.
After those steps, you can copy the "Site Key" and the "Secret Key".

= Setting Up Your First Campaign =

The first thing you will want to do is activate states that you are doing policy work for. Follow these steps:
1. Select the "States" submenu in the left admin panel.
1. Add an email for sync alerts (RECOMMENDED).
1. Find the states you are working with and check their checkbox.
1. At the bottom of the table, select "Activate States" in the "Bulk Action" dropdown, and then click "Perform Action".
1. Like before, use the "Bulk Action" dropdown and "Enable Federal-Level Syncing" if you are working on federal legislature.
1. Like before, use the "Bulk Action" dropdown and "Enable State-Level Syncing" if you are working on state legislature.
Please note that State API may say "Not Supported". This will cause State-Sync to be disabled (crossed out or it will say "Disabled").

The second thing you will want to do is set up representatives. Follow these steps:
1. Go to the "Representatives" submenu.
1. Automatically set up representatives: Find the "Sync Representatives" menu and change both dropdowns to "All". Then click "Sync".
1. Add missing emails: Federal representatives don't have emails, you most likely will have to manually add their staffer emails.
First, find the "Filter By" menu, and filter by "Federal" and "All" for Level and State respectively.
Then you can click the "Emails" button for each representative and add emails.
1. Manually add any missing representatives.  In step 6 above, you may have identified that State-Syncing doesn't work for some of your states. These will have to be manually added using "Add Representative".

Finally, you can create your first campaign:
1. Go to the "Campaigns" submenu.
1. Set the level of policy work and the campaign next to the "Add Campaign" button, then click the button.
1. The "Email Templates" page will open. Here is where you can create email templates that will be randomly selected from for each email.
You can use the following placeholders: "[[rep_first]]", "[[rep_last]]", "[[rep_title]]", "[[sender_first]]", "[[sender_last]]", and "[[address]]" (user address) that will be updated appropriately.


= Creating Your First Email Form =
Create a page using the WordPress Page Editor (See this [tutorial](https://learn.wordpress.org/lesson/creating-posts-and-pages-with-the-wordpress-block-editor/)).

As shown in the tutorial, there is a gallery of "Blocks".
On that panel, search for "Congress Contact Block", and drag it onto the page.

On the right panel, select the "Block" tab, then select the settings tab, and select the campaign from the dropdown.
You may also select the appearance tab and select the button colors.

== Frequently Asked Questions ==

= How can I use this plugin with page builders like Divi? =

Shortcodes will be coming in a future update, but you can use the following workaround:

1. Install the [Reusable Blocks Extended plugin](https://wordpress.org/plugins/reusable-blocks-extended/)
1. Select to "Blocks" in the left admin panel.
1. Click "Add Pattern", add the "Contact Congress Block" and save.
1. Copy the shortcode.
1. Paste the shortcode into Divi's "Code" block.

= What do I do if the State API isn't supported? =

See "Where can I report plugin issues or request features".

= Where can I report plugin issues or request features? =

All issues and feature requests are currently tracked on [GitHub](https://github.com/r-sauers/contact-congress-plugin/issues).
If an issue doesn't already exist, you may create an issue or contact a contributor.

= Can I help contribute? =

Yes! Please go to [Contributing.md](https://github.com/r-sauers/contact-congress-plugin/blob/main/contributing.md) on GitHub!

== Screenshots ==

1. Encourage your readers find their representatives, and send emails promoting your campaign!

2. Add a form for your readers to contact congress with one drop on any page.

3. Representatives are automatically synced with state/federal websites.

4. Create a list of well-crafted emails that will be sent out!

5. Manage which states are supported.

== Changelog ==

= 1.0.0 =
* Initialized Plugin
* Created "Congress" menu for setting up keys.

* Created "States" submenu.
* Implemented automatic daily representative syncing.
* Added federal representative syncing support for all states.
* Added state representative syncing support for Minnesota.

* Created "Representatives" submenu.
* Enabled filtering representatives by level/state/title.
* Enabled syncing representatives by level/state.

* Created "Campaigns" submenu.
* Number of email sent is tracked in the campaign header.
* Implemented campaign email templates with placeholders (rep_last, rep_first, rep_title, sender_first, sender_last, address).

* Created "Contact Congress Block" for sending emails.

== Upgrade Notice ==

No notices.
