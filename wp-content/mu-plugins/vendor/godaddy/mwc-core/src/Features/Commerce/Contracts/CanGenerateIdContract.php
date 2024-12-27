<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

interface CanGenerateIdContract
{
    /**
     * Generates a single ID.
     *
     * @return string
     */
    public function generateId() : string;
}
