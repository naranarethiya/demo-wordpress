<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

/**
 * Object representing the information we expect to receive when a merchant is provisioned via Chatterbox.
 */
class MerchantProvisionedViaChatterboxWebhookPayload extends AbstractWebhookPayload
{
    /** @var string|null */
    protected ?string $merchantUuid;

    /**
     * Gets the merchant UUID.
     *
     * @return string|null
     */
    public function getMerchantUuid() : ?string
    {
        return $this->merchantUuid;
    }

    /**
     * Sets the merchant UUID.
     *
     * @param string $value
     * @return $this
     */
    public function setMerchantUuid(string $value) : MerchantProvisionedViaChatterboxWebhookPayload
    {
        $this->merchantUuid = $value;

        return $this;
    }
}
