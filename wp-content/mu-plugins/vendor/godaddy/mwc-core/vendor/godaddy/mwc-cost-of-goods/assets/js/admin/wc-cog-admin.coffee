"use strict"

jQuery ( $ ) ->


	###*
	# Returns the status of the background process applying costs to previous orders.
	#
	# @since 2.8.0
	#
	# @param {string} job_id
	###
	getApplyCostsProcessStatus = ( job_id ) ->

		if $( 'p.applying-costs-result .progress' ).length is 0
			$( 'p.applying-costs-result' ).append( ' <span class="progress"></span>' )

		error   = '<span class="error"   style="color: #DC3232;">&#10005;</span> ' + wc_cog_admin.i18n.apply_costs_error
		success = '<span class="success" style="color: #008000;">&#10004;</span> ' + wc_cog_admin.i18n.apply_costs_success
		data    =
			action:   'wc_cog_get_applying_costs_status'
			security: wc_cog_admin.get_applying_cost_of_goods_status_nonce
			job_id:   job_id

		$.post( ajaxurl, data )

			.done ( response ) ->

				if response.success

					if response.data.status isnt 'completed'

						$( 'p.applying-costs-result .progress' ).html( response.data.progress + ' / ' + response.data.total )

						return setTimeout( getApplyCostsProcessStatus( response.data.id ), 100000 )

					$( 'p.applying-costs-result' ).html( success )

				else

					$( 'p.applying-costs-result' ).html( error )
					console.log response.data

				$( 'span.applying-costs-progress' ).removeClass( 'is-active' )
				$( '#wc_cog_apply_costs_to_previous_orders' ).prop( 'disabled', false )

			.fail ->

				$( 'p.applying-costs-result' ).html( error )
				$( 'span.applying-costs-progress' ).removeClass( 'is-active' )
				$( '#wc_cog_apply_costs_to_previous_orders' ).prop( 'disabled', false )

	# run once on page load if job exists (we must be on the settings page)
	if wc_cog_admin.existing_background_job_id
		getApplyCostsProcessStatus( wc_cog_admin.existing_background_job_id )


# Settings Page: Apply Costs to Previous Orders
	$( 'button#wc_cog_apply_costs_to_previous_orders' ).on 'click', ( e ) ->
		e.preventDefault()

		# ask confirmation first
		# bail out if confirmation denied
		if not confirm( wc_cog_admin.i18n.apply_costs_confirm_message_all )

			return false

		else

			$fieldset = $( this ).closest( 'fieldset' )
			$spinner  = $fieldset.find( 'span.applying-costs-progress' )
			$status   = $fieldset.find( '> p' )

			$( this ).prop( 'disabled', true )
			$spinner.addClass( 'is-active' )
			$status.addClass( 'applying-costs-result' ).html( wc_cog_admin.i18n.apply_costs_in_progress + '<br/>' + wc_cog_admin.i18n.apply_costs_notice )

			error = '<span class="error" style="color: #DC3232;">&#10005;</span> ' + wc_cog_admin.i18n.apply_costs_error
			data  =
				action:         'wc_cog_apply_costs_to_previous_orders'
				security:        wc_cog_admin.apply_cost_of_goods_nonce

			$.post( ajaxurl, data )

				.done ( response ) =>

					if response.success
						return setTimeout( getApplyCostsProcessStatus( response.data.id ), 100000 ) if response.data.id?
					else
						$status.html( error )
						$spinner.removeClass( 'is-active' )
						$( this ).prop( 'disabled', false )

				.fail =>
					$( this ).prop( 'disabled', false )
					$spinner.removeClass( 'is-active' )
					$status.html( error )


	# Variable Products Handling:
	$( 'select#field_to_edit' ).on( 'variable_cost_of_good_ajax_data', ->
		return value : window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value )
	)


	# Auto-fill the quick-edit fields with the product data:
	$( '#the-list' ).on 'click', '.editinline', ( e ) ->

		post_id = $( @ ).closest( 'tr' ).attr( 'id' )

		post_id = post_id.replace( 'post-', '' )

		inline_data = $( '#wc_cog_inline_' + post_id )

		cost = inline_data.find( '.cost' ).text()

		$( 'input[name="_wc_cog_cost"]' ).val( cost )


	# Cost of goods suggestion on changing quantity in order back-end.
	$( '#woocommerce-order-items' ).on 'change', 'input.quantity', ( e ) ->

		e.preventDefault()

		$row            = $( this ).closest( 'tr.item' )
		$qty            = $( this ).val()
		$o_qty          = $( this ).attr( 'data-qty' )
		$cog_total      = $( 'input.cog-total', $row )
		$cog_suggestion = $( 'input.cog-suggestion', $row )

		$unit_total = accounting.unformat( $cog_total.attr( 'data-cog-total' ), '.' )
		$unit_total = $unit_total / $o_qty

		$cog_suggestion.val(
			accounting.formatNumber( $unit_total * $qty, woocommerce_admin_meta_boxes.currency_format_num_decimals, '', woocommerce_admin_meta_boxes.currency_format_decimal_sep ).toString()
		)
