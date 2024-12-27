<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\SetupIntentAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\SetupIntent;
use Stripe\Exception\ApiErrorException;

/**
 * Setup Intent gateway.
 */
class SetupIntentGateway extends StripeGateway
{
    /**
     * Returns a setup intent object for the given ID.
     *
     * @param string $id
     *
     * @return SetupIntent
     *
     * @throws ApiErrorException
     */
    public function get(string $id) : SetupIntent
    {
        $args = [
            'expand' => [
                'payment_method',
            ],
        ];
        $this->maybeLogApiRequest(__METHOD__, ['id' => $id] + $args);
        $response = $this->getClient()->setupIntents->retrieve($id, $args);
        $this->maybeLogApiResponse(__METHOD__, $response);

        return SetupIntentAdapter::getNewInstance(SetupIntent::getNewInstance())->convertToSource($response->toArray());
    }

    /**
     * Creates and returns a SetupIntent model.
     *
     * @param SetupIntent $setupIntent
     * @return SetupIntent
     * @throws ApiErrorException
     */
    public function create(SetupIntent $setupIntent) : SetupIntent
    {
        $args = SetupIntentAdapter::getNewInstance($setupIntent)->convertFromSource();
        $this->maybeLogApiRequest(__METHOD__, $args);
        $response = $this->getClient()->setupIntents->create($args);
        $this->maybeLogApiResponse(__METHOD__, $response);

        return SetupIntentAdapter::getNewInstance($setupIntent)->convertToSource($response->toArray());
    }
}
