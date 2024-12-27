<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Operations\CreateOrUpdateCustomerOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

class NewRegisteredCustomerInterceptor extends AbstractInterceptor
{
    protected CustomersMappingServiceContract $customersMappingService;
    protected CustomersServiceContract $customersService;

    /**
     * Contains the information needed to register and de-register the wp_pre_insert_user_data hook. It is stored as a
     * property because it needs to be used across multiple methods inside this interceptor.
     *
     * @var RegisterFilter|null
     */
    protected ?RegisterFilter $preInsertUserDataFilter = null;

    public function __construct(
        CustomersServiceContract $customersService,
        CustomersMappingServiceContract $customersMappingService
    ) {
        $this->customersMappingService = $customersMappingService;
        $this->customersService = $customersService;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
                ->setGroup('woocommerce_new_customer_data')
                ->setHandler([$this, 'initiateCustomerDataPush'])
                ->execute();

        Register::action()
                ->setGroup('woocommerce_created_customer')
                ->setArgumentsCount(3)
                ->setHandler([$this, 'mapCustomerToPlatform'])
                ->execute();
    }

    /**
     * Registers a filter to capture customer data.
     *
     * @param mixed $data
     * @return mixed
     */
    public function initiateCustomerDataPush($data)
    {
        try {
            $this->getPreInsertUserDataFilter()->execute();
        } catch (Exception $e) {
            // silently ignore exception from RegisterAction::execute()
        }

        return $data;
    }

    /**
     * Pushes customer data to the commerce platform.
     *
     * @param mixed $data
     * @param mixed $update
     * @param mixed $user_id
     * @param mixed $userdata
     *
     * @return mixed
     */
    public function pushCustomerData($data, $update, $user_id, $userdata)
    {
        $this->tryToCreateCustomerFromPreInsertUserData(TypeHelper::array($data, []), TypeHelper::array($userdata, []));

        $this->deregisterPreInsertUserDataFilter();

        return $data;
    }

    /**
     * Attempts to create a customer record using the given user data.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $userdata
     * @return void
     */
    protected function tryToCreateCustomerFromPreInsertUserData($data, $userdata) : void
    {
        $this->tryToCreateCustomer($this->makeCustomerFromPreInsertUserData($data, $userdata));
    }

    /**
     * Attempts to create a remote record for the given customer.
     *
     * @param CustomerContract $customer
     * @return void
     */
    protected function tryToCreateCustomer(CustomerContract $customer) : void
    {
        try {
            $this->customersService->createOrUpdateCustomer(CreateOrUpdateCustomerOperation::fromCustomer($customer));
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance('An error occurred trying to create a remote record for a registered customer: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Deregisters the handler for the wp_pre_insert_user_data filter.
     *
     * @return void
     */
    protected function deregisterPreInsertUserDataFilter() : void
    {
        try {
            $this->getPreInsertUserDataFilter()->deregister();
        } catch (Exception $exception) {
            // silently ignore exceptions from RegisterFilter::deregister()
        }
    }

    /**
     * Maps the remote customer platform UUID to the local ID. The UUID comes from the mapping service.
     *
     * @param int $customerLocalId
     * @param array<string, mixed> $newCustomerData
     * @param string $_passwordGenerated unused
     *
     * @return void
     */
    public function mapCustomerToPlatform($customerLocalId, $newCustomerData, $_passwordGenerated) : void
    {
        $customerEmail = TypeHelper::string(ArrayHelper::get($newCustomerData, 'user_email'), '');
        $customer = (new Customer())
            ->setId((int) $customerLocalId)
            ->setEmail($customerEmail ?: null);

        $remoteId = $this->customersMappingService->getRemoteId($customer);

        try {
            $this->customersMappingService->saveRemoteId($customer, (string) $remoteId);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance(
                'An error occurred trying to save remote ID for a new registered customer: '.$exception->getMessage(),
                $exception
            );
        }
    }

    /**
     * Builds a RegisterFilter for wp_pre_insert_user_data.
     *
     * @return RegisterFilter
     */
    protected function buildPreInsertUserDataFilter() : RegisterFilter
    {
        return Register::filter()
                ->setGroup('wp_pre_insert_user_data')
                ->setArgumentsCount(4)
                ->setHandler([$this, 'pushCustomerData']);
    }

    /**
     * Memoized value of the $preInsertUserDataFilter property.
     *
     * @return RegisterFilter
     */
    protected function getPreInsertUserDataFilter() : RegisterFilter
    {
        return $this->preInsertUserDataFilter ??= $this->buildPreInsertUserDataFilter();
    }

    /**
     * Makes a new Customer instantiated with data from the wp_pre_insert_user_data filter.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $userdata
     *
     * @return CustomerContract
     */
    protected function makeCustomerFromPreInsertUserData(array $data, array $userdata) : CustomerContract
    {
        return (new Customer())
            ->setEmail(TypeHelper::string(ArrayHelper::get($data, 'user_email'), '') ?: null)
            ->setFirstName(TypeHelper::string(ArrayHelper::get($userdata, 'first_name'), '') ?: null)
            ->setLastName(TypeHelper::string(ArrayHelper::get($userdata, 'last_name'), '') ?: null);
    }
}
