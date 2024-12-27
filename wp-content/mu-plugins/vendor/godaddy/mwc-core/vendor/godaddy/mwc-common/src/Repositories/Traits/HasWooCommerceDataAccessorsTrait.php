<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\MissingPropertyException;
use WC_Data;

/**
 * Trait for WooCommerce repositories that handle WooCommerce data objects.
 */
trait HasWooCommerceDataAccessorsTrait
{
    /** @var WC_Data a WooCommerce object that implements the {@see WC_Data} abstraction */
    protected $object;

    /**
     * Creates a new repository instance for the given object.
     *
     * @param WC_Data $object note: the object type is not strict to ease mocking, it will be evaluated in concrete constructors
     * @return $this
     */
    public static function for($object)
    {
        return new self($object);
    }

    /**
     * Sets the WooCommerce object data.
     *
     * @param array<mixed> $properties optional properties to set on the object
     * @param array<mixed> $metadata optional metadata to set on the object
     * @return $this
     */
    protected function setData(array $properties, array $metadata)
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        foreach ($metadata as $key => $value) {
            $this->setMetaData($key, $value);
        }

        return $this;
    }

    /**
     * Sets a WooCommerce object property.
     *
     * @param string $property
     * @param mixed $value
     * @return $this
     */
    public function setProperty(string $property, $value)
    {
        $method = "set_{$property}";

        if (is_callable([$this->object, $method])) {
            $this->object->{$method}($value);
        }

        return $this;
    }

    /**
     * Gets the value of a WooCommerce object property.
     *
     * @param string $property
     * @param array<mixed> $args
     * @return array|string|mixed
     * @throws Exception
     */
    public function getProperty(string $property, array $args = [])
    {
        $method = "get_{$property}";

        if (! is_callable([$this->object, $method])) {
            $class = get_class($this->object);
            throw new MissingPropertyException("Property '{$property}' not found in '{$class}'.");
        }

        return $this->object->{$method}(...$args);
    }

    /**
     * Sets a WooCommerce object metadata.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setMetaData(string $key, $value)
    {
        $this->object->update_meta_data($key, $value);

        return $this;
    }

    /**
     * Gets the value of a WooCommerce object metadata.
     *
     * @param string $key
     * @TODO Refactor method to not use spread operator as the first param is a bool and the second is a string MWC-9416 {ssmith1 - 2022-11-22}
     * @phpstan-ignore-next-line
     * @param array $args
     * @return array|string|mixed
     */
    public function getMetaData(string $key, array $args = [])
    {
        return $this->object->get_meta($key, ...$args);
    }

    /**
     * Returns the built WooCommerce object, without saving it.
     *
     * @return WC_Data
     */
    public function build()
    {
        return $this->object;
    }

    /**
     * Saves and returns the built WooCommerce object.
     *
     * @return WC_Data
     */
    public function save()
    {
        $object = $this->build();

        $object->save_meta_data();
        $object->save();

        return $object;
    }
}
