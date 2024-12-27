<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\PaymentMethods;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;

class CardPaymentMethodAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var AbstractPaymentMethod|null */
    protected $source;

    /**
     * Constructor.
     *
     * @param AbstractPaymentMethod|null $paymentMethod
     */
    public function __construct(AbstractPaymentMethod $paymentMethod = null)
    {
        $this->source = $paymentMethod;
    }

    /**
     * This method is no-op.
     */
    public function convertFromSource() : void
    {
        // no-op
    }

    /**
     * Converts the given data to a card payment method.
     *
     * @param array<string, mixed>|null $data
     *
     * @return CardPaymentMethod
     */
    public function convertToSource(array $data = null) : CardPaymentMethod
    {
        if (! $this->source instanceof CardPaymentMethod) {
            $this->source = new CardPaymentMethod();
        }

        if (empty($data)) {
            return $this->source;
        }

        if ($brand = $this->convertToCardBrand(ArrayHelper::get($data, 'brand', ''))) {
            $this->source->setBrand($brand);
        }

        if ($expirationMonth = ArrayHelper::get($data, 'exp_month')) {
            $this->source->setExpirationMonth($expirationMonth);
        }

        if ($expirationYear = ArrayHelper::get($data, 'exp_year')) {
            $this->source->setExpirationYear($expirationYear);
        }

        if ($lastFour = ArrayHelper::get($data, 'last4')) {
            $this->source->setLastFour($lastFour);
        }

        return $this->source;
    }

    /**
     * Converts the given Stripe card brand to a card brand object.
     *
     * @param string $brand
     *
     * @return CardBrandContract|null
     */
    protected function convertToCardBrand(string $brand) : ?CardBrandContract
    {
        switch ($brand) {
            case 'amex':
                return new AmericanExpressCardBrand();
            case 'diners':
                return new DinersClubCardBrand();
            case 'discover':
                return new DiscoverCardBrand();
            case 'mastercard':
                return new MastercardCardBrand();
            case 'visa':
                return new VisaCardBrand();
            default:
                return null;
        }
    }
}
