<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GetBusinessRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;
use ReflectionException;

/**
 * An adapter to build a GET business request and convert its response to a Business model.
 */
class GetBusinessRequestAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Business */
    protected $source;

    /**
     * @param Business $business
     */
    public function __construct(Business $business)
    {
        $this->source = $business;
    }

    /**
     * Converts the business data to a GET business request.
     *
     * @return GetBusinessRequest
     * @throws ReflectionException
     */
    public function convertFromSource() : GetBusinessRequest
    {
        // NOTE: the business ID is already set in the request object using the payments.poynt.businessId configuration {@cwiseman 2022-03-25}
        return GetBusinessRequest::getNewInstance();
    }

    /**
     * Converts the given response to the source business model.
     *
     * @param Response|null $response
     *
     * @return Business
     */
    public function convertToSource(Response $response = null) : Business
    {
        $data = $response ? $response->getBody() : [];

        if (empty($data)) {
            return $this->source;
        }

        $this->source->setProperties($data);

        return $this->source;
    }
}
