<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Cache\HasRecentMerchantProvisioningAttemptCache;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions\ProvisionMerchantException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\ProvisionMerchantRequest;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\UpdateMerchantRequest;

/**
 * Handler to issue a provision merchant request to GoDaddy Marketplaces.
 */
class MerchantProvisioningHandler implements ComponentContract
{
    use CanGetNewInstanceTrait;

    /** @var string option key name to store a flag whether the merchant is provisioned */
    public static $merchant_provisioned_option_key = '_gdm_merchant_provisioned';

    /** @var string option key name to store a flag whether the merchant was updated */
    protected static $merchant_updated_option_key = '_gdm_merchant_updated';

    /**
     * Loads the component.
     *
     * @return void
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeSendMerchantRequest'])
            ->execute();
    }

    /**
     * Determines if the merchant was already provisioned.
     *
     * @return bool
     */
    public static function isMerchantProvisioned() : bool
    {
        return ! empty(get_option(static::$merchant_provisioned_option_key));
    }

    /**
     * Determines if the merchant was already updated.
     *
     * The merchant needs to be updated at least once after the initial provisioning so that GDM knows their site url.
     *
     * @return bool
     */
    public static function isMerchantUpdated() : bool
    {
        return ! empty(get_option(static::$merchant_updated_option_key));
    }

    /**
     * Maybe fires a request to update or provision a merchant.
     *
     *
     * @internal
     *
     * @return void
     */
    public function maybeSendMerchantRequest() : void
    {
        if (static::isMerchantProvisioned() && static::isMerchantUpdated()) {
            return;
        }

        $this->maybeSendUpdateMerchantRequest();
    }

    /**
     * Maybe fires a request to update a merchant.
     *
     * This is needed because the initial provisioning does not set the merchant url.
     *
     * If the merchant is not yet provisioned, will fire a request to provision it.
     *
     * @internal
     *
     * @return void
     */
    public function maybeSendUpdateMerchantRequest() : void
    {
        if (! static::isMerchantUpdated()) {
            try {
                $updateResponse = UpdateMerchantRequest::getNewInstance()->send();
            } catch (Exception $exception) {
                new ProvisionMerchantException(sprintf('Merchant could not be provisioned because the update request failed: %s', $exception->getMessage()), $exception);

                return;
            }

            if (404 === $updateResponse->getStatus()) {
                // the merchant was not found, which means it was not provisioned yet
                $this->maybeSendProvisionMerchantRequest();
            } elseif ($updateResponse->isSuccess()) {
                update_option(static::$merchant_updated_option_key, 'yes');
                update_option(static::$merchant_provisioned_option_key, 'yes');
            }
        }
    }

    /**
     * Maybe fires a request to provision a merchant and stores the response to configuration.
     *
     * If the merchant is already provisioned, may fire a request to update it.
     *
     * @internal
     *
     * @return void
     */
    public function maybeSendProvisionMerchantRequest() : void
    {
        $provisionMerchantAttemptCache = HasRecentMerchantProvisioningAttemptCache::getInstance();

        if (! static::isMerchantProvisioned() && ! $provisionMerchantAttemptCache->hasRecentAttempt()) {
            try {
                $provisionMerchantAttemptCache->set(true);

                $response = ProvisionMerchantRequest::getNewInstance()->send();
            } catch (Exception $exception) {
                new ProvisionMerchantException(sprintf('Merchant could not be provisioned: %s', $exception->getMessage()), $exception);

                return;
            }

            // store non-empty value if already provisioned, or normally try to store the merchant UUID if the response is successful
            if ('account already provisioned' === trim(strtolower($this->getProvisionMerchantResponseErrorMessage($response)))) {
                update_option(static::$merchant_provisioned_option_key, 'yes');

                $this->maybeSendUpdateMerchantRequest();
            } elseif ($response->isSuccess()) {
                update_option(static::$merchant_provisioned_option_key, $this->getProvisionMerchantResponseMerchantUuid($response));
            } else {
                new ProvisionMerchantException(sprintf('Merchant could not be provisioned: %s', $this->getProvisionMerchantResponseErrorMessage($response, 'Unknown error')));
            }
        }
    }

    /**
     * Gets a {@see ProvisionMerchantRequest} response merchant UUID.
     *
     * Defaults to non-empty value for the purposes of {@see MerchantProvisioningHandler::isMerchantProvisioned()}.
     *
     * @param ResponseContract $response
     * @return string
     */
    protected function getProvisionMerchantResponseMerchantUuid(ResponseContract $response) : string
    {
        return (string) ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'merchant_uuid', 'yes');
    }

    /**
     * Gets a {@see ProvisionMerchantRequest} response error message.
     *
     * @param ResponseContract $response
     * @param string $defaultMessage
     * @return string
     */
    protected function getProvisionMerchantResponseErrorMessage(ResponseContract $response, string $defaultMessage = '') : string
    {
        return (string) ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'error.message', $defaultMessage);
    }
}
