/* global gdMarketplacesProductEdit */

if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		let currentProductStatus = $('#post_status').val();

		$('#delete-action, #publish').on('click', function(event) {
			// confirmation should be done when clicking on the delete link or when the post status was changed
			let firedEventRequiresConfirmation = 'delete-action' === $(this).attr("id") || $('#post_status').val() !== currentProductStatus;
			let productHasListings = gdMarketplacesProductEdit && '1' === gdMarketplacesProductEdit.productHasListings;

			if (
				firedEventRequiresConfirmation &&
				productHasListings &&
				! window.confirm(gdMarketplacesProductEdit.i18n.unpublishConfirmationMessage)
			) {
				event.preventDefault();
			}
		});

		// do not allow changing SKU to empty if simple product has listing(s)
		$('#_sku').on('blur', function(event) {
			disallowSettingEmptySkuIfProductHasListing($(this));
		});

		// do not allow changing SKU to empty if variable product has listing(s)
		$(document).on('woocommerce_variations_loaded', function(event) {
			let $skuInputs = $('input[id^="variable_sku"]');

			if ($skuInputs.length) {
				$.each($skuInputs, function() {
					$(this).on('blur', function(event) {
						disallowSettingEmptySkuIfProductHasListing($(this));
					})
				})
			}
		});

		/**
		 * Disallows changing SKU to empty if product has listing(s).
		 *
		 * @param {{}} $skuInput
		 */
		function disallowSettingEmptySkuIfProductHasListing($skuInput) {
			let productHasListings = gdMarketplacesProductEdit && '1' === gdMarketplacesProductEdit.productHasListings;

			if (! productHasListings || ! $skuInput || $skuInput.length === 0) {
				return;
			}

			let inputId = $skuInput.attr('id'),
				errorMessageId = inputId + '_marketplaces_error_message',
				existingErrorMessage = document.getElementById(errorMessageId);

			if ($skuInput.val().length === 0) {
				$('#publish').prop('disabled', true);
				$('.save-variation-changes').prop('disabled', true);

				if (null === existingErrorMessage) {
					let errorHtml = document.createElement('small'),
						errorMsg = document.createTextNode(gdMarketplacesProductEdit.i18n.emptySkuNotAllowedWithListing);

					errorHtml.setAttribute('id', errorMessageId);
					errorHtml.setAttribute('style', 'display: block; clear: both; color: #D63638;');
					errorHtml.appendChild(errorMsg);

					document.getElementById(inputId).parentElement.append(errorHtml);
				}
			} else {
				$('#publish').prop('disabled', false);
				$('.save-variation-changes').prop('disabled', false);

				if (null !== existingErrorMessage) {
					existingErrorMessage.remove();
				}
			}
		}
	});

	jQuery(function($) {

		$('button.mwc-marketplaces-create-draft-listing').on('click', (event) => {

			event.preventDefault();

			const channelType = event.target.dataset.channelType;
			const channelUuid = event.target.dataset.channelUuid;
			const thisChannelPanel = $(`#gd-marketplaces-${channelType}`);
			const thisChannelErrorWrapper = thisChannelPanel.find('.gd-marketplaces-create-draft-error');

			thisChannelErrorWrapper.html('').hide();

			thisChannelPanel.block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			$.post({
				url: gdMarketplacesProductEdit.ajaxUrl,
				data: {
					channelUuid: channelUuid,
					action: gdMarketplacesProductEdit.createDraftAction,
					nonce: gdMarketplacesProductEdit.createDraftNonce,
					productId: gdMarketplacesProductEdit.productId
				}
			}).done((response) => {

				if (! response.success) {
					thisChannelErrorWrapper.html(`<p>${response.data}</p>`).show();
					return;
				}

				thisChannelPanel.html(response.data);

			}).fail(() => {

				thisChannelErrorWrapper.html(`<p>${gdMarketplacesProductEdit.i18n.createDraftGenericError}</p>`).show();

			}).always(() => {

				thisChannelPanel.unblock();
			});
		});
	});
}
