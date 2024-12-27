<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Stores\Contracts\StoreRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CommerceContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;

class CommerceContextServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CommerceContextContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CommerceContextContract::class, function () {
            /** @var StoreRepositoryContract $storeRepository */
            $storeRepository = $this->getContainer()->get(StoreRepositoryContract::class);

            return CommerceContext::seed([
                'storeId' => (string) $storeRepository->getStoreId(),
            ]);
        });
    }
}
