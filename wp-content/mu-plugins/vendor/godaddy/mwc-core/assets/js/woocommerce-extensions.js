/* global MWCExtensions */

(function($) {
	/**
	 * WooCommerce handlers
	 * @TODO: Should refactor to use vanilla JS here {JO 2021-02-21}
	 *
	 * @type {Object}
	 */
	var MWC = {
		hideManagedSubscriptions: function () {
			$(MWCExtensions.plugins).each(function(i, plugin) {
				if (plugin.homepageUrl) {
					$('a[href="' + plugin.homepageUrl + '"]').parents('tbody').hide();
				}
			});
		}
	};

	if (MWCExtensions.isSubscriptionsPage) {
		MWC.hideManagedSubscriptions();
	}
})(jQuery);

/**
 * Hides subscription information for GoDaddy-included extensions.
 */
document.addEventListener('DOMContentLoaded', function() {
	if (! MWCExtensions.isSubscriptionsPage) {
		return;
	}

	const subscriptions = document.querySelectorAll('.wc-subscriptions-wrap .wp-list-table');
	if (! subscriptions) {
		return;
	}

	subscriptions.forEach(function(subscription) {
		const toggleWrapper = subscription.querySelector('.form-toggle__wrapper');
		if (! toggleWrapper) {
			return;
		}

		const statusToggle = toggleWrapper.querySelector('a.active');
		if (! statusToggle || ! statusToggle.getAttribute('href')) {
			return;
		}

		// Bail if it's not a GoDaddy-included product.
		if (! statusToggle.getAttribute('href').includes('product-key=godaddymwc')) {
			return;
		}

		toggleWrapper.innerHTML = MWCExtensions.godaddyIncluded;

		// We don't need to display the description (e.g. subscription duration) for GoDaddy-included extensions.
		const description = subscription.querySelector('.wp-list-table__ext-description');
		if (description) {
			description.innerHTML = '';
		}
	});
});
