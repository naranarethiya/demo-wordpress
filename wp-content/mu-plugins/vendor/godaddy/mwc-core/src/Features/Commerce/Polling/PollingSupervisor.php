<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Contracts\PollingProcessorContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Contracts\PollingSupervisorContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Exceptions\PollingProcessorNotEnabledException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\Exceptions\PollingSupervisorException;

/**
 * Supervisor to manage and potentially dispatch polling processor jobs.
 *
 * @see AbstractPollingProcessor
 */
class PollingSupervisor extends AbstractInterceptor implements PollingSupervisorContract
{
    /** @var string name of the recurring job action */
    protected const SUPERVISOR_RECURRING_JOB_NAME = 'mwc_gd_commerce_polling_supervisor';

    /** @var PollingProcessorContract[] the initialized processors, for all those that are enabled */
    protected array $pollingProcessors = [];

    /**
     * Loads the feature.
     *
     * @return void
     */
    public function load() : void
    {
        $this->initializeProcessors();

        parent::load();
    }

    /**
     * Initializes all the registered polling jobs.
     *
     * @return void
     */
    protected function initializeProcessors() : void
    {
        $jobs = TypeHelper::array(Configuration::get('features.commerce_polling.jobs'), []);

        foreach ($jobs as $jobDetails) {
            $jobDetails = TypeHelper::array($jobDetails, []);

            try {
                if (ArrayHelper::get($jobDetails, 'enabled')) {
                    $this->pollingProcessors[] = $this->initializeProcessor($jobDetails);
                }
            } catch (PollingProcessorNotEnabledException $e) {
                // no action needed
            } catch (Exception $e) {
                SentryException::getNewInstance('Failed to initialize polling processor.', $e);
            }
        }
    }

    /**
     * Initializes a job processor from the provided array of job details.
     *
     * @param array<string, mixed> $jobDetails
     * @return PollingProcessorContract
     * @throws PollingSupervisorException|PollingProcessorNotEnabledException
     */
    protected function initializeProcessor(array $jobDetails) : PollingProcessorContract
    {
        /** @var class-string<PollingProcessorContract> $className */
        $className = TypeHelper::string(ArrayHelper::get($jobDetails, 'jobProcessor'), '');

        $this->validateProcessor($className);

        try {
            /** @var PollingProcessorContract $processor */
            $processor = ContainerFactory::getInstance()->getSharedContainer()->get($className);
            $processor->load();
        } catch (Exception $exception) {
            throw new PollingSupervisorException('Failed to initialize polling processor.', $exception);
        }

        return $processor;
    }

    /**
     * Validates a job processor class name.
     *
     * @param class-string<PollingProcessorContract> $className
     * @return void
     * @throws PollingSupervisorException|PollingProcessorNotEnabledException
     */
    protected function validateProcessor(string $className) : void
    {
        if (empty($className)) {
            throw new PollingSupervisorException('Missing jobProcessor configuration key.');
        }

        $classInterface = class_implements($className);

        if (empty($classInterface) || ! in_array(PollingProcessorContract::class, $classInterface)) {
            throw new PollingSupervisorException('Job processor must implement the PollingProcessorContract interface.');
        }

        if (! $className::shouldLoad()) {
            throw new PollingProcessorNotEnabledException(sprintf('Could not load polling processor %s.', $className));
        }
    }

    /**
     * Adds hooks to handle scheduled jobs.
     *
     * @internal
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        // creates the recurring supervisor job
        Register::action()
            ->setGroup('admin_init') // the reason this run at admin_init time is that the Action Scheduler will be available to check if the job is already scheduled
            ->setHandler([$this, 'maybeScheduleSupervisorJob'])
            ->execute();

        // callback for the above supervisor job
        Register::action()
            ->setGroup(static::SUPERVISOR_RECURRING_JOB_NAME)
            ->setHandler([$this, 'maybeSchedulePollingJobs'])
            ->execute();
    }

    /**
     * Schedules the recurring supervisor job, if it's not already scheduled.
     *
     * @internal
     *
     * @return void
     */
    public function maybeScheduleSupervisorJob() : void
    {
        $supervisorJob = Schedule::recurringAction()->setName(static::SUPERVISOR_RECURRING_JOB_NAME);

        if (! $supervisorJob->isScheduled()) {
            try {
                $supervisorJob
                    ->setScheduleAt(new DateTime('now'))
                    ->setInterval($this->getSupervisorInterval())
                    ->schedule();
            } catch(Exception $e) {
                // catch exceptions in hook callback to prevent runtime errors
                SentryException::getNewInstance('Failed to schedule Commerce supervisor job.', $e);
            }
        }
    }

    /**
     * Gets the interval at which the supervisor should run.
     *
     * @return DateInterval
     */
    protected function getSupervisorInterval() : DateInterval
    {
        $intervalString = TypeHelper::string(Configuration::get('features.commerce_polling.supervisorDateInterval'), 'PT2M');

        try {
            return new DateInterval($intervalString);
        } catch(Exception $e) {
            return new DateInterval('PT2M');
        }
    }

    /**
     * Maybe schedules the associated polling processor jobs.
     *
     * @internal
     *
     * @return void
     */
    public function maybeSchedulePollingJobs() : void
    {
        foreach ($this->pollingProcessors as $pollingProcessor) {
            if ($this->shouldSchedulePollingJob($pollingProcessor)) {
                $pollingProcessor::schedule();
            }
        }
    }

    /**
     * Determines if it's time to schedule another polling job.
     *
     * @param PollingProcessorContract $pollingProcessor
     * @return bool returns false if the interval has not elapsed or if there's already a poll in progress
     */
    protected function shouldSchedulePollingJob(PollingProcessorContract $pollingProcessor) : bool
    {
        $shouldPoll = true;

        try {
            if ($lastPolledAtTimestamp = $pollingProcessor->getLastPolledAt()) {
                $lastPolledAtDate = DateTime::createFromFormat('U', (string) $lastPolledAtTimestamp);
                $currentDate = new DateTime('now');

                $shouldPoll = ! $lastPolledAtDate || $currentDate >= $lastPolledAtDate->add($pollingProcessor->getJobInterval());
            }
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            $shouldPoll = false;
        }

        return $shouldPoll
            && ! $pollingProcessor->isPollingJobScheduled()
            && ! $pollingProcessor->isPollingJobInProgress();
    }
}
