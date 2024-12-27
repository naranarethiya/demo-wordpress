<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractTemporaryMappingStrategy;

/**
 * @template TModel of object
 */
abstract class AbstractItemTemporaryMappingStrategy extends AbstractTemporaryMappingStrategy
{
    /**
     * Uses the information in the given model to generate a temporary key.
     *
     * @param TModel $model
     */
    protected function getTemporaryKey(object $model) : ?string
    {
        return $this->getModelHash($model);
    }

    /**
     * Gets a consistent hash string out of the given model's contents.
     *
     * @param TModel $model
     * @return non-empty-string
     */
    abstract protected function getModelHash(object $model) : string;
}
