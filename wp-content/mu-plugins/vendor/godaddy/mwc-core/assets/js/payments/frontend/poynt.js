/**
 * Poynt Collect payment form handler.
 *
 * @since 1.0.0
 */
jQuery( ( $ ) => {

	'use strict';

	/**
	 * Payment form handler.
	 *
	 * Interacts with the Poynt Collect API to process a checkout payment form.
	 *
	 * @link https://docs.poynt.com/app-integration/poynt-collect/#poynt-collect
	 *
	 * @since 1.0.0
	 */
	window.MWCPaymentsPoyntPaymentFormHandler = class MWCPaymentsPoyntPaymentFormHandler {

		/**
		 * Instantiates the payment form handler.
		 *
		 * Loads the payment handler and intercepts form submissions to inject the token returned by Poynt Collect API.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} args form handler arguments
		 */
		constructor( args ) {

			this.appId            = args.appId;
			this.businessId       = args.businessId;
			this.customerAddress  = args.customerAddress;
			this.shippingAddress  = args.shippingAddress;
			this.isLoggingEnabled = args.isLoggingEnabled;
			this.options          = args.options;
			this.formInitialized  = false;

			// bail if no Poynt wrappers exist on the page
			if (! $('#mwc-payments-poynt-hosted-form').length) {
				return;
			}

			if ( $( 'form.checkout' ).length ) {
				this.form = $( 'form.checkout' );
				this.handleCheckout();
			} else if ( $( 'form#order_review' ).length ) {
				this.form = $( 'form#order_review' );
				this.handlePayPage();
			} else if ( $( 'form#add_payment_method' ).length ) {
				this.form = $( 'form#add_payment_method' );
				this.handleMyAccount();
			} else {
				this.debugLog('No payment form available');
				return;
			}

			// clear the payment nonce on errors
			$( document.body ).on( 'checkout_error', () => {
				this.clearNonce();
			} );
		}

		/**
		 * Gets the nonce field.
		 *
		 * Returns a jQuery object with the hidden input that holds a nonce value.
		 *
		 * @since 1.0.0
		 *
		 * @returns {Object} jQuery object
		 */
		getNonceField() {
			return $( '#mwc-payments-poynt-payment-nonce' );
		}

		/**
		 * Clears the payment nonce.
		 *
		 * Resets the nonce value in the hidden input.
		 *
		 * @since 1.0.0
		 */
		clearNonce() {
			this.getNonceField().val( '' );
		}

		/**
		 * Creates a nonce using Poynt Collect.
		 *
		 * Saves the nonce to a hidden input and resubmits the form.
		 *
		 * @link https://docs.poynt.com/app-integration/poynt-collect/#creating-a-nonce
		 *
		 * @since 1.0.0
		 */
		createNonce() {

			let nonceData = {
				businessId: this.businessId
			};

			if ( this.customerAddress.firstName )  {
				nonceData.firstName = this.customerAddress.firstName;
			}

			if ( this.customerAddress.lastName )  {
				nonceData.lastName = this.customerAddress.lastName;
			}

			if ( this.customerAddress.line1 )  {
				nonceData.line1 = this.customerAddress.line1;
			}

			if ( this.customerAddress.line2 ) {
				nonceData.line2 = this.customerAddress.line2;
			}

			if ( this.customerAddress.city ) {
				nonceData.city = this.customerAddress.city;
			}

			if ( this.customerAddress.state ) {
				nonceData.territory = this.customerAddress.state;
			}

			if ( this.customerAddress.country ) {
				nonceData.countryCode = this.customerAddress.country;
			}

			if ( this.customerAddress.postcode )  {
				nonceData.zip = this.customerAddress.postcode;
			}

			if ( this.customerAddress.phone ) {
				nonceData.phone = this.customerAddress.phone;
			}

			if ( this.customerAddress.email ) {
				nonceData.emailAddress = this.customerAddress.email;
			}

			if ( this.shippingAddress.line1 ) {
				nonceData.shippingLine1 = this.shippingAddress.line1;
			}

			if ( this.shippingAddress.line2 ) {
				nonceData.shippingLine2 = this.shippingAddress.line2;
			}

			if ( this.shippingAddress.city ) {
				nonceData.shippingCity = this.shippingAddress.city;
			}

			if ( this.shippingAddress.state ) {
				nonceData.shippingTerritory = this.shippingAddress.state;
			}

			if ( this.shippingAddress.postcode ) {
				nonceData.shippingZip = this.shippingAddress.postcode;
			}

			this.debugLog( nonceData );

			/**
			 * @link https://docs.poynt.com/app-integration/poynt-collect/#collect-getnonce
			 */
			this.collect.getNonce( nonceData );
		}

		handleCheckout() {
			$( document.body ).on( 'updated_checkout', () => this.setFields() );

			$( document.body ).on( 'updated_checkout', () => this.handleSavedPaymentMethods() );

			this.form.on( 'checkout_place_order_poynt', () => this.validatePaymentData() );
		}

		/**
		 * Determines whether a nonce exists.
		 *
		 * Checks the hidden input for a value.
		 *
		 * @since 1.0.0
		 *
		 * @returns {boolean} whether a nonce exists
		 */
		hasNonce() {
			return this.getNonceField().val().length > 0;
		}

		handleMyAccount() {

			this.setFields();

			this.form.submit( () => {

				if ( $( '#add_payment_method input[name=payment_method]:checked' ).val() === 'poynt' ) {
					return this.validatePaymentData();
				}
			} );
		}

		/**
		 * Handles the error event data.
		 *
		 * Logs errors to console and maybe renders them in a user-facing notice.
		 *
		 * @link https://docs.poynt.com/app-integration/poynt-collect/getting-started/event-listeners.html#error
		 *
		 * We render their provided message if the error is:
		 * - a result of the form submit (inline field errors are not handled by our JS)
		 * - of type "invalid_details" or "missing_fields". These are the only two known types right now,
		 *   but we check for them anyway in case more are added later that we might not want displayed
		 *
		 * Note: the event will have a different shape than what's documented above if it comes after form validation, directly from their
		 * server-side API. They just pass the raw error in that case, so there is no event.data.error object. For that
		 * we just render the generic error message.
		 *
		 * @param {Object} event after a form error
		 */
		handleError( event ) {

			// always console log the event
			this.debugLog( event );

			// only handle error events
			if ('error' !== event?.type) {
				return;
			}

			// default to a generic error message
			let errorMessage = poyntPaymentFormI18n.errorMessages.genericError;

			// special handling for errors from the form vs. the API
			if (event.data?.error) {

				// if this is not a submit event (such as a field change), don't render anything
				if ('submit' !== event.data.error?.source) {
					return;
				}

				// use the provided error message if available and is fixable by C2
				if (event.data.error.message && ['invalid_details', 'missing_fields'].includes(event.data.error.type)) {
					errorMessage = event.data.error.message;
				}
			}

			// render the error at the top of the page
			this.renderErrors( [ errorMessage ] );
		}

		handlePayPage() {

			this.setFields();

			this.handleSavedPaymentMethods();

			this.form.submit( () => {

				if ( $( '#order_review input[name=payment_method]:checked' ).val() === 'poynt' ) {
					return this.validatePaymentData();
				}
			} );
		}

		/**
		 * Handles a payment form ready event.
		 *
		 * Unblocks the payment form after initialization.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} event after the form is ready
		 */
		handlePaymentFormReady( event ) {

			if ( ! event.type || 'ready' !== event.type ) {
				this.debugLog( event );
			} else {
				this.debugLog( 'Payment form ready' );
			}

			this.form.unblock();
		}

		/**
		 * Handles a nonce ready event.
		 *
		 * Sets the nonce to hidden field and submits the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} payload containing the nonce
		 */
		handleNonceReady( payload ) {

			if ( payload.data && payload.data.nonce ) {
				this.getNonceField().val( payload.data.nonce );
				this.debugLog( 'Nonce set' );
			} else {
				this.clearNonce();
				this.debugLog( 'Nonce value is empty' );
			}

			this.form.submit();
		}

		handleSavedPaymentMethods() {

			let $newMethodForm = $('.mwc-payments-poynt-new-payment-method-form');

			$('input.mwc-payments-poynt-payment-method').change( () => {

				if ( $( "input.mwc-payments-poynt-payment-method:checked" ).val() ) {
					$newMethodForm.slideUp( 200 );
				} else {
					$newMethodForm.slideDown( 200 );
				}

			} ).change();

			$( 'input#createaccount' ).change(function () {

				let $parentRow = $('input.mwc-payments-tokenize-payment-method').closest( 'p.form-row' );

				if ( $( this ).is( ':checked' ) ) {
					$parentRow.slideDown();
					$parentRow.next().show();
				} else {
					$parentRow.hide();
					$parentRow.next().hide();
				}
			});

			if (! $( 'input#createaccount' ).is( ':checked' ) ) {
				$( 'input#createaccount' ).change();
			}
		}

		/**
		 * Initializes the form.
		 *
		 * Adds listeners for the ready and error events.
		 *
		 * @link https://docs.poynt.com/app-integration/poynt-collect/#collect-mount
		 *
		 * @since 1.0.0
		 */
		initForm() {

			// run only once
			if ( this.initializingForm ) {
				return;
			}

			this.initializingForm = true;

			this.collect = new TokenizeJs( this.businessId, this.appId );

			/**
			 * Initialize the Payment Form with Poynt Collect API.
			 */
			this.collect.mount( 'mwc-payments-poynt-hosted-form', document, this.options );

			// triggers when a nonce is ready
			this.collect.on( 'nonce', payload => {
				this.handleNonceReady( payload );
			} );

			// triggers when the payment form is ready
			this.collect.on( 'ready', event => {

				this.initializingForm = false;
				this.formInitialized  = true;

				this.handlePaymentFormReady( event );
			} );

			// triggers when there is a payment form error
			this.collect.on( 'error', error => {
				this.handleError( error );
			} );
		}

		/**
		 * Sets up the payment fields.
		 *
		 * Calls parent method and initializes the payment form.
		 *
		 * @since 1.0.0
		 */
		setFields() {

			this.fields = $('.payment_method_poynt');

			if ( this.formInitialized ) {
				this.collect.unmount( 'mwc-payments-poynt-hosted-form', document );
				this.formInitialized = false;
			}

			if ( this.businessId && this.appId && ! this.initializingForm ) {
				this.initForm();
			}
		}

		validatePaymentData() {

			if ( this.form.is( '.processing' ) ) {
				return false;
			}

			if ( this.fields.find( 'input.mwc-payments-poynt-payment-method:checked' ).val() || this.hasNonce() ) {
				return true;
			}

			// override the loaded address data if available via form fields
			if ( $( '#billing_first_name' ).val() ) {
				this.customerAddress.firstName = $( '#billing_first_name' ).val();
			}

			if ( $( '#billing_last_name' ).val() ) {
				this.customerAddress.lastName = $( '#billing_last_name' ).val();
			}

			if ( $( '#billing_phone' ).val() ) {
				this.customerAddress.phone = $( '#billing_phone' ).val();
			}

			if ( $( '#billing_email' ).val() ) {
				this.customerAddress.email = $( '#billing_email' ).val();
			}

			if ( $( '#billing_address_1' ).val() ) {
				this.customerAddress.line1 = $( '#billing_address_1' ).val();
			}

			if ( $( '#billing_address_2' ).val() ) {
				this.customerAddress.line2 = $( '#billing_address_2' ).val();
			}

			if ( $( '#billing_city' ).val() ) {
				this.customerAddress.city = $( '#billing_city' ).val();
			}

			if ( $( '#billing_state' ).val() ) {
				this.customerAddress.state = $( '#billing_state' ).val();
			}

			if ( $( '#billing_country' ).val() ) {
				this.customerAddress.country = $( '#billing_country' ).val();
			}

			if ( $( '#billing_postcode' ).val() ) {
				this.customerAddress.postcode = $( '#billing_postcode' ).val();
			}

			let shipToDifferentAddress = $( '#ship-to-different-address-checkbox' ).is( ':checked' );

			let shippingLine1    = this.shippingAddress.needsShipping ? (shipToDifferentAddress ? $( '#shipping_address_1' ).val() : this.customerAddress.line1) : '';
			let shippingLine2    = this.shippingAddress.needsShipping ? (shipToDifferentAddress ? $( '#shipping_address_2' ).val() : this.customerAddress.line2) : '';
			let shippingCity     = this.shippingAddress.needsShipping ? (shipToDifferentAddress ? $( '#shipping_city' ).val() : this.customerAddress.city) : '';
			let shippingState    = this.shippingAddress.needsShipping ? (shipToDifferentAddress ? $( '#shipping_state' ).val() : this.customerAddress.state) : '';
			let shippingPostcode = this.shippingAddress.needsShipping ? (shipToDifferentAddress ? $( '#shipping_postcode' ).val() : this.customerAddress.postcode) : '';

			if ( shippingLine1 && shippingLine1.length > 0 ) {
				this.shippingAddress.line1 = shippingLine1;
			}
			if ( shippingLine2 && shippingLine2.length > 0 ) {
				this.shippingAddress.line2 = shippingLine2;
			}
			if ( shippingCity && shippingCity.length > 0 ) {
				this.shippingAddress.city = shippingCity;
			}
			if ( shippingState && shippingState.length > 0 ) {
				this.shippingAddress.state = shippingState;
			}
			if ( shippingPostcode && shippingPostcode.length > 0 ) {
				this.shippingAddress.postcode = shippingPostcode;
			}

			// block the UI
			this.form.block( { message: null, overlayCSS: { background: '#fff', opacity: 0.6 } } );

			// create the nonce
			this.createNonce();

			// always return false to resubmit the form
			return false;
		}

		/**
		 * Logs an item to console if logging is enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param {String|Object} logData
		 */
		debugLog( logData ) {
			if ( this.isLoggingEnabled ) {
				console.log( logData );
			}
		}

		renderErrors(errors) {
			$( '.woocommerce-error, .woocommerce-message' ).remove();

			this.form.prepend( '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>' );

			this.form.removeClass( 'processing' ).unblock();
			this.form.find( '.input-text, select' ).blur();

			$( 'html, body' ).animate( { scrollTop: this.form.offset().top - 100 }, 1000 );
		}

	}

	// dispatch loaded event
	$( document.body ).trigger( 'mwc_payments_poynt_payment_form_handler_loaded' );

} );
