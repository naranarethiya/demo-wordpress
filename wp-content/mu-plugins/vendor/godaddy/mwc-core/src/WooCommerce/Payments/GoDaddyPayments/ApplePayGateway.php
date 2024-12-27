<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\GoDaddyPayments;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\Payments\Providers\PoyntProvider;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Exceptions\FailedApplePayAssociationException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets\ApplePayButton;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Payments\Payments;
use WC_Payment_Gateway;
use WP_Filesystem_Base;

/**
 * GoDaddy Payments Apple Pay Gateway.
 */
class ApplePayGateway extends AbstractWalletGateway
{
    /** @var string wallet ID */
    protected static string $walletId = 'apple-pay';

    /** @var string the option key for the Apple Pay registered domain */
    public const REGISTERED_DOMAIN_OPTION_NAME = 'mwc_payments_apple_pay_domain';

    /**
     * Apple Pay gateway constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->method_title = $this->title = 'GoDaddy Payments - Apple Pay';
        $this->method_description = $this->getDescription();

        if (Worldpay::shouldLoad()) {
            $this->method_title = $this->title = __('Apple Pay', 'mwc-core');
            $this->method_description = __('Securely accept card payments and make checkout easier for customers with Apple Pay® on supported devices.', 'mwc-core');
        }

        parent::__construct();
    }

    /**
     * Gets the method description.
     *
     * @return string
     */
    protected function getDescription() : string
    {
        /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
        $description = __('Accept Apple Pay® on supported devices and make checkout faster for customers. Get paid fast with deposits as soon as the next business day. %1$sGoDaddy Payments Terms apply%2$s.', 'mwc-core');

        return sprintf(
            $description,
            '<a href="'.GoDaddyPayments::getTermsOfServiceUrl().'" target="_blank">',
            ' <span class="dashicons dashicons-external"></span></a>'
        );
    }

    /**
     * Initializes the gateway settings form fields.
     *
     * @see WC_Payment_Gateway::init_settings()
     * @see WC_Payment_Gateway::get_form_fields()
     * @see WC_Payment_Gateway::generate_settings_html()
     */
    public function init_form_fields() : void
    {
        $this->form_fields = [
            'godaddy_payments_settings' => [
                'type' => 'parent_gateway_settings',
            ],
            'apple_pay_settings' => [
                'type'  => 'title',
                'title' => __('Apple Pay Settings', 'mwc-core'),
            ],
            'enabled' => [
                'title'       => __('Enable', 'mwc-core'),
                'label'       => __('Enable to add the payment method to your checkout.', 'mwc-core'),
                'description' => sprintf(
                    __('Apple Pay shows to %1$sSafari users on supported devices%2$s.', 'mwc-core'),
                    '<a href="https://support.apple.com/en-us/HT208531" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'enabled_pages' => [
                /* translators: Text displayed next to a dropdown input field with a list of pages for the user to choose from */
                'title'   => __('Pages to enable Apple Pay on', 'mwc-core'),
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'default' => [
                    ExternalCheckout::BUTTON_PAGE_CART,
                    ExternalCheckout::BUTTON_PAGE_CHECKOUT,
                ],
                'options' => [
                    ExternalCheckout::BUTTON_PAGE_CART           => __('Cart', 'mwc-core'),
                    ExternalCheckout::BUTTON_PAGE_CHECKOUT       => __('Checkout', 'mwc-core'),
                    ExternalCheckout::BUTTON_PAGE_SINGLE_PRODUCT => __('Single Product', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-types */
            'button_type' => [
                'title'       => __('Button label', 'mwc-core'),
                'description' => '<a href="https://developer.apple.com/design/human-interface-guidelines/technologies/apple-pay/buttons-and-marks#button-types" target="_blank">'.__('Check button labels here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => ApplePayButton::BUTTON_TYPE_BUY,
                'options'     => [
                    ApplePayButton::BUTTON_TYPE_ADD_MONEY => _x('Add Money with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_BOOK      => _x('Book with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_BUY       => _x('Buy with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_CHECKOUT  => _x('Check out with', 'Apple Pay', 'mwc-core'),
                    // the `continue` button is listed in https://developer.apple.com/documentation/apple_pay_on_the_web/applepaybuttontype, but it doesn't seem to work
                    // ApplePayButton::BUTTON_TYPE_CONTINUE   => _x('Continue with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_CONTRIBUTE => _x('Contribute with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_DONATE     => _x('Donate with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_ORDER      => _x('Order with', 'Apple Pay', 'mwc-core'),
                    // the `pay` button is listed in https://developer.apple.com/documentation/apple_pay_on_the_web/applepaybuttontype, but it doesn't seem to work
                    // ApplePayButton::BUTTON_TYPE_PAY        => _x('Pay with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_PLAIN   => _x('Plain (logo only)', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_RELOAD  => _x('Reload with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_RENT    => _x('Rent with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_SETUP   => _x('Set up', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_SUPPORT => _x('Support with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_TIP     => _x('Tip with', 'Apple Pay', 'mwc-core'),
                    ApplePayButton::BUTTON_TYPE_TOP_UP  => _x('Top Up with', 'Apple Pay', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-styles */
            'button_style' => [
                'title'       => __('Button style', 'mwc-core'),
                'description' => '<a href="https://developer.apple.com/design/human-interface-guidelines/technologies/apple-pay/buttons-and-marks#button-styles" target="_blank">'.__('Check button style here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => 'BLACK',
                'options'     => [
                    ApplePayButton::BUTTON_STYLE_BLACK         => __('Black', 'mwc-core'),
                    ApplePayButton::BUTTON_STYLE_WHITE         => __('White', 'mwc-core'),
                    ApplePayButton::BUTTON_STYLE_WHITE_OUTLINE => __('White with outline', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-size-and-position */
            'button_height' => [
                'type'              => 'number',
                'title'             => __('Button height', 'mwc-core'),
                'description'       => __('Apple requests the button size match your cart/checkout button and be 30 to 64 pixels tall. The width is set automatically.', 'mwc-core').'<br><a href="https://developer.apple.com/design/human-interface-guidelines/technologies/apple-pay/buttons-and-marks#button-size-and-position" target="_blank">'.__('Check button size here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'css'               => 'max-width: 105px',
                'default'           => ApplePayButton::BUTTON_HEIGHT_DEFAULT,
                'custom_attributes' => [
                    'step' => 1,
                    'min'  => ApplePayButton::BUTTON_HEIGHT_MIN,
                    'max'  => ApplePayButton::BUTTON_HEIGHT_MAX,
                ],
            ],
        ];
    }

    /**
     * Determines whether the gateway is active.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        $isGDPConnected = Onboarding::STATUS_CONNECTED === Onboarding::getStatus() && GoDaddyPaymentsGateway::isActive();

        // allow full override of AP availability for special cases
        if ($isGDPConnected && defined('MWC_ENABLE_APPLE_PAY') && MWC_ENABLE_APPLE_PAY) {
            return true;
        }

        return true === Configuration::get('features.apple_pay')
            && $isGDPConnected
            && ! Worldpay::shouldLoad();
    }

    /**
     * Determines whether the site domain is successfully registered with Apple.
     *
     * @return bool
     */
    public static function isDomainRegisteredWithApple() : bool
    {
        return ! empty(get_option(static::REGISTERED_DOMAIN_OPTION_NAME));
    }

    /**
     * Registers the site domain with Apple Pay.
     */
    public static function registerDomainWithApple() : void
    {
        // clear out any previously registered domain, so that if the domain registration
        // fails, we can display an admin notice to the merchant
        delete_option(static::REGISTERED_DOMAIN_OPTION_NAME);

        try {
            $applePay = static::getProvider();
            $fileContents = $applePay->getDomainAssociationFile();

            if (static::storeDomainAssociationFile($fileContents)) {
                $applePay->register();
                update_option(static::REGISTERED_DOMAIN_OPTION_NAME, SiteRepository::getDomain());
            }
        } catch (Exception $exception) {
            new SentryException(sprintf('Could not register site with Apple Pay: %s', $exception->getMessage()), $exception);
        }
    }

    /**
     * Gets an instance of the provider's Apple Pay gateway.
     *
     * @throws Exception
     */
    public static function getProvider() : Poynt\Gateways\ApplePayGateway
    {
        /** @var PoyntProvider $poynt * */
        $poynt = Payments::getInstance()->provider('poynt');

        return $poynt->applePay();
    }

    /**
     * Writes a file with the Apple Pay domain association.
     *
     * @param string $fileContents
     * @return bool
     * @throws FailedApplePayAssociationException
     * @throws Exception
     */
    protected static function storeDomainAssociationFile(string $fileContents) : bool
    {
        if (! $fileContents) {
            throw new FailedApplePayAssociationException('Apple Pay domain association file is empty.');
        }

        WordPressRepository::requireWordPressFilesystem();

        /* @var WP_Filesystem_Base $fileSystem */
        $fileSystem = WordPressRepository::getFilesystem();
        $fileDir = StringHelper::trailingSlash($fileSystem->abspath()).'.well-known';

        if (! $fileSystem->exists($fileDir)) {
            $fileSystem->mkdir($fileDir);
        }

        if (! $fileSystem->is_writable($fileDir)) {
            throw new FailedApplePayAssociationException('Apple Pay domain association file is not writable.');
        }

        $fileName = 'apple-developer-merchantid-domain-association';
        $filePath = StringHelper::trailingSlash($fileDir).$fileName;
        $success = $fileSystem->put_contents($filePath, $fileContents, 0755);

        if (! $success) {
            throw new FailedApplePayAssociationException('Apple Pay domain association file could not be written.');
        }

        return $success;
    }

    /**
     * Checks whether the payment gateway should be auto enabled.
     *
     * @return bool
     */
    public function shouldAutoEnable() : bool
    {
        return true;
    }
}
