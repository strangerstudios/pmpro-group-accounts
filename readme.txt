=== Paid Memberships Pro - Group Accounts Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, group accounts, corporate accounts, team memberships
Requires at least: 5.4
Tested up to: 6.8
Stable tag: 1.5.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sell group memberships where one member pays for a collection of people to access your content individually.

== Description ==

This plugin allows you to sell memberships to corporate organizations, families, teams, or any group of people where one member pays for a collection of people to access your content individually.

== Installation ==

= Prerequisites =
1. You must have Paid Memberships Pro installed and activated on your site.

= Download, Install and Activate! =
1. Download the latest version of the plugin.
1. Unzip the downloaded file to your computer.
1. Upload the /pmpro-group-accounts/ directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

View full documentation at: https://www.paidmembershipspro.com/add-ons/group-accounts/

== Changelog ==
= 1.5.1 - 2025-09-08 =
* BUG FIX/ENHANCEMENT: Now protecting against group leaders being added to their own groups on the Manage Group page. #79 (@dparker1005)
* BUG FIX: Fixed the placeholder for the "Transfer" bulk action input field. #78 (@dparker1005)

= 1.5 - 2025-09-04 =
* FEATURE: Admins can now migrate users between groups on the Manage Group page. #69 (@dparker1005)
* FEATURE: Admins can now add existing users to a group on the Manage Group page. #69 (@dparker1005)
* ENHANCEMENT: Now showing the date that a user joined or left a group on the Group Members table. #74 (@dparker1005)
* ENHANCEMENT: Now allowing filtering the Group Members table by current members and old members. #77 (@dparker1005, @kimcoleman)
* ENHANCEMENT: Added search capabilities to the Group Members table. #77 (@dparker1005, @kimcoleman)
* ENHANCEMENT: Added pagination to the Group Members table. #77 (@dparker1005, @kimcoleman)
* ENHANCEMENT: Child memberships are now removed asynchronously via Action Scheduler when a group parent loses their membership level to avoid timeouts. #71 (@dparker1005)

= 1.4.1 - 2025-08-07 =
* ENHANCEMENT: Now allowing sending test emails when editing the "Invite Member" email template. #57 (@MaximilianoRicoTabo)
* ENHANCEMENT: Improved performance of the `pmprogroupacct_level_can_be_claimed_using_group_codes()` function. #67 (@dalemugford)
* BUG FIX: Fixed an issue where the number of group members listed on the "Manage Group" page would be limited to 100. #68 (@dparker1005)
* BUG FIX: Fixed an issue where parent users would retain their current number of group seats when repurchasing their current level with 0 seats selected. #64 (@kvnbra)

= 1.4 - 2025-05-23 =
* ENHANCEMENT: Added parent account information to child accounts on the Members List admin table as well as the Member's List export. (@dwanjuki)
* ENHANCEMENT: Added group code and parent account information to the Orders List admin table as well as the Orders List export. (@dwanjuki)
* ENHANCEMENT: Added support for the "copy" a membership level logic, to copy the group account settings for parent levels. (@dwanjuki)
* BUG FIX: Fixed various PHP warnings when viewing the Manage Group page and child accounts or levels were deleted. This will now show "[deleted]" in these cases. (@andrewlimaza, @dwanjuki)

= 1.3 - 2025-03-04 =
* FEATURE: Added support for creating groups and adding members to a group during member imports when using the Import Members From CSV Add On. #31 (@MaximilianoRicoTabo)
* ENHANCEMENT: Now using the new `PMPro_Email_Template` class to show email template variables when editing email templates in PMPro v3.4+. #56 (@MaximilianoRicoTabo)
* BUG FIX/ENHANCEMENT: Changed the default price application setting to `initial`. This helps to avoid cases where a checkout level is not recurring but the Group Accounts setup accidentally makes it recurring. #50 (@andrewlimaza)
* BUG FIX/ENHANCEMENT: Now setting the existing member record to `active` when calling `PMProGroupAcct_Group_Member::create()` for a record that already exists. #35 (@andrewlimaza)
* BUG FIX: Fixed a PHP fatal error that would show when the "Manage Group" page was viewed without Paid Memberships Pro active. #54 (@dparker1005)
* BUG FIX: Fixed incorrect text domains throughout the plugin. #55 (@davidmutero)

= 1.2 - 2024-10-24 =
* FEATURE: Now allowing group owners to add new users to their group from the Manage Group page. #46 (@dparker1005)
* ENHANCEMENT: Added translation files for Spanish. #45 (@MaximilianoRicoTabo)
* BUG FIX: Fixed a fatal error when trying to apply an invalid group code at checkout. #48 (@dparker1005)

= 1.1 - 2024-07-18 =
* ENHANCEMENT: Updated UI for compatibility with PMPro v3.1. #43 (@dparker1005, @kimcoleman)
* BUG FIX/ENHANCEMENT: Adding the `pmpro_alter_price` class to the "seats" field at checkout. #39 (@dwanjuki)

= 1.0.1 - 2024-03-12 =
* ENHANCEMENT: Added a "Group Accounts" tab to the Edit Member page when using PMPro v3.0+. #23 (@kimcoleman)
* BUG FIX/ENHANCEMENT: Now creating a new group when a user has a group level and a group does not already exist. #27 (@dparker1005)
* BUG FIX: Fixed an issue where group codes could claim levels other than the ones specified for their corresponding parent level. #28 (@dparker1005)
* BUG FIX: Fixed some cases where payment fields may not show when purchasing seats at checkout. #33 (@dparker1005)

= 1.0 - 2023-12-06 =
* Initial release.
