<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository as WooCommerceProductRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\ListingAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions\CreateDraftListingException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\CreateDraftListingRequest;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\EditProduct\Metaboxes\MarketplacesMetabox;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ProductRepository as MarketplacesProductRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Handles requests to create draft listings.
 */
class CreateDraftListingAjaxInterceptor extends AbstractInterceptor
{
    /** @var string the action used to create a draft Marketplaces listing */
    const CREATE_DRAFT_LISTING_ACTION = 'mwc_create_draft_marketplaces_listing';

    /**
     * Adds the hook to register the AJAX handler.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('wp_ajax_'.static::CREATE_DRAFT_LISTING_ACTION)
            ->setHandler([$this, 'handleCreateDraftListingRequest'])
            ->execute();
    }

    /**
     * AJAX callback to handle a request to create a Marketplaces draft listing.
     *
     * @internal
     *
     * @return void
     */
    public function handleCreateDraftListingRequest()
    {
        check_ajax_referer(static::CREATE_DRAFT_LISTING_ACTION, 'nonce');

        $productId = (int) ArrayHelper::get($_POST, 'productId');
        $channelUuid = ArrayHelper::get($_POST, 'channelUuid');

        try {
            if (empty($productId)) {
                throw new Exception(__('A product ID is required.', 'mwc-core'));
            }

            if (empty($channelUuid)) {
                throw new Exception(__('A channel UUID is required.', 'mwc-core'));
            }

            if (! current_user_can('edit_product', $productId)) {
                throw new Exception(__('You do not have permission to perform this action.', 'mwc-core'));
            }

            if (! MarketplacesProductRepository::canProductBeListed($productId)) {
                throw new Exception(__('This product must meet all requirements below before creating a draft listing.', 'mwc-core'));
            }

            (new Response())
                ->setBody([
                    'success' => true,
                    'data'    => $this->getListingCreatedHtml($this->createAndSaveMarketplacesListing($productId, $channelUuid)),
                ])
                ->send();
        } catch (Exception $exception) {
            (new Response())
                ->setBody(['success' => false, 'data' => $exception->getMessage()])
                ->send();
        }
    }

    /**
     * Creates a new Marketplaces listing via the API and saves the resulting object to the WooCommerce product.
     *
     * @param int $productId
     * @param string $channelUuid
     * @return Listing
     * @throws Exception
     */
    protected function createAndSaveMarketplacesListing(int $productId, string $channelUuid) : Listing
    {
        try {
            $product = $this->getProductForListing($productId);
            $response = $this->createDraftListingForProduct($channelUuid, $product->getSku());
            $listing = $this->adaptListingFromResponseBody($response->getBody(), $channelUuid);

            $this->saveListingToProduct($product, $listing);

            return $listing;
        } catch (SentryException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);

            // @NOTE the response error message isn't necessarily helpful to the merchant, perhaps for now we'd be better off with just using the generic message {unfulvio 2022-08-09}
            throw new Exception(__('Failed to create a draft listing.', 'mwc-core'));
        }
    }

    /**
     * Gets a native product object for a given source product ID for draft listing purposes.
     *
     * @param int $productId
     * @return Product
     * @throws SentryException|Exception
     */
    protected function getProductForListing(int $productId) : Product
    {
        $product = WooCommerceProductRepository::get($productId);

        if (! $product) {
            throw new CreateDraftListingException(sprintf(__('Failed to retrieve a WooCommerce product with ID #%d while creating a draft Marketplaces listing.', 'mwc-core'), $productId));
        }

        return ProductAdapter::getNewInstance($product)->convertFromSource();
    }

    /**
     * Issues a request to Marketplaces for creating a new draft listing for a given product in the given sales channel.
     *
     * @param string $channelUuid
     * @param string $productSku
     * @return ResponseContract
     * @throws CreateDraftListingException|Exception
     */
    protected function createDraftListingForProduct(string $channelUuid, string $productSku) : ResponseContract
    {
        $response = CreateDraftListingRequest::getNewInstance()
            ->setChannelUuid($channelUuid)
            ->setProductSku($productSku)
            ->send();

        $responseBody = $response->getBody();

        if (empty($responseBody) || $response->isError()) {
            // @NOTE the response error message isn't necessarily helpful to the merchant, perhaps for now we'd be better off with just using the generic message {unfulvio 2022-08-09}
            throw new CreateDraftListingException(__('Failed to create a draft listing.', 'mwc-core'));
        }

        return $response;
    }

    /**
     * Builds the Listing model from the API response.
     *
     * @param array<mixed, mixed>|null $responseBody Response body from the API request.
     * @param string $channelUuid
     * @return Listing
     */
    protected function adaptListingFromResponseBody(?array $responseBody, string $channelUuid) : Listing
    {
        $listing = ListingAdapter::getNewInstance($responseBody ?: [])->convertFromSource();

        // manually set the channel type, because it's not in the API response
        $channel = ChannelRepository::getByUuid($channelUuid);
        if ($channel) {
            $listing->setChannelType($channel->getType());
        } else {
            $listing->setChannelType('');
        }

        return $listing;
    }

    /**
     * Saves the new draft listing to the WooCommerce product.
     *
     * @param Product $product
     * @param Listing $listing
     * @return void
     * @throws SentryException|EventTransformFailedException|Exception
     */
    protected function saveListingToProduct(Product $product, Listing $listing) : void
    {
        $product->setMarketplacesListings(
            TypeHelper::arrayOf(
                ArrayHelper::combine($product->getMarketplacesListings(), [$listing]),
                Listing::class,
                false
            )
        );

        ProductAdapter::getNewInstance(new WC_Product())->convertToSource($product)->save();
    }

    /**
     * Returns the metabox HTML to be set in the DOM after a successful operation.
     *
     * @param Listing $listing
     * @return string
     */
    protected function getListingCreatedHtml(Listing $listing) : string
    {
        ob_start();

        MarketplacesMetabox::getNewInstance()
            ->renderDraftListingCreated(null, null, $listing);

        return ob_get_clean() ?: '';
    }
}
