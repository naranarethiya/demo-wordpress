<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Trait for converting query args from a source input.
 */
trait CanConvertQueryArgsFromSourceTrait
{
    /**
     * Converts query args for a request.
     *
     * @return array<string, mixed>
     */
    protected function convertQueryArgsFromSource() : array
    {
        $queryArgs = $this->input->queryArgs;

        if (ArrayHelper::accessible($queryArgs['ids'] ?? null)) {
            $queryArgs['ids'] = implode(',', $queryArgs['ids']);
        }

        return array_map(function ($value) {
            // Map boolean to string: `toArray()` results in booleans becoming 1 or 0, but the API requires true/false strings.
            return is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }, $queryArgs);
    }
}
