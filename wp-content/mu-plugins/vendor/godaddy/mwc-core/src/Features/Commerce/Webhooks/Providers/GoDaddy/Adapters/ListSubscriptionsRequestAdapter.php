<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\ListSubscriptionsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataSources\Adapters\SubscriptionAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Http\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\ListSubscriptionsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\ListSubscriptionsResponse;

/**
 * @method static static getNewInstance(ListSubscriptionsInput $input)
 */
class ListSubscriptionsRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected ListSubscriptionsInput $input;

    protected SubscriptionAdapter $subscriptionAdapter;

    /**
     * @param ListSubscriptionsInput $input
     * @throws ContainerException|EntryNotFoundException
     */
    public function __construct(ListSubscriptionsInput $input)
    {
        $this->input = $input;

        $this->subscriptionAdapter = ContainerFactory::getInstance()->getSharedContainer()->get(SubscriptionAdapter::class);
    }

    /**
     * {@inheritDoc}
     *
     * @return ListSubscriptionsResponseContract
     */
    protected function convertResponse(ResponseContract $response) : ListSubscriptionsResponseContract
    {
        $subscriptions = array_map(function ($data) {
            return $this->convertSubscriptionResponse(TypeHelper::array($data, []));
        }, ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'subscriptions', [])));

        return ListSubscriptionsResponse::getNewInstance()->setSubscriptions($subscriptions);
    }

    /**
     * Converts the response to a {@see Subscription} instance.
     *
     * @param array<string, mixed> $subscriptionData
     * @return Subscription
     */
    protected function convertSubscriptionResponse(array $subscriptionData) : Subscription
    {
        return $this->subscriptionAdapter->convertToSourceFromArray($subscriptionData);
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        $storeId = $this->input->storeId;

        return Request::withAuth()
            ->setStoreId($storeId)
            ->setQuery(['storeId' => $storeId])
            ->setPath('/webhook-subscriptions')
            ->setMethod('get');
    }
}
