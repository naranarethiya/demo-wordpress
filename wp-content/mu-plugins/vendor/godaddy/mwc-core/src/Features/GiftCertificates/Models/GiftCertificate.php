<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Models;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * Core Gift Certificate object.
 *
 * @NOTE: This class may be updated when refactoring the Gift Certificate feature in its V2.
 *        When doing that, make sure to check its event structure to keep it consistent.
 */
class GiftCertificate extends AbstractModel
{
    /** @var int gift certificate internal ID */
    protected $id;

    /** @var string gift certificate number */
    protected $number;

    /** @var string gift certificate (post) status */
    protected $status;

    /** @var Customer gift certificate customer */
    protected $customer;

    /** @var Product */
    protected $product;

    /** @var Order gift certificate order */
    protected $order;

    /** @var int gift certificate template object */
    protected $templateId;

    /**
     * Gets the gift certificate ID.
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Gets the gift certificate number.
     *
     * @return string
     */
    public function getNumber() : string
    {
        return $this->number;
    }

    /**
     * Gets the gift certificate status.
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Gets the gift certificate customer.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Gets the gift certificate product.
     *
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Gets the gift certificate order.
     *
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Gets the gift certificate template id.
     *
     * @return int|null
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Sets the gift certificate ID.
     *
     * @param int $value
     * @return self
     */
    public function setId(int $value) : GiftCertificate
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the gift certificate number.
     *
     * @param string $value
     * @return self
     */
    public function setNumber(string $value) : GiftCertificate
    {
        $this->number = $value;

        return $this;
    }

    /**
     * Sets the gift certificate status.
     *
     * @param string $value
     * @return self
     */
    public function setStatus($value) : GiftCertificate
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Sets the gift certificate customer.
     *
     * @param Customer $value
     * @return self
     */
    public function setCustomer($value) : GiftCertificate
    {
        $this->customer = $value;

        return $this;
    }

    /**
     * Sets the gift certificate product.
     *
     * @param Product|null $value
     * @return self
     */
    public function setProduct($value) : GiftCertificate
    {
        $this->product = $value;

        return $this;
    }

    /**
     * Sets the gift certificate order.
     *
     * @param Order|null $value
     * @return self
     */
    public function setOrder($value) : GiftCertificate
    {
        $this->order = $value;

        return $this;
    }

    /**
     * Sets the gift certificate template id.
     *
     * @param int|null $value
     * @return self
     */
    public function setTemplateId($value) : GiftCertificate
    {
        $this->templateId = $value;

        return $this;
    }

    /**
     * Saves the gift certificate.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : GiftCertificate
    {
        $giftCertificate = parent::save();

        Events::broadcast($this->buildEvent('giftCertificate', 'create'));

        return $giftCertificate;
    }

    /**
     * Updates the gift certificate.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function update() : GiftCertificate
    {
        $giftCertificate = parent::update();

        Events::broadcast($this->buildEvent('giftCertificate', 'update'));

        return $giftCertificate;
    }

    /**
     * Deletes the gift certificate.
     *
     * This method also broadcast model events.
     */
    public function delete()
    {
        parent::delete();

        Events::broadcast($this->buildEvent('giftCertificate', 'delete'));
    }

    /**
     * Determines whether this gift certificate is a draft.
     *
     * @return bool
     */
    public function isDraft() : bool
    {
        return 'auto-draft' === $this->getStatus();
    }

    /**
     * Determines whether this gift certificate is redeemed.
     *
     * @return bool
     */
    public function isRedeemed() : bool
    {
        return 'redeemed' === $this->getStatus();
    }
}
