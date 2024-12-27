<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\ExtensionAdapterContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * The extension adapter.
 */
class ExtensionAdapter implements ExtensionAdapterContract
{
    /** @var array<mixed> source data */
    protected $data;

    /**
     * Constructor.
     *
     * @param array<mixed> $data data to be converted
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Converts from Data Source format.
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function convertFromSource() : array
    {
        $data = $this->getExtensionData();

        if (PluginExtension::TYPE === $this->getType()) {
            $data = ArrayHelper::combine($data, $this->getPluginData());
        }

        return ArrayHelper::where($data, function ($value) {
            return ! is_null($value);
        });
    }

    /**
     * Gets common data for extensions.
     *
     * @return array<string, mixed>
     */
    private function getExtensionData() : array
    {
        return [
            'id'                        => ArrayHelper::get($this->data, 'extensionId'),
            'slug'                      => ArrayHelper::get($this->data, 'slug'),
            'name'                      => ArrayHelper::get($this->data, 'label'),
            'shortDescription'          => ArrayHelper::get($this->data, 'shortDescription'),
            'type'                      => $this->getType(),
            'category'                  => ArrayHelper::get($this->data, 'category'),
            'version'                   => ArrayHelper::get($this->data, 'version.version'),
            'lastUpdated'               => strtotime(TypeHelper::string(ArrayHelper::get($this->data, 'version.releasedAt'), '')) ?: null,
            'minimumPhpVersion'         => ArrayHelper::get($this->data, 'version.minimumPhpVersion'),
            'minimumWordPressVersion'   => ArrayHelper::get($this->data, 'version.minimumWordPressVersion'),
            'minimumWooCommerceVersion' => ArrayHelper::get($this->data, 'version.minimumWooCommerceVersion'),
            'packageUrl'                => ArrayHelper::get($this->data, 'version.links.package.href'),
            'homepageUrl'               => ArrayHelper::get($this->data, 'links.homepage.href'),
            'documentationUrl'          => ArrayHelper::get($this->data, 'links.documentation.href'),
            'imageUrls'                 => $this->getImageUrls(),
            'brand'                     => strtolower(TypeHelper::string(ArrayHelper::get($this->data, 'brand') ?: 'godaddy', 'godaddy')),
        ];
    }

    /**
     * Gets data used for plugin extensions only.
     *
     * @return array<string, ?string>
     */
    private function getPluginData() : array
    {
        return [
            'basename' => $this->getPluginBasename(),
        ];
    }

    /**
     * Gets the WooCommerce plugin basename from its slug.
     *
     * @return string|null
     */
    private function getPluginBasename() : ?string
    {
        if (! $slug = ArrayHelper::get($this->data, 'slug')) {
            return null;
        }

        if (! is_string($slug)) {
            return null;
        }

        /*
         * We don't know a plugin's real basename until after it's installed. Calling `WordPressRepository::getPluginBasenameFromSlug()`
         * first will ensure we get the real basename value for installed extensions. If the extension is not installed
         * then that will return null and we will default to our "guess" value, which is correct in most cases.
         */
        return WordPressRepository::getPluginBasenameFromSlug($slug) ?? "{$slug}/{$slug}.php";
    }

    /**
     * Converts to Data Source format.
     *
     * @return array<mixed>
     */
    public function convertToSource() : array
    {
        return $this->data;
    }

    /**
     * Gets the type of the extension.
     *
     * @return string
     */
    public function getType() : string
    {
        return strtolower(TypeHelper::string(ArrayHelper::get($this->data, 'type'), PluginExtension::TYPE));
    }

    /**
     * Gets the image URLs.
     *
     * @return array<mixed>
     */
    public function getImageUrls() : array
    {
        return ArrayHelper::where(ArrayHelper::wrap(ArrayHelper::get($this->data, 'imageUrls')), function ($value) {
            return ! empty($value) && is_string($value);
        });
    }
}
