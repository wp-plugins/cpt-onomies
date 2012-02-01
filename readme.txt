=== CPT-onomies: Using Custom Post Types as Taxonomies ===
Contributors: bamadesigner
Donate link: https://www.paypal.com/us/cgi-bin/webscr?cmd=_flow&SESSION=LSa9VMv8-O9RLhb_Ns4y4Hdiw3DybZ9djrhM-NWy1Xpc4PmyJ9X_2zklJ5W&dispatch=5885d80a13c0db1f8e263663d3faee8db2b24f7b84f1819343fd6c338b1d9d60
Tags: custom post types, post, types, post type, tax, taxonomy, taxonomies, cpt-onomy, cpt-onomies
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.0.1

A CPT-onomy is a taxonomy built from a custom post type, using post titles to assign taxonomy relationships just as you would assign taxonomy terms.

== Description ==

*CPT-onomies* is a WordPress plugin designed for anyone wishing to utilize the power of custom post types. Featuring a full-fledged interface, CPT-onomies allows you to create custom post types and register CPT-onomies **without touching one line of code!**

= What Is A CPT-onomy? =

A CPT-onomy is a taxonomy built from a custom post type, using post titles to assign taxonomy relationships just as you would assign taxonomy terms.

*Wish to use a custom post type created by a theme or another plugin?* That's ok! Any registered custom post type can be used as a CPT-onomy.

= Is CPT-onomy An Official WordPress Term? =

No. It's just a fun word I made up.

= Need Custom Post Types But Not (Necessarily) CPT-onomies? =

CPT-onomies offers an extensive custom post type manager, allowing you to create and completely customize your custom post types without touching one line of code!

= Why CPT-onomies? =

It doesn't take long to figure out that custom post types can be a pretty powerful tool for creating and managing numerous types of content. For example, you might use the custom post types "Movies" and "Actors" to build a movie database but what if you wanted to group your "movies" by its "actors"? You could create a custom "actors" taxonomy but then you would have to manage your list of actors in two places: your "actors" custom post type and your "actors" taxonomy. This can be a pretty big hassle, especially if you have an extensive custom post type.

**This is where CPT-onomies steps in.** Register your custom post type as a CPT-onomy and CPT-onomies will build your taxonomy for you, using your post type's post titles as the terms. Pretty cool, huh?

= Using CPT-onomies =

What's really great about CPT-onomies is that they work just like any other taxonomy, allowing you to use WordPress taxonomy functions, like [get_terms()](http://codex.wordpress.org/Function_Reference/get_terms "get_terms()") and [wp_get_object_terms()](http://codex.wordpress.org/Function_Reference/wp_get_object_terms "wp_get_object_terms()"), to access the information you need. CPT-onomies even includes a tag cloud widget for your sidebar.

*Note: Unfortunately, not every taxonomy function can be used at this time. [Check out the CPT-onomy documentation](http://rachelcarden.com/cpt-onomies/documentation "CPT-onomy documentation") to see which WordPress taxonomy functions work and when you'll need to access the plugin's CPT-onomy class.*

== Installation ==

1. Upload 'cpt-onomies' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to *Settings > CPT-onomies*
1. Create a new custom post type or edit an existing custom post type
1. Register your custom post type as a CPT-onomy by "attaching" it to a post type, under "Register this Custom Post Type as a CPT-onomy" on the edit screen
1. Use your CPT-onomy just like any other taxonomy (refer to the [CPT-onomy documentation](http://rachelcarden.com/cpt-onomies/documentation "CPT-onomy documentation") for help)

== Frequently Asked Questions ==

CPT-onomies is hot off the presses so check in later for frequently asked questions.

== Screenshots ==

1. CPT-onomies offers an extensive custom post type manager, allowing you to create new custom post types or use custom post types created by themes and other plugins.
2. CPT-onomies lets you manage and customize your custom post types without touching one line of code.
3. Create your custom post types to power your CPT-onomy.
4. Assign your CPT-onomy terms just like any other taxonomy.
5. The admin shows which CPT-onomy terms have been assigned.

== Changelog ==

= 1.0.1 =
* Fixed bug that didn't delete relationships when CPT-onomy "term" is deleted.

= 1.0 =
* Plugin launch!

== Upgrade Notice ==

= 1.0.1 =
Fixed bug that didn't delete relationships when CPT-onomy "term" is deleted.