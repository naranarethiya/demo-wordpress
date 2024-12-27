<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects;

/**
 * Settings for job that processes items in batches.
 *
 * @method static static getNewInstance(array $data = [])
 */
class BatchJobSettings extends JobSettings
{
    /** @var int maximum number of resources to process in this batch */
    public int $maxPerBatch = 50;

    /**
     * Constructor.
     *
     * @param array{
     *     maxPerBatch?: int,
     * } $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
