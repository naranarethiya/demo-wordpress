<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ProductsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;

/**
 * Checks if a product has been deleted upstream, and if so, handles the appropriate user experience by displaying
 * an error message.
 *
 * Note: as a side effect of the read product operation, if the product exists it will be re-cached locally.
 */
class CheckForDeletedProductHelper
{
    protected ProductsServiceContract $productsService;
    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    /**
     * @param ProductsServiceContract $productsService
     * @param RemoteProductNotFoundHelper $remoteProductNotFoundHelper
     */
    public function __construct(ProductsServiceContract $productsService, RemoteProductNotFoundHelper $remoteProductNotFoundHelper)
    {
        $this->productsService = $productsService;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;
    }

    /**
     * Checks if the supplied local product ID has been deleted upstream.
     * If it has been, then we'll display an error message to the user {@see static::handleDeletedProductUserExperience()}
     * If it has not been, then the product is re-cached locally {@see ProductsService::readProduct()}.
     *
     * @param int $localId
     * @return ?ProductBase
     */
    public function deleteLocalProductIfDeletedUpstream(int $localId) : ?ProductBase
    {
        try {
            $response = $this->productsService->readProduct(
                (new ReadProductOperation())->setLocalId($localId)
            );

            return $response->getProduct();
        } catch(ProductNotFoundException $e) {
            // delete the product locally
            $this->remoteProductNotFoundHelper->handle($localId);

            $this->handleDeletedProductUserExperience();
        } catch(ProductMappingNotFoundException $e) {
            // this means the product hasn't been written to the platform yet; we do not need to report this error.
            // @TODO perhaps in a future story we will use this opportunity to write it immediately
        } catch(Exception $e) {
            SentryException::getNewInstance('Failed to fetch product by local ID.', $e);
        }

        return null;
    }

    /**
     * Handles the user experience for attempting to view a product that has since been deleted.
     *
     * @return void
     */
    protected function handleDeletedProductUserExperience() : void
    {
        if (WordPressRepository::isAdmin()) {
            wp_die(__('You attempted to edit an item that does not exist. Perhaps it was deleted?', 'mwc-core'));
        } else {
            // @TODO determine how to handle front-end in MWC-12620 {agibson 2023-06-15}
        }
    }
}
