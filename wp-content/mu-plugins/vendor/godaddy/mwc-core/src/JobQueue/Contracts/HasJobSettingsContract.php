<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Contracts;

use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\Contracts\JobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\JobSettings;

/**
 * Describes classes that have a job setting {@see JobSettingsContract}.
 */
interface HasJobSettingsContract
{
    /**
     * Sets the job settings using a {@see JobSettings}-compatible DTO.
     *
     * @param JobSettingsContract $jobSettings
     * @return $this
     */
    public function setJobSettings(JobSettingsContract $jobSettings);

    /**
     * Gets the job settings.
     *
     * @return JobSettingsContract
     */
    public function getJobSettings() : JobSettingsContract;

    /**
     * Configures the {@see JobSettingsContract}-compatible DTO.
     *
     * @return JobSettingsContract
     */
    public function configureJobSettings() : JobSettingsContract;
}
