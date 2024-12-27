<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\CustomerContract;
use GoDaddy\WordPress\MWC\Common\Traits\BillableTrait;
use GoDaddy\WordPress\MWC\Common\Traits\ShippableTrait;

class AbstractCustomer extends AbstractModel implements CustomerContract
{
    use BillableTrait;
    use ShippableTrait;

    /** @var non-empty-string|null the first name of the customer */
    protected ?string $firstName = null;

    /** @var non-empty-string|null the last name of the customer */
    protected ?string $lastName = null;

    /** @var non-empty-string|null the email address of the customer */
    protected ?string $email = null;

    /**
     * {@inheritDoc}
     */
    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstName(?string $value)
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    /**
     * {@inheritDoc}
     */
    public function setLastName(?string $value)
    {
        $this->lastName = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail() : ?string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail(?string $value)
    {
        $this->email = $value;

        return $this;
    }
}
