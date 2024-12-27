<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Countries;

/**
 * Repository for handling WooCommerce countries.
 */
class CountriesRepository
{
    /**
     * Gets the WooCommerce countries handler instance.
     *
     * @return WC_Countries
     * @throws Exception
     */
    public static function getInstance() : WC_Countries
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->countries) || ! $wc->countries instanceof WC_Countries) {
            throw new Exception(__('The WC_Countries class is not available.', 'mwc-common'));
        }

        return $wc->countries;
    }
}
