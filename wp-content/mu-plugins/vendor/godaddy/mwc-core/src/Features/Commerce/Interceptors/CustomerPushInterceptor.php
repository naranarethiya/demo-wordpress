<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushFailedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushSuccessfulEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs\CustomerPushJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies\Contracts\CustomerPushStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Strategies\CustomerPushStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\CustomerDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidCustomerIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingCustomerIdException;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * A handler for the scheduled action associated with a CustomerPushJob.
 */
class CustomerPushInterceptor extends AbstractInterceptor
{
    /** @var string */
    public const COMMERCE_PROVIDER_NAME = 'gd_commerce';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::action()
            ->setGroup('mwc_push_commerce_customer_objects')
            ->setArgumentsCount(2)
            ->setHandler([$this, 'push'])
            ->execute();
    }

    /**
     * Creates a customerPushFailedEvent using the specified job.
     *
     * @param CustomerPushJob $job
     *
     * @return CustomerPushFailedEvent
     */
    protected function createFailedEventForJob(CustomerPushJob $job) : CustomerPushFailedEvent
    {
        return CustomerPushFailedEvent::getNewInstance()->setJob($job);
    }

    /**
     * Possibly handles a push job.
     *
     * @param mixed $jobId
     * @param mixed $objectIds
     *
     * @return void
     */
    public function push($jobId, $objectIds) : void
    {
        $jobId = TypeHelper::int($jobId, 0);
        $objectIds = TypeHelper::arrayOfIntegers($objectIds);

        if (! $jobId || empty($objectIds) || ! ($job = CustomerPushJob::get($jobId)) || 'customer' !== $job->getObjectType()) {
            return;
        }

        try {
            $customer = $this->getCustomer(array_shift($objectIds));
        } catch (InvalidCustomerIdException|MissingCustomerIdException $exception) {
            Events::broadcast($this->createFailedEventForJob($job)->setException($exception));

            return;
        }

        try {
            $this->getCustomerStrategy($customer)->sync();
        } catch (CommerceExceptionContract $exception) {
            Events::broadcast($this->createFailedEventForJob($job)->setException($exception)->setCustomers([$customer]));

            return;
        }

        Events::broadcast(CustomerPushSuccessfulEvent::getNewInstance()->setJob($job)->setCustomers([$customer]));
    }

    /**
     * @param Customer $customer
     *
     * @return CustomerPushStrategyContract
     * @throws CommerceExceptionContract
     */
    protected function getCustomerStrategy(Customer $customer) : CustomerPushStrategyContract
    {
        return CustomerPushStrategyFactory::getNewInstance()->getStrategyFor($customer);
    }

    /**
     * @param int $customerId
     *
     * @return Customer
     * @throws InvalidCustomerIdException
     * @throws MissingCustomerIdException
     */
    protected function getCustomer(int $customerId) : Customer
    {
        return CustomerDataStore::getNewInstance(static::COMMERCE_PROVIDER_NAME)->read($customerId);
    }
}
