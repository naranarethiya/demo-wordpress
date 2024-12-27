jQuery(function($) {
	"use strict";

	$( '#woocommerce_stripe_disconnect' ).on( 'click', ( event ) => {

		if (! window.confirm(MWCStripeSettings.confirmMessage)) {
			event.preventDefault();
		}

	} );
});
