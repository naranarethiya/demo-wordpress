<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits\MasksData;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\StripeGateway as WooCommerceStripeGateway;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;
use Stripe\ApiResource;
use Stripe\StripeClient;

/**
 * Stripe gateway.
 */
class StripeGateway extends AbstractGateway
{
    use CanGetNewInstanceTrait;
    use MasksData;

    /**
     * Returns an instantiated Stripe client loaded with the correct API key.
     *
     * @return StripeClient
     */
    public function getClient() : StripeClient
    {
        return new StripeClient(Stripe::getApiSecretKey());
    }

    /**
     * Maybe log API request information based on configuration.
     *
     * @param string $method
     * @param array<mixed> $requestArgs
     * @param AbstractModel|null $requestModel
     */
    protected function maybeLogApiRequest(string $method, array $requestArgs, ?AbstractModel $requestModel = null) : void
    {
        if (! $this->shouldLogEntry()) {
            return;
        }

        $logEntry = ['args' => $requestArgs];

        if ($requestModel) {
            $logEntry['model'] = $requestModel->toArray();
        }

        $this->logEntry(sprintf('Request: %s %s', $method, $this->jsonEncode($logEntry)));
    }

    /**
     * Maybe log API response based on configuration.
     *
     * @param string $method
     * @param ApiResource $resource
     */
    protected function maybeLogApiResponse(string $method, ApiResource $resource) : void
    {
        if (! $this->shouldLogEntry()) {
            return;
        }

        $this->logEntry(sprintf('Response: %s %s %s', $method, $resource->instanceUrl(), $this->jsonEncode($this->maskData($resource->toArray(), ['client_secret']))));
    }

    /**
     * JSON-encodes and pretty prints the given array.
     *
     * @param array<mixed> $data
     * @return string
     */
    protected function jsonEncode(array $data) : string
    {
        if (! $jsonEncode = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) {
            return '';
        }

        return $jsonEncode;
    }

    /**
     * Checks log setting status.
     *
     * @return bool
     */
    protected function shouldLogEntry() : bool
    {
        $debugMode = Configuration::get('payments.stripe.debugMode');

        return $debugMode === WooCommerceStripeGateway::DEBUG_MODE_LOG || $debugMode === WooCommerceStripeGateway::DEBUG_MODE_BOTH;
    }

    /**
     * Logs the entry.
     *
     * @param string $logEntry
     */
    protected function logEntry(string $logEntry) : void
    {
        wc_get_logger()->info($logEntry, ['source' => 'mwc-stripe']);
    }

    /**
     * Returns whether the Stripe payment gateway is enabled in WooCommerce.
     *
     * @return bool
     */
    public static function isStripeGatewayEnabled() : bool
    {
        /* @phpstan-ignore-next-line */
        if (! $woocommerce = WC()) {
            return false;
        }

        /* @phpstan-ignore-next-line */
        if (! $gateways = $woocommerce->payment_gateways()) {
            return false;
        }

        return ArrayHelper::exists($gateways->get_available_payment_gateways(), 'stripe');
    }
}
