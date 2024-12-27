<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * A feature flag.
 */
class FeatureFlag extends AbstractModel
{
    /** @var string|null */
    protected $id;

    /** @var FeatureFlag|null */
    protected $value;

    /**
     * Sets the ID of the feature flag.
     *
     * @param string $value
     * @return $this
     */
    public function setId(string $value)
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the ID of the feature flag.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Sets the value of the feature flag.
     *
     * @param FeatureFlagValue $value
     * @return $this
     */
    public function setValue(FeatureFlagValue $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value of the feature flag.
     *
     * @return FeatureFlagValue|null
     */
    public function getValue() : ?FeatureFlagValue
    {
        return $this->value;
    }

    /**
     * Gets the boolean value of the feature flag.
     *
     * @param bool $default fallback value
     * @return bool
     */
    public function bool(bool $default) : bool
    {
        return $this->getScalarValueOrDefault('bool', $default);
    }

    /**
     * Gets the value of the feature flag using the specified method.
     *
     * @param string $type value type
     * @param bool|int|float|string $default
     * @return bool|int|float|string
     */
    protected function getScalarValueOrDefault(string $type, $default)
    {
        if (! $value = $this->getValue()) {
            return $default;
        }

        $method = sprintf('get%sValue', ucfirst($type));

        if (! method_exists($value, $method)) {
            return $default;
        }

        return $value->{$method}() ?? $default;
    }

    /**
     * Gets the integer value of the feature flag.
     *
     * @param int $default fallback value
     * @return int
     */
    public function int(int $default) : int
    {
        return $this->getScalarValueOrDefault('int', $default);
    }

    /**
     * Gets the float value of the feature flag.
     *
     * @param float $default fallback value
     * @return float
     */
    public function float(float $default) : float
    {
        return $this->getScalarValueOrDefault('float', $default);
    }

    /**
     * Gets the string value of the feature flag.
     *
     * @param string $default fallback value
     * @return string
     */
    public function string(string $default) : string
    {
        return $this->getScalarValueOrDefault('string', $default);
    }
}
