<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;

class ApplePayButton extends AbstractWalletButton
{
    /** @var string button style with white outline */
    public const BUTTON_STYLE_WHITE_OUTLINE = 'WHITE_OUTLINE';

    /** @var string "Add Money with Apple Pay" button type */
    public const BUTTON_TYPE_ADD_MONEY = 'ADD_MONEY';

    /** @var string "Continue with Apple Pay" button type */
    public const BUTTON_TYPE_CONTINUE = 'CONTINUE';

    /** @var string "Contribute with Apple Pay" button type */
    public const BUTTON_TYPE_CONTRIBUTE = 'CONTRIBUTE';

    /** @var string "Reload with Apple Pay" button type */
    public const BUTTON_TYPE_RELOAD = 'RELOAD';

    /** @var string "Rent with Apple Pay" button type */
    public const BUTTON_TYPE_RENT = 'RENT';

    /** @var string "Set-up Apple Pay" button type */
    public const BUTTON_TYPE_SETUP = 'SET_UP';

    /** @var string "Support with Apple Pay" button type */
    public const BUTTON_TYPE_SUPPORT = 'SUPPORT';

    /** @var string "Tip with Apple Pay" button type */
    public const BUTTON_TYPE_TIP = 'TIP';

    /** @var string "Top Up with Apple Pay" button type */
    public const BUTTON_TYPE_TOP_UP = 'TOP_UP';

    /**
     * {@inheritDoc}
     */
    public function getWalletId() : string
    {
        return 'apple-pay';
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function isAvailable(string $context) : bool
    {
        return true === Configuration::get('payments.applePay.enabled')
            && ArrayHelper::contains(ArrayHelper::wrap(Configuration::get('payments.applePay.enabledPages', [])), $context)
            && Poynt::isConnected()
            && ApplePayGateway::isActive()
            && ApplePayGateway::isDomainRegisteredWithApple();
    }
}
