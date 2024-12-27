<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Traits;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Contracts\GraphQLFragmentContract;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Traits\HasGraphQLFragmentsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Fragments\OrderFieldsFragment;

trait HasOrderFieldsFragmentTrait
{
    use HasGraphQLFragmentsTrait;

    /**
     * {@inheritDoc}
     *
     * @return GraphQLFragmentContract[]
     */
    protected function getFragments() : array
    {
        return [new OrderFieldsFragment()];
    }
}
