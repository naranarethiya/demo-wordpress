<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * Label contract interface.
 */
interface HasLabelContract
{
    /**
     * Gets the label name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Gets the label value.
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Sets the label name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * Sets the label value.
     *
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label);
}
