<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects;

/**
 * Information about the outcome of a single batch process.
 */
class BatchJobOutcome extends AbstractDataObject
{
    /** @var bool true if there are no more items of this resource to process; false if there are still some outstanding */
    public bool $isComplete = true;

    /**
     * Constructor.
     *
     * @param array{
     *     isComplete?: bool,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
