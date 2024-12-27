<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\CreateOrderInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderOutput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders\OrderBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Mutations\AddOrderMutation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

/**
 * @method static static getNewInstance(CreateOrderInput $input)
 */
class CreateOrderRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected CreateOrderInput $input;

    public function __construct(CreateOrderInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth($this->getAddOrderOperation())
            ->setStoreId($this->input->storeId)
            ->setMethod('post');
    }

    /**
     * Gets the AddOrderMutation using the input.
     *
     * @return AbstractGraphQLOperation
     */
    protected function getAddOrderOperation() : AbstractGraphQLOperation
    {
        return (new AddOrderMutation())
            ->setVariables([
                'input' => $this->getInputVariable(),
            ]);
    }

    /**
     * Gets the value for the input variable.
     *
     * @return array<string, mixed>
     */
    protected function getInputVariable() : array
    {
        $data = $this->input->order->toArray();

        $data = $this->removeKeysIfValueIsNull($data, [
            'number',
        ]);

        return array_merge(
            ArrayHelper::except($data, 'id'),
            $this->getItemsWithIdRemoved($data, 'lineItems'),
            $this->getItemsWithIdRemoved($data, 'notes'),
        );
    }

    /**
     * Remove the specified keys from the given array of data if their value is null.
     *
     * @param array<string, mixed> $data source data
     * @param string[] $keys keys present in the source data
     * @return array<string, mixed>
     */
    protected function removeKeysIfValueIsNull(array $data, array $keys) : array
    {
        foreach ($keys as $key) {
            if (is_null(ArrayHelper::get($data, $key, 0))) {
                ArrayHelper::remove($data, $key);
            }
        }

        return $data;
    }

    /**
     * Maps over the array of items under $key, removing id from each item.
     *
     * @param array<string, mixed> $data source data
     * @param string $key a key to access the list of items inside $data
     *
     * @return array<string, array<int, mixed>> $key => array of items without IDs
     */
    protected function getItemsWithIdRemoved(array $data, string $key) : array
    {
        if (! ArrayHelper::exists($data, $key)) {
            return [];
        }

        return [$key => array_map(
            static fn (array $item) => ArrayHelper::except($item, 'id'),
            ArrayHelper::wrap(ArrayHelper::get($data, $key)),
        )];
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response)
    {
        return new OrderOutput([
            'order' => OrderBuilder::getNewInstance()
                ->setData(TypeHelper::array(ArrayHelper::get($response->getBody(), 'data.addOrder'), []))
                ->build(),
        ]);
    }
}
