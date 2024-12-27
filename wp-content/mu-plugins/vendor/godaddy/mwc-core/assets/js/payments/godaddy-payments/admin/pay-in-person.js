jQuery(function($) {
	"use strict";

	$('.wc-settings-notice__dismiss a').on( 'click', ( event ) => {
		event.preventDefault();
		event.target.closest('.wc-settings-notice').remove();
	} );

	// Open GoDaddy Payments modal on click of Get Started button
	$(document.body).on('click', '.submitdelete', function(e) {
		if ($.WCBackboneModal && $('#tmpl-mwc-payments-godaddy-product-delete').length) {
			e.preventDefault();
			new $.WCBackboneModal.View({
				target: 'mwc-payments-godaddy-product-delete'
			});
		}
	});

	$(document).ready(function(){
		$('.pay-in-person-sync-select').on('select2:close', function() {
			if($(this).val().length === 0){
				$(this).parent().find('.select2-selection--multiple').css({borderColor: "#DB1802"});
				$(this).parent().find('.pay-in-person-sync-select--error').css({display: "inherit"});
			} else {
				$(this).parent().find('.select2-selection--multiple').css({borderColor: "#8c8f94"});
				$(this).parent().find('.pay-in-person-sync-select--error').css({display: "none"});
			}
		});
	})
});
