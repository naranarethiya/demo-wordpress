<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Cache\Types\CacheFeatureFlags;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Cache\Types\CacheLastTimeCheckedTimestamp;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\DataSources\Cache\Adapters\FeatureFlagAdapter;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\DataSources\MWC\Adapters\FeatureEvaluationsAdapter;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Exceptions\FeatureEvaluationsQueryFailedException;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Http\GraphQL\Queries\FeatureEvaluationsQuery;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Models\FeatureFlag;

/**
 * Feature flags repository.
 */
class FeatureFlagsRepository
{
    /**
     * Gets the boolean value of the feature flag.
     *
     * @param string $id feature flag ID
     * @param bool $default default value for the feature flag
     * @return bool
     */
    public static function bool(string $id, bool $default) : bool
    {
        return static::getFeatureFlagValue($id, 'bool', $default);
    }

    /**
     * Gets the value of the feature flag using the specified method.
     *
     * @param string $id feature flag ID
     * @param string $method method name
     * @param bool|int|float|string $default
     * @return bool|int|float|string
     */
    protected static function getFeatureFlagValue(string $id, string $method, $default)
    {
        if (! $feature = static::getById($id)) {
            return $default;
        }

        return $feature->{$method}($default);
    }

    /**
     * Gets the integer value of the feature flag.
     *
     * @param string $id feature flag ID
     * @param int $default fallback value
     * @return int
     */
    public static function int(string $id, int $default) : int
    {
        return static::getFeatureFlagValue($id, 'int', $default);
    }

    /**
     * Gets the float value of the feature flag.
     *
     * @param string $id feature flag ID
     * @param float $default fallback value
     * @return float
     */
    public static function float(string $id, float $default) : float
    {
        return static::getFeatureFlagValue($id, 'float', $default);
    }

    /**
     * Gets the string value of the feature flag.
     *
     * @param string $id feature flag ID
     * @param string $default fallback value
     * @return string
     */
    public static function string(string $id, string $default) : string
    {
        return static::getFeatureFlagValue($id, 'string', $default);
    }

    /**
     * Gets a list of features available for the site from cache.
     *
     * @return FeatureFlag[]
     */
    public static function all() : array
    {
        return array_filter(array_map(
            [static::class, 'buildFeatureFlag'],
            static::getCachedData()
        ));
    }

    /**
     * Gets the feature flags data from cache.
     *
     * @return array
     */
    protected static function getCachedData() : array
    {
        return ArrayHelper::wrap(CacheFeatureFlags::getInstance()->get());
    }

    /**
     * Builds a feature flag instance from the given data.
     *
     * @param array $data feature flag data
     * @return FeatureFlag|null
     */
    protected static function buildFeatureFlag(array $data) : ?FeatureFlag
    {
        return FeatureFlagAdapter::getNewInstance($data)->convertFromSource();
    }

    /**
     * Gets the feature flag with the specified ID.
     *
     * @param string $id feature flag ID
     * @return FeatureFlag|null
     */
    public static function getById(string $id) : ?FeatureFlag
    {
        if (! $data = ArrayHelper::get(static::getCachedData(), $id)) {
            return null;
        }

        return static::buildFeatureFlag($data);
    }

    /**
     * Retrieves the list of features available for the site from the external API.
     *
     * This method will return early if the cache is still considered valid.
     */
    public static function refresh()
    {
        if (CacheLastTimeCheckedTimestamp::getInstance()->get()) {
            return;
        }

        try {
            $data = static::requestFeatureEvaluations();
        } catch (FeatureEvaluationsQueryFailedException $exception) {
            $data = null;
        }

        if (! is_null($data)) {
            CacheFeatureFlags::getInstance()->set(static::prepareFeatureEvaluationsDataForCache($data));
        }

        CacheLastTimeCheckedTimestamp::getInstance()->set(time());
    }

    /**
     * Issues a request to get the feature evaluations for the site.
     *
     * @return array
     * @throws FeatureEvaluationsQueryFailedException
     */
    protected static function requestFeatureEvaluations() : array
    {
        $request = static::getFeatureEvaluationsRequest();

        try {
            $response = $request->send();
        } catch (Exception $exception) {
            throw new FeatureEvaluationsQueryFailedException("An unknown error occurred trying query the feature evaluations. {$exception->getMessage()}", $exception);
        }

        if ($response->isError()) {
            throw new FeatureEvaluationsQueryFailedException("API responded with status {$response->getStatus()}, error: {$response->getErrorMessage()}");
        }

        $data = ArrayHelper::get($response->getBody(), 'data.featureEvaluations.nodes');

        if (! is_array($data)) {
            throw new FeatureEvaluationsQueryFailedException("No feature evaluations included in the response. The API responded with status {$response->getStatus()}.");
        }

        return $data;
    }

    /**
     * Gets a request instance use to get the feature evaluations for the site.
     *
     * @return Request
     * @throws Exception
     */
    protected static function getFeatureEvaluationsRequest() : Request
    {
        $operation = (new FeatureEvaluationsQuery())
            ->setVariables([
                'entityId' => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId(),
                // TODO: remove the featureIds parameter when the GraphQL query no longer requires it {wvega 2022-02-22}
                'featureIds' => array_keys(ArrayHelper::wrap(Configuration::get('features'))),
            ]);

        return Request::withAuth($operation);
    }

    /**
     * Prepares the feature evaluations data to be stored in the feature flags cache.
     *
     * @param array feature evaluations data
     * @return array
     */
    protected static function prepareFeatureEvaluationsDataForCache(array $data) : array
    {
        return FeatureEvaluationsAdapter::getNewInstance($data)->convertFromSource();
    }
}
