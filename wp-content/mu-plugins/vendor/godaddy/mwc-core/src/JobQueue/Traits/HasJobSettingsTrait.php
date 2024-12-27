<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Traits;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\Contracts\JobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\JobSettings;

/**
 * Trait for jobs that have settings. Implements common methods in {@see HasJobSettingsContract}.
 *
 * @method string getJobKey()
 */
trait HasJobSettingsTrait
{
    /** @var JobSettingsContract settings for the current job */
    protected JobSettingsContract $jobSettings;

    /**
     * Sets the job settings against the handler.
     *
     * @param JobSettingsContract $jobSettings
     * @return $this
     */
    public function setJobSettings(JobSettingsContract $jobSettings)
    {
        $this->jobSettings = $jobSettings;

        return $this;
    }

    /**
     * Gets the batch settings.
     *
     * @return JobSettingsContract
     */
    public function getJobSettings() : JobSettingsContract
    {
        return $this->jobSettings;
    }

    /**
     * Configures the settings for this job.
     *
     * Makes a {@see JobSettingsContract} DTO with settings overrides from the config for this job (if applicable).
     *
     * @return JobSettingsContract settings for this job (default: {@see JobSettings})
     */
    public function configureJobSettings() : JobSettingsContract
    {
        $settingsClass = method_exists($this, 'getJobKey')
            ? TypeHelper::string(Configuration::get("queue.jobs.{$this->getJobKey()}.settings.class"), '')
            : '';

        if (
            empty($settingsClass) ||
            ! class_exists($settingsClass) ||
            ! method_exists($settingsClass, 'getNewInstance') ||
            ! in_array(JobSettingsContract::class, class_implements($settingsClass), true)
        ) {
            return JobSettings::getNewInstance([]);
        }

        return $settingsClass::getNewInstance(
            TypeHelper::array(
                Configuration::get("queue.jobs.{$this->getJobKey()}.settings.values"),
                []
            )
        );
    }
}
