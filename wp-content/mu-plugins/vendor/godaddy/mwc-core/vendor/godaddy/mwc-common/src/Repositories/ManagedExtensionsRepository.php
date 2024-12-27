<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\ExtensionAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters\ExtensionAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Extensions\Configuration\Contracts\ManagedExtensionsRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\ThemeExtension;
use GoDaddy\WordPress\MWC\Common\Features\EnabledFeaturesCache;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;

/**
 * Managed extensions repository class.
 *
 * Provides methods for getting Woo and SkyVerge managed extensions.
 */
class ManagedExtensionsRepository
{
    /**
     * Gets all managed extensions.
     *
     * @return AbstractExtension[]
     * @throws Exception
     */
    public static function getManagedExtensions() : array
    {
        /** @throws Exception */
        return array_map(static function ($data) {
            return self::buildManagedExtension(new ExtensionAdapter($data));
        }, Cache::extensions()->remember(function () {
            return static::getManagedExtensionsData();
        }));
    }

    /**
     * Gets the managed plugins.
     *
     * @return PluginExtension[]
     * @throws Exception
     */
    public static function getManagedPlugins() : array
    {
        return ArrayHelper::where(static::getManagedExtensions(), static function (AbstractExtension $extension) {
            return $extension->getType() === PluginExtension::TYPE;
        }, false);
    }

    /**
     * Gets the managed plugin by basename.
     *
     * @return PluginExtension|null
     * @throws Exception
     */
    public static function getManagedPlugin(string $basename)
    {
        foreach (self::getManagedPlugins() as $plugin) {
            if ($plugin->getBasename() === $basename) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Get only the installed managed plugins.
     *
     * @return PluginExtension[]
     * @throws Exception
     */
    public static function getInstalledManagedPlugins() : array
    {
        WordPressRepository::requireWordPressFilesystem();

        $availablePlugins = get_plugins();

        return ArrayHelper::where(static::getManagedPlugins(), function (PluginExtension $plugin) use ($availablePlugins) {
            return ArrayHelper::exists($availablePlugins, $plugin->getBasename());
        });
    }

    /**
     * Gets the managed plugin by basename only if installed.
     *
     * @return PluginExtension|null
     * @throws Exception
     */
    public static function getInstalledManagedPlugin(string $basename)
    {
        $plugin = self::getManagedPlugin($basename);

        return $plugin && $plugin->isInstalled() ? $plugin : null;
    }

    /**
     * Get only the installed managed plugins.
     *
     * @return PluginExtension[]
     * @throws Exception
     */
    public static function getInstalledManagedThemes() : array
    {
        WordPressRepository::requireWordPressFilesystem();

        $availableThemes = wp_get_themes();

        return ArrayHelper::where(static::getManagedThemes(), function (ThemeExtension $theme) use ($availableThemes) {
            return ArrayHelper::exists($availableThemes, $theme->getSlug());
        });
    }

    /**
     * Gets data for managed SkyVerge extensions.
     *
     * @return array
     * @throws Exception
     */
    protected static function getManagedExtensionsData() : array
    {
        $response = GoDaddyRequest::withAuth()
            ->setQuery(static::getExtensionsRequestQuery())
            ->setUrl(static::getManagedExtensionsApiUrl())
            ->send();

        return ArrayHelper::get($response->getBody(), 'data', []);
    }

    /**
     * Retrieves the query arguments for an extensions API request.
     *
     * @return array<string, mixed>
     */
    protected static function getExtensionsRequestQuery() : array
    {
        $queryArgs = ['method' => 'GET'];

        if ($excludedBrands = static::getExcludedExtensionBrands()) {
            $queryArgs['excludedBrands'] = implode(',', $excludedBrands);
        }

        return $queryArgs;
    }

    /**
     * Gets a list of extension brands that should be excluded from the query results.
     *
     * @return non-empty-string[]
     */
    protected static function getExcludedExtensionBrands() : array
    {
        if ($runtimeConfiguration = static::getExtensionsRuntimeConfiguration()) {
            return $runtimeConfiguration->getExcludedBrands();
        }

        return array_values(array_filter(TypeHelper::arrayOfStrings(Configuration::get('mwc.extensions.api.excludedBrands'))));
    }

    /**
     * Gets the Managed Extensions runtime configuration instance.
     *
     * @return ManagedExtensionsRuntimeConfigurationContract|null
     */
    protected static function getExtensionsRuntimeConfiguration() : ?ManagedExtensionsRuntimeConfigurationContract
    {
        $container = ContainerFactory::getInstance()->getSharedContainer();

        try {
            return $container->get(ManagedExtensionsRuntimeConfigurationContract::class);
        } catch (ContainerException $exception) {
            SentryException::getNewInstance(
                'Could not resolve concrete implementation of '.ManagedExtensionsRuntimeConfigurationContract::class,
                $exception
            );
        }

        return null;
    }

    /**
     * Gets the URL for the Managed SkyVerge Extensions API.
     *
     * @return string
     */
    protected static function getManagedExtensionsApiUrl() : string
    {
        if (! $baseUrl = ManagedWooCommerceRepository::getApiUrl()) {
            return '';
        }

        return StringHelper::trailingSlash($baseUrl).'extensions/';
    }

    /**
     * Builds an instance of an extension using the data returned by the given adapter.
     *
     * @since 1.0.0
     *
     * @param ExtensionAdapterContract $adapter data source adapter
     *
     * @return AbstractExtension
     */
    protected static function buildManagedExtension(ExtensionAdapterContract $adapter) : AbstractExtension
    {
        if (ThemeExtension::TYPE === $adapter->getType()) {
            return (new ThemeExtension())->setProperties($adapter->convertFromSource());
        }

        return (new PluginExtension())->setProperties($adapter->convertFromSource());
    }

    /**
     * Gets the managed themes.
     *
     * @return ThemeExtension[]
     * @throws Exception
     */
    public static function getManagedThemes() : array
    {
        return ArrayHelper::where(static::getManagedExtensions(), static function (AbstractExtension $extension) {
            return $extension->getType() === ThemeExtension::TYPE;
        }, false);
    }

    /**
     * Gets available versions for the given extension.
     *
     * It currently returns data for extensions listed in the SkyVerge Extensions API only.
     *
     * @param AbstractExtension $extension the extension object
     * @return AbstractExtension[]
     * @throws Exception
     */
    public static function getManagedExtensionVersions(AbstractExtension $extension) : array
    {
        if (! $extension->getId()) {
            return ArrayHelper::wrap($extension);
        }

        return array_map(static function ($data) {
            return static::buildManagedExtension(new ExtensionAdapter($data));
        }, static::getManagedExtensionVersionsDataFromCache($extension));
    }

    /**
     * Gets version data for the given managed extension from cache.
     *
     * It the cache has no value, it attempts to get the data from the API.
     *
     * @param AbstractExtension $extension the extension object
     * @return array
     * @throws Exception
     */
    protected static function getManagedExtensionVersionsDataFromCache(AbstractExtension $extension) : array
    {
        // @NOTE: If a valid slug is not given then the extension is corrupt and should not be returned nor saved to cache {JO 2021-07-07}
        if (! $extension->getSlug()) {
            // @TODO: Decide if we should be throwing a Sentry Error here {JO 2021-07-07}
            return [];
        }

        /** @throws Exception */
        return static::loadManagedExtensionVersionsData($extension);
    }

    /**
     * Loads data for the available versions of the given managed extension either from cache or the API.
     *
     * @param AbstractExtension $extension
     * @return array
     * @throws Exception
     */
    protected static function loadManagedExtensionVersionsData(AbstractExtension $extension) : array
    {
        /** @throws Exception */
        /** @var array<string|int, array<string, mixed>> $allVersions */
        $allVersions = Cache::versions()->remember(function () {
            return static::getManagedExtensionsVersionsFromApi();
        });

        /** @throws Exception */
        $versions = ArrayHelper::wrap(
            $extension->getId() ? ArrayHelper::get($allVersions, $extension->getId(), []) : []
        );

        usort($versions, static function ($a, $b) {
            $aVersion = ArrayHelper::get(TypeHelper::array($a, []), 'version', 1);
            $bVersion = ArrayHelper::get(TypeHelper::array($b, []), 'version', 1);

            return version_compare($aVersion, $bVersion);
        });

        return static::addExtensionDataToVersionData($extension, $versions);
    }

    /**
     * Loads data for the available versions of managed extensions from the API.
     *
     * @return array
     * @throws Exception
     */
    protected static function getManagedExtensionsVersionsFromApi() : array
    {
        $versions = [];

        $response = GoDaddyRequest::withAuth()
            ->setQuery(static::getExtensionsRequestQuery())
            ->setUrl(static::getManagedExtensionVersionsApiUrl())
            ->send();

        foreach (ArrayHelper::get($response->getBody(), 'data', []) as $version) {
            if ($extensionId = ArrayHelper::get($version, 'extensionId')) {
                $versions[$extensionId][] = $version;
            }
        }

        return $versions;
    }

    /**
     * Gets the URL for the endpoint used to retrieve available versions for a given extension.
     *
     * @param int $count the max number of versions to retrieve
     * @return string
     */
    protected static function getManagedExtensionVersionsApiUrl(int $count = 2500) : string
    {
        if (! $baseUrl = ManagedWooCommerceRepository::getApiUrl()) {
            return '';
        }

        return StringHelper::trailingSlash($baseUrl)."versions?limit={$count}";
    }

    /**
     * Updates the version data with the values of the properties of the given extension.
     *
     * @param AbstractExtension $extension the extension object
     * @param array $versions available versions data
     * @return array
     */
    protected static function addExtensionDataToVersionData(AbstractExtension $extension, array $versions) : array
    {
        return array_map(static function ($version) use ($extension) {
            return [
                'extensionId'      => $extension->getId(),
                'slug'             => $extension->getSlug(),
                'label'            => $extension->getName(),
                'shortDescription' => $extension->getShortDescription(),
                'type'             => $extension->getType(),
                'category'         => $extension->getCategory(),
                'version'          => $version,
                'links'            => [
                    'homepage' => [
                        'href' => $extension->getHomepageUrl(),
                    ],
                    'documentation' => [
                        'href' => $extension->getDocumentationUrl(),
                    ],
                ],
            ];
        }, $versions);
    }

    /*
     * Gets a list of all enabled features.
     *
     * @return array
     */
    public static function getEnabledFeatures() : array
    {
        return (array) EnabledFeaturesCache::getNewInstance()->get();
    }
}
