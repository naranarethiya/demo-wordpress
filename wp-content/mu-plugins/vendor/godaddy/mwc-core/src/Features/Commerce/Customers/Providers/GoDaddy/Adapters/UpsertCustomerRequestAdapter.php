<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\CustomerBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\DataObjects\UpsertCustomerInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingCustomerRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * @method static static getNewInstance(UpsertCustomerInput $input)
 */
class UpsertCustomerRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    /** @var UpsertCustomerInput */
    protected UpsertCustomerInput $input;

    /**
     * Constructor.
     *
     * @param UpsertCustomerInput $input
     */
    public function __construct(UpsertCustomerInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setStoreId($this->input->storeId)
            ->setBody([
                'customer' => $this->input->customer->toArray(),
                'source'   => $this->input->customerSource,
            ])
            ->setPath('/customers')
            ->setMethod('post');
    }

    /**
     * Converts ResponseContract to CustomerBase object.
     *
     * @param ResponseContract $response
     * @return CustomerBase
     * @throws CommerceExceptionContract
     */
    protected function convertResponse(ResponseContract $response) : CustomerBase
    {
        $customerId = $this->getCustomerUuidFromResponse($response);

        if (! $customerId) {
            throw MissingCustomerRemoteIdException::withDefaultMessage();
        }

        $this->input->customer->customerId = $customerId;

        return $this->input->customer;
    }

    /**
     * Gets the UUID of the customer from the response links.
     *
     * @param ResponseContract $response
     * @return string|null
     */
    protected function getCustomerUuidFromResponse(ResponseContract $response) : ?string
    {
        $customerUrl = $this->getCustomerUrlFromResponse($response);

        if (! $customerUrl) {
            return null;
        }

        // matches a string that ends with /customers/{uuid}
        if (! preg_match('#/customers/([0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12})$#', $customerUrl, $matches)) {
            return null;
        }

        return TypeHelper::string(ArrayHelper::get($matches, '1'), '') ?: null;
    }

    /**
     * Gets the URL for the customer from the response links.
     *
     * @param ResponseContract $response
     * @return string|null
     */
    protected function getCustomerUrlFromResponse(ResponseContract $response) : ?string
    {
        $links = TypeHelper::array(ArrayHelper::get((array) $response->getBody(), 'links'), []);

        foreach ($links as $link) {
            $data = TypeHelper::array($link, []);

            if (ArrayHelper::get($data, 'rel') !== 'customer') {
                continue;
            }

            $href = TypeHelper::string(ArrayHelper::get($data, 'href'), '');

            if (! $href) {
                continue;
            }

            return $href;
        }

        return null;
    }
}
