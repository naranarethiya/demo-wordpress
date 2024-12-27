<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Common\Schedule\Types\SingleAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Contracts\PollingProcessorContract;

/**
 * Abstract class for polling processors.
 */
abstract class AbstractPollingProcessor extends AbstractFeature implements PollingProcessorContract
{
    /** @var DateInterval interval that the job should run at */
    protected DateInterval $jobInterval;

    /** @var bool|null internal flag to be set by the poll method while a poll is in progress */
    protected ?bool $pollingInProgress = null;

    /** @var string unique identifier for this processing job, also used in the configuration array */
    public static string $pollingJobConfigName;

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_polling.jobs.'.static::getJobConfigName();
    }

    /**
     * Gets the name of the job, as defined in the configuration.
     *
     * @return string
     */
    protected static function getJobConfigName() : string
    {
        return static::$pollingJobConfigName;
    }

    /**
     * Gets the polling job action name.
     *
     * @return string
     */
    protected static function getPollingJobName() : string
    {
        return 'mwc_gd_commerce_polling_'.static::getJobConfigName();
    }

    /**
     * Loads the polling processor.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        $this->setJobInterval($this->makeJobInterval());

        $this->addHooks();
    }

    /**
     * Makes a job interval from the provided configuration array.
     *
     * @return DateInterval
     */
    protected function makeJobInterval() : DateInterval
    {
        $defaultDuration = 'PT2M';
        $duration = static::getConfiguration('jobDateInterval.default', $defaultDuration);

        if ($overrideDuration = static::getConfiguration('jobDateInterval.override', $duration)) {
            $duration = $overrideDuration;
        }

        try {
            return new DateInterval(TypeHelper::string($duration, $defaultDuration));
        } catch (Exception $exception) {
            return new DateInterval($defaultDuration);
        }
    }

    /**
     * Gets the polling job schedule object.
     *
     * @return SingleAction
     */
    protected static function getPollingJob() : SingleAction
    {
        return Schedule::singleAction()->setName(static::getPollingJobName());
    }

    /**
     * Determines if a polling job has been scheduled.
     *
     * @NOTE to check this reliably, this method should be called after WordPress `init` hook time
     *
     * @return bool
     */
    public function isPollingJobScheduled() : bool
    {
        return static::getPollingJob()->isScheduled();
    }

    /**
     * Determines if a polling job is in progress.
     *
     * This may be used as an internal flag in concrete implementations.
     *
     * @return bool
     */
    public function isPollingJobInProgress() : bool
    {
        return (bool) $this->pollingInProgress;
    }

    /**
     * Adds hooks for registering a polling job.
     *
     * @return void
     * @throws Exception
     */
    protected function addHooks() : void
    {
        Register::action()
            ->setGroup(static::getPollingJobName())
            ->setHandler([$this, 'poll'])
            ->execute();
    }

    /**
     * Schedules a polling job.
     *
     * Pushes all exceptions to Sentry.
     *
     * @param array<string, mixed> $args
     * @return void
     */
    public static function schedule(array $args = []) : void
    {
        try {
            static::getPollingJob()
                ->setScheduleAt(static::getScheduleAt())
                ->setArguments($args)
                ->schedule();
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }

    /**
     * Gets the next scheduled date/time for the polling job.
     *
     * Defaults to current time, but can be overridden by concrete implementations to add a delay.
     *
     * @return DateTime
     */
    protected static function getScheduleAt() : DateTime
    {
        /* @phpstan-ignore-next-line this won't return false because the timestamp will always be valid */
        return DateTime::createFromFormat('U', (string) time());
    }

    /**
     * Gets the name of the option where the timestamp of the last polling job is stored.
     *
     * @return string
     */
    protected function getLastPolledAtOptionName() : string
    {
        return sprintf('mwc_gd_commerce_%s_last_polled_at', static::getJobConfigName());
    }

    /**
     * Gets the job interval.
     *
     * @return DateInterval
     */
    public function getJobInterval() : DateInterval
    {
        return $this->jobInterval;
    }

    /**
     * Sets the job interval.
     *
     * @param DateInterval $value
     * @return PollingProcessorContract
     */
    public function setJobInterval(DateInterval $value) : PollingProcessorContract
    {
        $this->jobInterval = $value;

        return $this;
    }

    /**
     * Gets the timestamp when the last polling job occurred.
     *
     * @return int|null
     */
    public function getLastPolledAt() : ?int
    {
        return TypeHelper::int(get_option($this->getLastPolledAtOptionName()), 0) ?: null;
    }

    /**
     * Sets the timestamp when the last polling job occurred.
     *
     * @param int|null $value defaults to now
     * @return void
     */
    public function setLastPolledAt(?int $value = null) : void
    {
        update_option($this->getLastPolledAtOptionName(), $value ?: time());
    }

    /**
     * Polling function to be implemented in concrete objects.
     *
     * @return void
     */
    abstract public function poll() : void;
}
