<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;

class RemoteProductNotFoundHelper
{
    /**
     * Handles the scenario where we have a local product ID, a mapping entry linking it to a remote ID, but the request returned a 404 response.
     *
     * When this happens, that's an indicator that the remote product existed at one point, but has now been deleted.
     * We now want to delete the local WooCommerce instance to match.
     *
     * @param int $localId
     * @return void
     */
    public function handle(int $localId) : void
    {
        // disable reads, because `wp_delete_post()` issues a `get_post()` call that we do not need to be routed to the platform
        CatalogIntegration::withoutReads(fn () => wp_delete_post($localId, true));

        /* the mapping entry is automatically removed via `delete_post` hook in {@see LocalProductDeletedInterceptor} */
    }
}
