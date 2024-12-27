<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;

/**
 * A trait for objects that can have a string identifier assigned.
 *
 * @see HasStringIdentifierContract
 * @see HasNumericIdentifierTrait for identfiers that are integers instead
 */
trait HasStringIdentifierTrait
{
    /** @var string */
    protected $id;

    /**
     * Gets the ID.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the identifier.
     *
     * @param string $value
     * @return $this
     */
    public function setId(string $value)
    {
        $this->id = $value;

        return $this;
    }
}
