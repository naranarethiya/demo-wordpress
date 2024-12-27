<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Traits\HasOrderFieldsFragmentTrait;

class CancelOrderMutation extends AbstractGraphQLOperation
{
    use HasOrderFieldsFragmentTrait;

    protected $operation = 'mutation CancelOrder($cancelOrderId: ID!) {
      updateOrderStatus: cancelOrder(id: $cancelOrderId) {
        ...orderFields
      }
    }';

    protected $operationType = 'mutation';
}
