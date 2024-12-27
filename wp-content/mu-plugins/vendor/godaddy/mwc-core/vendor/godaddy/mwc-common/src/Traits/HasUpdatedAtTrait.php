<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use DateTimeInterface;

trait HasUpdatedAtTrait
{
    /** @var DateTimeInterface|null date updated */
    protected $updatedAt;

    /**
     * Gets the date when the entity was updated.
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt() : ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Sets the date when the entity was updated.
     *
     * @param DateTimeInterface|null $value
     * @return $this
     */
    public function setUpdatedAt(?DateTimeInterface $value)
    {
        $this->updatedAt = $value;

        return $this;
    }
}
