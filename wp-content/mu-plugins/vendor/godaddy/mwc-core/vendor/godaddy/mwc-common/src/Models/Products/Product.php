<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Products;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\AttributeValue;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasRemoteResourceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasWeightTrait;

/**
 * Native product object.
 */
class Product extends AbstractModel
{
    use HasNumericIdentifierTrait;
    use HasRemoteResourceTrait;
    use HasWeightTrait;

    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $sku;

    /** @var CurrencyAmount|null */
    protected $regularPrice;

    /** @var CurrencyAmount|null */
    protected $salePrice;

    /** @var string|null */
    protected $type;

    /** @var string|null */
    protected $status;

    /** @var Attribute[]|null */
    protected ?array $attributes = null;

    /** @var array<string, ?AttributeValue> */
    protected ?array $variantAttributeMapping = null;

    /** @var array<mixed>|null @deprecated */
    protected ?array $attributeData = null;

    /** @var string|null */
    protected $shortDescription;

    /**
     * Gets the product name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the product SKU.
     *
     * @return string
     */
    public function getSku() : string
    {
        return $this->sku ?? '';
    }

    /**
     * Gets the product regular price.
     *
     * @return CurrencyAmount|null
     */
    public function getRegularPrice()
    {
        return $this->regularPrice;
    }

    /**
     * Gets the product sale price.
     *
     * @return CurrencyAmount|null
     */
    public function getSalePrice()
    {
        return $this->salePrice;
    }

    /**
     * Gets the product type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the product status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Determines whether the product is published.
     *
     * @return bool
     */
    public function isPublished() : bool
    {
        return 'publish' === $this->getStatus();
    }

    /**
     * Gets any attributes for the product.
     *
     * @return Attribute[]|null
     */
    public function getAttributes() : ?array
    {
        return $this->attributes;
    }

    /**
     * Gets the product variant attribute mapping.
     *
     * @return array<string, ?AttributeValue>|null
     */
    public function getVariantAttributeMapping() : ?array
    {
        return $this->variantAttributeMapping;
    }

    /**
     * Gets the product attributes data.
     *
     * @deprecated
     *
     * @NOTE this is a legacy method that should be removed in the future {unfulvio 2023-03-24}
     * Prefer using {@see Attribute} and {@see AttributeValue} objects to handle attributes.
     *
     * @return array<mixed>
     */
    public function getAttributeData() : array
    {
        return $this->attributeData ?? [];
    }

    /**
     * Gets the product short description.
     *
     * @return string|null
     */
    public function getShortDescription() : ?string
    {
        return $this->shortDescription;
    }

    /**
     * Sets the product name.
     *
     * @param string|null $value
     * @return $this
     */
    public function setName($value) : Product
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Sets the product SKU.
     *
     * @param string $value
     * @return Product
     */
    public function setSku(string $value) : Product
    {
        $this->sku = $value;

        return $this;
    }

    /**
     * Sets the product regular price.
     *
     * @param CurrencyAmount|null $value
     * @return $this
     */
    public function setRegularPrice($value) : Product
    {
        $this->regularPrice = $value;

        return $this;
    }

    /**
     * Sets the product sale price.
     *
     * @param CurrencyAmount|null $value
     * @return $this
     */
    public function setSalePrice($value) : Product
    {
        $this->salePrice = $value;

        return $this;
    }

    /**
     * Sets the product type.
     *
     * @param string $value
     * @return $this
     */
    public function setType(string $value) : Product
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Sets the product status.
     *
     * @param string $value
     * @return $this
     */
    public function setStatus(string $value) : Product
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Sets product attributes.
     *
     * @param Attribute[] $value
     * @return $this
     */
    public function setAttributes(array $value) : Product
    {
        $this->attributes = $value;

        return $this;
    }

    /**
     * Sets the product variant attribute mapping.
     *
     * @param array<string, ?AttributeValue> $value
     * @return $this
     */
    public function setVariantAttributeMapping(array $value) : Product
    {
        $this->variantAttributeMapping = $value;

        return $this;
    }

    /**
     * Sets the product attribute data.
     *
     * @deprecated
     *
     * @NOTE this is a legacy method that should be removed in the future {unfulvio 2023-03-24}
     * Prefer using {@see Attribute} and {@see AttributeValue} objects to handle attributes.
     *
     * @param array<mixed> $value
     * @return Product
     */
    public function setAttributeData(array $value) : Product
    {
        $this->attributeData = $value;

        return $this;
    }

    /**
     * Sets the product short description.
     *
     * @param string $value
     * @return $this
     */
    public function setShortDescription(string $value) : Product
    {
        $this->shortDescription = $value;

        return $this;
    }
}
