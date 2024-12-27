<?php

declare(strict_types=1);

namespace GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Argument\Literal;

use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Argument\LiteralArgument;

class ArrayArgument extends LiteralArgument
{
    public function __construct(array $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_ARRAY);
    }
}
