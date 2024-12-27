<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;

/**
 * Adds an adapter class the ability to convert Inventory API common properties that are shared between its possible response structures.
 */
trait CanConvertResponseTrait
{
    /**
     * Converts response date time (if any).
     *
     * @param array<string, mixed> $body
     * @param string $index
     *
     * @return array<string, DateTime>
     *
     * @throws Exception
     */
    protected function convertDateTime(array $body, string $index) : array
    {
        $data = [];

        $dateTimeString = TypeHelper::string(ArrayHelper::get($body, $index), '');

        if (! empty($dateTimeString)) {
            $data[$index] = new DateTime($dateTimeString);
        }

        return $data;
    }

    /**
     * Converts response external ID instances (if any).
     *
     * @param array<string, mixed> $body
     * @return array<string, ExternalId[]>
     */
    protected function convertExternalIds(array $body) : array
    {
        $data = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($body, 'externalIds', [])) as $externalId) {
            $data['externalIds'][] = new ExternalId([
                'type'  => TypeHelper::string(ArrayHelper::get($externalId, 'type'), ''),
                'value' => TypeHelper::string(ArrayHelper::get($externalId, 'value'), ''),
            ]);
        }

        return $data;
    }
}
