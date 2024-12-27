/* global gdAnalytics */

gdAnalyticsListenForViewProduct();
gdAnalyticsListenForAddToCart();
gdAnalyticsListenForRemoveFromCart();
gdAnalyticsListenForInitCheckout();
gdAnalyticsListenForPaymentMethodAdded();
gdAnalyticsListenForOrderCreated();

/**
 * Listens for when a product is viewed.
 */
function gdAnalyticsListenForViewProduct() {
	document.addEventListener('DOMContentLoaded', () => {
		if (typeof gdAnalytics !== 'undefined' && true === gdAnalytics.events.fireViewEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_viewed_product', {
				detail: gdAnalytics.data
			}));
		}
	});
}

/**
 * Listens for when a product is added to cart.
 */
function gdAnalyticsListenForAddToCart() {
	// plain add-to-cart events
	document.addEventListener('DOMContentLoaded', () => {
		if (typeof gdAnalytics !== 'undefined' && true === gdAnalytics.events.fireAddedToCartEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_add_to_cart', {
				detail: gdAnalytics.data
			}));
		}
	});

	// AJAX-enabled add-to-cart events
	document.addEventListener('click', event => {
		// bail if they didn't click on the 'add to cart' button
		if (! event.target.classList.contains('add_to_cart_button')) {
			return;
		}
		// bail if AJAX is disabled when adding to cart
		if (typeof gdAnalytics === "undefined" || ! gdAnalytics.flags || ! gdAnalytics.flags.ajaxAddToCartEnabled) {
			return;
		}

		const cartButton = event.target;

		document.dispatchEvent(new CustomEvent('gd_analytics_add_to_cart', {
			detail: {
				products: [
					{
						id: cartButton.getAttribute('data-product_id'),
						sku: cartButton.getAttribute('data-product_sku'),
						price: cartButton.getAttribute('data-product_price'),
						name: cartButton.getAttribute('data-product_name'),
						quantity: cartButton.getAttribute('data-quantity'),
						googleProductId: cartButton.getAttribute('data-google_product_id')
					}
				]
			}
		}))
	});
}

/**
 * Listens for when a product is removed from the cart or the mini-cart.
 */
function gdAnalyticsListenForRemoveFromCart() {
	// plain remove-from-cart events
	if (typeof gdAnalytics !== 'undefined' && gdAnalytics.flags && ! gdAnalytics.flags.ajaxAddToCartEnabled) {
		// mini-cart (other pages)
		if (true === gdAnalytics.events.fireRemovedFromCartEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_remove_from_cart', {
				detail: gdAnalytics.data
			}));
		// cart page
		} else {
			jQuery('.remove_from_cart_button').on('click', function() {
				gdAnalyticsFireRemoveFromCartEvent(jQuery(this));
			});
		}
	// AJAX-enabled remove-from-cart events
	} else {
		jQuery(document.body).on('removed_from_cart', function(event, fragments, cartHash, $button) {
			if (! $button.length) {
				return;
			}

			gdAnalyticsFireRemoveFromCartEvent($button);
		});
	}
}

/**
 * Triggers a `gd_analytics_remove_from_cart` event.
 *
 * @param {{}} $removeFromCartButton jQuery object
 */
function gdAnalyticsFireRemoveFromCartEvent($removeFromCartButton) {
	let productPrice = $removeFromCartButton.data('product_price'),
		productQuantity = $removeFromCartButton.data('product_quantity');
	document.dispatchEvent(new CustomEvent('gd_analytics_remove_from_cart', {
		detail: {
			totalAmount: parseFloat(productPrice) * parseFloat(productQuantity),
			products: [
				{
					id: $removeFromCartButton.data('product_id'),
					sku: $removeFromCartButton.data('product_sku'),
					price: productPrice,
					name: $removeFromCartButton.data('product_name'),
					quantity: productQuantity,
					googleProductId: $removeFromCartButton.data('google_product_id')
				}
			]
		}
	}));
}

/**
 * Listens for checkout started.
 */
function gdAnalyticsListenForInitCheckout() {
	jQuery(document.body).on('init_checkout', function() {
		if (typeof gdAnalytics !== 'undefined' && true === gdAnalytics.events.fireCheckoutEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_init_checkout', {
				detail: gdAnalytics.data
			}));
		}
	});
}

/**
 * Listens for when a payment method is chosen by the customer at checkout.
 */
function gdAnalyticsListenForPaymentMethodAdded() {
	jQuery(document.body).on('payment_method_selected', function () {
		if (typeof gdAnalytics !== 'undefined' && true === gdAnalytics.events.fireCheckoutEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_payment_info_added', {
				detail: gdAnalytics.data
			}));
		}
	});
}

/**
 * Listens for new orders created.
 */
function gdAnalyticsListenForOrderCreated() {
	document.addEventListener('DOMContentLoaded', () => {
		if (typeof gdAnalytics !== 'undefined' && true === gdAnalytics.events.firePurchasedEvent && gdAnalytics.data.products && gdAnalytics.data.products.length > 0) {
			document.dispatchEvent(new CustomEvent('gd_analytics_order_created', {
				detail: gdAnalytics.data
			}));
		}
	});
}
