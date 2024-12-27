<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait for objects that have a URL.
 */
trait HasUrlTrait
{
    /** @var string */
    protected $url = '';

    /**
     * Gets the object's URL.
     *
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Sets the object's URL.
     *
     * @param string $value
     * @return $this
     */
    public function setUrl(string $value)
    {
        $this->url = $value;

        return $this;
    }
}
