<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\DataSources\Cache\Adapters;

use BadMethodCallException;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Models\FeatureFlag;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Models\FeatureFlagValue;

/**
 * Adapter for feature flag data stored in the cache.
 */
class FeatureFlagAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array source data */
    protected $source;

    /**
     * Constructor.
     *
     * @param array $source
     */
    public function __construct(array $source = [])
    {
        $this->source = $source;
    }

    /**
     * Converts feature flag data into an instance of a {@see FeatureFlag}.
     *
     * @return FeatureFlag|null
     */
    public function convertFromSource() : ?FeatureFlag
    {
        if (! $id = ArrayHelper::get($this->source, 'id')) {
            return null;
        }

        return FeatureFlag::seed([
            'id'    => $id,
            'value' => $this->convertFeatureFlagValueFromSource(ArrayHelper::wrap(ArrayHelper::get($this->source, 'value'))),
        ]);
    }

    /**
     * Converts the given data into an instance of {@see FeatureFlagValue}.
     *
     * @param array $data
     * @return FeatureFlagValue
     */
    protected function convertFeatureFlagValueFromSource(array $data) : FeatureFlagValue
    {
        return FeatureFlagValue::seed(ArrayHelper::whereNotNull([
            'boolValue'   => ArrayHelper::get($data, 'boolValue'),
            'intValue'    => ArrayHelper::get($data, 'longValue'),
            'floatValue'  => ArrayHelper::get($data, 'doubleValue'),
            'stringValue' => ArrayHelper::get($data, 'stringValue'),
        ]));
    }

    /**
     * Not implemented.
     *
     * @return void
     */
    public function convertToSource()
    {
        throw new BadMethodCallException('Method not implemented.');
    }
}
