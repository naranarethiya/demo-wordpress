<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingDeletedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\ListingWebhookPayload;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Saves a new listing to an existing WooCommerce product.
 *
 * @method static ProductListingService getNewInstance(ListingWebhookPayload $listingWebhookPayload)
 */
class ProductListingService
{
    use CanGetNewInstanceTrait;

    /** @var ListingWebhookPayload */
    protected $listingWebhookPayload;

    /**
     * Constructor;.
     *
     * @param ListingWebhookPayload $listingWebhookPayload
     */
    public function __construct(ListingWebhookPayload $listingWebhookPayload)
    {
        $this->listingWebhookPayload = $listingWebhookPayload;
    }

    /**
     * Saves the listing to the product.
     *
     * @return void
     * @throws SentryException|EventTransformFailedException|BaseException|Exception
     */
    public function saveListing() : void
    {
        if (! $this->hasRequiredWebhookData()) {
            throw new SentryException('Missing expected data from listing webhook payload.');
        }

        $coreProduct = $this->getProduct();

        /** @var Listing $newListing */
        $newListing = $this->listingWebhookPayload->getListing();

        $coreProduct->setMarketplacesListings(
            $this->buildNewListingArray($coreProduct->getMarketplacesListings(), $newListing)
        );

        ProductAdapter::getNewInstance(new WC_Product())->convertToSource($coreProduct)->save();

        Events::broadcast((new ListingCreatedEvent($newListing, $coreProduct)));
    }

    /**
     * Deletes the listing from the product.
     *
     * @return void
     * @throws SentryException|Exception
     */
    public function deleteListing() : void
    {
        if (! $this->hasRequiredWebhookData()) {
            throw new SentryException('Missing expected data from listing webhook payload.');
        }

        $coreProduct = $this->getProduct();
        $productListings = $coreProduct->getMarketplacesListings();
        $webhookListing = $this->listingWebhookPayload->getListing();
        $deletedListingKey = $webhookListing ? $this->getListingKeyById($productListings, $webhookListing->getListingId()) : null;

        if (null === $webhookListing || null === $deletedListingKey) {
            return;
        }

        unset($productListings[$deletedListingKey]);

        $coreProduct->setMarketplacesListings($productListings);

        ProductAdapter::getNewInstance(new WC_Product())->convertToSource($coreProduct)->save();

        Events::broadcast(new ListingDeletedEvent($webhookListing, $coreProduct));
    }

    /**
     * Determines whether we have all the data required to save the listing.
     *
     * @return bool
     */
    protected function hasRequiredWebhookData() : bool
    {
        return $this->listingWebhookPayload->getProductId() && $this->listingWebhookPayload->getListing();
    }

    /**
     * Retrieves a core Product object from the supplied product ID.
     *
     * @return Product
     * @throws SentryException|Exception
     */
    protected function getProduct() : Product
    {
        $productId = $this->listingWebhookPayload->getProductId();

        if (empty($productId)) {
            throw new SentryException('Missing required product ID in payload.');
        }

        if (! $wcProduct = ProductsRepository::get($productId)) {
            throw new SentryException('Failed to retrieve WooCommerce product.');
        }

        return ProductAdapter::getNewInstance($wcProduct)->convertFromSource();
    }

    /**
     * Builds the new listing array for the product. This combines the existing listings with the new one.
     *
     * @param Listing[] $existingListings Listing already saved to the product.
     * @param Listing $newListing New listing being added.
     * @return Listing[]
     */
    protected function buildNewListingArray(array $existingListings, Listing $newListing) : array
    {
        $matchedListingKey = $this->getKeyOfMatchedExistingListing($existingListings, $newListing);

        if (null !== $matchedListingKey && array_key_exists($matchedListingKey, $existingListings)) {
            // be sure to retain the listing URL from the existing one, as we don't want to replace that!
            $newListing->setLink($existingListings[$matchedListingKey]->getLink());

            $existingListings[$matchedListingKey] = $newListing;
        } else {
            $existingListings[] = $newListing;
        }

        return $existingListings;
    }

    /**
     * Gets the key of a listing from an array of listings for a given listing matched by its ID.
     *
     * @param Listing[] $listings listings to search from
     * @param string|null $listingId listing to match
     * @return int|null found index key or null
     */
    protected function getListingKeyById(array $listings, ?string $listingId) : ?int
    {
        foreach ($listings as $listingKey => $existingListing) {
            if ($listingId === $existingListing->getListingId()) {
                return (int) $listingKey;
            }
        }

        return null;
    }

    /**
     * Gets the array key of an existing listing record that has the same ID as the new listing ID that's provided.
     * If the provided listing ID is not in the array of existing listings then `null` is returned instead.
     *
     * @param Listing[] $existingListings existing listings to search from
     * @param Listing $listing listing to match
     * @return int|null found index key or null
     */
    protected function getKeyOfMatchedExistingListing(array $existingListings, Listing $listing) : ?int
    {
        // prioritize a match based on listing ID
        $listingKeyById = $this->getListingKeyById($existingListings, $listing->getListingId());

        if (null !== $listingKeyById) {
            return $listingKeyById;
        }

        // if we still don't have a match, draft listings where channel type matches
        foreach ($existingListings as $listingKey => $existingListing) {
            if (
                ! $existingListing->isPublished() &&
                ! $existingListing->getListingId() &&
                $existingListing->getChannelType() === $listing->getChannelType()
            ) {
                return (int) $listingKey;
            }
        }

        return null;
    }
}
