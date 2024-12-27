<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;

class SitePropertiesTransformer extends AbstractEventTransformer
{
    /**
     * {@inheritDoc}
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof EventBridgeEventContract;
    }

    /**
     * @param EventBridgeEventContract|EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event) : void
    {
        /** @var EventBridgeEventContract $event */
        $data = $event->getData();

        ArrayHelper::set($data, 'site.isGdpEligible', WooCommerceRepository::isWooCommerceActive() && GoDaddyPayments::isSiteEligible());
        ArrayHelper::set($data, 'site.isBankingPartner', $this->isBankingPartner());

        $event->setData($data);
    }

    /**
     * Determines if this site is associated with a banking partner.
     *
     * @TODO rework this in the future to be more sustainable -- maybe move to a config, or is there a way to avoid a hard-coded list? etc. {agibson 2023-08-08}
     *
     * @return bool
     */
    protected function isBankingPartner() : bool
    {
        try {
            // for now Worldpay is the only banking partner
            return Worldpay::shouldLoad();
        } catch(Exception $e) {
            // we don't want to throw exceptions here
            SentryException::getNewInstance('Failed to determine banking partner status.', $e);

            return false;
        }
    }
}
