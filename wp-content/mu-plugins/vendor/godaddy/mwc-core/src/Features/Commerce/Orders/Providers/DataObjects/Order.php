<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class Order extends AbstractDataObject
{
    public BillingInfo $billing;

    /** @var non-empty-string|null */
    public ?string $cartId = null;

    public OrderContext $context;

    /** @var non-empty-string|null */
    public ?string $customerId = null;

    /** @var LineItem[] */
    public array $lineItems;

    /** @var non-empty-string|null */
    public ?string $id = null;

    /** @var Note[] */
    public array $notes = [];

    /**
     * Order number.
     *
     * This property is nullable because we don't always have a value. However, null and
     * empty strings are not accepted values for input to the commerce order API.
     *
     * @var non-empty-string|null
     */
    public ?string $number = null;

    /** @var string|null */
    public ?string $numberDisplay = null;

    /** @var non-empty-string */
    public string $processedAt;

    public OrderStatuses $statuses;

    public OrderTotals $totals;

    /**
     * Constructor.
     *
     * @param array{
     *     billing: BillingInfo,
     *     cartId?: ?non-empty-string,
     *     context: OrderContext,
     *     customerId?: ?non-empty-string,
     *     lineItems: LineItem[],
     *     id?: ?non-empty-string,
     *     notes?: Note[],
     *     number?: ?non-empty-string,
     *     numberDisplay?: ?string,
     *     processedAt: non-empty-string,
     *     statuses: OrderStatuses,
     *     totals: OrderTotals
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
