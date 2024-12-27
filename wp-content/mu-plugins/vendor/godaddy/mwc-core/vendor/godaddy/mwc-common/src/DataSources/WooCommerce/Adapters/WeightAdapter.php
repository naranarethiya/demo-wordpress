<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Weight;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Weight adapter.
 */
class WeightAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var mixed weight value */
    private $value;

    /** @var mixed|null weight unit */
    private $unit;

    /**
     * Weight adapter constructor.
     *
     * @param mixed $value
     * @param mixed|null $unit
     */
    public function __construct($value, $unit = null)
    {
        $this->value = $value;
        $this->unit = $unit;
    }

    /**
     * Converts a weight into a native object.
     *
     * @return Weight
     */
    public function convertFromSource() : Weight
    {
        $weight = new Weight();

        $value = $this->convertNumberToFloat($this->value);

        if (! is_null($value)) {
            $weight->setValue($value);
        }

        if ($unit = $this->convertWeightUnitFromSource()) {
            $weight->setUnitOfMeasurement($unit);
        }

        return $weight;
    }

    /**
     * Ensures that a number will be adapted to a float.
     *
     * @param string|int|float|mixed $number
     * @return float|null
     */
    protected function convertNumberToFloat($number) : ?float
    {
        return is_numeric($number) ? (float) $number : null;
    }

    /**
     * Converts the given unit into a non-empty string.
     *
     * @return non-empty-string|null
     */
    protected function convertWeightUnitFromSource() : ?string
    {
        if ($this->unit && is_string($this->unit)) {
            return $this->unit;
        }

        if ($unit = WooCommerceRepository::getWeightUnit()) {
            return $unit;
        }

        return null;
    }

    /**
     * Converts a {@see Weight} object into a float.
     *
     * @param ?Weight $weight
     *
     * @return float
     */
    public function convertToSource(?Weight $weight = null) : float
    {
        if ($weight) {
            $this->value = $weight->getValue();
            $this->unit = $weight->getUnitOfMeasurement() ?: null;
        }

        return $this->value;
    }
}
