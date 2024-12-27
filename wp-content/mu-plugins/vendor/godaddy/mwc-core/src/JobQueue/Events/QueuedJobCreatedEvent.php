<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\JobQueue;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\HasChainedJobsTrait;

/**
 * Event fired when a job is created ({@see JobQueue::chain()}).
 *
 * @method static static getNewInstance(array $chained, ?array $args = null, bool $withOverlapping = true)
 */
class QueuedJobCreatedEvent implements EventContract
{
    use CanGetNewInstanceTrait;
    use HasChainedJobsTrait;
}
