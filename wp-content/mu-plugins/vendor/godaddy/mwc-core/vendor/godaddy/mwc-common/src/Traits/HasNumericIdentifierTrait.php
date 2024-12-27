<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;

/**
 * A trait for objects that can have a (possibly unique) numerical identifier.
 *
 * @see HasNumericIdentifierContract
 * @see HasStringIdentifierContract for string identfiers instead
 */
trait HasNumericIdentifierTrait
{
    /** @var int|null */
    protected $id;

    /**
     * Gets the object ID.
     *
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    /**
     * Sets the object ID.
     *
     * @param int $value
     * @return $this
     */
    public function setId(int $value)
    {
        $this->id = $value;

        return $this;
    }
}
