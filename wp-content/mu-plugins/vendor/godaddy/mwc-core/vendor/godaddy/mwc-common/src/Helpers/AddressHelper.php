<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

class AddressHelper
{
    /**
     * Creates a formatted string representation of the given address using the format configured for the store.
     *
     * @param Address $address
     * @param string $separator uses an HTML line break element (`<br/>`) by default
     * @return non-empty-string|null
     */
    public static function format(Address $address, string $separator = '<br/>') : ?string
    {
        if (! $wooCommerce = WooCommerceRepository::getInstance()) {
            return null;
        }

        $data = AddressAdapter::getNewInstance([])->convertToSource($address);

        if (! $formatted = $wooCommerce->countries->get_formatted_address($data, $separator)) {
            return null;
        }

        return $formatted;
    }
}
