<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaSynchronizer;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;

/**
 * Service class to insert a Commerce-originating product into the local database.
 */
class InsertLocalProductService extends AbstractInsertLocalResourceService
{
    /** @var ProductBaseAdapter adapter for {@see ProductBase} objects */
    protected ProductBaseAdapter $productBaseAdapter;

    /** @var AttachmentsService service class to help insert any attachments we don't have locally */
    protected AttachmentsService $attachmentsService;

    protected ProductPostMetaSynchronizer $productPostMetaSynchronizer;

    /** @var class-string<AbstractIntegration> name of the integration class */
    protected string $integrationClassName = CatalogIntegration::class;

    public function __construct(
        ProductBaseAdapter $productBaseAdapter,
        ProductsMappingServiceContract $productsMappingService,
        AttachmentsService $attachmentsService,
        ProductPostMetaSynchronizer $productPostMetaSynchronizer
    ) {
        $this->productBaseAdapter = $productBaseAdapter;
        $this->attachmentsService = $attachmentsService;
        $this->productPostMetaSynchronizer = $productPostMetaSynchronizer;

        parent::__construct($productsMappingService);
    }

    /**
     * Inserts a local version {@see Product} of the remote resource {@see ProductBase} into the local database.
     *
     * @param ProductBase $remoteResource
     * @return Product
     * @throws InsertLocalResourceException
     */
    protected function insertLocalResource(AbstractDataObject $remoteResource) : object
    {
        try {
            // pre-flight check to add any attachment records that don't exist yet
            $insertedAttachmentIds = $this->insertMissingAttachments($remoteResource);

            $coreProduct = $this->productBaseAdapter->convertFromSource($remoteResource);
            $wooProduct = ProductAdapter::getNewInstance(new WC_Product())->convertToSource($coreProduct);
        } catch(Exception $e) {
            throw new InsertLocalResourceException('Failed to insert local product for remote product with ID '.$remoteResource->productId.': '.$e->getMessage(), $e);
        }

        $wooProduct->save();

        $localId = TypeHelper::int($wooProduct->get_id(), 0);

        if (empty($localId)) {
            throw new InsertLocalResourceException('Failed to save local resource (empty local ID).');
        }

        if ($insertedAttachmentIds) {
            // Update any inserted attachment IDs to set the parent (product) ID. We couldn't do this earlier because we didn't have the product ID yet.
            $this->updateAttachmentsWithProductId($insertedAttachmentIds, $localId);
        }

        $this->productPostMetaSynchronizer->syncProductMeta($wooProduct, $remoteResource);

        return $coreProduct->setId($localId);
    }

    /**
     * Inserts missing assets into the local database.
     *
     * @param ProductBase $remoteProduct
     * @return int[] array of attachment IDs that were just inserted
     */
    protected function insertMissingAttachments(ProductBase $remoteProduct) : array
    {
        try {
            if ($remoteProduct->assets) {
                return $this->attachmentsService->handle($remoteProduct->assets);
            }
        } catch(SentryException $e) {
            // we don't need to re-report Sentry exceptions
        } catch(Exception $e) {
            // catch exceptions because this doesn't have to halt the entire insert process
            SentryException::getNewInstance('Failed to insert missing attachments for product ID '.TypeHelper::string($remoteProduct->productId, 'unknown'));
        }

        return [];
    }

    /**
     * Updates the local attachment records to set the `post_parent`.
     *
     * @TODO could optionally move this to a different location so we can reuse it for categories one day?
     *
     * @param int[] $attachmentIds
     * @param int $productId
     * @return void
     */
    protected function updateAttachmentsWithProductId(array $attachmentIds, int $productId) : void
    {
        // disabling reads and writes probably isn't required now, but it will be in the future when assets get their own endpoints.
        // no harm in disabling them now anyway
        CatalogIntegration::withoutWrites(function () use ($attachmentIds, $productId) {
            CatalogIntegration::withoutReads(function () use ($attachmentIds, $productId) {
                foreach ($attachmentIds as $attachmentId) {
                    wp_update_post([
                        'ID'          => $attachmentId,
                        'post_parent' => $productId,
                    ]);
                }
            });
        });
    }

    /**
     * Gets the remote resource's UUID.
     *
     * @param ProductBase $remoteResource
     * @return string
     * @throws MissingProductRemoteIdException
     */
    protected function getRemoteResourceId(AbstractDataObject $remoteResource) : string
    {
        if (empty($remoteResource->productId)) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        return $remoteResource->productId;
    }

    /**
     * Gets the local resource's unique identifier.
     *
     * @param object $localResource
     * @return int
     * @throws CommerceException|MissingProductLocalIdException
     */
    protected function getLocalResourceId(object $localResource) : int
    {
        if (! $localResource instanceof Product) {
            throw new CommerceException('Local resource is expected to be a Product instance.');
        }

        if (! $localId = $localResource->getId()) {
            throw new MissingProductLocalIdException('Local Product resource is missing unique ID.');
        }

        return $localId;
    }
}
