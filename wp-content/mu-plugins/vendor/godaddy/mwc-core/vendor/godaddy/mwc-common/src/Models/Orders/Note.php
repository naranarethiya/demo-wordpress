<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * An object representation of an {@see Order} note.
 */
class Note extends AbstractNote implements HasNumericIdentifierContract
{
    use HasNumericIdentifierTrait;
}
