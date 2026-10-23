=== LTW Order-to-Board Sync for WooCommerce and Trello ===
Contributors: lessthanweb
Tags: woocommerce, trello, order management, automation, project management
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically create Trello cards from WooCommerce orders with configurable, per-status card details.

== Description ==

Connect your WooCommerce store to Trello and turn order workflows into actionable cards. Configure card creation independently for each built-in WooCommerce order status: choose a Trello board and list, enable card creation for the statuses you need, and customize the card name, description, color, due date, and position.

The Free plugin includes WooCommerce's built-in order statuses. Upgrade to Pro to use order statuses registered by other plugins or custom code.

Cards can be created automatically as orders reach an enabled status, or manually from the order screen. If a card request fails, review the plugin logs and retry card creation after correcting the issue.

Repeated requests for the same order, status, Trello board, and list will not create another card. A card can still be created for a different enabled status or destination. If a request is interrupted, check Trello before trying again.

Use placeholders in card names and descriptions to include information from each order:

* [ORDER_ID] - order ID
* [ORDER_STATUS] - current order status
* [NAME], [FIRST_NAME], [LAST_NAME], [COMPANY] - customer name and company
* [EMAIL] - customer email address
* [ORDER_DATE] - order date and time
* [ORDER_TOTAL] - order total with currency

Connect to Trello with OAuth 2.0 before enabling card creation. Create a confidential OAuth client for your Trello app, register the callback URL shown in the plugin settings, save the client ID and secret, then authorize your Trello account. Order status settings are independent, so enabling one status does not enable cards for other statuses.

The Free plugin requires WooCommerce and a Trello account. It sends the card details you configure, including any order or customer values inserted with placeholders, to Trello through the Trello API. Review the configured card content and your Trello account permissions before enabling automatic card creation. Trello handles received information under its own privacy policy.

== Pro version ==

The optional, separately installed LTW Order-to-Board Sync Pro add-on builds on this Free plugin. Pro requires the Free plugin and adds:

* Multiple Trello board and list destinations for each order status.
* Conditional routing to Board/List destinations based on order details.
* Support for order statuses registered by other plugins or custom code.
* Selected WooCommerce order metadata in Trello card descriptions.
* Trello card URLs saved in order history and displayed as links.
* Automatic archiving of recorded Trello cards when an order is cancelled or refunded.

Pro is not required to use the Free plugin. Learn more about the Pro add-on at https://www.lessthanweb.com/order-to-board-sync-plugin.

== Requirements ==

* WordPress 6.8 or later
* WooCommerce 8.0 or later
* PHP 8.2 or later
* A Trello account and a confidential OAuth 2.0 client configured for the plugin

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin ZIP through Plugins > Add Plugin > Upload Plugin, or place the plugin files in the /wp-content/plugins/ directory.
3. Activate the plugin through the Plugins screen.
4. In Trello's apps administration page, create a confidential OAuth 2.0 client and register the exact callback URL shown in the plugin settings. Enable the `read:member:trello`, `read:board:trello`, and `write:board:trello` scopes.
5. Open the plugin's Trello OAuth 2.0 settings, enter the OAuth client ID and secret, save, and connect your Trello account.
6. In the Cards settings, choose an order status, select its Trello board and list, configure the card details, and enable card creation for that status.
7. Save the settings and test with an order that reaches the enabled status.

== Frequently Asked Questions ==

= Does this plugin create Trello cards for every order status by default? =

No. Configure and enable card creation separately for each WooCommerce order status.

= Can I use custom order statuses? =

The Free plugin includes WooCommerce's built-in order statuses. The Pro add-on also supports order statuses registered by other plugins or custom code.

= What happens if I delete a linked card directly in Trello? =

The plugin checks saved cards with Trello when it next considers creating a card for the same order status and board/list destination. Deleted and archived cards remain in the order history but do not block a replacement. Archived cards are checked again later in case they are restored. If Trello cannot confirm whether the card exists, creation is paused to avoid duplicates.

= Can repeated order or payment events create duplicate cards? =

No. Repeated requests for the same order, status, Trello board, and list will not create another card. A card can still be created if the order reaches a different enabled status or uses a different destination. If a request is interrupted and you are unsure whether the card was created, check Trello before trying again.

= Do I need the Pro add-on to use the Free plugin? =

No. The Free plugin works without Pro. Pro is a separate add-on for multiple destinations, order metadata, card links in order history, and automatic card archiving.

= What order information is sent to Trello? =

The plugin sends the card information configured for an enabled order status. This can include order or customer information when you add the supported placeholders to a card name or description. Pro can also send selected order metadata. Only configure and share information you intend to send to Trello.

== Extension API ==

The Free plugin provides documented actions and filters for extensions. See docs/EXTENSION_API.md in the plugin package. Pro extends the Free plugin through these extension points.

== Support ==

https://www.lessthanweb.com/order-to-board-sync-plugin

== Changelog ==

= 1.1.0 =
* Added extension hooks to retain validated per-destination settings and filter the Board/List destinations used before card creation.
* Added support for grouped fields and custom add-button labels in repeatable Board/List settings.
* Displayed notes configured for select fields.
* Added extra hooks that extensions can use to add destination-specific behavior.
* Removed compiled .mo file for English.

= 1.0.0 =
* Initial release of LTW Order-to-Board Sync for WooCommerce and Trello.
