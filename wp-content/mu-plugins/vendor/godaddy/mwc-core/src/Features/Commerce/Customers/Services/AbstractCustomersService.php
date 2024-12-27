<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\Contracts\CustomersProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpdateCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpsertCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataSources\Adapters\CustomerBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\CustomersProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\Contracts\CreateOrUpdateCustomerOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses\Contracts\CreateOrUpdateCustomerResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Responses\CreateOrUpdateCustomerResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCustomerRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CommerceContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;

abstract class AbstractCustomersService implements CustomersServiceContract
{
    protected CommerceContextContract $commerceContext;

    /** @var CustomersProviderContract */
    protected CustomersProviderContract $customersProvider;

    /** @var CustomersMappingServiceContract */
    protected CustomersMappingServiceContract $customersMappingService;

    /**
     * Constructor.
     *
     * @param CommerceContextContract $commerceContext
     * @param CustomersProviderContract $customersProvider
     * @param CustomersMappingServiceContract $customersMappingService
     */
    final public function __construct(
        CommerceContextContract $commerceContext,
        CustomersProviderContract $customersProvider,
        CustomersMappingServiceContract $customersMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->customersProvider = $customersProvider;
        $this->customersMappingService = $customersMappingService;
    }

    /**
     * Creates or updates the customer.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return CreateOrUpdateCustomerResponseContract
     * @throws CommerceExceptionContract
     */
    public function createOrUpdateCustomer(CreateOrUpdateCustomerOperationContract $operation) : CreateOrUpdateCustomerResponseContract
    {
        $customer = $this->createOrUpdateCustomerInRemoteService($operation);

        if (! $customer->customerId) {
            throw MissingCustomerRemoteIdException::withDefaultMessage();
        }

        $this->customersMappingService->saveRemoteId($operation->getCustomer(), $customer->customerId);

        return new CreateOrUpdateCustomerResponse($customer->customerId);
    }

    /**
     * Creates an instance in the remote service.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return CustomerBase
     * @throws CommerceException
     * @throws CommerceExceptionContract
     * @throws MissingCustomerRemoteIdException
     */
    protected function createOrUpdateCustomerInRemoteService(CreateOrUpdateCustomerOperationContract $operation) : CustomerBase
    {
        $customerData = $this->customersProvider->customers()->createOrUpdate(
            $this->getCreateOrUpdateCustomerInput($operation)
        );

        if (! $customerData->customerId) {
            throw MissingCustomerRemoteIdException::withDefaultMessage();
        }

        return $customerData;
    }

    /**
     * Creates an instance of {@see UpsertCustomerInput} using the information from the given customer.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return UpsertCustomerInput
     * @throws CommerceExceptionContract
     */
    protected function getCreateOrUpdateCustomerInput(CreateOrUpdateCustomerOperationContract $operation) : UpsertCustomerInput
    {
        $customerData = $this->getCustomerDataForCreate($operation->getCustomer());

        if (! $customerData) {
            throw new CommerceException('Unable to prepare customer input data.');
        }

        return new UpsertCustomerInput([
            'storeId'  => $this->commerceContext->getStoreId(),
            'customer' => $customerData,
        ]);
    }

    /**
     * Attempts to create a customer data object for the given customer.
     *
     * @param CustomerContract $customer
     * @return CustomerBase|null
     */
    protected function getCustomerDataForCreate(CustomerContract $customer) : ?CustomerBase
    {
        return $this->buildCustomerData($customer, $this->customersMappingService->getRemoteId($customer));
    }

    /**
     * Updates a customer in the Commerce platform.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return CreateOrUpdateCustomerResponseContract
     * @throws CommerceExceptionContract
     */
    public function updateCustomer(CreateOrUpdateCustomerOperationContract $operation) : CreateOrUpdateCustomerResponseContract
    {
        $output = $this->customersProvider->customers()->update($this->getUpdateCustomerInput($operation));

        return new CreateOrUpdateCustomerResponse($output->customerId);
    }

    /**
     * Gets an input data object for the update operation using the information from the given customer.
     *
     * @param CreateOrUpdateCustomerOperationContract $operation
     * @return UpdateCustomerInput
     * @throws MissingCustomerRemoteIdException
     * @throws CommerceException
     */
    protected function getUpdateCustomerInput(CreateOrUpdateCustomerOperationContract $operation) : UpdateCustomerInput
    {
        $remoteId = $this->customersMappingService->getRemoteId($operation->getCustomer());

        if (! $remoteId) {
            throw new MissingCustomerRemoteIdException('The customer has no UUID.');
        }

        return new UpdateCustomerInput([
            'storeId'    => $this->commerceContext->getStoreId(),
            'customer'   => $this->getCustomerDataForUpdate($operation->getCustomer()),
            'customerId' => $remoteId,
        ]);
    }

    /**
     * Creates a {@see CustomerBase} data object using the information from the given customer.
     *
     * @param CustomerContract $customer
     * @return CustomerBase
     * @throws CommerceException
     */
    protected function getCustomerDataForUpdate(CustomerContract $customer) : CustomerBase
    {
        if (! $data = $this->buildCustomerData($customer)) {
            throw new CommerceException('Unable to prepare customer input data.');
        }

        return $data;
    }

    /**
     * Attempts to build a new {@see CustomerBase} data object for the given customer.
     *
     * @param CustomerContract $customer
     * @param string|null $remoteId
     * @return CustomerBase|null
     */
    protected function buildCustomerData(CustomerContract $customer, ?string $remoteId = null) : ?CustomerBase
    {
        $adapter = CustomerBaseAdapter::getNewInstance();

        if ($remoteId) {
            $adapter->setRemoteId($remoteId);
        }

        return $adapter->convertToSource($customer);
    }

    /**
     * Gets an instance of the service with the default store ID and customer provider.
     *
     * @param CommerceContextContract|null $commerceContext
     * @param CustomersProviderContract|null $customersProvider
     * @param CustomersMappingServiceContract|null $customersMappingService
     * @return AbstractCustomersService
     * @throws CommerceExceptionContract
     */
    public static function getNewInstance(
        CommerceContextContract $commerceContext = null,
        CustomersProviderContract $customersProvider = null,
        CustomersMappingServiceContract $customersMappingService = null
    ) : AbstractCustomersService {
        $commerceContext = $commerceContext ?? CommerceContext::seed(['storeId' => (string) Commerce::getStoreId()]);

        return new static(
            $commerceContext,
            $customersProvider ?? CustomersProvider::getNewInstance(),
            $customersMappingService ?? new CustomersMappingService(static::getCustomerMappingStrategyFactory())
        );
    }

    /**
     * Gets an instance of {@see CustomerMappingStrategyFactory} from the container.
     *
     * @return CustomerMappingStrategyFactory
     * @throws CommerceExceptionContract
     */
    protected static function getCustomerMappingStrategyFactory() : CustomerMappingStrategyFactory
    {
        try {
            /** @var CustomerMappingStrategyFactory $factory */
            $factory = ContainerFactory::getInstance()->getSharedContainer()->get(CustomerMappingStrategyFactory::class);

            return $factory;
        } catch(Exception $e) {
            throw new CommerceException($e->getMessage(), $e);
        }
    }
}
