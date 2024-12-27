<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\AbstractOrdersRequestInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\ListOrdersByIdInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Order;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrdersListOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders\OrderBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Queries\ListOrdersByIdQuery;

/**
 * @method static static getNewInstance(ListOrdersByIdInput $input)
 */
class ListOrdersByIdRequestAdapter extends AbstractOrdersRequestAdapter
{
    use CanGetNewInstanceTrait;

    /** @var ListOrdersByIdInput */
    protected AbstractOrdersRequestInput $input;

    /**
     * {@inheritDoc}
     */
    public function __construct(ListOrdersByIdInput $input)
    {
        parent::__construct($input);
    }

    /**
     * {@inheritDoc}
     */
    protected function getGraphQLOperation() : GraphQLOperationContract
    {
        return (new ListOrdersByIdQuery())->setVariables($this->getQueryVariables());
    }

    /**
     * Gets an array with an IDs entry.
     *
     * @return array<string, mixed>
     */
    protected function getQueryVariables() : array
    {
        return [
            'ids' => $this->input->ids,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response) : OrdersListOutput
    {
        return new OrdersListOutput([
            'orders' => $this->getOrdersFromResponse($response),
        ]);
    }

    /**
     * Gets a list of order data objects from the given response.
     *
     * @param ResponseContract $response
     * @return Order[]
     */
    protected function getOrdersFromResponse(ResponseContract $response) : array
    {
        $builder = OrderBuilder::getNewInstance();
        $nodes = array_values(
            array_filter(TypeHelper::array(ArrayHelper::get($response->getBody(), 'data.nodes'), []))
        );

        return array_map(static fn ($node) : Order => $builder->setData(ArrayHelper::wrap($node))->build(), $nodes);
    }
}
