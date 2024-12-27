<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects;

use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\Contracts\JobSettingsContract;

/**
 * Job settings DTO.
 */
class JobSettings extends AbstractDataObject implements JobSettingsContract
{
    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
