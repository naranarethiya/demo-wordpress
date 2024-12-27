<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\DataSources\MWC\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\DataSources\Cache\Adapters\FeatureFlagAdapter;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Models\FeatureFlag;
use RuntimeException;

/**
 * Adapter for the feature evaluations data returned from MWC.
 */
class FeatureEvaluationsAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array source data */
    protected $source;

    /**
     * Constructor.
     */
    public function __construct(array $source = [])
    {
        $this->source = $source;
    }

    /**
     * Converts feature evaluations data into an associative array of feature flag data.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        return array_reduce(
            $this->source,
            function (array $features, array $data) {
                if ($feature = $this->convertFeatureFlagFromSource($data)) {
                    $features[$feature->getId()] = $feature->toArray();
                }

                return $features;
            },
            []
        );
    }

    /**
     * Converts data for a single feature evaluation into a {@see FeatureFlag} instance.
     *
     * @param array $data feature evaluation data
     * @return FeatureFlag|null
     */
    public function convertFeatureFlagFromSource(array $data) : ?FeatureFlag
    {
        if (ArrayHelper::get($data, 'reason') === 'ERROR') {
            return null;
        }

        return (new FeatureFlagAdapter($data))->convertFromSource();
    }

    /**
     * Not implemented.
     *
     * @return void
     */
    public function convertToSource()
    {
        throw new RuntimeException('Method not implemented');
    }
}
