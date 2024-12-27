if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		// removes "Any..." attribute value option from product attribute dropdowns in the variations tab
		$(document).on('woocommerce_variations_loaded', function(event) {
			let $attributeInputs = $('select[name^="attribute_"]'),
				isNewProduct = mwcCommerceCatalogProductEdit.isNewProduct;
			if ($attributeInputs.length) {
				$.each($attributeInputs, function() {
					if (isNewProduct) {
						$(this).children('#variable_product_options div.woocommerce_variations option[value=""]').remove();
					} else {
						// keep the "Any..." option if it's selected for existing products that need to be updated
						$.each($(this).children('#variable_product_options div.woocommerce_variations option[value=""]:not(:selected)'), function () {
							$(this).remove();
						});
					}
				});
			}
		});

		$(document).on('woocommerce_variations_added', function (event) {
			$('#variable_product_options div.woocommerce_variations select[name^="attribute_"]:first').children('option[value=""]').remove();
		});
	});
}
