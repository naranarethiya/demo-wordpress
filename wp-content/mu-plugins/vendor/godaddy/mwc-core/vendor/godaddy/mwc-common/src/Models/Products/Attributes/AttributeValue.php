<?php
/**
 * This file includes a use statement for the {@see Attribute} class to help the NewClassesSniff
 * from PHPCompatibility understand that the Attribute class referenced below is not the standard
 * class that was introduced in PHP 8.0. The alias in that use statement is equal to the original
 * name of the class because we don't need or want to use a different name to reference the class.
 *
 * @link https://github.com/gdcorp-partners/mwc-common/pull/1389#discussion_r1693203531
 */

namespace GoDaddy\WordPress\MWC\Common\Models\Products\Attributes;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Products\Attributes\Attribute as Attribute;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;

/**
 * Object representation of a {@see Attribute} value.
 *
 * @method static static getNewInstance(Attribute $attribute, array $args = [])
 */
class AttributeValue
{
    use CanBulkAssignPropertiesTrait;
    use CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as protected traitToArray;
    }
    use CanGetNewInstanceTrait;
    use HasLabelTrait;
    use HasStringIdentifierTrait;

    /** @var Attribute intentionally private so it's not converted to array */
    private Attribute $attribute;

    /**
     * Constructor.
     *
     * @param Attribute $attribute
     * @param array<string, mixed> $properties
     */
    public function __construct(Attribute $attribute, array $properties = [])
    {
        $this->attribute = $attribute;

        $this->setProperties($properties);
    }

    /**
     * Determines if the value is mapped to a term.
     *
     * @return bool
     */
    public function isTerm() : bool
    {
        return null !== $this->getTerm();
    }

    /**
     * Gets the associated term, if any.
     *
     * @return Term|null
     */
    public function getTerm() : ?Term
    {
        $termId = $this->getId();
        $taxonomy = $this->attribute->getTaxonomy();

        return is_numeric($termId) && $taxonomy ? Term::get(TypeHelper::int($termId, 0), $taxonomy) : null;
    }

    /**
     * Gets the value name.
     *
     * @return string
     */
    public function getName() : string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        if ($term = $this->getTerm()) {
            return TypeHelper::string($term->getName(), '');
        }

        return '';
    }

    /**
     * Gets the value label.
     *
     * @return string
     */
    public function getLabel() : string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        if ($term = $this->getTerm()) {
            return TypeHelper::string($term->getLabel(), '');
        }

        return '';
    }

    /**
     * Converts the attribute value to array.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        // ensures that name and label properties are properly set when coming from a taxonomy
        if ($this->isTerm()) {
            $this->name = $this->getName();
            $this->label = $this->getLabel();
        }

        return $this->traitToArray();
    }

    /**
     * Gets the associated attribute.
     *
     * @return Attribute
     */
    public function getAttribute() : Attribute
    {
        return $this->attribute;
    }
}
