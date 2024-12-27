<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantAccountLevelDataUpdatedEvent;

/**
 * Handler for GoDaddy Marketplaces merchant account data.
 */
class MerchantAccountLevelDataHandler implements ComponentContract
{
    /** @var string scheduled action hook name */
    const MERCHANT_ACCOUNT_LEVEL_DATA_UPDATED_ACTION = 'mwc_merchant_account_level_data_update';

    /**
     * Adds hooks related to merchant account data.
     *
     * Whenever merchant account data is updated we schedule an event to be triggered.
     *
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('woocommerce_tax_rate_added')
            ->setHandler([$this, 'onUpdatedTaxRates'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_tax_rate_updated')
            ->setHandler([$this, 'onUpdatedTaxRates'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_tax_rate_deleted')
            ->setHandler([$this, 'onUpdatedTaxRates'])
            ->execute();

        Register::action()
            ->setGroup(static::MERCHANT_ACCOUNT_LEVEL_DATA_UPDATED_ACTION)
            ->setHandler([$this, 'onUpdatedMerchantAccountLevelData'])
            ->execute();
    }

    /**
     * Schedules a single action to broadcast its corresponding event when store tax and shipping settings change.
     *
     * This is scheduled after 5 minutes since the settings have changed.
     * @see MerchantAccountLevelDataHandler::onUpdatedMerchantAccountLevelData()
     *
     * @internal
     *
     * @return void
     */
    public function onUpdatedTaxRates() : void
    {
        try {
            $job = Schedule::singleAction()
                ->setName(static::MERCHANT_ACCOUNT_LEVEL_DATA_UPDATED_ACTION)
                ->setScheduleAt(new DateTime('+5 minutes'));

            if (! $job->isScheduled()) {
                $job->schedule();
            }
        } catch (Exception $exception) {
            new SentryException('Could not schedule merchant account level data updated event.', $exception);
        }
    }

    /**
     * Broadcasts an event when merchant account level data is updated.
     *
     * @internal
     *
     * @return void
     */
    public function onUpdatedMerchantAccountLevelData() : void
    {
        Events::broadcast(new MerchantAccountLevelDataUpdatedEvent());
    }

    /**
     * Gets the store's tax and shipping data in the format required for GoDaddy Marketplaces.
     *
     * @link https://godaddy-corp.atlassian.net/wiki/spaces/HANK/pages/44991097/Marketplaces+Adapter+payloads#MarketplacesAdapterpayloads-17.TaxandshipmentsettingsforGoogle
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getStoreData() : array
    {
        $storeAddress = WooCommerceRepository::getShopAddress();

        return [
            'tax_settings' => [
                'country'        => $storeAddress->getCountryCode(),
                'state'          => current($storeAddress->getAdministrativeDistricts()),
                'global_rate'    => true, // Google will calculate tax based on store's location
                'shipping_taxed' => static::isShippingTaxed(),
            ],
            'shipment_settings' => [],
        ];
    }

    /**
     * Gets all the shipping rates configured in the store.
     *
     * @return bool
     */
    protected static function isShippingTaxed() : bool
    {
        $db = DatabaseRepository::instance();
        $table = $db->prefix.'woocommerce_tax_rates';

        return ! empty($db->get_results("SELECT * FROM {$table} WHERE tax_rate_shipping = 1 LIMIT 1"));
    }
}
