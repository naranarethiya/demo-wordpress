<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Traits;

use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Services\ScheduledJobQueueDispatchService;

/**
 * Trait for classes that hold queued jobs that are chained. {@see QueueableJobContract}.
 */
trait HasChainedJobsTrait
{
    /** @var class-string<QueueableJobContract>[] job class names, in the order they should be executed */
    public array $chained;

    /** @var array<mixed>|null optional arguments to be passed to the job */
    public ?array $args;

    /** @var bool should the job allow overlapping {@see ScheduledJobQueueDispatchService} */
    public bool $withOverlapping = true;

    /**
     * Constructor.
     *
     * @param class-string<QueueableJobContract>[] $chained
     * @param array<mixed>|null $args
     * @param bool $withOverlapping
     */
    public function __construct(array $chained, ?array $args = null, bool $withOverlapping = true)
    {
        $this->chained = $chained;
        $this->args = $args;
        $this->withOverlapping = $withOverlapping;
    }
}
