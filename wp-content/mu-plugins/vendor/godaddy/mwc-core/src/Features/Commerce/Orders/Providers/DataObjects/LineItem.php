<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemMode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemStatus;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

class LineItem extends AbstractDataObject
{
    /** @var LineItemDetails|null All product snapshot-related information for a line item */
    public ?LineItemDetails $details = null;

    /** @var non-empty-string|null */
    public ?string $id = null;

    /** @var LineItemMode::* */
    public string $fulfillmentMode;

    /** @var non-empty-string|null */
    public ?string $fulfillmentChannelId = null;

    public string $name;

    /** @var non-empty-string|null */
    public ?string $productId = null;

    public float $quantity = 1;

    /** @var LineItemStatus::* */
    public string $status;

    public LineItemTotals $totals;

    /** @var LineItemType::* */
    public string $type;

    public SimpleMoney $unitAmount;

    /**
     * Constructor.
     *
     * @param array{
     *     details?: ?LineItemDetails,
     *     id?: ?non-empty-string,
     *     fulfillmentMode: LineItemMode::*,
     *     fulfillmentChannelId?: ?string,
     *     name: string,
     *     productId?: ?non-empty-string,
     *     quantity: float,
     *     status: LineItemStatus::*,
     *     totals: LineItemTotals,
     *     type: LineItemType::*,
     *     unitAmount: SimpleMoney
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
