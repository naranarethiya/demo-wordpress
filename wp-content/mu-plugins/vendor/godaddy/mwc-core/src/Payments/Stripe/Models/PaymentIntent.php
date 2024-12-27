<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\ShippableTrait;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * The payment intent model.
 */
class PaymentIntent extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use ShippableTrait;

    /** @var string */
    protected $id;

    /** @var int */
    protected $amount;

    /** @var string */
    protected $currency;

    /** @var Customer */
    protected $customer;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $captureMethod;

    /** @var array<string, string> */
    protected $metaData = [];

    /** @var AbstractPaymentMethod|null */
    protected $paymentMethod;

    /** @var string */
    protected $setupFutureUsage;

    /** @var int|null */
    protected $created;

    /** @var string|null */
    protected $status;

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
     * @return PaymentIntent
     */
    public function setId(string $value) : PaymentIntent
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the customer object.
     *
     * @return Customer|null
     */
    public function getCustomer() : ?Customer
    {
        return $this->customer;
    }

    /**
     * Sets the customer object.
     *
     * @param Customer $customer
     * @return self
     */
    public function setCustomer(Customer $customer) : PaymentIntent
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets the amount.
     *
     * @return int|null
     */
    public function getAmount() : ?int
    {
        return $this->amount;
    }

    /**
     * Sets the amount.
     *
     * @param int $value
     * @return PaymentIntent
     */
    public function setAmount(int $value) : PaymentIntent
    {
        $this->amount = $value;

        return $this;
    }

    /**
     * Gets the currency.
     *
     * @return string|null
     */
    public function getCurrency() : ?string
    {
        return $this->currency;
    }

    /**
     * Sets the currency.
     *
     * @param string $value
     * @return PaymentIntent
     */
    public function setCurrency(string $value) : PaymentIntent
    {
        $this->currency = $value;

        return $this;
    }

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
    public function setClientSecret(string $value) : PaymentIntent
    {
        $this->clientSecret = $value;

        return $this;
    }

    /**
     * Gets the capture method.
     *
     * @return string|null
     */
    public function getCaptureMethod() : ?string
    {
        return $this->captureMethod;
    }

    /**
     * Sets the capture method.
     *
     * @param string $value
     * @return self
     */
    public function setCaptureMethod(string $value) : PaymentIntent
    {
        $this->captureMethod = $value;

        return $this;
    }

    /**
     * Gets the metadata.
     *
     * @return array<string, string>
     */
    public function getMetaData() : array
    {
        return $this->metaData;
    }

    /**
     * Sets the metadata.
     *
     * @param array<string, string> $value
     *
     * @return $this
     */
    public function setMetaData(array $value) : PaymentIntent
    {
        $this->metaData = $value;

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
     * @return self
     */
    public function setPaymentMethod(AbstractPaymentMethod $value) : PaymentIntent
    {
        $this->paymentMethod = $value;

        return $this;
    }

    /**
     * Gets the setup future usage setting value.
     *
     * @return string|null
     */
    public function getSetupFutureUsage() : ?string
    {
        return $this->setupFutureUsage;
    }

    /**
     * Sets the setup future usage setting value.
     *
     * @param string $value
     * @return self
     */
    public function setSetupFutureUsage(string $value) : PaymentIntent
    {
        $this->setupFutureUsage = $value;

        return $this;
    }

    /**
     * Gets the created value.
     *
     * @return int|null
     */
    public function getCreated() : ?int
    {
        return $this->created;
    }

    /**
     * Sets the created value.
     *
     * @param int|null $value
     * @return self
     */
    public function setCreated(?int $value) : PaymentIntent
    {
        $this->created = $value;

        return $this;
    }

    /**
     * Gets the status value.
     *
     * @return string|null
     */
    public function getStatus() : ?string
    {
        return $this->status;
    }

    /**
     * Sets the status value.
     *
     * @param string|null $value
     * @return self
     */
    public function setStatus(?string $value) : PaymentIntent
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Determines whether the payment intent is cancelable.
     *
     * @return bool
     */
    public function isCancelable() : bool
    {
        return in_array($this->getStatus(), ['requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action', 'processing'], true);
    }
}
