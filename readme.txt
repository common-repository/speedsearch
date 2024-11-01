=== SpeedSearch ===
Contributors: tmmtechnology
Tags: woocommerce, search, filters, autocomplete, products
Tested up to: 6.5.2
Stable tag: 1.7.52
Requires PHP: 7.4
Requires at least: 6.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Fast Search filter for WooCommerce. Fast autocomplete and products search. Tags, attributes, categories, custom filters. Customize everything.

== Settings ==

- The plugin has many settings. It has autocomplete and fast products search for WooCommerce. All settings available are shown on the screenshots.

== External Services ==

#### Tawk.to Service Information

- **Service**: Live chat support
- **Privacy Policy**: [https://www.tawk.to/privacy-policy/](https://www.tawk.to/privacy-policy/)
- **Terms of Service**: [https://www.tawk.to/terms-of-service/](https://www.tawk.to/terms-of-service/)

By using this feature, data may be transmitted to Tawk.to servers, adhering to their privacy policy and terms of service. Users are encouraged to review these documents to ensure compliance with their local laws and regulations regarding data transmission and privacy.

== Required PHP extensions ==

- curl
- dom
- json
- libxml
- mbstring

== Bug reports / Questions / Suggestions ==

[wp@tmm.ventures](mailto:wp@tmm.ventures)

== Changelog ==

= 1.7.52 2024-04-25 =

#### Bugfixes

* Fix post trash webhook.

= 1.7.51 2024-04-25 =

#### Bugfixes

* New feed logic.

= 1.7.50 2024-04-24 =

#### Bugfixes

* Set unique = true for actions wherever possible.

= 1.7.49 2024-04-18 =

#### Bugfixes

* Fix products feed gen.
* Add extra feed debug data.

= 1.7.48 2024-04-15 =

#### Bugfixes

* When loading from the HTML cache, variations do not swap the product image on hover.
* Filters can't be opened during the initial loading (when listeners are added).

= 1.7.47 2024-04-12 =

#### Bugfixes

* Fix the styling.

= 1.7.46 2024-04-05 =

#### Bugfixes

* When no alt for autocomplete search (and the setting is set), use product title.

= 1.7.45 2024-04-03 =

#### Bugfixes

* Fix the feed.

= 1.7.44 2024-03-27 =

#### Bugfixes

* Cannot save Customizer.

= 1.7.43 2024-03-15 =

#### Bugfixes

* Cannot save Appearance options.
* postsUpdate pageTop is not present().

= 1.7.42 2024-03-08 =

#### Enhancements

* Reduce unnecessary migrations.

= 1.7.41 2024-03-07 =

#### Enhancements

* Reduce the chance of duplicate intervals.

= 1.7.40 2024-03-03 =

#### Enhancements

* Add a logic to not add duplicates to the feed.

= 1.7.39 2024-02-27 =

#### Enhancements

* Use getElementsByClassName instead of querySelectorAll.
* Use getElementsByClassName instead of querySelectorAll when possible.
* Optimized handling of unique product IDs to improve performance and prevent duplicates using associative arrays.

= 1.7.38 2024-02-16 =

#### Enhancements

* Clear html cache on site wide / theme setting change

#### Bugfixes

* Tags are in filter terms when doing filtering.
* When selecting the text in autocomplete, the result is not retained.
* Do not clear the autocomplete text on enter press.

= 1.7.37 2024-02-14 =

#### Bugfixes

* Fix function.js

= 1.7.36 2024-02-14 =

#### Enhancements

* Wrap all fetch in catch.
* Errors should reject.
* Disable running actions on plugin deactivation when possible.
* Disable document body scroll when mobile menu is opened.

#### Bugfixes

* Bug: On filter select, preserve the currently selected filters (instead of resetting them) - doesn't work when adding to text a category.
* Fix autocomplete select bug.

= 1.7.35 2024-02-10 =

#### Bugfixes

* "speedsearch.updatePosts 'pageTop' is called before json-cache-settings-retrieval.js added to the page".
* Gutenberg styling support, and add 2024 theme.

= 1.7.34 2024-02-09 =

#### Bugfixes

* Fix progress bar.

= 1.7.33 2024-02-08 =

#### Bugfixes

* Fix progress bar.

= 1.7.32 2024-02-07 =

#### Enhancements

* Remove unnecessary queries.

= 1.7.31 2024-02-07 =

#### Dev changes

* Enable /sync/status back.

= 1.7.30 2024-02-06 =

#### Bugfixes

* Fix clicking "Reset all filters" crashes the page.
* Do not infinity update posts on error response.

#### Dev changes

* Temporarily disable /sync/status endpoint.

= 1.7.29 2024-02-06 =

#### Bugfixes

* Fix undefined "invalid products" variable.

= 1.7.28 2024-02-04 =

#### Bugfixes

* Fix the request params cache.

= 1.7.27 2024-02-03 =

#### Enhancements

* New progress tracking logic.

= 1.7.26 2024-02-02 =

#### Bugfixes

* Fix the minor JS posts rendering bug.

= 1.7.25 2024-01-31 =

#### Enhancements

* Use speedsearch DB table for product.deleted retention instead of an option.

= 1.7.24 2024-01-25 =

#### Enhancements

* Revert feed back to the JSONL format (and regenerate it).

= 1.7.22 2024-01-22 =

#### Enhancements

* Render the UI correctly on server errors (response code != 200).

= 1.7.21 2024-01-22 =

#### Enhancements

* Revert feed back to the CSV format.
* Improved console output debug (for more endpoints).

#### Bugfixes

* Fix often update_indexes() call.

= 1.7.19 2024-01-16 =

#### Enhancements

* Revert feed back to the CSV format (temporarily).

= 1.7.17 2024-01-10 =

#### Bugfixes

* Fix YITH wishlist bug when makes too many AJAX requests at once.

= 1.7.15 2024-01-10 =

#### Enhancements

* When noPosts for the current chunk, add some notice like "Couldn't get posts for the current page. Please try again later".

= 1.7.14 2024-01-09 =

#### Bugfixes

* Fix a bug when speedsearch.postsData returns no products but just HTML wrapper, and that causes postsUpdate infinity loop.

= 1.7.13 2024-01-09 =

#### Enhancements

* Add indexeddb key for autoload.
* Add delete rows on initial feed gen.
* No cache for 'no posts' results.
* Feed: Use sha1 for hashes (not md5).

#### Bugfixes

* Eliminate JS error (undefined function call).
* E_DEPRECATED (parse_str(): Passing null to parameter #1 ($string) of type string is deprecated) in file /src/integrations/plugins/wordpress-seo.php on line 29
* Feed hooks: wp_trash_post does product.restored now; no duplicate product.deleted anymore.

= 1.7.12 2024-01-06 =

#### Enhancements

* New feed generation logic (with index file and jsonl format).

= 1.7.11 2023-12-29 =

#### Enhancements

* Fix search button overlap.

= 1.7.11 2023-12-29 =

#### Enhancements

* Use microtime for feed hashes.

= 1.7.9 2023-12-25 =

#### Enhancements

* New feed format (json instead of csv).

= 1.7.8 2023-12-19 =

#### Enhancements

* Make speedsearch.getPostDebugData() to return instead of printing.
* Fix a bug with a lot of theme integration calls (speedsearch.hookAFuncAfterThePostsAddition()) when no posts were added.
* Limit posts decrease loop up to the max number the page is expected to have (find a better solution later).

= 1.7.7 2023-12-15 =

#### Enhancements

* Use DB table as a buffer.

= 1.7.6 2023-12-12 =

#### Enhancements

* Add option-based buffer for feed generation.

= 1.7.5 2023-12-12 =

#### Enhancements

* Autoload most important cache from IDB into memory.
* Make the soundex warning unclickable (was searching for the correction previously).

#### Bugfixes

* Fix an error when the toggle container was hidden, and you get the error.
* Fix autocomplete rendering when for 'text' it was not active.
* Fix previous & next pagination.
* Make autocomplete to wait for results before making a search when the Enter was pressed.
* Revert feed generation to be without locking mechanism logic.

= 1.7.3 2023-11-22 =

#### Enhancements

* Export IDB cache to local variable.

#### Bugfixes

* Mark eligible event listeners as passive.
* Fix mobile menu overlap.

= 1.7.2 2023-11-17 =

#### Bugfixes

* Fix a bug when clicking reset filters from an archive page when the archive got no results the site crashes.

= 1.7.1 2023-11-16 =

#### Bugfixes

* Fix querySelectorAll on body error.

= 1.7.0 2023-11-13 =

#### Bugfixes

* Add a centralized lock for the feed generation, to avoid race conditions (and therefore duplicate indexes).

= 1.6.99 2023-11-13 =

#### Bugfixes

* Avoid using querySelectorAll on document.

= 1.6.98 2023-11-12 =

#### Bugfixes

* Fix the lack of placeholders on non-shop pages.
* Use the correct URL for categories and tags inside of archives.

= 1.6.97 2023-11-10 =

#### Bugfixes

* Fix rewrite rules.

= 1.6.96 2023-11-09 =

#### Bugfixes

* Fix IDB cache cleanup (retain initial-posts-html).

= 1.6.95 2023-11-06 =

#### Bugfixes

* If you select child category within the pare_nt (and the parent is enabled) = select child category, even when the setting "select multiple categories" is on.
* Delete a dot from Math.random() to avoid confusing caching intermediates.
* Rename "0 seconds" to "Served from cache".
* Many other mini fixes.

= 1.6.94 2023-10-14 =

#### Bugfixes

* Enable idb cache (a bit improved).

= 1.6.93 2023-10-13 =

#### Bugfixes

* Temporarily disable idb cache to see if that helps to eliminate Chrome + Win crash.

= 1.6.92 2023-10-13 =

#### Bugfixes

* Delete "autoload" from IDB table as it's not used and unnecessary overcomplicated the logic.

= 1.6.91 2023-10-13 =

#### Bugfixes

* Get rid of 'Failed to initialize plugin: speedsearch_add_shortcodes' error for TinyMCE plugin.

= 1.6.90 2023-10-11 =

#### Bugfixes

* Mobile menu z-index fix.

= 1.6.89 2023-10-11 =

#### Bugfixes

* Fix feed regeneration, to add attributes data before the terms.

= 1.6.88 2023-10-10 =

#### Bugfixes

* Fix feed regeneration, to create from 0.csv (and not from 1.csv).

= 1.6.87 2023-10-08 =

#### Bugfixes

* Fix IDB.

= 1.6.86 2023-10-08 =

#### Enhancements

* Do not show in preview variations without thumbnails.
* Improve (optimize) IDB logic.

#### Bugfixes

* Get rid of 'Failed to initialize plugin: speedsearch_add_shortcodes' error for TinyMCE plugin.
* Fix an error when tag ID cannot be converted into a slug, and the page crashes.
* Fix an issue when couldn't preview products.
* Align to the "center" the error image.
* Fix the issue when the placeholders posts were not deleted after the "No filters match your filters" error was displayed.

= 1.6.85 2023-09-29 =

#### Enhancements

* Cache flush - also flush HTML cache (in files and object cache) on the interval.

#### Bugfixes

* Fix feed generation (to have max 1k of lines per file).

= 1.6.84 2023-09-23 =

#### Bugfixes

* Improve the categories and tags archive URLs.

= 1.6.83 2023-09-22 =

#### Bugfixes

* Use category and tag archive link instead of "?categories" and "?tags" for links HTML.
* Fix a console error for active filters block on tag archive page.
* Hide "speedsearch-ul-container" when all of its children are hidden

= 1.6.80 2023-08-31 =

#### Bugfixes

* Fix IndexedDB cache expiration.

= 1.6.79 2023-08-31 =

#### Bugfixes

* Feed regeneration fix.

= 1.6.78 2023-08-29 =

#### Enhancements

* Add Equipo theme integration.

= 1.6.77 2023-08-26 =

#### Enhancements

* Add product tags, categories, and terms to the feed on the initial generation.

= 1.6.72 2023-08-25 =

#### Bugfixes

* Fix feed generation.

= 1.6.71 2023-08-21 =

#### Bugfixes

* Fix feed generation.

= 1.6.68 2023-08-13 =

#### Enhancements

* Improve posts container classes fetch reliability.

= 1.6.67 2023-08-11 =

#### Enhancements

* Improve hashes and feed generation by getting posts by batches, and not by one; and by decreasing batch size from 100 to 25.

#### Bugfixes

* Fix a bug of different hashes are being generation for variable products every time by deleting 'related_ids' from the product data.
* Fix SpeedSearch shortcodes block initialization.

= 1.6.66 2023-08-10 =

#### Bugfixes

* Fix tabs content style.

= 1.6.65 2023-08-09 =

#### Enhancements

* Add margin to the posts' container.

= 1.6.64 2023-08-09 =

#### Bugfixes

* Fix recently viewed products HTML block.

= 1.6.63 2023-08-09 =

#### Enhancements

* Use WooCommerce-like tabs.
* Reorganize settings in 4 categories and move the menu to the top.

#### Bugfixes

* Fix filesystem init by getting it from the global variable, instead of declaring Filesystem Direct class in the code.

= 1.6.61 2023-08-07 =

#### Bugfixes

* Fix migrations.

= 1.6.60 2023-08-02 =

#### Enhancements

* When in categories or tags archive, show them in autocomplete, and on click redirect to the other archive (like for terms)

#### Bugfixes

* Migration fix.

= 1.6.59 2023-08-01 =

#### Bugfixes

* Migrations fix.

= 1.6.58 2023-07-27 =

#### Enhancements

* MUCH better themes support (tested on Astra, Storefront, Flatsome (without integrations)).
* Use the same tags for posts container as they are in template (and calculate child product container tag based on this).
* Add a is_file() check for MU-plugin for AJAX optimization, to produce less admin panel warnings.

#### Bugfixes

* Add correct classes to the posts container of recently viewed products.
* Add correct classes to recently viewed products container.
* Fix a bug when bodyClasses() returned an object, and not an array.

= 1.6.57 2023-07-27 =

#### Bugfixes

* Fix missing ranges bug.
* Add z-index to mobile menu button, so it's not overlapped by anything.

= 1.6.53 2023-07-26 =

#### Enhancements

* Use wp_options table instead of cache for the lock of migrations.

#### Bugfixes

* Posts loading fix.
* Filters opening for Safari fix.

= 1.6.51 2023-07-21 =

* Hashes generation fix.

= 1.6.48 2023-07-21 =

#### Bugfixes

* Fix products hash generation logic (include meta within).

= 1.6.46 2023-07-21 =

#### Bugfixes

* Fix pagination container alignment.
* Fix cache validation by removing "first" and ""last" classes from products classes.
* Fix no-integration posts classes.

= 1.6.45 2023-07-20 =

#### Enhancements

* Add files-based HTML cache.

#### Bugfixes

* Fix cache validation.
* Fix a bug when autocomplete tag select from non-shop page lead to "tags=false".
* Fix posts container width (make it 100%).
* Extra categories are added to request params.
* Clearing filters does not clear search input.

= 1.6.44 2023-07-18 =

#### Enhancements

* Delete REST API credentials creation logic, as it's not needed anymore.
* Add a setting to hide particular filters when no filters are selected (active).
* Set min PHP version to 7.4.

#### Tech Changes

* Delete Self_REST_Requests and WC_REST_API_Credentials classes.
* New hashes generation logic using feed data (not self REST-API requests).
* Check for unclosed container element in loop start.

= 1.6.41 2023-07-16 =

#### Enhancements

* Add a notice when the plugin is being updated instead of blocking the whole logic.

= 1.6.39 2023-07-14 =

#### Enhancements

* When “text” url param is applied, all global search results bars are cleaned.

= 1.6.38 2023-07-13 =

#### Enhancements

* Use posts container classes from WooCommerce (and therefore tha layout).

= 1.6.36 2023-07-11 =

#### Enhancements

* Show "0 seconds" instead of "served from cache".

#### Bugfixes

* Improve Yoast SEO "canonical" integration by adding a correct URL to rel="next" and "prev", along with the standard "canonical".
* Fix duplicate WooCommerce initialization.
* Do not delete "page" URL param on the admin dashboard.

= 1.6.34 2023-07-10 =

#### Bugfixes

* Fix autocomplete to render right panel when paginating down.
* Now categories, tags and attribute terms filtering considers "text" param.

= 1.6.33 2023-07-06 =

#### Bugfixes

* Remove <br> from names.
* Fix a bug with the posts being stuck.
* Fix a theme-specific bug when toggles deselect didn't work.

= 1.6.30 2023-07-05 =

#### Bugfixes

* Function is missing bug on plugin deactivation.

= 1.6.29 2023-07-05 =

#### Enhancements

* Add "onerror" as image attribute instead of a separate JS.
* Add admin notice when the theme is outdated.
* Add time to find results.
* Add the shortcodes to the shortcodes dropdown in the editor.

#### Bugfixes

* Size of search box fixed.

= 1.6.28 2023-07-01 =

#### Bugfixes

* Hide placeholder when no input value for search box.

= 1.6.27 2023-07-01 =

#### Bugfixes

* Fix fancy URLs by encoding/decoding URI components.

= 1.6.23 2023-06-30 =

#### Bugfixes

* Fix a bug when WC "takes over" by deleting has_posts() check from the template.

= 1.6.21 2023-06-29 =

#### Bugfixes

* Fix action scheduler scheduling (improve initialization check).

= 1.6.20 2023-06-26 =

#### Enhancements

* Replace failed to load images with placeholder image.

#### Bugfixes

* Fix elements rendering data fetching from cache (now pagination and fullText are retrieved correctly).
* Fix stuck posts bug on fresh refresh.

= 1.6.19 2023-06-24 =

#### Enhancements

* Eliminate CS violations.

= 1.6.18 2023-06-22 =

#### Enhancements

* Improved race conditions prevention logic.

= 1.6.17 2023-06-21 =

#### Enhancements

* Improve breadcrumbs logic to use the correct breadcrumbs separator (add update them only to the shop page).
* Improved updates logic.

#### Bugfixes

* Fix a error on plugin deactivation.

= 1.6.9 2023-06-15 =

#### Enhancements

* Debug improved.

= 1.6.7 2023-06-07 =

#### Enhancements

* Make Recent searches block to open even when no results.

= 1.6.6 2023-06-07 =

#### Bugfixes

* Feed prune fix.

= 1.6.4 2023-06-07 =

#### Bugfixes

* Fix analytics.
* Fix categories archive.

= 1.6.1 2023-06-07 =

#### Bugfixes

* Minor fix.

= 1.6.0 2023-06-07 =

#### Enhancements

* Add Recent searches block.
* Start feed generation on plugin activation.

= 1.5.1 2023-06-03 =

#### Bugfixes

* Fix Intercom script.

= 1.5.0 2023-06-03 =

#### Enhancements

* Version changed to follow SemVer.
* Add correct intercom script.
