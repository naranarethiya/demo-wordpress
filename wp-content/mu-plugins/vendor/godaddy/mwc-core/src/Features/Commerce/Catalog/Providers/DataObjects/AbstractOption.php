<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

/**
 * An option (attribute) that can be applied to the product. Examples might include:
 *  - Color (red, blue)
 *  - Size (small, medium, large).
 *
 * This object contains any common properties shared among all option types.
 */
abstract class AbstractOption extends AbstractDataObject
{
    /** @var string an option containing a list of values */
    const TYPE_LIST = 'LIST';

    /** @var string text-based option (unused in WooCommerce at the present time) */
    const TYPE_TEXT = 'TEXT';

    /** @var string option meant to describe variants with its values */
    const TYPE_VARIANT_LIST = 'VARIANT_LIST';

    /** @var string the number of selections that are required for a given option. This can be a single value (`1`) or a range (`0..1`) */
    public string $cardinality = '1';

    /** @var string internal identifier of the option (e.g. "color" or "size") */
    public string $name;

    /** @var string display name of the option (e.g. "Color" or "Size") */
    public string $presentation;

    /** @var string one of `LIST`, `VARIANT_LIST`, or `TEXT` */
    public string $type;
}
