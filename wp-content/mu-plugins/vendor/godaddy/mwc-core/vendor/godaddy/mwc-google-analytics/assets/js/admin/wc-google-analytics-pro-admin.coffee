###*
# WooCommerce Google Analytics Pro Admin scripts
#
# @since 1.0.0
###
jQuery ( $ ) ->
	"use strict";

	# Google Auth window holder
	google_auth_window = null

	# toggle google optimize code field
	$( '#woocommerce_google_analytics_pro_enable_google_optimize' ).change( ->

		if $( this ).is( ':checked' )
			$( '#woocommerce_google_analytics_pro_google_optimize_code' ).closest( 'tr' ).show()
		else
			$( '#woocommerce_google_analytics_pro_google_optimize_code' ).closest( 'tr' ).hide()

	).change()

	$( '#woocommerce_google_analytics_pro_property' ).on( 'change', (e) ->
		if( !e.currentTarget.value )
			$( '#woocommerce_google_analytics_pro_tracking_id' ).val( '' ).closest( 'tr' ).hide()
	)

	# revoke an access token
	$( '.js-wc-google-analytics-pro-revoke-authorization' ).click (e) ->

		e.preventDefault()

		if confirm wc_google_analytics_pro.i18n.ays_revoke

			$( '#woocommerce_google_analytics_pro_oauth_button' ).closest( 'table' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} )

			data =
				action:   'wc_google_analytics_pro_revoke_access'
				security: wc_google_analytics_pro.revoke_access_nonce

			$.post wc_google_analytics_pro.ajax_url, data, ( response ) ->
				window.location.reload()


	# handle Google OAuth callback
	wc_google_analytics_pro.auth_callback = (token) ->

		google_auth_window.close()
		$( '#woocommerce_google_analytics_pro_oauth_button' ).closest( 'table' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} )
		window.location.reload()


	# start Google OAuth flow
	$( "#woocommerce_google_analytics_pro_oauth_button" ).click (e) ->

		e.preventDefault()

		pWidth  = 500
		pHeight = 300
		xPos    = ( $(window).width()  - pWidth  ) / 2
		yPos    = ( $(window).height() - pHeight ) / 2

		google_auth_window = window.open wc_google_analytics_pro.auth_url, 'google-login', 'location=1,menubar=1,resizable=1,width=' + pWidth + ',height=' + pHeight + ',left=' + xPos + ',top=' + yPos


	# smooth scroll to the event field when clicking on it on the funnel steps
	$( '.wc-google-analytics-pro-funnel-steps' ).on 'click', '.event a', (e) ->

		e.preventDefault()

		href = $.attr( this, 'href' )

		$( 'html, body' ).animate
			scrollTop: $( href ).offset().top - 120 # extra offset so the field is not hidden behind the admin bar
		, 500, ->
			$( href ).focus()


	# update event name and status in funnel steps if the event name is changed
	$( '#woocommerce_google_analytics_pro_event_names_section' ).nextAll( '.form-table:first' ).on 'change', 'input', ->

		event_key  = $( this ).prop( 'id' ).replace( 'woocommerce_google_analytics_pro_', '' ).replace( '_event_name', '' )
		event_name = $( this ).val()

		$( '.wc-google-analytics-pro-funnel-steps' ).find( '.event-' + event_key ).each ->

			$( this ).find( '.name' ).text( event_name )

			if ! event_name
				$( this ).find( '.status-enabled' ).hide()
				$( this ).find( '.status-disabled' ).css( 'display', 'block' ) # can't use show() as it will set display:inline
			else
				$( this ).find( '.status-enabled' ).css( 'display', 'block' ) # can't use show() as it will set display:inline
				$( this ).find( '.status-disabled' ).hide()


	# toggle UA-specific fields
	toggle_ua_fields = ( has_property ) =>

		$ua_sections = $( '
			#woocommerce_google_analytics_pro_event_names_section,
			#woocommerce_google_analytics_pro_funnel_steps_section,
			#woocommerce_google_analytics_pro_ua_subscription_event_names_section
		' )

		if ( has_property )
			$( '.universal-analytics-option' ).closest('tr').show()

			$ua_sections.show()
			$ua_sections.next( 'p' ).show()
			$ua_sections.each( () -> $(this).nextAll( '.form-table:first' ).show() )
		else
			$( '.universal-analytics-option' ).closest('tr').hide()

			$ua_sections.hide()
			$ua_sections.next( 'p' ).hide()
			$ua_sections.each( () -> $(this).nextAll( '.form-table:first' ).hide() )

	$( '#woocommerce_google_analytics_pro_property' ).on( 'change', ->
		toggle_ua_fields( $( this ).val() )
	).change()

	# hide UA-specific fields if there is no property selector (meaning, no UA properties are available for the selected GA account)
	if (!$( '#woocommerce_google_analytics_pro_property' ).length)
		toggle_ua_fields( false )


	# toggle GA4-specific fields
	$( '#woocommerce_google_analytics_pro_ga4_property' ).on( 'change', ->

		$ga4_sections = $( '
			#woocommerce_google_analytics_pro_recommended_event_names_section,
			#woocommerce_google_analytics_pro_custom_event_names_section,
			#woocommerce_google_analytics_pro_subscription_event_names_section
		' )

		if ( $( this ).val() )
			$ga4_sections.show()
			$ga4_sections.next( 'p' ).show()
			$ga4_sections.each( () -> $(this).nextAll( '.form-table:first' ).show() )
		else
			$ga4_sections.hide()
			$ga4_sections.next( 'p' ).hide()
			$ga4_sections.each( () -> $(this).nextAll( '.form-table:first' ).hide() )

	).change()

	# show warning when customizing GA4 recommended events
	$( 'input[recommended_event="yes"]' ).on( 'change, input', () ->

		value = $( this ).val()
		default_name = $( this ).attr( 'default_name' )

		if value != default_name
			if ! $( this ).nextAll( 'p.warning' ).length
				warning = if value then wc_google_analytics_pro.i18n.recommended_event_warning else wc_google_analytics_pro.i18n.recommended_event_warning_empty
				$( '<p class="warning" />' ).html( warning ).insertAfter( this )
		else
			$( this ).nextAll( 'p.warning' ).remove( )

	).change()

