'use strict';

const bodyId = 'speedsearch-body';

document.addEventListener(
    'DOMContentLoaded',
    function() {
        document.body.setAttribute( 'id', bodyId );
    }
);

/**
 * After products added.
 */
speedsearch.hookAFuncAfterThePostsAddition( function() {
    speedsearch.themeIntegration_afterProductsFetch( bodyId );
} );
