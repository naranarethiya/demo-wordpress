<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters\ExtensionAdapter;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Plugin adapter.
 *
 * Converts between a native plugin extension object and WordPress plugin data.
 *
 * Unfortunately WordPress doesn't have a first class plugin object to rely upon.
 * In this adapter we assume to adapt from plugin headers retrievable via {@see \get_plugin_data()}.
 *
 * @see ExtensionAdapter for converting between API extension data and native extension objects
 *
 * @method static static getNewInstance(string $baseName, array $pluginData)
 */
class PluginAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var string WordPress base name for the plugin */
    protected $baseName;

    /** @var string the class name of the native object, overrideable */
    protected $pluginClass = PluginExtension::class;

    /** @var array<mixed> plugin data */
    protected $source;

    /**
     * Adapter constructor.
     *
     * @param string $baseName
     * @param array<mixed> $pluginData
     */
    public function __construct(string $baseName, array $pluginData)
    {
        $this->baseName = $baseName;
        $this->source = $pluginData;
    }

    /**
     * Gets an instance of the plugin class.
     *
     * @return PluginExtension
     */
    protected function getPluginInstance() : PluginExtension
    {
        /* @phpstan-ignore-next-line */
        return new $this->pluginClass();
    }

    /**
     * Converts plugin array source data to a native plugin extension object.
     *
     * @return PluginExtension
     */
    public function convertFromSource() : PluginExtension
    {
        // attempt first to set properties automatically, since we're dealing with a variable set of array data
        $plugin = $this->getPluginInstance()->setProperties($this->source);

        /* @phpstan-ignore-next-line */
        return $plugin->setBasename($this->baseName)
            ->setName(ArrayHelper::get($this->source, 'Title', $plugin->getName() ?? ''))
            ->setHomepageUrl(ArrayHelper::get($this->source, 'PluginURI', $plugin->getHomepageUrl() ?? ''))
            ->setVersion(ArrayHelper::get($this->source, 'Version', $plugin->getVersion() ?? ''))
            ->setShortDescription(ArrayHelper::get($this->source, 'Description', $plugin->getShortDescription() ?? ''))
            ->setBrand(ArrayHelper::get($this->source, 'Author', $plugin->getBrand() ?? ''))
            ->setSlug(ArrayHelper::get($this->source, 'TextDomain', $plugin->getSlug() ?? ''))
            ->setMinimumWordPressVersion(ArrayHelper::get($this->source, 'RequiresWP', $plugin->getMinimumWordPressVersion() ?? ''))
            ->setMinimumPHPVersion(ArrayHelper::get($this->source, 'RequiresPHP', $plugin->getMinimumPHPVersion() ?? ''))
            ->setPackageUrl(ArrayHelper::get($this->source, 'UpdateURI', $plugin->getPackageUrl() ?? ''));
    }

    /**
     * Converts a native plugin extension object to plugin array source data.
     *
     * @param PluginExtension|null $pluginExtension
     * @return array<string, mixed>
     */
    public function convertToSource(?PluginExtension $pluginExtension = null) : array
    {
        if (! $pluginExtension instanceof PluginExtension) {
            return $this->source;
        }

        $this->source['Title'] = $pluginExtension->getName();
        $this->source['PluginURI'] = $pluginExtension->getHomepageUrl();
        $this->source['Version'] = $pluginExtension->getVersion();
        $this->source['Description'] = $pluginExtension->getShortDescription();
        $this->source['Author'] = $pluginExtension->getBrand();
        $this->source['TextDomain'] = $pluginExtension->getSlug();
        $this->source['RequiresWP'] = $pluginExtension->getMinimumWordPressVersion();
        $this->source['RequiresPHP'] = $pluginExtension->getMinimumPHPVersion();
        $this->source['UpdateURI'] = $pluginExtension->getPackageUrl();

        return $this->source;
    }
}
