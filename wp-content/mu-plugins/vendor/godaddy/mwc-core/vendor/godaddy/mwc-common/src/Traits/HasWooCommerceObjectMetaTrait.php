<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use WC_Data;

/**
 * A trait used for handling WooCommerce metadata.
 *
 * @see HasPostMetaTrait for a trait that can be used for handling WordPress post metadata instead.
 */
trait HasWooCommerceObjectMetaTrait
{
    /** @var scalar|array<mixed>|null */
    protected $metaValue;

    /** @var WC_Data WooCommerce object */
    protected WC_Data $wcDataObject;

    /**
     * Loads and returns the value stored in the metadata.
     *
     * @param scalar|array<mixed>|null $defaultValue optional, defaults to this value (default null)
     * @return scalar|array<mixed>|null
     */
    protected function loadMeta($defaultValue = null)
    {
        $metaKey = $this->getMetaKey();

        if ($this->wcDataObject->meta_exists($metaKey)) {
            /** @var scalar|array<mixed>|null $value */
            $value = $this->wcDataObject->get_meta($metaKey);
            $this->metaValue = $value;
        } else {
            $this->metaValue = $defaultValue;
        }

        return $this->metaValue;
    }

    /**
     * Gets the metadata value.
     *
     * @return scalar|array<mixed>|null
     */
    protected function getMeta()
    {
        return $this->metaValue;
    }

    /**
     * Sets the metadata value on the object.
     *
     * @param scalar|array<mixed>|null $value metadata value
     * @return $this
     */
    protected function setMeta($value = null)
    {
        $this->metaValue = $value;

        return $this;
    }

    /**
     * Saves (persists) the metadata for the object.
     *
     * @return $this
     */
    protected function saveMeta()
    {
        /** @var string|array<mixed> $value WooCommerce will convert scalar values to string eventually */
        $value = $this->metaValue;

        $this->wcDataObject->update_meta_data($this->getMetaKey(), $value);

        $this->wcDataObject->save_meta_data();

        return $this;
    }

    /**
     * Deletes the metadata from the object.
     *
     * @return $this
     */
    protected function deleteMeta()
    {
        $this->wcDataObject->delete_meta_data($this->getMetaKey());

        $this->wcDataObject->save_meta_data();

        return $this;
    }

    /**
     * Gets the meta key.
     *
     * @return non-empty-string
     */
    abstract protected function getMetaKey() : string;
}
