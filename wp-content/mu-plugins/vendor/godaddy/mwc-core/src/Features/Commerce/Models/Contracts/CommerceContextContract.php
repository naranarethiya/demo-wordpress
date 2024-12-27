<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts;

interface CommerceContextContract
{
    /**
     * Gets context's ID.
     *
     * @return int|null
     */
    public function getId() : ?int;

    /**
     * Gets context's Store ID.
     *
     * @return string
     */
    public function getStoreId() : string;
}
