<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Traits;

trait HasNewObjectFlagMetaTrait
{
    /**
     * Determines whether the flag is enabled for the associated object.
     *
     * @return bool
     */
    public function isOn() : bool
    {
        return 'yes' === $this->getMeta();
    }

    /**
     * Determines whether the flag is disabled for the associated object.
     *
     * @return bool
     */
    public function isOff() : bool
    {
        return ! $this->isOn();
    }

    /**
     * Enables the flag for the associated object.
     *
     * @return $this
     */
    public function turnOn()
    {
        return $this->setMeta('yes')->saveMeta();
    }

    /**
     * Deletes the flag for the associated object.
     *
     * @return $this
     */
    public function turnOff()
    {
        return $this->setMeta('no')->deleteMeta();
    }

    /**
     * Gets the key for the new object flag metadata.
     *
     * @return string
     */
    protected function getMetaKey() : string
    {
        return '_gd_mwc_is_new_object';
    }
}
