'use strict';

import './_style.scss';

/**
 * Close mobile menu when autocomplete has been selected.
 */
document.addEventListener( 'speedsearch_autocomplete_entity_selected', function() {
    const mobileMenuCloseBtn = document.querySelector( '.mfp-close' );
    if ( mobileMenuCloseBtn ) {
        mobileMenuCloseBtn.click();
    }
} );
