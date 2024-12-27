<?php

namespace GoDaddy\WordPress\MWC\Common\Container\Contracts;

interface HasDefinitionIdentifiersContract
{
    /**
     * Returns the list of definition identifiers provided by this service provider.
     *
     * @return string[]
     */
    public function getDefinitionIdentifiers() : array;
}
