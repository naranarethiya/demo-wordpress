<?php

namespace GoDaddy\WordPress\MWC\Common\Http\GraphQL\Traits;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Contracts\GraphQLFragmentContract;

/**
 * A trait used by subclasses of {@see AbstractGraphQLOperation} that define a getFragments() method.
 */
trait HasGraphQLFragmentsTrait
{
    /**
     * Gets GraphQL operation, such as query or mutation, including selection fields and argument definition.
     *
     * @return string
     */
    public function getOperation() : string
    {
        return $this->getFragmentsAsString()."\n".$this->operation;
    }

    /**
     * Joins all fragments as a newline-separated string.
     *
     * @return string
     */
    protected function getFragmentsAsString() : string
    {
        return implode("\n", $this->getFragments());
    }

    /**
     * Gets GraphQL fragments.
     *
     * @return GraphQLFragmentContract[]
     */
    protected function getFragments() : array
    {
        return [];
    }
}
