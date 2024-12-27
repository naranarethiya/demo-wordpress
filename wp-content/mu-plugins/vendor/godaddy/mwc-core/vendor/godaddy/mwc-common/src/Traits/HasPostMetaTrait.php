<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait used for handling post metadata.
 *
 * @see HasWooCommerceObjectMetaTrait for a trait that can be used for handling WooCommerce object metadata instead.
 */
trait HasPostMetaTrait
{
    /** @var scalar|array<mixed>|null */
    protected $metaValue;

    /** @var int WordPress post ID */
    protected int $objectId;

    /**
     * Loads and returns the value stored in the post metadata.
     *
     * @param scalar|array<mixed>|null $defaultValue optional, defaults to this value (default null)
     * @return scalar|array<mixed>|null
     */
    protected function loadMeta($defaultValue = null)
    {
        $metaKey = $this->getMetaKey();

        if (metadata_exists('post', $this->objectId, $metaKey)) {
            /** @var scalar|array<mixed>|null $value */
            $value = get_post_meta($this->objectId, $metaKey, true);

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
     * Sets the metadata value on the post object.
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
     * Saves (persists) the metadata for the post object.
     *
     * @return $this
     */
    protected function saveMeta()
    {
        update_post_meta($this->objectId, $this->getMetaKey(), $this->metaValue);

        return $this;
    }

    /**
     * Deletes the metadata from the post object.
     *
     * @return $this
     */
    protected function deleteMeta()
    {
        delete_post_meta($this->objectId, $this->getMetaKey());

        return $this;
    }

    /**
     * Gets the meta key.
     *
     * @return non-empty-string
     */
    abstract protected function getMetaKey() : string;
}
