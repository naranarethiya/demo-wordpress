<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * The Account model.
 */
class Account extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $emailAddress;

    /** @var array */
    protected $capabilities;

    /**
     * Gets the ID.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Sets the ID.
     *
     * @param string $value
     * @return Account
     */
    public function setId(string $value) : Account
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $value
     * @return Account
     */
    public function setName(string $value) : Account
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Gets the email address.
     *
     * @return string|null
     */
    public function getEmailAddress() : ?string
    {
        return $this->emailAddress;
    }

    /**
     * Sets the email address.
     *
     * @param string $value
     * @return Account
     */
    public function setEmailAddress(string $value) : Account
    {
        $this->emailAddress = $value;

        return $this;
    }

    /**
     * Gets the capabilities.
     *
     * @return array|null
     */
    public function getCapabilities() : ?array
    {
        return $this->capabilities;
    }

    /**
     * Sets the capabilities.
     *
     * @param array $value
     * @return Account
     */
    public function setCapabilities(array $value) : Account
    {
        $this->capabilities = $value;

        return $this;
    }
}
