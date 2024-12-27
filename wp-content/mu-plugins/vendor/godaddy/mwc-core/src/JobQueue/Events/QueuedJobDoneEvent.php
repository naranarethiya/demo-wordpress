<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\HasChainedJobsTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\QueueableJobTrait;

/**
 * Event fired when a job has finished processing ({@see QueueableJobTrait::jobDone()}).
 *
 * @method static static getNewInstance(array $chained, ?array $args = null)
 */
class QueuedJobDoneEvent implements EventContract
{
    use CanGetNewInstanceTrait;
    use HasChainedJobsTrait;
}
