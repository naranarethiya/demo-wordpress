if (typeof jQuery !== 'undefined') {
	jQuery(function ($) {
		// determines whether the list of products has at least one product with listing selected
		selectedProductsContainOneListedProduct = function() {
			return gdMarketplacesProductsList && gdMarketplacesProductsList.productsWithListing.map((productId) => {
				return $(`#cb-select-${productId}`).is(':checked');
			}).some((checked) => checked);
		};

		// will show a confirmation dialog if at least one product with listing is about to be moved to trash
		$('#doaction').on('click', function (event) {
			if (
				gdMarketplacesProductsList &&
				selectedProductsContainOneListedProduct() &&
				'trash' === $('#bulk-action-selector-top').val() &&
				!window.confirm(gdMarketplacesProductsList.i18n.bulkUnpublishConfirmationMessage)
			) {
				event.preventDefault();
			}
		});
	});
}
