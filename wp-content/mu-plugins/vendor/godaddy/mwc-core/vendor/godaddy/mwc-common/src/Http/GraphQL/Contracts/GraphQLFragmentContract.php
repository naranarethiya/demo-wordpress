<?php

namespace GoDaddy\WordPress\MWC\Common\Http\GraphQL\Contracts;

interface GraphQLFragmentContract
{
    /**
     * Gets GraphQL fragment construct.
     *
     * @return non-empty-string
     */
    public function __toString() : string;
}
