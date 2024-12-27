<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Events\BackfillJobSkippedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\AbstractSkippedResourcesRepository;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\BatchJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\HasJobSettingsContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\Contracts\QueueableJobContract;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobOutcome;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;
use GoDaddy\WordPress\MWC\Core\JobQueue\Traits\BatchJobTrait;

/**
 * Abstract backfill resource job class.
 *
 * @method BatchJobSettings getJobSettings()
 */
abstract class AbstractBackfillResourceJob implements QueueableJobContract, HasJobSettingsContract, BatchJobContract
{
    use BatchJobTrait;

    /** @var AbstractSkippedResourcesRepository skipped resources repository */
    protected AbstractSkippedResourcesRepository $skippedResourcesRepository;

    /**
     * Constructor.
     *
     * @param AbstractSkippedResourcesRepository $skippedResourcesRepository
     */
    public function __construct(AbstractSkippedResourcesRepository $skippedResourcesRepository)
    {
        $this->skippedResourcesRepository = $skippedResourcesRepository;

        $this->setJobSettings($this->configureJobSettings());
    }

    /**
     * Processes a single batch.
     *
     * This method handles:
     *
     *  - Querying for local resources that do not exist upstream (using the supplied {@see BatchJobSettings}).
     *  - Inserting them into the remote platform.
     *  - Updating the mapping table accordingly.
     *
     * @return BatchJobOutcome
     * @throws WordPressDatabaseException|Exception
     */
    protected function processBatch() : BatchJobOutcome
    {
        if ($this->hasWriteCapability()) {
            $localResources = $this->getLocalResources();

            if (empty($localResources)) {
                // no more records to process!
                return $this->makeOutcome();
            }

            $this->createResourcesInPlatform($localResources);
        } else {
            Events::broadcast(BackfillJobSkippedEvent::getNewInstance($this->getJobKey()));
        }

        return $this->makeOutcome();
    }

    /**
     * Performs any required actions when all batches have been successfully completed, and the job is fully done.
     *
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function onAllBatchesCompleted() : void
    {
        $this->purgeSkippedItems();
    }

    /**
     * Queries for the local resource objects.
     *
     * @return array<mixed>|null
     */
    abstract protected function getLocalResources() : ?array;

    /**
     * Attempts to create remote resources from the local copies.
     *
     * @param array<mixed> $localResources
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function createResourcesInPlatform(array $localResources) : void
    {
        foreach ($localResources as $product) {
            $this->maybeCreateResourceInPlatform($product);
        }
    }

    /**
     * Creates a resource in the platform if it's eligible. Logs ineligible and failed items.
     *
     * @param mixed $resource
     * @return void
     * @throws WordPressDatabaseException
     */
    abstract protected function maybeCreateResourceInPlatform($resource) : void;

    /**
     * Records a resource ID as skipped so we can exclude it from queries in the next batch.
     *
     * @param int $localId
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function markLocalResourceAsSkipped(int $localId) : void
    {
        $this->skippedResourcesRepository->add($localId);
    }

    /**
     * Deletes all of the current resource type from the skipped items table. We only want to keep items in this table
     * for one full cycle of backfilling. Once we've completed a cycle, we purge the table so we can start fresh next time.
     * This ensures we don't end up with stale items in the table that are maybe eligible now.
     *
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function purgeSkippedItems() : void
    {
        $this->skippedResourcesRepository->deleteAll();
    }

    /**
     * Has write capability.
     *
     * @return bool
     */
    protected function hasWriteCapability() : bool
    {
        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_WRITE);
    }

    /**
     * {@inheritDoc}
     */
    public function getJobKey() : string
    {
        // @phpstan-ignore-next-line
        return static::JOB_KEY;
    }
}
