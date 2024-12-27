<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantProvisioningHandler;

/**
 * Transforms product events to add any Marketplaces-related data.
 */
class MarketplacesProductTransformer extends AbstractEventTransformer
{
    /**
     * {@inheritDoc}
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof EventBridgeEventContract && 'product' === $event->getResource();
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        // sanity check to prevent phpstan warnings
        if ($event instanceof EventBridgeEventContract) {
            $data = $event->getData();

            /*
             * Whether to forward this event to Marketplaces.
             * If `yes`, then the front-end team will pick up this event from EventBridge and forward the information
             * to Marketplaces via a separate API request.
             *
             * Sites using the Commerce feature SHOULD NOT separately forward events to Marketplaces.
             *
             * In the future we could combine the `isMerchantProvisionedOnGDM` logic into this (if not provisioned, do
             * not send to Marketplaces), but for now we maintain both properties for backwards compatibility.
             */
            ArrayHelper::set($data, 'shouldSendToMarketplaces', Commerce::shouldLoad() ? 'no' : 'yes');

            // whether the merchant has been provisioned
            $isMerchantProvisionedOnGDM = MerchantProvisioningHandler::isMerchantProvisioned() ? 'yes' : 'no';
            ArrayHelper::set($data, 'isMerchantProvisionedOnGDM', $isMerchantProvisionedOnGDM);

            $event->setData($data);
        }
    }
}
