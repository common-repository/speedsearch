# About

A WordPress plugin for WooCommerce products AJAX Search with filters.

# SpeedSearch themes

To add a custom SpeedSearch theme, add it to your WP theme under `speedsearch/themes/` directory.

You can see an example of the theme in `samples/example-theme/` dir. So its path should be `wp-content/themes/my-theme/speedsearch/themes/example-theme/` (and don't forget to run `npm install` and `npm run build` for it).

And then select a theme under SpeedSearch settings in "Themes" tab (you don't have this tab if you have no themes installed).

## Cache warmer

Set `speedsearch-warmer' URL param, and HTML will be warmed (fetch posts HTML from server, render in HTML (instead if dynamic via JS)) - for HTML cache.

## Plugin status endpoint

https://example.com/wp-json/speedsearch/status

## Shortcodes

| Syntax                                                                                                                                 | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
|----------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `[speedsearch html_id="speedsearch-main-block" hide_search="0"]`                                                                       | Main block (contains all below parts).<br/><br/>Attributes:<br/><br/>`hide_search` - Whether to hide the search bar. Default: `0`.                                                                                                                                                                                                                                                                                                                                                                                                  |
| `[speedsearch_search small_size="1" align="center" search_in_results="0"]`                                                             | Search block. <br/><br/>Attributes:<br/><br/>`small_size` - Whether search bar size is small or not.  Default: `0`. <br/>`align` - Alignment. Default: `right`. <br/>`search_in_results` - Whether to search in results. Default: `0`.                                                                                                                                                                                                                                                                                              |
| `[speedsearch_part_categories html_id="speedsearch-categories"]`                                                                             | Categories.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| `[speedsearch_part_filters]`                                                                                                           | Filters. Contains Toggles (because toggles are filters).                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| `[speedsearch_part_tags]`                                                                                                              | Tags.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `[speedsearch_part_posts]`                                                                                                             | Posts.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `[speedsearch_part_filter name="price"]`                                                                                               | Single "Price" filter.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `[speedsearch_part_active_filters]`                                                                                                    | Active filters block.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `[speedsearch_part_toggle name="reviews_allowed"]`                                                                                     | Single toggle.<br/><br/> To get toggle name, hover over it in admin menu and wait for 2 seconds ("title" attribute). For example, the toggle "Reviews Allowed" will be "reviews_allowed". <br/><br/>**Important:** If the toggle is not active (in general or at least for one filter), it will not be shown.                                                                                                                                                                                                                       |
| `[speedsearch_recently_viewed_products show_limit="3" add_most_popular_products_if_limit_is_not_hit="1" thumbnail_image_size="large"]` | List recently viewed products. <br/><br/>Attributes:<br/><br/>`show_limit` - Maximum number of recently viewed products to show. Can't be more than 12. Default: `12.` <br/>`add_most_popular_products_if_limit_is_not_hit` - Add the most popular products if recently viewed products are less than the limit (up to the limit (e.g. viewed 6 products, limit is 12, add 6 the most popular products)). Default: `1`<br>`thumbnail_image_size` - Thumbnail image size ("thumbnail" or "large").  Default: `woocommerce_thumbnail`. |

* Each shortcode supports `html_id` attribute, which adds ID to HTML the element.

## Env variables

| Variable               | Description                      |
|------------------------|----------------------------------|
| `SPEEDSEARCH_SERVER`   | BE server address with protocol. |
| `SPEEDSEARCH_LICENSE`  | SpeedSearch license key.         |
| `SPEEDSEARCH_ORIGIN`   | Origin server for BE requests.   |

## HTML Classes

| Syntax                                        | Description                                      |
|-----------------------------------------------|--------------------------------------------------|
| `speedsearch-hide-when-no-recently-viewed-products` | Hides the elem when no recently viewed products. |

## REST API Endpoints

| Syntax                              | Method | Description                                                                       |
|-------------------------------------|--------|-----------------------------------------------------------------------------------|
| `products-hash`                     | GET    | The list of products with their hash.                                             |
| `speedsearch-settings`                    | GET    | Webhooks secret.                                                                  |
| `product-hash-generation-data/6360` | GET    | For debugging, returns the array used right before the product hash generation.   |
| `speedsearch-term-products/462`           | GET    | For debugging, returns the list of products that belong to the specified term ID. |
| `speedsearch-backend-fix-counts`          | GET    | For BE fix, returns the list of different types of counts.                        |

## Fake params 

| Syntax               | Description                                                                                               |
|----------------------|-----------------------------------------------------------------------------------------------------------|
| `fake_posts_counter` | Specify the fake posts counter (used for debugging and demoing of counter for more than 100k of results). |

### Notes

- `html_id` is a global attribute that can be added to any shortcode. Adds the specified ID to the HTML element.
Should be unique.

------

## Plugin templates overwriting

- To overwrite template files from within your theme, put them to `/speedsearch/` directory in your theme directory.
For example, `/themes/my-theme-directory/speedsearch/parts/filters/sort-by.php` will be given priority over 
the `/plugins/speedsearch/src/templates/parts/filters/sort-by.php`.

------

## Meta

| Type  | Key                  | Description                                                           |
|-------|----------------------|-----------------------------------------------------------------------|
| Term  | `speedsearch-swatch-image` | Term image swatch of `thumbnail` size with two keys: `url` and `alt`. |
| Post  | `speedsearch-product-hash` | Product hash (for sync on hash diff with the backend).                |

## Action

| Action                                                    | Description | Args               | 
|-----------------------------------------------------------|-------------|--------------------|
| `speedsearch_generate_all_products_hash`                  |             |                    |
| `speedsearch_products_object_cache_validation`            |             |                    |
| `speedsearch_check_changed_taxonomies`                    |             |                    |
| `speedsearch_before_render_recently_viewed_products_data` |             | `$viewed_products` |

## Filters 

| Filter                                                  | Description                                                                                                                                                                         | Params                             |
|---------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------|
| `speedsearch_post_field_content_types`                  | Posts block field content types.                                                                                                                                                    | `$types`                           |
| `speedsearch_opportunity_to_insert_custom_html_for_ids` | A place where `html` for IDs could be inserted (this case, no raw posts data will be returned). Used for search and recently_viewed_products requests. Used for theme integrations. | `$data`, `$posts_ids`, `$endpoint` |
| `speedsearch_before_public_settings_ajax_output`        | Before public settings AJAX output.                                                                                                                                                 | `$data`                            |
| `speedsearch_get_sort_by_property_params`               | Get property params.                                                                                                                                                                | `$property_param`, `$sorting_name` |
| `speedsearch_page_embed_data`                           | SpeedSearch page embed data.                                                                                                                                                        | `$page_embed_data`                 |

## JS

### Events

| Event                                            | Target       | Description                                                                                                                    | event.data                                                              |
|--------------------------------------------------|--------------|--------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| `speedsearch_after_filter_window_close`                | `let filter` | After filter window close.                                                                                                     |                                                                         | 
| `speedsearch_pre_filter_reset`                         | `let filter` | Before filter reset (on Reset button click).                                                                                   |                                                                         | 
| `speedsearch_after_posts_updated`                      | `document`   | After the posts are updated (fires only if at least one post was retrieved from the backend).                                  |                                                                         | 
| `speedsearch_after_filters_init`                       | `document`   | After filters are initialized.                                                                                                 |                                                                         |
| `speedsearch_after_categories_init`                    | `document`   | After categories init.                                                                                                         |                                                                         |
| `speedsearch_after_tags_init`                          | `document`   | After tags are initialized.                                                                                                    |                                                                         |
| `speedsearch_after_category_change`                    | `document`   | After category changed.                                                                                                        | `'categorySlug', 'isSelected', 'isAllCategoriesActive', 'categoryName'` |
| `speedsearch_after_tag_change`                         | `document`   | After tag changed.                                                                                                             | `'tagID', 'isSelected', 'tagSlug', 'tagName'`                           |
| `speedsearch_after_attribute_change`                   | `document`   | After attribute field changed.                                                                                                 | `'attributeSlug', 'value', 'isSelected', 'isSingleSelect'`              |
| `speedsearch_admin_after_themes_init`                  | `document`   | Admin panel. After themes are initialized.                                                                                     |                                                                         |
| `speedsearch_after_date_change`                        | `document`   | After price changed (but not saved yet).                                                                                       |                                                                         |
| `speedsearch_after_price_change`                       | `document`   | After price changed (but not saved yet).                                                                                       | `newMinPrice`, `newMaxPrice`                                            |
| `speedsearch_admin_after_swatch_images_added`          | `document`   | Admin panel. After swatch images were added to the field.                                                                      | `field`                                                                 |
| `speedsearch_before_posts_request`                     | `document`   | After request params for search request are retrieved but before the request is done.                                          | `requestParams`                                                         |
| `speedsearch_admin_after_select_table_row_click`       | `table`      | Admin panel. After select table row click.                                                                                     |                                                                         |
| `speedsearch_after_wc_breadcrumbs_updated`             | `document`   | After WC breadcrumbs were updated.                                                                                             |                                                                         |
| `speedsearch_after_attribute_terms_filtering_finished` | `document`   | After attribute terms filtering (first/second layer) finished.                                                                 |                                                                         | 
| `speedsearchLoaded`                                    | `document`   | Equivalent to "DOMContentLoaded" but fires only when JSON cache settings are retrieved.                                        |                                                                         |
| `speedsearch_autocomplete_window_rendering_finished`   | `document`   | When autocomplete window rendering finished.                                                                                   |                                                                         |
| `speedsearch_after_first_layer_filtering_finished`     | `document`   | When first layer filtering finished (any of the times).                                                                        |                                                                         |
| `speedsearch_after_second_layer_filtering_finished`    | `document`   | When second layer filtering finished (any of the times).                                                                       |                                                                         |
| `speedsearch_autocomplete_entity_selected`             | `document`   | After autocomplete entity has been selected (it's data is available via speedsearch.autocompletePreviouslyActivatedFilter variable). |                                                                         |
| `speedsearch_filters_retrieved`                        | `document`   | After filters data is retrieved.                                                                                               |                                                                         |
| `speedsearch_initial_init`                             | `document`   | Initial pre-init. When the most of routine are initialized but all data not necessary finished the loading.                    |                                                                         |

### Dev Admin Panel Methods

| Event                       | Description                                                              |
|-----------------------------|--------------------------------------------------------------------------|
| `speedsearch.resetToFreshState()` | Resets the plugin to the fresh state. And de-activates the license also. |
| `speedsearch.activateLicense()`   | Activates the license.                                                   |

## Libs

### JS

| Name                                                   | Description                                              |
|--------------------------------------------------------|----------------------------------------------------------|
| [SortableJS](https://sortablejs.github.io/Sortable/)   | Sorting of filters. Admin menu only.                     |
| [Pickr](https://simonwep.github.io/pickr/)             | Color picker. Admin menu only.                           |
| [noUiSlider](https://refreshless.com/nouislider/)      | Price range slider.                                      |
| [Flatpickr](https://flatpickr.js.org/)                 | Date range select.                                       |
| [intro.js](https://github.com/usablica/intro.js/)      | Plugin introduction tour.                                |
| [DataTables](https://github.com/DataTables/DataTables) | DataTables (for analytics tables sorting and pagination) |
| [jsPanel4](https://github.com/Flyer53/jsPanel4)        | Floating blocks.                                         |
 
## Other Notes

* There are 3 levels on filtering:

| Level | Description                                                                                                                                                                                                                                              |
|-------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 0     | When you select "how to treat empty categories and attributes" (`speedsearch-setting-how-to-treat-empty` option) or "hide unavailable tags" setting.                                                                                                           |
| 1     | When you select category. Its behaviour is also affected by level 0 setting to a full degree.                                                                                                                                                            | 
| 2     | When you select filters (tags, toggles, attributes) inside the category. Its behaviour is also affected by level 0 setting - but it doesn't hide, just disables (even if the level 0 option is set to "hide"), and doesn't work when it's set to "show". |

## Dev Notes / TODO

- Be aware of possible filters' data-name collisions! Use 'attribute' or 'pa_' prefixes for attributes.
