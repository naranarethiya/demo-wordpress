if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		window.MWCCartRecoveryEmailsCheckoutHandler = class MWCCartRecoveryEmailsCheckoutHandler {
			constructor(args) {
				this.ajaxUrl = args.ajaxUrl;
				this.checkoutEmailUpdatedAction = args.checkoutEmailUpdatedAction;
				this.checkoutEmailUpdatedNonce = args.checkoutEmailUpdatedNonce;
				this.cartRecoveryEmailsOptOutPreferenceToggleAction = args.cartRecoveryEmailsOptOutPreferenceToggleAction;
				this.cartRecoveryEmailsOptOutPreferenceToggleNonce = args.cartRecoveryEmailsOptOutPreferenceToggleNonce;
				this.isUserLoggedIn = args.isUserLoggedIn;
				this.checkoutEmailField = $('#billing_email');
				this.checkoutOptOutField = $('#' + args.cartRecoveryEmailsOptOutPreferenceFieldName);

				this.addEventListeners();
			}

			addEventListeners() {
				this.checkoutEmailField.on('blur', () => this.onCheckoutEmailUpdate());
				this.checkoutOptOutField.on('change', (e) => this.onCartRecoveryOptOutPreferenceToggle(e));
			}

			onCheckoutEmailUpdate() {
				if (this.isUserLoggedIn) {
					// for logged in customers, the registered email takes precedence over the email used in the Checkout form
					return;
				}
				$.post(this.ajaxUrl, {
					action: this.checkoutEmailUpdatedAction,
					nonce: this.checkoutEmailUpdatedNonce,
					email: this.checkoutEmailField.val()
				});
			}

			onCartRecoveryOptOutPreferenceToggle(e) {
				const emailAddress = this.checkoutEmailField.val();
				// no point continuing with the AJAX request if we don't have an email address
				if (! emailAddress && ! this.isUserLoggedIn) {
					return;
				}

				$.post(this.ajaxUrl, {
					action: this.cartRecoveryEmailsOptOutPreferenceToggleAction,
					nonce: this.cartRecoveryEmailsOptOutPreferenceToggleNonce,
					email: emailAddress,
					optOut: ! e.currentTarget.checked
				});
			}
		};

		// dispatch loaded event
		$( document.body ).trigger( 'mwc_cart_recovery_emails_checkout_handler_loaded' );
	});
}
