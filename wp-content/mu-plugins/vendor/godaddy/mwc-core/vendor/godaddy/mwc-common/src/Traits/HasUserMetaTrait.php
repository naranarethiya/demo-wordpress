<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait used for storing data as WP user meta.
 */
trait HasUserMetaTrait
{
    /** @var int user ID used to load/store the metadata */
    protected $userId;

    /** @var string meta key used to load/store the metadata */
    protected $metaKey;

    /** @var string|array<mixed>|int|float|bool|null value to be stored as metadata */
    protected $value;

    /**
     * Loads and returns the value stored in the user metadata.
     *
     * It sets the value property to the value loaded from the user metadata or the default value.
     *
     * @param string|array<mixed>|int|float|bool|null $defaultValue value used if the user metadata doesn't exist
     * @return string|array<mixed>|int|float|bool|null
     */
    public function loadUserMeta($defaultValue)
    {
        if (! metadata_exists('user', $this->userId, $this->metaKey)) {
            $this->value = $defaultValue;
        } else {
            /** @var string|array<mixed>|int|float|bool|null $value */
            $value = get_user_meta($this->userId, $this->metaKey, true);
            $this->value = $value;
        }

        return $this->value;
    }

    /**
     * Gets the value property.
     *
     * @return string|array<mixed>|int|float|bool|null
     */
    public function getUserMeta()
    {
        return $this->value;
    }

    /**
     * Sets the value property.
     *
     * @param string|array<mixed>|int|float|bool|null $value value to store
     * @return $this
     */
    public function setUserMeta($value) : self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Stores the value property as user metadata.
     *
     * @return $this
     */
    public function saveUserMeta() : self
    {
        update_user_meta($this->userId, $this->metaKey, $this->value);

        return $this;
    }
}
