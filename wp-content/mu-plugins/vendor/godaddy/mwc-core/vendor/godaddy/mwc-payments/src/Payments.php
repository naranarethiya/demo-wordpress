<?php

namespace GoDaddy\WordPress\MWC\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\BankAccountTypeContract;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types\CheckingBankAccountType;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types\SavingsBankAccountType;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\CreditCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DebitCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MaestroCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;

/**
 * The main payments class.
 *
 * @since 0.1.0
 */
class Payments
{
    use IsSingletonTrait;

    /** @var AbstractProvider[] payment providers */
    protected $providers = [];

    /**
     * Sets up the providers from configuration.
     *
     * @since 0.1.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        // load the textdomain
        load_plugin_textdomain('mwc-payments', false, plugin_basename(dirname(__DIR__)).'/languages');

        $this->setProviders(Configuration::get('payments.providers', []));
    }

    /**
     * Sets and instantiates given list of providers.
     *
     * @since 0.1.0
     *
     * @param array $providersClasses
     * @return Payments
     */
    protected function setProviders(array $providersClasses) : Payments
    {
        foreach ($providersClasses as $class) {
            if (false === is_subclass_of($class, AbstractProvider::class)) {
                continue;
            }

            /** @var AbstractProvider $provider */
            $provider = new $class();

            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * Get the providers.
     *
     * @since 0.1.0
     *
     * @return AbstractProvider[]
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * Returns the requested provider, if found in the providers attribute.
     *
     * @since 0.1.0
     *
     * @param string $provider
     * @return AbstractProvider
     * @throws Exception
     */
    public function provider(string $provider) : AbstractProvider
    {
        $foundProvider = ArrayHelper::get($this->providers, $provider);

        if (empty($foundProvider)) {
            throw new Exception("The given provider {$provider} is not found.");
        }

        return $foundProvider;
    }

    /**
     * Gets the card brand object by its name.
     *
     * @param string $brandName
     * @return CardBrandContract
     */
    public static function getCardBrand(string $brandName) : CardBrandContract
    {
        $amex = new AmericanExpressCardBrand();
        $diners = new DinersClubCardBrand();
        $discover = new DiscoverCardBrand();
        $maestro = new MaestroCardBrand();
        $mastercard = new MastercardCardBrand();
        $visa = new VisaCardBrand();

        switch ($brandName) {
            case $amex->getName():
                return $amex;
            case $diners->getName():
                return $diners;
            case $discover->getName():
                return $discover;
            case $maestro->getName():
                return $maestro;
            case $mastercard->getName():
                return $mastercard;
            case $visa->getName():
                return $visa;
            default:
                return StringHelper::contains($brandName, ['debit', 'Debit', __('Debit', 'mwc-payments')]) ? new DebitCardBrand() : new CreditCardBrand();
        }
    }

    /**
     * Gets the bank account type object by its name.
     *
     * @param string $accountType
     * @return BankAccountTypeContract|null
     */
    public static function getBankAccountType(string $accountType)
    {
        $checking = new CheckingBankAccountType();
        $savings = new SavingsBankAccountType();

        switch ($accountType) {
            case $checking->getName():
                return $checking;
            case $savings->getName():
                return $savings;
            default:
                return null;
        }
    }
}
