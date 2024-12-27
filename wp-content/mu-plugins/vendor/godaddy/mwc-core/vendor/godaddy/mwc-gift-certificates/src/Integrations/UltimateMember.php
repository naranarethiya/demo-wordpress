<?php

namespace GoDaddy\WordPress\MWC\GiftCertificates\Integrations;

use GoDaddy\WordPress\MWC\GiftCertificates\Customizer\MWC_Gift_Certificates_Customizer;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;

/**
 * Ultimate Member integration.
 */
class UltimateMember
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		add_action( 'pre_option_blogname', [ $this, 'maybeRemoveTitleFilter' ], -1 );
	}

	/**
	 * Unhooks the Ultimate Member `the_title` filter while we're in the voucher template customizer.
	 *
	 * If that filter runs inside our `pre_option_blogname` callback then it causes an infinite loop (MWC-3822)
	 * @see MWC_Gift_Certificates_Customizer::adjust_customizer_title()
	 *
	 * @return void
	 */
	public function maybeRemoveTitleFilter() : void
	{
		if ( function_exists( 'um_dynamic_user_profile_title' ) && wc_pdf_product_vouchers()->get_customizer_instance()->is_voucher_template_customizer() ) {
			remove_filter( 'the_title', 'um_dynamic_user_profile_title', 100000 );
		}
	}
}
