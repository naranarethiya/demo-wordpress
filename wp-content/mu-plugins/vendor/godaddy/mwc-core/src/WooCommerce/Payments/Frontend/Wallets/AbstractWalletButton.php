<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Wallets;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

abstract class AbstractWalletButton
{
    use CanGetNewInstanceTrait;

    /** @var int default button height */
    public const BUTTON_HEIGHT_DEFAULT = 45;

    /** @var int min button height */
    public const BUTTON_HEIGHT_MIN = 30;

    /** @var int max button height */
    public const BUTTON_HEIGHT_MAX = 64;

    /** @var string black button style */
    public const BUTTON_STYLE_BLACK = 'BLACK';

    /** @var string white button style */
    public const BUTTON_STYLE_WHITE = 'WHITE';

    /** @var string "Book with Pay" button type */
    public const BUTTON_TYPE_BOOK = 'BOOK';

    /** @var string "Buy with Pay" button type */
    public const BUTTON_TYPE_BUY = 'BUY';

    /** @var string "Donate with Pay" button type */
    public const BUTTON_TYPE_DONATE = 'DONATE';

    /** @var string "Check out with Pay" button type */
    public const BUTTON_TYPE_CHECKOUT = 'CHECKOUT';

    /** @var string "Order with Pay" button type */
    public const BUTTON_TYPE_ORDER = 'ORDER';

    /** @var string "Pay with Pay" button type */
    public const BUTTON_TYPE_PAY = 'PAY';

    /** @var string button type with no text, only logo */
    public const BUTTON_TYPE_PLAIN = 'PLAIN';

    /**
     * Gets the button options.
     *
     * @return array<string, mixed>
     */
    public function getOptions() : array
    {
        /*
         * Filters the wallet button options.
         *
         * @param array<string, mixed> $options
         */
        return apply_filters(sprintf('mwc_payments_%s_button_options', StringHelper::snakeCase($this->getWalletId())), [
            'color'  => $this->getStyle(),
            'type'   => $this->getType(),
            'locale' => $this->getLanguage(),
            'height' => $this->getHeight().'px',
            'width'  => '100%',
            'margin' => '0px', // passing integer 0 won't work, a unit has to be provided
        ]);
    }

    /**
     * Gets the style (color) for the wallet button.
     *
     * @return string
     */
    protected function getStyle() : string
    {
        return str_replace('_', '-', strtolower(TypeHelper::string(Configuration::get($this->getWalletConfigurationKey('buttonStyle')), static::BUTTON_STYLE_BLACK)));
    }

    /**
     * Gets the type for the wallet button.
     *
     * @return string
     */
    protected function getType() : string
    {
        return str_replace('_', '-', strtolower(TypeHelper::string(Configuration::get($this->getWalletConfigurationKey('buttonType')), static::BUTTON_TYPE_BUY)));
    }

    /**
     * Gets the locale for the wallet button.
     *
     * @NOTE if the locale is invalid or unsupported by the wallet, it should automatically have the browser default to English
     *
     * @return string
     */
    protected function getLanguage() : string
    {
        return substr(WordPressRepository::getLocale(), 0, 2);
    }

    /**
     * Gets the button height.
     *
     * @return int
     */
    protected function getHeight() : int
    {
        return max(
            static::BUTTON_HEIGHT_MIN,
            min(
                static::BUTTON_HEIGHT_MAX,
                (int) Configuration::get($this->getWalletConfigurationKey('buttonHeight'), static::BUTTON_HEIGHT_DEFAULT)
            )
        );
    }

    /**
     * Gets the full configuration key for the wallet, given the base key.
     *
     * @param string $key
     * @return string
     */
    protected function getWalletConfigurationKey(string $key) : string
    {
        return sprintf('payments.%s.%s', StringHelper::camelCase($this->getWalletId()), $key);
    }

    /**
     * Gets the wallet ID for this wallet button.
     *
     * @return string
     */
    abstract public function getWalletId() : string;

    /**
     * Decides if the button should be available.
     *
     * @param string $context used in concrete implementations
     * @return bool
     */
    abstract public function isAvailable(string $context) : bool;
}
