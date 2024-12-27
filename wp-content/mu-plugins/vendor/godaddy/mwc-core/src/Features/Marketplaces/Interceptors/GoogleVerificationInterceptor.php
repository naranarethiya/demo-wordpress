<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Handles hooks logic for Google site verification.
 */
class GoogleVerificationInterceptor extends AbstractInterceptor
{
    /** @var string */
    const GOOGLE_VERIFICATION_ID_OPTION_KEY = 'mwc_google_site_verification_id';

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('wp_head')
            ->setHandler([$this, 'maybeOutputGoogleVerificationMetaTag'])
            ->execute();
    }

    /**
     * Outputs the Google verification meta tag.
     *
     * @internal
     *
     * @return void
     */
    public function maybeOutputGoogleVerificationMetaTag() : void
    {
        $googleSiteVerificationId = TypeHelper::string(get_option(self::GOOGLE_VERIFICATION_ID_OPTION_KEY), '');

        if (empty($googleSiteVerificationId)) {
            return;
        }

        echo '<meta name="google-site-verification" content="'.esc_attr($googleSiteVerificationId).'" />';
    }
}
