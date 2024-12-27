<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\Contracts\DataObjectContract;
use ReflectionClass;

/**
 * An abstract data object.
 *
 * Subclasses MUST declare public properties for all the fields of the data object.
 *
 * Subclasses MUST override {@see AbstractDataObject::__construct()} and use array shapes to enforce required properties and their types.
 *
 * @TODO This class can be removed once the Commerce {@see \GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject} DTO is moved into a more generic namespace.
 * @link https://github.com/gdcorp-partners/mwc-core/discussions/6589
 *
 * @method static static getNewInstance(array $data)
 */
abstract class AbstractDataObject implements DataObjectContract
{
    use CanGetNewInstanceTrait;
    use CanConvertToArrayTrait;

    /**
     * Creates a new data object.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
            $propertyName = $property->getName();

            if (! array_key_exists($propertyName, $data)) {
                continue;
            }

            $this->{$propertyName} = $data[$propertyName];
        }
    }
}
