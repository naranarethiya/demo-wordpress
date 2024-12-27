<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order as CommonOrder;
use GoDaddy\WordPress\MWC\Core\Channels\Traits\HasOriginatingChannelTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CustomerNote;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Builders\CustomerNoteBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Traits\HasMarketplacesDataTrait;

/**
 * Core order model.
 */
class Order extends CommonOrder
{
    use HasMarketplacesDataTrait;
    use HasOriginatingChannelTrait;

    /** @var bool whether the payment for the order is captured */
    protected $captured = false;

    /** @var bool whether the payment for the order is refunded */
    protected $refunded = false;

    /** @var bool whether the payment for the order is voided */
    protected $voided = false;

    /** @var string customer email address */
    protected $emailAddress;

    /** @var CurrencyAmount|null */
    protected $discountAmount;

    /**
     * Note that the customer added to the order.
     *
     * This property is kept so that Order events remain backward-compatible with subscribers that may rely on customerNote.
     *
     * @var string
     */
    protected $customerNote;

    /** @var bool whether the order is ready to have a payment captured */
    protected $readyForCapture = false;

    /** @var string the order source */
    protected $source;

    /** @var string the remote order id */
    protected $remoteId;

    /** @var bool whether the order requires payment */
    protected bool $needsPayment = false;

    /** @var string|null URL to complete payment on the order */
    protected ?string $checkoutPaymentUrl = '';

    /** @var ?string The cart ID. */
    protected ?string $cartId = null;

    /**
     * Gets the customer's email address.
     *
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Sets the customer's email address.
     *
     * @param string $value
     * @return self
     */
    public function setEmailAddress(string $value) : Order
    {
        $this->emailAddress = $value;

        return $this;
    }

    /**
     * Gets the customer note for the order.
     *
     * @return string|null
     */
    public function getCustomerNote() : ?string
    {
        if ($customerNote = $this->findCustomerNote($this->getNotes())) {
            return $customerNote->getContent();
        }

        return '';
    }

    /**
     * Gets a {@see CustomerNote} instance from the given array of notes.
     *
     * Returns null if there are no customer notes in the array.
     *
     * @param NoteContract[] $notes
     * @return CustomerNote|null
     */
    protected function findCustomerNote(array $notes) : ?CustomerNote
    {
        foreach ($notes as $note) {
            if ($note instanceof CustomerNote) {
                return $note;
            }
        }

        return null;
    }

    /**
     * Sets the customer note for the order.
     *
     * @param string $value
     * @return $this
     */
    public function setCustomerNote(string $value) : Order
    {
        $this->customerNote = $value;

        if ($customerNote = $this->findCustomerNote($this->getNotes())) {
            return $this->updateCustomerNote($customerNote, $value);
        }

        return $this->addCustomerNote($value);
    }

    /**
     * Updates the given customer note instance with the given content.
     *
     * @return $this
     */
    protected function updateCustomerNote(CustomerNote $customerNote, string $value)
    {
        $customerNote->setContent($value);

        return $this;
    }

    /**
     * Adds an new customer note to the list of notes using the given value as the note's content.
     *
     * @return $this
     */
    protected function addCustomerNote(string $value)
    {
        return $this->addNotes($this->buildCustomerNote($value));
    }

    /**
     * Builds a new customer note for this order with the given value as the note's content.
     */
    protected function buildCustomerNote(string $value) : CustomerNote
    {
        return CustomerNoteBuilder::getNewInstance()->setOrderId($this->getId())->build($value);
    }

    /**
     * Gets the order discount amount.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount|null
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Sets the order discount amount.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $value
     * @return self
     */
    public function setDiscountAmount(CurrencyAmount $value) : Order
    {
        $this->discountAmount = $value;

        return $this;
    }

    /**
     * Sets a flag whether the payment for the order has been captured.
     *
     * @param bool $value
     * @return self
     */
    public function setCaptured(bool $value) : Order
    {
        $this->captured = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was captured.
     *
     * @return bool
     */
    public function isCaptured() : bool
    {
        return $this->captured;
    }

    /**
     * Sets whether the order is ready to have its payment captured.
     *
     * @param bool $value
     * @return self
     */
    public function setReadyForCapture(bool $value) : Order
    {
        $this->readyForCapture = $value;

        return $this;
    }

    /**
     * Determines whether the order is ready to have its payment captured.
     *
     * @return bool
     */
    public function isReadyForCapture() : bool
    {
        return $this->readyForCapture;
    }

    /**
     * Sets a flag whether the payment for the order has been refunded.
     *
     * @param bool $value
     * @return self
     */
    public function setRefunded(bool $value) : Order
    {
        $this->refunded = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was refunded.
     *
     * @return bool
     */
    public function isRefunded() : bool
    {
        return $this->refunded;
    }

    /**
     * Sets a flag whether the payment for the order has been voided.
     *
     * @param bool $value
     * @return self
     */
    public function setVoided(bool $value) : Order
    {
        $this->voided = $value;

        return $this;
    }

    /**
     * Determines whether the payment for the order was voided.
     *
     * @return bool
     */
    public function isVoided() : bool
    {
        return $this->voided;
    }

    /**
     * Gets the order source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets the order source.
     *
     * @param string $value
     * @return self
     */
    public function setSource(string $value) : Order
    {
        $this->source = $value;

        return $this;
    }

    /**
     * Gets the order remote id, if any.
     *
     * @return string|null
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * Sets the order remote id.
     *
     * @param string $value
     * @return $this
     */
    public function setRemoteId(string $value) : Order
    {
        $this->remoteId = $value;

        return $this;
    }

    /**
     * Whether the order still needs payment.
     *
     * @return bool
     */
    public function getNeedsPayment() : bool
    {
        return $this->needsPayment;
    }

    /**
     * Sets whether the order still needs payment.
     *
     * @param bool $value
     * @return $this
     */
    public function setNeedsPayment(bool $value) : Order
    {
        $this->needsPayment = $value;

        return $this;
    }

    /**
     * Gets the checkout URL to pay for the order.
     *
     * @return string|null
     */
    public function getCheckoutPaymentUrl() : ?string
    {
        return $this->checkoutPaymentUrl;
    }

    /**
     * Sets the checkout payment URL.
     *
     * @param string $value
     * @return $this
     */
    public function setCheckoutPaymentUrl(string $value) : Order
    {
        $this->checkoutPaymentUrl = $value;

        return $this;
    }

    /**
     * Gets the cart ID.
     *
     * @return ?string
     */
    public function getCartId() : ?string
    {
        return $this->cartId;
    }

    /**
     * Sets the cart ID.
     *
     * @param string $value
     * @return $this
     */
    public function setCartId(string $value) : Order
    {
        $this->cartId = $value;

        return $this;
    }

    /**
     * Check if the order has a certain shipping method. Accepts a string or
     * array of strings and returns true if the order uses at least *one* of
     * the provided $methods.
     *
     * @param string|array $methods
     * @return bool
     */
    public function hasShippingMethod($methods) : bool
    {
        foreach (ArrayHelper::wrap($this->getShippingItems()) as $shippingItem) {
            if (ArrayHelper::contains(ArrayHelper::wrap($methods), $shippingItem->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves the order.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : Order
    {
        $order = parent::save();

        Events::broadcast($this->buildEvent('order', 'create'));

        return $order;
    }

    /**
     * Updates the order.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function update() : Order
    {
        $order = parent::update();

        Events::broadcast($this->buildEvent('order', 'update'));

        return $order;
    }
}
