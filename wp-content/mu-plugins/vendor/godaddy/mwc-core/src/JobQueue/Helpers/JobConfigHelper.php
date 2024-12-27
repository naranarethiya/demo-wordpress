<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions\InvalidJobException;
use GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions\UnregisteredJobException;

/**
 * Helper class to aid in interacting with the `queue.php` configuration file and settings.
 */
class JobConfigHelper
{
    /**
     * Gets registered jobs from the queue configuration.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getRegisteredJobs() : array
    {
        /** @var array<string, array<string, mixed>> $registeredJobs */
        $registeredJobs = TypeHelper::array(Configuration::get('queue.jobs'), []);

        return $registeredJobs;
    }

    /**
     * Converts the array of job class names into their equivalent config keys, while maintaining the supplied order.
     *
     * @param class-string<QueueableJobContract>[] $jobNames
     * @return string[] corresponding job keys, as registered in `queue.jobs` config (e.g. ['job1', 'job2'])
     */
    public static function convertJobClassNamesToKeys(array $jobNames) : array
    {
        $registeredJobs = static::getRegisteredJobs();

        if (empty($registeredJobs)) {
            return [];
        }

        $keys = [];

        foreach ($jobNames as $className) {
            try {
                $keys[] = static::getJobKeyByClassName($className);
            } catch(Exception $e) {
                // do nothing; job will be excluded from the final array
            }
        }

        return $keys;
    }

    /**
     * Converts the array of job config keys to their equivalent {@see QueueableJobContract} class names, while
     * maintaining the supplied order.
     *
     * This also validates that the returned job class names are instances of {@see QueueableJobContract}.
     *
     * @param string[] $keys
     * @return class-string<QueueableJobContract>[]
     */
    public static function convertJobKeysToClassNames(array $keys) : array
    {
        $registeredJobs = static::getRegisteredJobs();

        if (empty($registeredJobs)) {
            return [];
        }

        $jobClassNames = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $registeredJobs) && $jobName = ArrayHelper::get($registeredJobs, "{$key}.job")) {
                $jobClassNames[] = $jobName;
            }
        }

        return TypeHelper::arrayOfClassStrings($jobClassNames, QueueableJobContract::class);
    }

    /**
     * Gets an instance of a job by its config key.
     *
     * @param string $jobKey
     * @return QueueableJobContract
     * @throws ContainerException
     * @throws EntryNotFoundException
     * @throws Exception
     */
    public static function getJobByKey(string $jobKey) : QueueableJobContract
    {
        $jobClassName = TypeHelper::string(Configuration::get("queue.jobs.{$jobKey}.job"), '');
        if (empty($jobClassName)) {
            throw new UnregisteredJobException("The {$jobKey} job key does not exist in the configuration.");
        }

        $instance = ContainerFactory::getInstance()->getSharedContainer()->get($jobClassName);
        if (! $instance instanceof QueueableJobContract) {
            throw new InvalidJobException("The {$jobKey} job must implement the QueueableJobContract interface.");
        }

        return $instance;
    }

    /**
     * Gets the config key for a given job class.
     *
     * @param class-string<QueueableJobContract> $jobClassName
     * @return string
     * @throws UnregisteredJobException
     */
    public static function getJobKeyByClassName(string $jobClassName) : string
    {
        $jobs = ArrayHelper::where(static::getRegisteredJobs(), function ($jobSettings) use ($jobClassName) {
            return $jobClassName === ArrayHelper::get($jobSettings, 'job');
        });

        if (! $index = TypeHelper::string(array_key_first($jobs), '')) {
            throw new UnregisteredJobException("The job class {$jobClassName} does not exist in the configuration.");
        }

        return $index;
    }
}
