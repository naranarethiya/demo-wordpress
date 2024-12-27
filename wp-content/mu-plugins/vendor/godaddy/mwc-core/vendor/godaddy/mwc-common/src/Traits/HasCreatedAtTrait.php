<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use DateTimeInterface;

trait HasCreatedAtTrait
{
    /** @var DateTimeInterface|null date created */
    protected $createdAt;

    /**
     * Gets the date when the entity was created.
     *
     * @return DateTimeInterface|null
     */
    public function getCreatedAt() : ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Sets the date when the entity was created.
     *
     * @param DateTimeInterface|null $value
     * @return $this
     */
    public function setCreatedAt(?DateTimeInterface $value)
    {
        $this->createdAt = $value;

        return $this;
    }
}
