<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\GoDaddyCustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

class GoDaddyCustomer extends AbstractModel implements GoDaddyCustomerContract
{
    use CanBulkAssignPropertiesTrait;

    /** @var string id */
    protected $id;

    /** @var string federationPartnerId */
    protected $federationPartnerId;

    /**
     * Gets the customer's ID.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Gets the customer's federation partner ID.
     *
     * @return string|null
     */
    public function getFederationPartnerId() : ?string
    {
        return $this->federationPartnerId;
    }

    /**
     * Sets the customer's ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setId(string $value) : self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the customer's federation partner ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setFederationPartnerId(string $value) : self
    {
        $this->federationPartnerId = $value;

        return $this;
    }
}
