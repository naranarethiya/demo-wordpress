/* global gdAnalyticsGoogle */

document.addEventListener('gd_analytics_add_to_cart', function(event) {
	gdFireGaEvent('add_to_cart', gdBuildGaDataPayload(event.detail, 'cart'));
});

document.addEventListener('gd_analytics_viewed_product', function(event) {
	gdFireGaEvent('view_item', gdBuildGaDataPayload(event.detail, 'product'));
});

document.addEventListener('gd_analytics_init_checkout', function(event) {
	gdFireGaEvent('begin_checkout', gdBuildGaDataPayload(event.detail, 'cart'));
});

document.addEventListener('gd_analytics_payment_info_added', function(event) {
	gdFireGaEvent('add_payment_info', gdBuildGaDataPayload(event.detail, 'cart'));
});

document.addEventListener('gd_analytics_remove_from_cart', function(event) {
	gdFireGaEvent('remove_from_cart', gdBuildGaDataPayload(event.detail, 'cart'));
});

document.addEventListener('gd_analytics_order_created', function(event) {
	gdFireGaEvent('purchase', gdBuildGaDataPayload(event.detail, 'purchase'));
});

/**
 * Builds the payload for a GA event.
 *
 * @param {object} data
 * @param {string} pageType
 * @param {float|null} totalValue
 * @returns {{}}
 */
function gdBuildGaDataPayload(data, pageType, totalValue = null) {
	return {
		ecomm_pagetype: pageType,
		value: data.totalAmount ? data.totalAmount : data.products[0].price,
		items: gdConvertProductsToGaItems(data.products)
	}
}

/**
 * Converts native products data to GA items.
 *
 * @param {object[]} products
 * @returns {object}[]
 */
function gdConvertProductsToGaItems(products) {
	return products.map((productData) => {
		return {
			id: productData.googleProductId && productData.googleProductId.length > 0 ? productData.googleProductId : productData.sku,
			price: productData.price,
			google_business_vertical: 'retail',
			name: productData.name,
			quantity: productData.quantity
		};
	});
}

/**
 * Fires a GA event.
 *
 * @param {string} eventName
 * @param {object} payload
 */
function gdFireGaEvent(eventName, payload)
{
	if (typeof gdAnalyticsGoogle === 'undefined' || ! gdAnalyticsGoogle.providers || gdAnalyticsGoogle.providers.length <= 0 || typeof gtag !== 'function') {
		return;
	}

	gdAnalyticsGoogle.providers.forEach((provider) => {
		if (provider.trackingId) {
			payload.send_to = provider.trackingId;

			if (eventName === 'purchase') {
				payload.send_to = provider.trackingId + '/' + provider.conversionLabel;
			}

			gtag('event', eventName, payload);
		}
	});
}
