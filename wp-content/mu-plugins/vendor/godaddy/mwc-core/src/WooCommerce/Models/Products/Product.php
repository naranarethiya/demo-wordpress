<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Downloadable;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product as CommonProduct;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Traits\HasDimensionsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;

/**
 * Core product object.
 */
class Product extends CommonProduct
{
    use HasDimensionsTrait;

    /** @var int|null used by products that have a parent, like variations */
    protected ?int $parentId = null;

    /** @var string|null the product's slug */
    protected ?string $slug = null;

    /** @var string the product's description (may contain HTML) */
    protected string $description = '';

    /** @var Term[] */
    protected array $categories = [];

    /** @var bool whether the product is virtual */
    protected bool $isVirtual = false;

    /** @var bool whether the product is downloadable */
    protected bool $isDownloadable = false;

    /** @var bool whether stock is managed for the product */
    protected bool $stockManagementEnabled = false;

    /** @var float|null current stock quantity (if managed) */
    protected ?float $currentStock = null;

    /** @var string|null the stock status */
    protected ?string $stockStatus = null;

    /** @var string|null whether backorders are allowed -- one of: `no`, `notify`, or `yes` */
    protected ?string $backordersAllowed = null;

    /** @var Product[]|null variations of this product, if present */
    protected ?array $variants = null;

    /** @var Listing[] */
    protected array $marketplacesListings = [];

    /** @var string|null */
    protected ?string $marketplacesBrand = null;

    /** @var string|null */
    protected ?string $marketplacesCondition = null;

    /** @var string|null Global Trade Item Number (GTIN) */
    protected ?string $marketplacesGtin = null;

    /** @var string|null product Manufacturer Part Number (MPN) */
    protected ?string $marketplacesMpn = null;

    /** @var string|null ID of the product in Google */
    protected ?string $marketplacesGoogleProductId = null;

    /** @var string|null */
    protected ?string $url = null;

    /** @var int|null */
    protected ?int $mainImageId = null;

    /** @var int[] */
    protected array $imageIds = [];

    /** @var string|null applicable tax category */
    protected ?string $taxCategory = null;

    /** @var Downloadable[]|null array of downloadable assets */
    protected ?array $downloadables = null;

    /** @var DateTime|null when the product was created */
    protected ?DateTime $createdAt = null;

    /** @var DateTime|null when the product was last updated */
    protected ?DateTime $updatedAt = null;

    /** @var bool whether the product is purchasable */
    protected bool $isPurchasable = false;

    /** @var string|null password for password-protected products */
    protected ?string $password = null;

    /** @var float|null low stock threshold */
    protected ?float $lowStockThreshold = null;

    /** @var string|null global unique ID (e.g. UPC) */
    protected ?string $globalUniqueId = null;

    /**
     * Gets the product categories.
     *
     * @return Term[]
     */
    public function getCategories() : array
    {
        return $this->categories;
    }

    /**
     * Gets the product's virtual status.
     *
     * @return bool
     */
    public function getIsVirtual() : bool
    {
        return $this->isVirtual;
    }

    /**
     * Determines if the product is a virtual product.
     *
     * @return bool
     */
    public function isVirtual() : bool
    {
        return $this->getIsVirtual();
    }

    /**
     * Gets the product's downloadable status.
     *
     * @return bool
     */
    public function getIsDownloadable() : bool
    {
        return $this->isDownloadable;
    }

    /**
     * Determines if the product is downloadable.
     *
     * @return bool
     */
    public function isDownloadable() : bool
    {
        return $this->getIsDownloadable();
    }

    /**
     * Gets the product's purchasable status.
     *
     * @return bool
     */
    public function isPurchasable() : bool
    {
        return $this->getIsPurchasable();
    }

    /**
     * Gets the product stock management enabled value.
     *
     * @return bool
     */
    public function getStockManagementEnabled() : bool
    {
        return $this->stockManagementEnabled;
    }

    /**
     * Determines if the stock management is enabled for the product.
     *
     * @return bool
     */
    public function hasStockManagementEnabled() : bool
    {
        return $this->getStockManagementEnabled();
    }

    /**
     * Gets the product current stock level.
     *
     * @return float|null
     */
    public function getCurrentStock() : ?float
    {
        return $this->currentStock;
    }

    /**
     * Gets the product's stock status.
     *
     * @return string|null
     */
    public function getStockStatus() : ?string
    {
        return $this->stockStatus;
    }

    /**
     * Gets the backorders allowed setting.
     *
     * @return string|null
     */
    public function getBackordersAllowed() : ?string
    {
        return $this->backordersAllowed;
    }

    /**
     * Gets the product variants.
     *
     * @return Product[]|null
     */
    public function getVariants() : ?array
    {
        return $this->variants;
    }

    /**
     * Gets the product Marketplaces listings.
     *
     * @return Listing[]
     */
    public function getMarketplacesListings() : array
    {
        return $this->marketplacesListings;
    }

    /**
     * Gets the product brand, used in Marketplaces listings.
     *
     * @return string|null
     */
    public function getMarketplacesBrand() : ?string
    {
        return $this->marketplacesBrand;
    }

    /**
     * Gets the product condition, used in Marketplaces listings.
     *
     * @return string|null
     */
    public function getMarketplacesCondition() : ?string
    {
        return $this->marketplacesCondition;
    }

    /**
     * Gets the product's Global Trade Item Number (GTIN), used in Marketplaces listings.
     *
     * @return string|null
     */
    public function getMarketplacesGtin() : ?string
    {
        return $this->marketplacesGtin;
    }

    /**
     * Gets the product's Manufacturer Part Number (MPN), used in Marketplaces listings.
     *
     * @return string|null
     */
    public function getMarketplacesMpn() : ?string
    {
        return $this->marketplacesMpn;
    }

    /**
     * Gets the ID of the product in Google. This will exist if the Google sales channel is connected and a Google listing has been created.
     *
     * @return string|null
     */
    public function getMarketplacesGoogleProductId() : ?string
    {
        return $this->marketplacesGoogleProductId;
    }

    /**
     * Gets the product URL.
     *
     * @return string|null
     */
    public function getUrl() : ?string
    {
        return $this->url;
    }

    /**
     * Gets the identifier of the main product image.
     *
     * @return int|null
     */
    public function getMainImageId() : ?int
    {
        return $this->mainImageId;
    }

    /**
     * Gets the product main image.
     *
     * @return Image|null
     */
    public function getMainImage() : ?Image
    {
        if (! $this->mainImageId) {
            return null;
        }

        $mainImage = Image::get($this->mainImageId);

        return $mainImage instanceof Image ? $mainImage : null;
    }

    /**
     * Gets the identifier for the product images.
     *
     * @return int[]
     */
    public function getImageIds() : array
    {
        return $this->imageIds;
    }

    /**
     * Gets the product images.
     *
     * @return Image[]
     */
    public function getImages() : array
    {
        $images = [];

        foreach ($this->imageIds as $imageId) {
            if ($image = Image::get($imageId)) {
                $images[] = $image;
            }
        }

        return TypeHelper::arrayOf($images, Image::class);
    }

    /**
     * Get the product purchasable status.
     *
     * @return bool
     */
    public function getIsPurchasable() : bool
    {
        return $this->isPurchasable;
    }

    /**
     * Determines if the product is password-protected.
     *
     * @return bool
     */
    public function isPasswordProtected() : bool
    {
        return null !== $this->getPassword();
    }

    /**
     * Gets the product password for password-protected products.
     *
     * @return string|null
     */
    public function getPassword() : ?string
    {
        return '' === $this->password ? null : $this->password;
    }

    /**
     * Gets the global unique ID value.
     *
     * @return string|null
     */
    public function getGlobalUniqueId() : ?string
    {
        return $this->globalUniqueId;
    }

    /**
     * Gets the low stock threshold.
     *
     * @return float|null
     */
    public function getLowStockThreshold() : ?float
    {
        return $this->lowStockThreshold;
    }

    /**
     * Sets the product categories.
     *
     * @param Term[] $value
     * @return $this
     */
    public function setCategories(array $value) : Product
    {
        $this->categories = $value;

        return $this;
    }

    /**
     * Sets the product virtual status.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsVirtual(bool $value) : Product
    {
        $this->isVirtual = $value;

        return $this;
    }

    /**
     * Sets the product downloadable status.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsDownloadable(bool $value) : Product
    {
        $this->isDownloadable = $value;

        return $this;
    }

    /**
     * Sets the stock management enabled value.
     *
     * @param bool $value
     * @return $this
     */
    public function setStockManagementEnabled(bool $value) : Product
    {
        $this->stockManagementEnabled = $value;

        return $this;
    }

    /**
     * Sets the current stock level.
     *
     * @param float|null $value
     * @return $this
     */
    public function setCurrentStock(?float $value) : Product
    {
        $this->currentStock = $value;

        return $this;
    }

    /**
     * Sets the product's stock status.
     *
     * @param string $value
     * @return $this
     */
    public function setStockStatus(string $value) : Product
    {
        $this->stockStatus = $value;

        return $this;
    }

    /**
     * Sets the backorders allowed setting.
     *
     * @param string|null $value
     * @return Product
     */
    public function setBackordersAllowed(?string $value) : Product
    {
        $this->backordersAllowed = $value;

        return $this;
    }

    /**
     * Sets the product variants.
     *
     * @param Product[] $value
     * @return $this
     */
    public function setVariants(array $value) : Product
    {
        $this->variants = $value;

        return $this;
    }

    /**
     * Sets the product Marketplaces listings.
     *
     * @param Listing[] $value
     * @return $this
     */
    public function setMarketplacesListings(array $value) : Product
    {
        $this->marketplacesListings = $value;

        return $this;
    }

    /**
     * Sets the product brand, used in Marketplaces listings.
     *
     * @param string|null $value
     * @return $this
     */
    public function setMarketplacesBrand(?string $value) : Product
    {
        $this->marketplacesBrand = $value;

        return $this;
    }

    /**
     * Sets the product condition, used in Marketplaces listings.
     *
     * @param string|null $value
     * @return $this
     */
    public function setMarketplacesCondition(?string $value) : Product
    {
        $this->marketplacesCondition = $value;

        return $this;
    }

    /**
     * Sets the product's Global Trade Item Number (GTIN), used in Marketplaces listings.
     *
     * @param string|null $value
     * @return $this
     */
    public function setMarketplacesGtin(?string $value) : Product
    {
        $this->marketplacesGtin = $value;

        return $this;
    }

    /**
     * Sets the product's Manufacturer Part Number (MPN), used in Marketplaces listings.
     *
     * @param string|null $value
     * @return $this
     */
    public function setMarketplacesMpn(?string $value) : Product
    {
        $this->marketplacesMpn = $value;

        return $this;
    }

    /**
     * Sets the Marketplaces Google product ID.
     *
     * @param string|null $value
     * @return $this
     */
    public function setMarketplacesGoogleProductId(?string $value) : Product
    {
        $this->marketplacesGoogleProductId = $value;

        return $this;
    }

    /**
     * Sets the product URL.
     *
     * Note: any value set on this method will NOT be persisted on the corresponding WC_Product, as it is not possible
     * to directly set a post permalink.
     *
     * If you need to persist a URL for a WC_Product, consider using the {@see setName()} method instead.
     *
     * @param string|null $value
     * @return $this
     */
    public function setUrl(?string $value) : Product
    {
        $this->url = $value;

        return $this;
    }

    /**
     * Sets the main image ID.
     *
     * @param int|null $value
     * @return $this
     */
    public function setMainImageId(?int $value) : Product
    {
        $this->mainImageId = $value;

        return $this;
    }

    /**
     * Sets the image IDs.
     *
     * @param int[] $value
     * @return $this
     */
    public function setImageIds(array $value) : Product
    {
        $this->imageIds = $value;

        return $this;
    }

    /**
     * Gets the product's description.
     *
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * Sets the product's description.
     *
     * @param string $value
     * @return $this
     */
    public function setDescription(string $value) : Product
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Gets the product's tax category.
     *
     * @return string|null
     */
    public function getTaxCategory() : ?string
    {
        return $this->taxCategory;
    }

    /**
     * Sets the product tax category.
     *
     * @param string $value
     * @return $this
     */
    public function setTaxCategory(string $value) : Product
    {
        $this->taxCategory = $value;

        return $this;
    }

    /**
     * Gets the product's slug.
     *
     * @return string|null
     */
    public function getSlug() : ?string
    {
        return $this->slug;
    }

    /**
     * Sets the product's slug.
     *
     * @param string $value
     * @return $this
     */
    public function setSlug(string $value) : Product
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * Gets the parent ID.
     *
     * @return int|null
     */
    public function getParentId() : ?int
    {
        return $this->parentId;
    }

    /**
     * Sets the parent ID.
     *
     * @param int $value
     * @return $this
     */
    public function setParentId(int $value) : Product
    {
        $this->parentId = $value;

        return $this;
    }

    /**
     * Determines if the product has downloadable assets attached.
     *
     * @return bool
     */
    public function hasDownloadables() : bool
    {
        return ! empty($this->getDownloadables());
    }

    /**
     * Gets any downloadable assets associated with the product.
     *
     * @return Downloadable[]|null
     */
    public function getDownloadables() : ?array
    {
        return $this->downloadables;
    }

    /**
     * Sets downloadables assets for the product.
     *
     * @param Downloadable[] $value
     * @return $this
     */
    public function setDownloadables(array $value) : Product
    {
        $this->downloadables = $value;

        return $this;
    }

    /**
     * Gets the date when the product was created.
     *
     * @return DateTime|null
     */
    public function getCreatedAt() : ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * Sets the date when the product was created.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setCreatedAt(DateTime $value) : Product
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Gets the date when the product was last modified.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Sets the date when the product was last modified.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setUpdatedAt(DateTime $value) : Product
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Sets the product's purchasability.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsPurchasable(bool $value) : Product
    {
        $this->isPurchasable = $value;

        return $this;
    }

    /**
     * Sets a password to make the product password-protected.
     *
     * @param string $value
     * @return $this
     */
    public function setPassword(string $value) : Product
    {
        $this->password = '' === $value ? null : $value;

        return $this;
    }

    /**
     * Sets the global unique ID value.
     *
     * @param string|null $value
     * @return $this
     */
    public function setGlobalUniqueId(?string $value) : Product
    {
        $this->globalUniqueId = $value;

        return $this;
    }

    /**
     * Updates the product.
     *
     * This method also broadcast model events.
     *
     * @return $this
     */
    public function update() : Product
    {
        $product = parent::update();

        Events::broadcast($this->buildEvent('product', 'update'));

        /* @phpstan-ignore-next-line */
        return $product;
    }

    /**
     * Saves the product.
     *
     * This method also broadcast model events.
     *
     * @return $this
     */
    public function save() : Product
    {
        $product = parent::save();

        Events::broadcast($this->buildEvent('product', 'create'));

        /* @phpstan-ignore-next-line */
        return $product;
    }

    /**
     * Deletes the product.
     *
     * This method also broadcast model events.
     *
     * @return void
     */
    public function delete() : void
    {
        parent::delete();

        Events::broadcast($this->buildEvent('product', 'delete'));
    }

    /**
     * Sets the low stock threshold.
     *
     * @param float|null $value
     * @return $this
     */
    public function setLowStockThreshold(?float $value) : Product
    {
        $this->lowStockThreshold = $value;

        return $this;
    }
}
