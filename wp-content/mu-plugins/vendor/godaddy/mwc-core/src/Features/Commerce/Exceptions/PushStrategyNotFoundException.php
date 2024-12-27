<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions;

class PushStrategyNotFoundException extends PushFailedException
{
    /** {@inheritdoc} */
    protected string $errorCode = 'COMMERCE_PUSH_STRATEGY_NOT_FOUND_EXCEPTION';
}
