<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Url;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Object representation of a URL query parameters set.
 */
class QueryParameters
{
    /** @var array<string, mixed> */
    protected $parameters = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Builds a new query parameters object from a string.
     *
     * @param string $query
     * @return QueryParameters
     */
    public static function fromString(string $query = '') : QueryParameters
    {
        if ('' === $query) {
            return new QueryParameters();
        }

        $parameters = [];

        parse_str($query, $parameters);

        $parameters = array_map(function ($param) {
            return '' !== $param ? $param : null;
        }, $parameters);

        return new QueryParameters(TypeHelper::array($parameters, []));
    }

    /**
     * Gets a query parameter by key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return ArrayHelper::get($this->parameters, $key, $default);
    }

    /**
     * Determines if a query parameter exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return ArrayHelper::has($this->parameters, $key);
    }

    /**
     * Gets the count of the current query parameters.
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->parameters);
    }

    /**
     * Adds a query parameter.
     *
     * @TODO consider supporting a customizable validation as a callback to be called here {unfulvio 2022-07-19}
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function add(string $key, $value) : QueryParameters
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Adds many query parameters.
     *
     * @param array<string, mixed> $parameters
     * @return $this
     */
    public function addMany(array $parameters) : QueryParameters
    {
        foreach ($parameters as $key => $value) {
            $this->add($key, $value);
        }

        return $this;
    }

    /**
     * Removes one or more query parameters.
     *
     * @param string|string[] $value
     * @return $this
     */
    public function remove($value) : QueryParameters
    {
        if (ArrayHelper::accessible($value)) {
            /* @phpstan-ignore-next-line */
            foreach ($value as $key) {
                unset($this->parameters[$key]);
            }
        } else {
            /* @phpstan-ignore-next-line */
            unset($this->parameters[$value]);
        }

        return $this;
    }

    /**
     * Gets all the query parameters as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return $this->parameters;
    }

    /**
     * Gets all the query parameters as a string.
     *
     * @return string
     */
    public function toString() : string
    {
        return trim(ArrayHelper::query($this->parameters));
    }

    /**
     * Implements the magic method to convert the query parameters to a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }
}
