<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets\AbstractWalletButton;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets\ApplePayButton;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets\GooglePayButton;

class WalletCheckoutIntegration extends AbstractExternalCheckoutIntegration
{
    /** @var string style and script handler name */
    protected const RESOURCE_HANDLER_NAME = 'godaddy-payments-wallets-frontend';

    /** @var AbstractWalletButton[] list of wallet button instances */
    protected $buttons;

    /**
     * WalletCheckoutIntegration Constructor.
     */
    public function __construct()
    {
        $this->buttons = [
            new ApplePayButton(),
            new GooglePayButton(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(string $context) : bool
    {
        foreach ($this->buttons as $button) {
            if ($button->isAvailable($context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function render() : void
    {
        ?>
        <div id="mwc-payments-wallet-buttons"></div>
        <?php
    }

    /**
     * Enqueues the integration's frontend scripts and styles.
     *
     * @param string $context
     *
     * @throws Exception
     */
    public function enqueueFrontendScriptsAndStyles(string $context) : void
    {
        $sdkUrl = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.api.productionSdkUrl') : Configuration::get('payments.poynt.api.stagingSdkUrl');

        Enqueue::script()
            ->setHandle('poynt-collect-sdk')
            ->setSource(TypeHelper::string($sdkUrl, ''))
            ->execute();

        Enqueue::style()
            ->setHandle(static::RESOURCE_HANDLER_NAME)
            ->setSource(WordPressRepository::getAssetsUrl('css/wallets-frontend.css'))
            ->execute();

        Enqueue::script()
            ->setHandle(static::RESOURCE_HANDLER_NAME)
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/frontend/wallets.js'))
            ->setDependencies(['jquery'])
            ->attachInlineScriptObject('poyntPaymentFormI18n')
            ->attachInlineScriptVariables([
                'errorMessages' => [
                    'genericError'          => __('An error occurred, please try again or try an alternate form of payment.', 'mwc-core'),
                    'missingCardDetails'    => __('Missing card details.', 'mwc-core'),
                    'missingBillingDetails' => __('Missing billing details.', 'mwc-core'),
                ],
            ])
            ->execute();

        wc_enqueue_js(sprintf(
            'window.mwc_payments_wallets_handler = new MWCPaymentsWalletsHandler(%s);',
            ArrayHelper::jsonEncode([
                'appId'            => Poynt::getAppId(),
                'apiNonce'         => wp_create_nonce(API::NONCE_ACTION),
                'apiUrl'           => rest_url(),
                'businessId'       => Poynt::getBusinessId(),
                'enabledButtons'   => $this->getEnabledButtons($context),
                'isLoggingEnabled' => Configuration::get('mwc.debug'),
            ])
        ));
    }

    /**
     * Return associated array of available buttons with Wallet id's as keys and options as values.
     *
     * @param string $context
     * @return array<string, array<string, string>>
     */
    protected function getEnabledButtons(string $context) : array
    {
        $availableButtons = [];

        foreach ($this->buttons as $button) {
            if ($button->isAvailable($context)) {
                $availableButtons[StringHelper::snakeCase($button->getWalletId())] = $button->getOptions();
            }
        }

        return $availableButtons;
    }
}
