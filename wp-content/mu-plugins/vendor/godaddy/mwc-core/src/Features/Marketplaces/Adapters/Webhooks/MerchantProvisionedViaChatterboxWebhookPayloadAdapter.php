<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\MerchantProvisionedViaChatterboxWebhookPayload;

/**
 * Adapts data from a GDM webhook payload to a native {@see MerchantProvisionedViaChatterboxWebhookPayload} payload.
 *
 * @method static static getNewInstance(array $decodedWebhookPayload)
 */
class MerchantProvisionedViaChatterboxWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> data from the webhook payload */
    protected array $source;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $decodedWebhookPayload
     */
    public function __construct(array $decodedWebhookPayload)
    {
        $this->source = $decodedWebhookPayload;
    }

    /**
     * Converts the decoded payload into a {@see MerchantProvisionedViaChatterboxWebhookPayload} object.
     *
     * @return MerchantProvisionedViaChatterboxWebhookPayload
     */
    public function convertFromSource() : MerchantProvisionedViaChatterboxWebhookPayload
    {
        return MerchantProvisionedViaChatterboxWebhookPayload::getNewInstance()
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), ''))
            ->setIsExpectedEvent($this->isChatterboxProvisioningEvent())
            ->setMerchantUuid(TypeHelper::string(ArrayHelper::get($this->source, 'payload.merchant_uuid'), ''));
    }

    /**
     * Determines whether the event is of the expected type.
     *
     * @return bool
     */
    protected function isChatterboxProvisioningEvent() : bool
    {
        return 'webhook_merchant_chatterbox_provisioned' === TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), '');
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource() : void
    {
        // not implemented
    }
}
