<?php

use GoDaddy\WordPress\MWC\Common\Validation\Validator;
use GoDaddy\WordPress\MWC\Core\Auth\JWT\TokenDecoder;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Validation\TokenContainsUsernameRule;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Validation\TokenHasValidCustomerIdRule;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Validation\TokenNotCachedRule;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\Validation\TokenNotExpiredRule;

return [
    /*
     *--------------------------------------------------------------------------
     * JWT Authentication Settings
     *--------------------------------------------------------------------------
     */
    'jwt' => [
        /*
         * SSO token time to live for JWT auth services.
         *
         * @see TokenNotExpiredRule
         */
        'sso_ttl' => 2 * 60,

        /*
         * MWC SSO provider configuration
         */
        'mwc_sso' => [
            'decoder'         => TokenDecoder::class,
            'algorithm'       => 'RS256',
            'keySetProvider'  => \GoDaddy\WordPress\MWC\Core\Auth\JWT\ManagedWooCommerce\Http\Providers\KeySetProvider::class,
            'validator'       => Validator::class,
            'validationRules' => [
                TokenNotExpiredRule::class,
                TokenContainsUsernameRule::class,
                TokenHasValidCustomerIdRule::class,
                TokenNotCachedRule::class,
            ],
            'tokenObject' => \GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\JWT\ManagedWooCommerce\Token::class,
        ],
    ],
];
