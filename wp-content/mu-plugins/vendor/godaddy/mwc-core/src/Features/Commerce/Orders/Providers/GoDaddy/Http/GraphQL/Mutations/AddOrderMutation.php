<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Traits\HasOrderFieldsFragmentTrait;

class AddOrderMutation extends AbstractGraphQLOperation
{
    use HasOrderFieldsFragmentTrait;

    protected $operation = 'mutation($input: OrderInput!) {
        addOrder(input: $input) {
            ...orderFields
        }
    }';

    protected $operationType = 'mutation';
}
