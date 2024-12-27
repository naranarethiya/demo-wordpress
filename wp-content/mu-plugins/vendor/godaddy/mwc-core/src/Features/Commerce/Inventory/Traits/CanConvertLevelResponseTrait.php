<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

/**
 * Adds an adapter class the ability to convert a level endpoint response to a {@see Level} object.
 */
trait CanConvertLevelResponseTrait
{
    use CanConvertResponseTrait;

    /**
     * Converts {@see ResponseContract} data into a {@see Level} object.
     *
     * @param array<string, mixed> $inventoryLevelData
     *
     * @return Level
     *
     * @throws Exception
     */
    protected function convertLevelResponse(array $inventoryLevelData) : Level
    {
        $data = ArrayHelper::combine(
            [
                'inventoryLevelId'    => ArrayHelper::get($inventoryLevelData, 'inventoryLevelId'),
                'inventorySummaryId'  => ArrayHelper::get($inventoryLevelData, 'inventorySummaryId'),
                'inventoryLocationId' => ArrayHelper::get($inventoryLevelData, 'inventoryLocationId'),
                'productId'           => ArrayHelper::get($inventoryLevelData, 'productId'),
                'quantity'            => ArrayHelper::get($inventoryLevelData, 'quantity'),
            ],
            $this->convertExternalIds($inventoryLevelData),
            $this->convertCost($inventoryLevelData),
            $this->convertDateTime($inventoryLevelData, 'createdAt'),
            $this->convertDateTime($inventoryLevelData, 'updatedAt'),
        );

        // @phpstan-ignore-next-line
        return new Level($data);
    }

    /**
     * Converts the level response cost instance (if any).
     *
     * @param array<string, mixed> $body
     * @return array<string, SimpleMoney>
     */
    protected function convertCost(array $body) : array
    {
        $data = [];

        if ($cost = ArrayHelper::wrap(ArrayHelper::get($body, 'cost'))) {
            $data['cost'] = new SimpleMoney([
                'currencyCode' => TypeHelper::string(ArrayHelper::get($cost, 'currencyCode'), ''),
                'value'        => TypeHelper::int(ArrayHelper::get($cost, 'value'), 0),
            ]);
        }

        return $data;
    }
}
