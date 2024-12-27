<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanConvertDateTimeFromTimestampTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits\CanParseNullableStringPropertyTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Context;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;

class SubscriptionAdapter
{
    use CanParseNullableStringPropertyTrait;
    use CanConvertDateTimeFromTimestampTrait;

    /**
     * Converts an array of data into a {@see Subscription} object.
     *
     * @param array<string, mixed> $data
     * @return Subscription
     */
    public function convertToSourceFromArray(array $data) : Subscription
    {
        return new Subscription([
            'id'          => TypeHelper::string(ArrayHelper::get($data, 'id', ''), ''),
            'name'        => TypeHelper::string(ArrayHelper::get($data, 'name', ''), ''),
            'description' => $this->parseNullableStringProperty($data, 'description'),
            'context'     => new Context([
                'storeId' => TypeHelper::string(ArrayHelper::get($data, 'context.storeId', ''), ''),
            ]),
            'eventTypes'  => TypeHelper::arrayOfStrings(ArrayHelper::get($data, 'eventTypes', [])),
            'deliveryUrl' => TypeHelper::string(ArrayHelper::get($data, 'deliveryUrl', ''), ''),
            'isEnabled'   => TypeHelper::bool(ArrayHelper::get($data, 'isEnabled', false), false),
            'createdAt'   => $this->convertDateTimeFromTimestamp($data, 'createdAt'),
            'updatedAt'   => $this->convertDateTimeFromTimestamp($data, 'updatedAt'),
            'secret'      => $this->parseNullableStringProperty($data, 'secret'),
        ]);
    }
}
