<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

interface GoDaddyCustomerContract extends ModelContract
{
    /**
     * Gets the customer's ID.
     *
     * @return string|null
     */
    public function getId() : ?string;

    /**
     * Gets the customer's federation partner ID.
     *
     * @return string|null
     */
    public function getFederationPartnerId() : ?string;

    /**
     * Sets the customer's ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setId(string $value);

    /**
     * Sets the customer's federation partner ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setFederationPartnerId(string $value);
}
