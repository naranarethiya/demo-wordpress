<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

interface CanGetModelHashContract
{
    /**
     * Gets a consistent hash string out of the given model's contents.
     *
     * @param object $model
     * @return non-empty-string
     */
    public function getModelHash(object $model) : string;
}
