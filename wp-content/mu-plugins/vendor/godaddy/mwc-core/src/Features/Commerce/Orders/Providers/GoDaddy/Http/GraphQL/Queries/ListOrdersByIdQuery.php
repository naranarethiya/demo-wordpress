<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Traits\HasOrderFieldsFragmentTrait;

class ListOrdersByIdQuery extends AbstractGraphQLOperation
{
    use HasOrderFieldsFragmentTrait;

    protected $operation = 'query GetOrdersById($ids: [ID!]!) {
        nodes(ids: $ids) {
            ...orderFields
        }
    }';
}
