<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Helper_Options;

class WooCommerceSubscriptionAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts an array of local extension data into a WooCommerce.com subscription array.
     *
     * @param array<string, mixed>|null $extensionData
     * @return array{
     *     product_key: string,
     *     product_id: int,
     *     product_name: string,
     *     product_url: string,
     *     key_type: string,
     *     key_type_label: string,
     *     lifetime: bool,
     *     product_status: string,
     *     connections: int[],
     *     expires: bool,
     *     expired: bool,
     *     expiring: bool,
     *     sites_max: int,
     *     sites_active: int,
     *     autorenew: bool,
     *     maxed: bool,
     * }
     * @throws AdapterException
     */
    public function convertFromSource(?array $extensionData = null) : array
    {
        if (empty($extensionData)) {
            throw new AdapterException('Missing required extension data.');
        }

        return [
            'product_key'    => $this->generateProductKey(),
            'product_id'     => TypeHelper::int(ArrayHelper::get($extensionData, '_product_id'), 0),
            'product_name'   => TypeHelper::string(ArrayHelper::get($extensionData, 'Name'), ''),
            'product_url'    => TypeHelper::string(ArrayHelper::get($extensionData, 'PluginURI'), ''),
            'key_type'       => 'single',
            'key_type_label' => 'Single site',
            'lifetime'       => true,
            'product_status' => 'publish',
            'connections'    => [
                TypeHelper::int(ArrayHelper::get(WC_Helper_Options::get('auth'), 'site_id'), 0),
            ],
            'expires'      => false,
            'expired'      => false,
            'expiring'     => false,
            'sites_max'    => 1,
            'sites_active' => 1,
            'autorenew'    => false,
            'maxed'        => false,
        ];
    }

    /**
     * Generates a product key.
     *
     * We don't have a real key, so we have to make one up. The value isn't important, but it needs to start with
     * something distinctive and related to GoDaddy that we can target in JavaScript in order to customize the
     * appearance of all GoDaddy-included extensions.
     *
     * @return string
     */
    protected function generateProductKey() : string
    {
        return 'godaddymwc-'.rand(0, PHP_INT_MAX);
    }

    public function convertToSource()
    {
        // no-op
    }
}
