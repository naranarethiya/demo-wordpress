<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Traits\HasOrderFieldsFragmentTrait;

class FulfillAndCompleteOrderMutation extends AbstractGraphQLOperation
{
    use HasOrderFieldsFragmentTrait;

    protected $operation = 'mutation FulfillAndCompleteOrder($completeOrderId: ID!) {
        fulfillOrder: fulfillOrder(id: $completeOrderId) {
            ...orderFields
        }
        updateOrderStatus: completeOrder(id: $completeOrderId) {
           ...orderFields
        }
    }';

    protected $operationType = 'mutation';
}
