<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\AbstractOrdersRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

abstract class AbstractOrdersRequestAdapter extends AbstractGatewayRequestAdapter
{
    protected AbstractOrdersRequestInput $input;

    /**
     * Constructor.
     *
     * @param AbstractOrdersRequestInput $input The input to use when making this request.
     */
    public function __construct(AbstractOrdersRequestInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth($this->getGraphQLOperation())
            ->setStoreId($this->input->storeId)
            ->setMethod('post');
    }

    /**
     * Gets the graphQL operation to use for this request.
     *
     * @return GraphQLOperationContract
     */
    abstract protected function getGraphQLOperation() : GraphQLOperationContract;
}
