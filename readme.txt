=== Plugin Name ===
Contributors: julian1828
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9BBXFXSUG2D6U
Tags: meta_query, custom fields, query
Requires at least: 3.0
Tested up to: 3.1
Stable tag: trunk

Extend your site's querying and sorting functionality using custom field values.

== Description ==

THIS PLUGIN IS IN HEAVY DEVELOPMENT!

Extend your site's querying and sorting functionality using custom field values.

The following function registers queryable fields.
`register_custom_queryable_field($fieldName, $options);`


Simple Example:
`register_custom_queryable_field('city');`
(The above registers the custom field 'city' to be searchable using query variables)
`http://www.yoursite.com/?city=Anahiem`
(displays posts which have a meta key 'city' and a value of 'Anahiem')


Advanced Example:
`register_custom_queryable_field("price", array("dataType"=>"numeric"));`
(The above registers the custom field "price" to be searchable as a number using query variables)
`http://www.yoursite.com/?price=500`
(displays posts which have a meta key 'price' and a exact value of '500')
`http://www.yoursite.com/?price_min=200&price_max=800&order_by=price`
(displays posts, sorted by 'price', which have a meta key 'price' and a value between '200' and '800')


Available Options:
<ul>
<li>dataType
<ul>
<li>text (default)</li>
<li>numeric (receive min/max query variables, see above example)</li>
</ul></li>
<li>order
<ul>
<li>If the user uses the "order_by" query variable then this option determines the order. Available options are "ASC" and "DESC"(default)</li>
</ul></li>
<li>compare
<ul>
<li>Compare method for text types. Defaults to '='. Recommend 'LIKE'</li>
</ul></li>
</ul>

There is a "order_by" query var made available.  This is used by adding `&order_by=price` to the URL.  In this instance, the query would sort based on the "price" field only.

Note: This plugin does not alter any queries when in the backend.

== Installation ==

1. Upload `custom-query-fields` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. See Description on how to enable the custom variables.

== Frequently Asked Questions ==

= When will you have some FAQ's? =

Soon.

== Screenshots ==

1. None yet.

== Changelog ==

= 0.1.2b =
* Fixed some foreach bugs that threw warnings if no fields were registered.
* The plugin is now only using one global variable.
* Added backward compatibility with version 3.0.
* Improved code efficiency.
* Added enable and disable functions to allow you to apply custom field query to external query's.

= 0.1.1b =
* Added the ability to control whether the query altering function is run only once.
* Added the compare option to the registered fields. This compare is ignored for numeric comparisons.

= 0.1b =
* Initial build.

== Upgrade Notice ==

= 0.1.2b =
Fixed alot of major bugs. Also, added backward compatibility with version 3.0. Some code efficiency improved.

= 0.1b =
Initial build.