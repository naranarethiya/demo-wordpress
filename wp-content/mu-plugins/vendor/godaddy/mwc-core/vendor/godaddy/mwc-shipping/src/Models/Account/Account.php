<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Account;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasCreatedAtTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasUpdatedAtTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountStatusContract;

class Account extends AbstractModel implements AccountContract
{
    use HasStringIdentifierTrait;
    use HasStringRemoteIdentifierTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var string */
    protected $companyName;

    /** @var string */
    protected $originCountryCode;

    /** @var AccountStatusContract */
    protected $status;

    /** {@inheritDoc} */
    public function getFirstName() : string
    {
        return $this->firstName;
    }

    /** {@inheritDoc} */
    public function setFirstName(string $value)
    {
        $this->firstName = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getLastName() : string
    {
        return $this->lastName;
    }

    /** {@inheritDoc} */
    public function setLastName(string $value)
    {
        $this->lastName = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getCompanyName() : string
    {
        return $this->companyName;
    }

    /** {@inheritDoc} */
    public function setCompanyName(string $value)
    {
        $this->companyName = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getOriginCountryCode() : string
    {
        return $this->originCountryCode;
    }

    /** {@inheritDoc} */
    public function setOriginCountryCode(string $value)
    {
        $this->originCountryCode = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getStatus() : AccountStatusContract
    {
        return $this->status;
    }

    /* {@inheritDoc} */
    public function setStatus(AccountStatusContract $value)
    {
        $this->status = $value;

        return $this;
    }

    /* {@inheritDoc} */
    public function toArray() : array
    {
        $data = parent::toArray();

        if (ArrayHelper::get($data, 'status')) {
            $data['status'] = $this->getStatus()->getName();
        }

        if (ArrayHelper::get($data, 'createdAt') && $this->getCreatedAt()) {
            $data['createdAt'] = $this->getCreatedAt()->getTimestamp();
        }

        if (ArrayHelper::get($data, 'updatedAt') && $this->getUpdatedAt()) {
            $data['updatedAt'] = $this->getUpdatedAt()->getTimestamp();
        }

        return $data;
    }
}
