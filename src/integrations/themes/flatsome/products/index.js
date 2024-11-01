'use strict';

import './_style.scss';

/**
 * After products added.
 */
speedsearch.hookAFuncAfterThePostsAddition( function() {

	/**
	 * Patches Flatsome wishlists.
	 */
	function patchFlatsomeWishlists() {
		Flatsome.behavior( 'wishlist', {
			attach: function ( context ) {
				jQuery( '.wishlist-button', context ).each( function ( index, element ) {
					'use strict'

					if ( ! element.flatsomeWishListListenerAdded ) {
						element.flatsomeWishListListenerAdded = true;

						jQuery( element ).on( 'click', function ( e ) {
							var $this = jQuery( this )
							// Browse wishlist
							if ( $this.parent().find( '.yith-wcwl-wishlistexistsbrowse, .yith-wcwl-wishlistaddedbrowse' ).length ) {
								window.location.href = $this.parent().find( '.yith-wcwl-wishlistexistsbrowse a, .yith-wcwl-wishlistaddedbrowse a' ).attr( 'href' )
								return
							}
							$this.addClass( 'loading' )
							// Delete or add item (only one of both is present).
							$this.parent().find( '.delete_item' ).click()
							$this.parent().find( '.add_to_wishlist' ).click()

							e.preventDefault()
						} );
					}
				} )
			},
		} )
	}

    // Attaches listeners after the posts are added.
    // Iterate over all behaviors instead of targeting to the specific ones (e.g. "quick-view") to minimize the chances to miss some
    // behaviors. And I also pay a fair price for it: performance.

	/**
	 * Attaches Flatsome behaviors.
	 */
	function attachBehaviors() {
		if ( ! speedsearch.flatsomeWishlistPatched ) { // Patches Flatsome wishlists.
			speedsearch.flatsomeWishlistPatched = true;

			patchFlatsomeWishlists();
		}

		for ( const behavior in Flatsome.behaviors ) {
			if ( 'toggle' !== behavior ) { // We don't need multiple arrows for sidebar toggle.
				Flatsome.behaviors[ behavior ].attach();
			}
		}

		// Add YITH Wishlist action.

		if ( jQuery ) {

			// Delete duplicate actions from previous hooks.

			jQuery( document ).off( 'click', '.add_to_wishlist' );
			jQuery( document ).off( 'click', '.delete_item' );

			// Trigger new actions.

			jQuery( document ).trigger( 'yith_wcwl_init' );
		}
	}

	if ( window.hasOwnProperty( 'Flatsome' ) ) {
		attachBehaviors();
	} else if ( ! speedsearch.flatsomeBehaviorsInterval ) {
		speedsearch.flatsomeBehaviorsInterval = setInterval(
			function() {
				if ( window.hasOwnProperty( 'Flatsome' ) ) {
					attachBehaviors();

					clearInterval( speedsearch.flatsomeBehaviorsInterval );
					speedsearch.flatsomeBehaviorsInterval = null;
				}
			},
			500
		);
	}
} );

document.addEventListener( 'speedsearch_after_wc_breadcrumbs_updated', function() {
    const titleElem = document.querySelector( 'h1.shop-page-title' );
    if ( titleElem ) {
        let currentlySelectedCategory = speedsearch.getUrlCategories();
        if ( currentlySelectedCategory ) {
            currentlySelectedCategory = currentlySelectedCategory[0];
            titleElem.innerHTML = speedsearch.getCategoryName( currentlySelectedCategory );
        } else {
            titleElem.innerHTML = speedsearch.settings.shopPageTitle;
        }
    }
} );
