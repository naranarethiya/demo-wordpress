<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class LineItemDetails extends AbstractDataObject
{
    /** @var non-empty-string|null SKU of the product */
    public ?string $sku = null;

    /** @var string Product's associated asset URL */
    public string $productAssetUrl = '';

    /** @var non-empty-string|null Product's unit of measure */
    public ?string $unitOfMeasure = null;

    /** @var LineItemOption[] */
    public array $selectedOptions = [];

    /**
     * Constructor.
     *
     * @param array{
     *     sku?: ?non-empty-string,
     *     productAssetUrl?: string,
     *     unitOfMeasure?: ?non-empty-string,
     *     selectedOptions?: LineItemOption[]
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
