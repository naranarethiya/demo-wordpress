<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue\Contracts;

/**
 * Something that can be enqueued, like a static asset, script or style.
 */
interface EnqueuableContract
{
    /**
     * Sets the enqueue type.
     */
    public function __construct();

    /**
     * Registers and enqueues the asset in WordPress.
     *
     * @return void
     */
    public function execute() : void;

    /**
     * Validates the current instance settings.
     *
     * @return void
     */
    public function validate() : void;
}
