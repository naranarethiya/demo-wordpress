<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertResponseTrait;

trait CanConvertSummaryResponseTrait
{
    use CanConvertResponseTrait;

    /**
     * Converts the given response data into a summary data object.
     *
     * @param array<string, mixed> $responseData
     *
     * @return Summary
     * @throws Exception
     */
    protected function convertSummaryResponse(array $responseData) : Summary
    {
        // these values can be null, so only cast to a float if there is a value
        $lowInventoryThreshold = ArrayHelper::get($responseData, 'lowInventoryThreshold');
        $maxBackorders = ArrayHelper::get($responseData, 'maxBackorders');
        $maxReservations = ArrayHelper::get($responseData, 'maxReservations');

        if (! is_null($lowInventoryThreshold)) {
            $lowInventoryThreshold = TypeHelper::float($lowInventoryThreshold, 0.0);
        }

        if (! is_null($maxBackorders)) {
            $maxBackorders = TypeHelper::float($maxBackorders, 0.0);
        }

        if (! is_null($maxReservations)) {
            $maxReservations = TypeHelper::float($maxReservations, 0.0);
        }

        $data = ArrayHelper::combine(
            [
                'inventorySummaryId'    => ArrayHelper::get($responseData, 'inventorySummaryId'),
                'productId'             => TypeHelper::string(ArrayHelper::get($responseData, 'productId'), ''),
                'totalAvailable'        => TypeHelper::float(ArrayHelper::get($responseData, 'totalAvailable'), 0),
                'totalOnHand'           => TypeHelper::float(ArrayHelper::get($responseData, 'totalOnHand'), 0),
                'totalBackordered'      => TypeHelper::float(ArrayHelper::get($responseData, 'totalBackordered'), 0),
                'isBackorderable'       => TypeHelper::bool(ArrayHelper::get($responseData, 'isBackorderable'), false),
                'lowInventoryThreshold' => $lowInventoryThreshold,
                'maxBackorders'         => $maxBackorders,
                'maxReservations'       => $maxReservations,
            ],
            $this->convertExternalIds($responseData),
            $this->convertDateTime($responseData, 'createdAt'),
            $this->convertDateTime($responseData, 'updatedAt'),
        );

        /* @phpstan-ignore-next-line */
        return new Summary($data);
    }
}
