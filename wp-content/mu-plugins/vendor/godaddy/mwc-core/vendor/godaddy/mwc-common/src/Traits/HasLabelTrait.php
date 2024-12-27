<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait to handle labels.
 */
trait HasLabelTrait
{
    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $label;

    /**
     * Gets the label name.
     *
     * @return string
     */
    public function getName() : string
    {
        return is_string($this->name) ? $this->name : '';
    }

    /**
     * Gets the label value.
     *
     * @return string
     */
    public function getLabel() : string
    {
        return is_string($this->label) ? $this->label : '';
    }

    /**
     * Sets the label name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the label value.
     *
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
    }
}
