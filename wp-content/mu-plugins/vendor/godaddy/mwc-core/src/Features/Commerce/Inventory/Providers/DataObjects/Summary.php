<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects;

class Summary extends AbstractDataObject
{
    public ?string $inventorySummaryId = null;
    public string $productId;
    public float $totalAvailable;
    public float $totalOnHand;
    public float $totalBackordered;
    public ?float $lowInventoryThreshold = null;
    public ?float $maxBackorders = null;
    public ?float $maxReservations = null;
    public bool $isBackorderable = false;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     inventorySummaryId?: ?string,
     *     productId: string,
     *     totalAvailable: float,
     *     totalOnHand: float,
     *     totalBackordered: float,
     *     lowInventoryThreshold?: ?float,
     *     maxBackorders?: ?float,
     *     maxReservations?: ?float,
     *     isBackorderable: bool,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
