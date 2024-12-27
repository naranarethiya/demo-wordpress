<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters\CardPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidPaymentMethodException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingPaymentMethodException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingTokenIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\Adapters\AlternativePaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\DataSources\WooCommerce\AlternativePaymentToken;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\BankAccountPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use WC_Payment_Tokens;

/**
 * WooCommerce Payment Method datastore class.
 *
 * @since 2.10.0
 */
class PaymentMethodDataStore implements DataStoreContract
{
    /** @var string Data provider class name */
    protected $providerName;

    /** @var class-string|class-string<WC_Payment_Tokens> Payment Tokens class name */
    protected $paymentTokens;

    /** @var array<string, string> key value list of payment method adapters per type */
    protected $paymentMethodAdapters;

    /**
     * The WooCommerce Payment Method constructor.
     *
     * @param string $providerName
     * @param class-string|class-string<WC_Payment_Tokens> $paymentTokens
     * @param class-string[] $paymentMethodAdapters
     */
    public function __construct(string $providerName, string $paymentTokens = '\WC_Payment_Tokens', array $paymentMethodAdapters = [])
    {
        $this->providerName = $providerName;
        $this->paymentTokens = $paymentTokens;

        $this->setPaymentMethodAdapters($paymentMethodAdapters);
    }

    /**
     * Sets the supported payment methods adapters.
     *
     * @param array<string, class-string> $paymentMethodAdapters
     * @return $this
     */
    protected function setPaymentMethodAdapters(array $paymentMethodAdapters) : PaymentMethodDataStore
    {
        if (! ArrayHelper::exists($paymentMethodAdapters, 'CC')) {
            $paymentMethodAdapters['CC'] = CardPaymentMethodAdapter::class;
        }

        if (! ArrayHelper::exists($paymentMethodAdapters, 'eCheck')) {
            $paymentMethodAdapters['eCheck'] = BankAccountPaymentMethodAdapter::class;
        }

        if (! ArrayHelper::exists($paymentMethodAdapters, 'mwc_stripe')) {
            $paymentMethodAdapters['mwc_stripe'] = AlternativePaymentMethodAdapter::class;
        }

        $this->paymentMethodAdapters = $paymentMethodAdapters;

        return $this;
    }

    /**
     * Deletes method data from the data store.
     *
     * @param int|null $id
     * @return bool
     * @throws MissingTokenIdException
     */
    public function delete(int $id = null) : bool
    {
        if (null === $id) {
            throw new MissingTokenIdException('Token ID is missing.');
        }

        /* @phpstan-ignore-next-line  */
        call_user_func([$this->paymentTokens, 'delete'], $id);

        return true;
    }

    /**
     * Reads method data from the data store.
     *
     * @param int|null $id
     * @return AbstractPaymentMethod
     * @throws BaseException
     */
    public function read(int $id = null) : AbstractPaymentMethod
    {
        if (null === $id) {
            throw new MissingTokenIdException('Token ID is missing.');
        }

        /** @phpstan-ignore-next-line  */
        $wooToken = call_user_func([$this->paymentTokens, 'get'], $id);
        if (null === $wooToken) {
            throw new MissingTokenIdException('Token not found.');
        }

        $tokenAdapterClass = ArrayHelper::get($this->paymentMethodAdapters, $wooToken->get_type());
        if (null === $tokenAdapterClass) {
            throw new InvalidPaymentMethodException('No matching Payment method adapter found.');
        }

        return (new $tokenAdapterClass($wooToken))->convertFromSource();
    }

    /**
     * Saves method's data to the data store.
     *
     * @param AbstractPaymentMethod|null $method
     * @return AbstractPaymentMethod
     * @throws MissingPaymentMethodException|InvalidPaymentMethodException
     */
    public function save(AbstractPaymentMethod $method = null) : AbstractPaymentMethod
    {
        if (null === $method) {
            throw new MissingPaymentMethodException('Payment Method is missing.');
        }

        $matchingWooTokenType = $this->findMatchingWooTokenType(get_class($method));
        if (null === $matchingWooTokenType) {
            throw new InvalidPaymentMethodException('No matching Payment method adapter found.');
        }

        $wooPaymentTokenClass = $this->getMatchingWooTokenClass($matchingWooTokenType);
        $adapter = new $this->paymentMethodAdapters[$matchingWooTokenType](new $wooPaymentTokenClass());

        /** @var \WC_Payment_Token $convertedWooPaymentToken */
        $convertedWooPaymentToken = $adapter->convertToSource($method); // @phpstan-ignore-line
        $convertedWooPaymentToken->save();

        $method->setId($convertedWooPaymentToken->get_id());

        return $method;
    }

    /**
     * Finds a matching WooCommerce payment token type to the given native payment method.
     *
     * @param string $methodName
     * @return string|null
     */
    protected function findMatchingWooTokenType(string $methodName)
    {
        $methodNameParts = explode('\\', $methodName);
        $className = array_pop($methodNameParts);

        foreach ($this->paymentMethodAdapters as $tokenType => $adapterName) {
            if (false !== strpos($adapterName, $className.'Adapter')) {
                return $tokenType;
            }
        }

        return null;
    }

    /**
     * Gets a matching WooCommerce payment token class to the given token type.
     *
     * @param string $tokenType
     * @return string
     */
    protected function getMatchingWooTokenClass(string $tokenType) : string
    {
        if ('mwc_stripe' === $tokenType) {
            return AlternativePaymentToken::class;
        }

        return '\WC_Payment_Token_'.$tokenType;
    }
}
