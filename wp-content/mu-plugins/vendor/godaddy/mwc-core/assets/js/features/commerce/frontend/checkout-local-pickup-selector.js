if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		window.MwcCheckoutLocalPickupHandler = class MwcCheckoutLocalPickupHandler {
			constructor() {
				this.addEventListeners();
				this.addTriggers();
				this.onUpdatedCheckout();
			}

			addEventListeners() {
				$(document.body).on('updated_checkout', () => this.onUpdatedCheckout());
				$(document.body).on('updated_cart_totals', () => this.onUpdatedCheckout());
			}

			addTriggers() {
				$.ajaxSetup({
					beforeSend: function (xhr, settings) {
						if (settings.url && settings.url === $('.woocommerce-cart-form').attr('action')) {
							settings.data = settings.data + '&update_cart=1&mwc-commerce-local-pickup-location-selection-id=' + $('.mwc-commerce-local-pickup-location:checked').val();
						}
					}
				});

				$('.mwc-commerce-local-pickup-location').on('click', () => $('body').trigger('update_checkout').trigger('wc_update_cart'));
			}

			onUpdatedCheckout() {
				this.updateReferences();
				this.addTriggers();

				const localPickupIsChecked = this.localPickupOptionRadioButton.is(':checked');

				this.locationsWrapper.toggle(localPickupIsChecked);
				this.locationsTitle.toggle(localPickupIsChecked);
			}

			updateReferences() {
				this.locationsWrapper = $('.mwc-commerce-local-pickup-locations-wrapper');
				this.locationsTitle = $('.mwc-commerce-local-pickup-locations-title');
				this.localPickupOptionRadioButton = $(this.locationsWrapper.parent().find('input[type=radio]')[0]);
			}
		};

		new MwcCheckoutLocalPickupHandler();
	});
}
