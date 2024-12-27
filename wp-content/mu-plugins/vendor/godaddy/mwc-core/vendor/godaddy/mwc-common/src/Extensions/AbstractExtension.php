<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDownloadFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\Exceptions\FailedExtensionCreationException;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * Abstract extension class.
 *
 * Represents an extension to the WordPress base, such as a plugin or theme.
 */
abstract class AbstractExtension extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string install action identifier used for broadcasting events, etc. */
    const ACTION_INSTALL = 'install';

    /** @var string uninstall action identifier used for broadcasting events, etc. */
    const ACTION_UNINSTALL = 'uninstall';

    /** @var string activate action identifier used for broadcasting events, etc. */
    const ACTION_ACTIVATE = 'activate';

    /** @var string deactivate action identifier used for broadcasting events, etc. */
    const ACTION_DEACTIVATE = 'deactivate';

    /** @var string update action identifier used for broadcasting events, etc. */
    const ACTION_UPDATE = 'update';

    /** @var string delete action identifier used for broadcasting events, etc. */
    const ACTION_DELETE = 'delete';

    /** @var string|null the ID, if any */
    protected $id;

    /** @var string|null the slug */
    protected $slug;

    /** @var string|null the name */
    protected $name;

    /** @var string|null the short description */
    protected $shortDescription;

    /** @var string|null the extension type */
    protected $type;

    /** @var string|null the slug of an assigned category, if any */
    protected $category;

    /** @var string|null the extension's brand */
    protected $brand;

    /** @var string|null the version number */
    protected $version;

    /** @var int|null the UNIX timestamp representing when the extension was last updated */
    protected $lastUpdated;

    /** @var string|null the minimum version of PHP required to run the extension */
    protected $minimumPhpVersion;

    /** @var string|null the minimum version of WordPress required to run the extension */
    protected $minimumWordPressVersion;

    /** @var string|null the minimum version of WooCommerce required to run the extension */
    protected $minimumWooCommerceVersion;

    /** @var string|null the URL to download the extension package */
    protected $packageUrl;

    /** @var string|null the URL for the extension's homepage */
    protected $homepageUrl;

    /** @var string|null the URL for the extension's documentation */
    protected $documentationUrl;

    /**
     * Gets the ID.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Gets the slug.
     *
     * @return string|null
     */
    public function getSlug() : ?string
    {
        return $this->slug;
    }

    /**
     * Gets the name.
     *
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * Gets the short description.
     *
     * @return string|null
     */
    public function getShortDescription() : ?string
    {
        return $this->shortDescription;
    }

    /**
     * Gets the type.
     *
     * @return string|null
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * Gets the category.
     *
     * @return string|null
     */
    public function getCategory() : ?string
    {
        return $this->category;
    }

    /**
     * Gets the brand.
     *
     * @return string|null
     */
    public function getBrand() : ?string
    {
        return $this->brand;
    }

    /**
     * Gets the version.
     *
     * @return string|null
     */
    public function getVersion() : ?string
    {
        return $this->version;
    }

    /**
     * Gets the timestamp representing when the asset was last updated.
     *
     * @return int|null
     */
    public function getLastUpdated() : ?int
    {
        return $this->lastUpdated;
    }

    /**
     * Gets the minimum required PHP version to use this asset.
     *
     * @return string|null
     */
    public function getMinimumPHPVersion() : ?string
    {
        return $this->minimumPhpVersion;
    }

    /**
     * Gets the minimum required WordPress version to use this asset.
     *
     * @return string|null
     */
    public function getMinimumWordPressVersion() : ?string
    {
        return $this->minimumWordPressVersion;
    }

    /**
     * Gets the minimum required WooCommerce version to use this asset.
     *
     * @return string|null
     */
    public function getMinimumWooCommerceVersion() : ?string
    {
        return $this->minimumWooCommerceVersion;
    }

    /**
     * Gets the package URL.
     *
     * @return string|null
     */
    public function getPackageUrl() : ?string
    {
        return $this->packageUrl;
    }

    /**
     * Gets the homepage URL.
     *
     * @return string|null
     */
    public function getHomepageUrl() : ?string
    {
        return $this->homepageUrl;
    }

    /**
     * Gets the documentation URL.
     *
     * @return string|null
     */
    public function getDocumentationUrl() : ?string
    {
        return $this->documentationUrl;
    }

    /**
     * Sets the ID.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setId(string $value) : AbstractExtension
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the slug.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setSlug(string $value) : AbstractExtension
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * Sets the name.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setName(string $value) : AbstractExtension
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Sets the short description.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setShortDescription(string $value) : AbstractExtension
    {
        $this->shortDescription = $value;

        return $this;
    }

    /**
     * Sets the type.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setType(string $value) : AbstractExtension
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Sets the category.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setCategory(string $value) : AbstractExtension
    {
        $this->category = $value;

        return $this;
    }

    /**
     * Sets the brand.
     *
     * @param string $brand value to set
     * @return $this
     */
    public function setBrand(string $brand) : AbstractExtension
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Sets the version.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setVersion(string $value) : AbstractExtension
    {
        $this->version = $value;

        return $this;
    }

    /**
     * Sets the time the asset was last updated.
     *
     * @param int $value value to set, as a UTC timestamp
     * @return $this
     */
    public function setLastUpdated(int $value) : AbstractExtension
    {
        $this->lastUpdated = $value;

        return $this;
    }

    /**
     * Sets the minimum PHP version required to use this asset.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setMinimumPHPVersion(string $value) : AbstractExtension
    {
        $this->minimumPhpVersion = $value;

        return $this;
    }

    /**
     * Sets the minimum WordPress version required to use this asset.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setMinimumWordPressVersion(string $value) : AbstractExtension
    {
        $this->minimumWordPressVersion = $value;

        return $this;
    }

    /**
     * Sets the minimum WooCommerce version required to use this asset.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setMinimumWooCommerceVersion(string $value) : AbstractExtension
    {
        $this->minimumWooCommerceVersion = $value;

        return $this;
    }

    /**
     * Sets the package URL.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setPackageUrl(string $value) : AbstractExtension
    {
        $this->packageUrl = $value;

        return $this;
    }

    /**
     * Sets the homepage URL.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setHomepageUrl(string $value) : AbstractExtension
    {
        $this->homepageUrl = $value;

        return $this;
    }

    /**
     * Sets the documentation URL.
     *
     * @param string $value value to set
     * @return $this
     */
    public function setDocumentationUrl(string $value) : AbstractExtension
    {
        $this->documentationUrl = $value;

        return $this;
    }

    /**
     * Downloads the extension.
     *
     * @NOTE Methods calling this function need to {@see unlink()} the temporary file returned by {@see download_url()}.
     *
     * @return string temporary filename
     * @throws ExtensionDownloadFailedException
     */
    public function download() : string
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionDownloadFailedException($exception->getMessage(), $exception);
        }

        $downloadable = download_url($this->getPackageUrl() ?: '');

        if (is_a($downloadable, '\WP_Error', true)) {
            throw new ExtensionDownloadFailedException($downloadable->get_error_message());
        }

        return $downloadable;
    }

    /**
     * Determines if the extension is blocked.
     *
     * @return bool
     */
    public function isBlocked() : bool
    {
        return false;
    }

    /**
     * Activates the extension.
     *
     * @return void
     */
    abstract public function activate() : void;

    /**
     * Determines whether the extension is active.
     *
     * @return bool
     */
    abstract public function isActive() : bool;

    /**
     * Deactivates the extension.
     *
     * @return void
     */
    abstract public function deactivate() : void;

    /**
     * Installs the extension.
     *
     * @return void
     */
    abstract public function install() : void;

    /**
     * Determines if the extension is installed.
     *
     * @return bool
     */
    abstract public function isInstalled() : bool;

    /**
     * Uninstalls the extension.
     *
     * @return void
     */
    abstract public function uninstall() : void;

    /**
     * Extensions cannot use the {@see ModelContract::create()} method.
     * Use {@see AbstractExtension::save()} method instead.
     *
     * @return void
     * @throws Exception
     */
    public static function create() : void
    {
        throw new FailedExtensionCreationException('Extensions cannot be created. Use AbstractExtension::save() method to install and save an extension.');
    }

    /**
     * Updates the extension.
     *
     * @return AbstractExtension
     */
    public function update() : AbstractExtension
    {
        // @TODO we let WordPress handle updates for now, should this change in the future we should think of an `upgrade()` method to interact with WordPress theme or plugin upgrade mechanisms {unfulvio 2022-01-12}
        return $this;
    }

    /**
     * Uninstalls and deletes the extension.
     *
     * @see AbstractExtension::uninstall() alias
     *
     * @return void
     */
    public function delete() : void
    {
        $this->uninstall();
    }

    /**
     * Installs the extension.
     *
     * @see AbstractExtension::install() alias
     *
     * @return $this
     */
    public function save() : AbstractExtension
    {
        $this->install();

        return $this;
    }
}
