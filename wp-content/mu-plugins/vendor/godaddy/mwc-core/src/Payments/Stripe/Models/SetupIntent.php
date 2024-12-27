<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

class SetupIntent extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $id;

    /** @var AbstractPaymentMethod|null */
    protected $paymentMethod;

    /** @var string */
    protected $status;

    /** @var Customer */
    protected $customer;

    /**
     * Gets the client secret.
     *
     * @return string|null
     */
    public function getClientSecret() : ?string
    {
        return $this->clientSecret;
    }

    /**
     * Sets the client secret.
     *
     * @param string $value
     * @return self
     */
    public function setClientSecret(string $value) : SetupIntent
    {
        $this->clientSecret = $value;

        return $this;
    }

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
     * @return self
     */
    public function setId(string $value) : SetupIntent
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the payment method.
     *
     * @return AbstractPaymentMethod|null
     */
    public function getPaymentMethod() : ?AbstractPaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * Sets the payment method.
     *
     * @param AbstractPaymentMethod $value
     *
     * @return $this
     */
    public function setPaymentMethod(AbstractPaymentMethod $value) : SetupIntent
    {
        $this->paymentMethod = $value;

        return $this;
    }

    /**
     * Gets the customer object.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Sets the customer object.
     *
     * @param Customer $customer
     * @return self
     */
    public function setCustomer(Customer $customer) : SetupIntent
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets the status.
     *
     * @return string|null
     */
    public function getStatus() : ?string
    {
        return $this->status;
    }

    /**
     * Sets the status.
     *
     * @param string $value
     * @return self
     */
    public function setStatus(string $value) : SetupIntent
    {
        $this->status = $value;

        return $this;
    }
}
