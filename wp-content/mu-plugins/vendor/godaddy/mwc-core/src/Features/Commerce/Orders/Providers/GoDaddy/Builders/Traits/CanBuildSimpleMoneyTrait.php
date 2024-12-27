<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

/**
 * Builds a {@see SimpleMoney} object, with typed properties, from a data associative array.
 */
trait CanBuildSimpleMoneyTrait
{
    /**
     * Builds a {@see SimpleMoney} object using the given data.
     *
     * @param array<string, mixed> $data
     *
     * @return SimpleMoney
     */
    protected function buildSimpleMoney(array $data) : SimpleMoney
    {
        return new SimpleMoney([
            'currencyCode' => TypeHelper::string(ArrayHelper::get($data, 'currencyCode'), ''),
            'value'        => TypeHelper::int(ArrayHelper::get($data, 'value'), 0),
        ]);
    }
}
