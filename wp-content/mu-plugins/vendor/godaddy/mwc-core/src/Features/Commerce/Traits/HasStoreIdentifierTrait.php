<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

trait HasStoreIdentifierTrait
{
    /** @var string store UUID */
    protected string $storeId = '';

    /**
     * Gets store UUID.
     *
     * @return string
     */
    public function getStoreId() : string
    {
        return $this->storeId;
    }

    /**
     * Sets store UUID.
     *
     * @param string $value
     * @return $this
     */
    public function setStoreId(string $value)
    {
        $this->storeId = $value;

        return $this;
    }
}
