<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Traits;

use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidNonceException;
use WP_REST_Request;

/**
 * A trait for API controllers that need to verify the request nonce.
 */
trait VerifiesNonceTrait
{
    /**
     * Verifies a nonce to prevent CSRF attacks.
     *
     * Nonces will mismatch if the logged-in session cookie is different! If using a client to test, set this cookie
     * to match the logged in cookie in your browser.
     *
     * @param WP_REST_Request $request Request object.
     * @param string $action
     * @return bool
     * @throws InvalidNonceException
     */
    protected function verifyNonce(WP_REST_Request $request, string $action) : bool
    {
        if (! wp_verify_nonce($nonce = $request->get_header('X-MWC-Payments-Nonce') ?? '', $action)) {
            throw new InvalidNonceException(
                $nonce ? __('Invalid nonce', 'mwc-core') : __('Missing nonce', 'mwc-core'),
                $nonce ? 403 : 401
            );
        }

        return true;
    }

    /**
     * Determines whether the given route should be authenticated by nonce verification.
     *
     * @param string $route
     * @return bool
     */
    public function shouldAuthenticateRouteByNonceVerification(string $route) : bool
    {
        return 0 === strpos($route, '/'.$this->namespace.'/'.$this->route);
    }
}
