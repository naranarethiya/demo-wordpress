<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasAltIdFilterTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasPageSizeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasPageTokenTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasParentIdTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasRemoteIdsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasSortingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasLocalIdsTrait;

/**
 * Operation for listing/querying products.
 */
class ListProductsOperation implements ListProductsOperationContract
{
    use CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }
    use CanSeedTrait;
    use HasAltIdFilterTrait;
    use HasLocalIdsTrait;
    use HasPageSizeTrait;
    use HasPageTokenTrait;
    use HasParentIdTrait;
    use HasRemoteIdsTrait;
    use HasSortingTrait;

    /** @var array<int> the products local IDs */
    protected array $localIds = [];

    /** @var string[]|null the remote (Commerce) product IDs to include */
    protected ?array $ids = null;

    /** @var ?bool include deleted */
    protected ?bool $includeDeleted = null;

    /** @var ?bool include child products */
    protected ?bool $includeChildProducts = null;

    /** @var ?string sort by */
    protected ?string $sortBy = null;

    /** @var ?string the sort order */
    protected ?string $sortOrder = null;

    /** @var ?int the local category ID */
    protected ?int $localCategoryId = null;

    /** @var ?string the channel ID */
    protected ?string $channelId = null;

    /** @var ?string the SKU */
    protected ?string $sku = null;

    /** @var ?string the altID (aka slug) */
    protected ?string $altId = null;

    /** @var ?string the name */
    protected ?string $name = null;

    /** @var ?string the type */
    protected ?string $type = null;

    /** @var int|null maximum number of results per page */
    protected ?int $pageSize = null;

    /** @var ?string the page token */
    protected ?string $pageToken = null;

    /** @var ?string the parent product id */
    protected ?string $parentId = null;

    /** @var ?string the token direction */
    protected ?string $tokenDirection = null;

    /**
     * {@inheritDoc}
     */
    public function setIncludeDeleted(?bool $includeDeleted)
    {
        $this->includeDeleted = $includeDeleted;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludeDeleted() : ?bool
    {
        return $this->includeDeleted;
    }

    /**
     * {@inheritDoc}
     */
    public function setIncludeChildProducts(?bool $includeChildProducts) : ListProductsOperationContract
    {
        $this->includeChildProducts = $includeChildProducts;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludeChildProducts() : ?bool
    {
        return $this->includeChildProducts;
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalCategoryId(?int $localCategoryId)
    {
        $this->localCategoryId = $localCategoryId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalCategoryId() : ?int
    {
        return $this->localCategoryId;
    }

    /**
     * {@inheritDoc}
     */
    public function setChannelId(?string $channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChannelId() : ?string
    {
        return $this->channelId;
    }

    /**
     * {@inheritDoc}
     */
    public function setSku(?string $sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSku() : ?string
    {
        return $this->sku;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setType(?string $type) : ListProductsOperationContract
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * Overrides the {@see CanConvertToArrayTrait::toArray()} method to exclude some irrelevant properties and only return not-null values.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        return ArrayHelper::whereNotNull(ArrayHelper::except($data, ['localIds', 'localCategoryId']));
    }
}
