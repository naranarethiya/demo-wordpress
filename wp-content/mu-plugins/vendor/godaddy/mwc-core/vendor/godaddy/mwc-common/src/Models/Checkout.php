<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * Checkout model.
 */
class Checkout extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use HasNumericIdentifierTrait;

    /** @var Cart */
    protected $cart;

    /** @var string */
    protected $emailAddress = '';

    /** @var User|null */
    protected $customer;

    /** @var DateTime */
    protected $createdAt;

    /** @var DateTime */
    protected $updatedAt;

    /**
     * Gets the linked Cart object.
     *
     * @return Cart
     */
    public function getCart() : Cart
    {
        return $this->cart;
    }

    /**
     * Gets the e-mail address.
     *
     * @return string
     */
    public function getEmailAddress() : string
    {
        return $this->emailAddress;
    }

    /**
     * Gets the customer's user object, if applicable.
     *
     * @return User|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Gets the DateTime when this Checkout was created.
     *
     * @return DateTime
     */
    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets the DateTime when this Checkout was last updated.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the linked Cart object.
     *
     * @param Cart $value
     * @return Checkout
     */
    public function setCart(Cart $value) : Checkout
    {
        $this->cart = $value;

        return $this;
    }

    /**
     * Sets the e-mail address.
     *
     * @param string $value
     * @return Checkout
     */
    public function setEmailAddress(string $value) : Checkout
    {
        $this->emailAddress = $value;

        return $this;
    }

    /**
     * Sets the customer's user object.
     *
     * @param User $value
     * @return Checkout
     */
    public function setCustomer(User $value) : Checkout
    {
        $this->customer = $value;

        return $this;
    }

    /**
     * Sets the DateTime when this Checkout was created.
     *
     * @param DateTime $value
     * @return Checkout
     */
    public function setCreatedAt(DateTime $value) : Checkout
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Sets the DateTime when the Checkout was last updated.
     *
     * @param DateTime $value
     * @return Checkout
     */
    public function setUpdatedAt(DateTime $value) : Checkout
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Determines whether or not this model has been created yet.
     *
     * @return bool
     */
    public function isNew() : bool
    {
        return empty($this->getId());
    }
}
