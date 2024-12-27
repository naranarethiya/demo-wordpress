<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Trait for parsing a nullable string property from a Commerce response payload.
 */
trait CanParseNullableStringPropertyTrait
{
    /**
     * Parses a simple nullable property from a Commerce response to string or null.
     *
     * @param array<string, mixed> $responseData
     * @param string $key
     * @return string|null
     */
    protected function parseNullableStringProperty(array $responseData, string $key) : ?string
    {
        $value = ArrayHelper::get($responseData, $key);

        return $value ? TypeHelper::string($value, '') : null;
    }
}
