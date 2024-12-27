<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Products\Attributes;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * Object representation of a {@see Product} attribute.
 *
 * A product attribute describes a property or a quality of a product, for example a "Color" or a "Size".
 * A "Color" attribute should specify a set of {@see AttributeValue} objects for colors like red, blue, etc.
 * A "Size" attribute would have similar values expressing different sizes, etc.
 */
class Attribute extends AbstractModel
{
    use HasLabelTrait;
    use HasNumericIdentifierTrait;

    /** @var AttributeValue[] possible values for the attribute */
    protected array $values = [];

    /** @var bool whether the attribute is used for {@see Product} variants, default false */
    protected bool $isForVariant = false;

    /** @var bool whether the attribute is visible to the customer, default true */
    protected bool $isVisible = true;

    /** @var Taxonomy|null optional associated taxonomy */
    protected ?Taxonomy $taxonomy = null;

    /**
     * Gets the associated values.
     *
     * @return AttributeValue[]
     */
    public function getValues() : array
    {
        return $this->values;
    }

    /**
     * Sets the attribute values.
     *
     * @param AttributeValue[] $value
     * @return $this
     */
    public function setValues(array $value)
    {
        $this->values = $value;

        return $this;
    }

    /**
     * Determines if the attribute is meant for variants.
     *
     * @return bool
     */
    public function isForVariant() : bool
    {
        return $this->getIsForVariant();
    }

    /**
     * Gets the value whether the attribute is meant for variants.
     *
     * @return bool
     */
    public function getIsForVariant() : bool
    {
        return $this->isForVariant;
    }

    /**
     * Sets the attribute as intended for variants.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsForVariant(bool $value)
    {
        $this->isForVariant = $value;

        return $this;
    }

    /**
     * Determines if the attribute is visible to customers.
     *
     * @return bool
     */
    public function isVisible() : bool
    {
        return $this->getIsVisible();
    }

    /**
     * Gets the value whether the attribute is visible to customers.
     *
     * @return bool
     */
    public function getIsVisible() : bool
    {
        return $this->isVisible;
    }

    /**
     * Sets the attribute as visible to customers.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsVisible(bool $value)
    {
        $this->isVisible = $value;

        return $this;
    }

    /**
     * Whether the attribute is associated to a taxonomy.
     *
     * @return bool
     */
    public function hasTaxonomy() : bool
    {
        return $this->taxonomy instanceof Taxonomy;
    }

    /**
     * Gets the associated taxonomy, if available.
     *
     * @return Taxonomy|null
     */
    public function getTaxonomy() : ?Taxonomy
    {
        return $this->taxonomy;
    }

    /**
     * Sets the associated taxonomy.
     *
     * @param Taxonomy|null $value
     * @return $this
     */
    public function setTaxonomy(?Taxonomy $value)
    {
        $this->taxonomy = $value;

        return $this;
    }
}
