<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGetModelHashContract;

/**
 * @template TModel of object
 */
abstract class AbstractHashService implements CanGetModelHashContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     *
     * @param TModel $model
     * @return non-empty-string
     */
    public function getModelHash(object $model) : string
    {
        return sha1(implode('|', $this->getValuesForHash($model)));
    }

    /**
     * Returns an array of strings that will be converted to a hash.
     *
     * @param TModel $model
     * @return string[]
     */
    abstract protected function getValuesForHash(object $model) : array;
}
