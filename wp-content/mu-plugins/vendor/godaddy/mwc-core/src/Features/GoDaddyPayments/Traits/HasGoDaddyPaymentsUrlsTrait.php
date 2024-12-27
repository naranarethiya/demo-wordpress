<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;

trait HasGoDaddyPaymentsUrlsTrait
{
    /**
     * Gets the GoDaddy Payments Hub URL.
     *
     * @return string
     */
    protected function getHubUrl() : string
    {
        try {
            if (Worldpay::shouldLoad() && ! Worldpay::shouldProcessTemporaryContent()) {
                return StringHelper::trailingSlash(TypeHelper::string(Configuration::get('features.worldpay.hqUrl', ''), ''));
            }
        } catch (Exception $exception) {
        }

        $url = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.hub.productionUrl', '') : Configuration::get('payments.poynt.hub.stagingUrl', '');

        return StringHelper::trailingSlash(TypeHelper::string($url, ''));
    }

    /**
     * Gets the GoDaddy Payments menu URL.
     *
     * @return string
     * @throws Exception
     */
    public function getMenuLabel() : string
    {
        if (Worldpay::shouldProcessTemporaryContent()) {
            return __('Commerce Home', 'mwc-core');
        }

        if (Worldpay::shouldLoad()) {
            return __('Payments Dashboard', 'mwc-core');
        }

        return __('GoDaddy Payments', 'mwc-core');
    }

    /**
     * Gets the GoDaddy Payments menu URL.
     *
     * @return string
     * @throws Exception
     */
    public function getMenuUrl() : string
    {
        $baseUrl = Worldpay::shouldProcessTemporaryContent() ? Configuration::get('features.worldpay.baseMenuUrl') : $this->getHubUrl();

        $url = StringHelper::trailingSlash(TypeHelper::string($baseUrl, ''));

        $params = [
            'businessId' => Poynt::getBusinessId(),
            'storeId'    => Poynt::getSiteStoreId(),
        ];

        $section = '';

        if (Worldpay::shouldLoad()) {
            $section = 'home';

            if (! Configuration::get('features.worldpay.useNewUrls')) {
                $section = 'dashboard';
            }

            $params['ua_placement'] = 'shared_header';
        }

        return add_query_arg($params, $url.$section);
    }

    /**
     * Gets the GoDaddy Payments external settings page URL.
     *
     * @return string
     */
    protected function getSettingsUrl() : string
    {
        $baseUrl = $this->getHubUrl();

        try {
            $poyntBusinessId = Poynt::getBusinessId();

            $queryArgs = [
                'businessId' => $poyntBusinessId,
                'storeId'    => Poynt::getSiteStoreId(),
            ];
            $url = $baseUrl.'settings';

            if (Worldpay::shouldProcessTemporaryContent()) {
                $queryArgs['ua_placement'] = 'shared_header';
            } elseif (Worldpay::shouldLoad()) {
                $url = $baseUrl.'dashboard';
            }

            return add_query_arg($queryArgs, $url);
        } catch (Exception $exception) {
        }

        return '';
    }

    /**
     * Gets the GoDaddy Payments external devices page URL.
     *
     * @return string
     */
    protected function getDevicesUrl() : string
    {
        $baseUrl = $this->getHubUrl();

        try {
            $poyntBusinessId = Poynt::getBusinessId();
            if (Worldpay::shouldProcessTemporaryContent()) {
                return add_query_arg([
                    'businessId'   => $poyntBusinessId,
                    'storeId'      => Poynt::getSiteStoreId(),
                    'ua_placement' => 'shared_header',
                ], $baseUrl.'in-person/devices');
            }

            return add_query_arg(['businessId' => $poyntBusinessId], $baseUrl.'payment-tools/devices');
        } catch (Exception $exception) {
        }

        return '';
    }

    /**
     * Gets the GoDaddy Payments external catalog page URL.
     *
     * @return string
     */
    protected function getCatalogUrl() : string
    {
        $baseUrl = $this->getHubUrl();

        try {
            $poyntBusinessId = Poynt::getBusinessId();

            $queryArgs = ['businessId' => $poyntBusinessId];
            $url = $baseUrl.'payment-tools/catalog';

            if (Worldpay::shouldProcessTemporaryContent()) {
                $queryArgs['storeId'] = Poynt::getSiteStoreId();
                $queryArgs['ua_placement'] = 'shared_header';

                $url = $baseUrl.'in-person/catalogs';
            } elseif (Worldpay::shouldLoad()) {
                $url = $baseUrl.'catalogs';
            }

            return add_query_arg($queryArgs, $url);
        } catch (Exception $exception) {
        }

        return '';
    }

    /**
     * Gets the GoDaddy Payments external terminal page URL.
     *
     * @return string
     */
    protected function getTerminalUrl() : string
    {
        $baseUrl = $this->getHubUrl();

        try {
            $poyntBusinessId = Poynt::getBusinessId();

            $queryArgs = ['businessId' => $poyntBusinessId];
            $url = $baseUrl.'payment-tools/customization';

            if (Worldpay::shouldProcessTemporaryContent()) {
                $queryArgs['storeId'] = Poynt::getSiteStoreId();
                $queryArgs['ua_placement'] = 'shared_header';

                $url = $baseUrl.'in-person/customization';
            } elseif (Worldpay::shouldLoad()) {
                $url = $baseUrl.'settings/store/terminals';
            }

            return add_query_arg($queryArgs, $url);
        } catch (Exception $exception) {
        }

        return '';
    }
}
