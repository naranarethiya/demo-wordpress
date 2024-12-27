if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		window.MwcLocalPickupPopupHandler = class MwcLocalPickupPopupHandler {
			constructor() {
				this.addEventListeners();
			}

			addEventListeners() {
				$(document.body).on('wc_backbone_modal_loaded', () => this.onLocalPickupModelRender());
			}

			onLocalPickupModelRender() {
				$(document.body).trigger('wc-enhanced-select-init');
			}
		};

		new MwcLocalPickupPopupHandler();
	});
}
