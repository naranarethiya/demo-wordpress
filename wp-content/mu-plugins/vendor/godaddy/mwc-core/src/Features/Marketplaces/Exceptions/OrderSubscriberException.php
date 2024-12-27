<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderChannelSubscriber;

/**
 * Thrown during {@see OrderChannelSubscriber} errors.
 */
class OrderSubscriberException extends BaseException
{
}
