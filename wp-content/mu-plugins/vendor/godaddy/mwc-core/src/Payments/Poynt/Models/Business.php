<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * The business model.
 */
class Business extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string */
    protected $id;

    /** @var string */
    protected $doingBusinessAs;

    /** @var string */
    protected $emailAddress;

    /**
     * Gets the ID.
     *
     * @return string
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Sets the ID.
     *
     * @param string $id
     * @return Business
     */
    public function setId(string $id) : Business
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the doing business as.
     *
     * @return string
     */
    public function getDoingBusinessAs() : ?string
    {
        return $this->doingBusinessAs;
    }

    /**
     * Sets the doing business as.
     *
     * @param string $doingBusinessAs
     * @return Business
     */
    public function setDoingBusinessAs(string $doingBusinessAs) : Business
    {
        $this->doingBusinessAs = $doingBusinessAs;

        return $this;
    }

    /**
     * Gets the email address.
     *
     * @return string
     */
    public function getEmailAddress() : ?string
    {
        return $this->emailAddress;
    }

    /**
     * Sets the email address.
     *
     * @param string $emailAddress
     * @return Business
     */
    public function setEmailAddress(string $emailAddress) : Business
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }
}
