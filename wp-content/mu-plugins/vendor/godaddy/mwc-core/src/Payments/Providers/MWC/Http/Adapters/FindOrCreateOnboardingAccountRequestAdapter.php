<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers\MWC\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class FindOrCreateOnboardingAccountRequestAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Creates a new GoDaddy request.
     *
     * @return GoDaddyRequest
     * @throws Exception
     */
    public function convertFromSource() : GoDaddyRequest
    {
        /** @var string $requestUrl */
        $requestUrl = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.api.productionRoot', '') : Configuration::get('payments.api.stagingRoot', '');

        return (new GoDaddyRequest())->setBody([
            'siteUrl'       => SiteRepository::getHomeUrl(),
            'siteName'      => SiteRepository::getTitle(),
            'siteXid'       => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformSiteId(),
            'webhookSecret' => Onboarding::getWebhookSecret(),
        ])->setUrl(StringHelper::trailingSlash($requestUrl).'onboarding/account')
          ->setMethod('POST');
    }

    /**
     * Converts the given response into an array.
     *
     * @param Response|null $response
     * @return array
     */
    public function convertToSource(Response $response = null) : array
    {
        $data = $response ? $response->getBody() : [];

        return [
            'cloudAppId'    => ArrayHelper::get($data, 'cloudAppId'),
            'applicationId' => ArrayHelper::get($data, 'applicationId'),
            'businessId'    => ArrayHelper::get($data, 'businessId'),
            'privateKey'    => ArrayHelper::get($data, 'privateKey'),
            'publicKey'     => ArrayHelper::get($data, 'publicKey'),
            'serviceId'     => ArrayHelper::get($data, 'serviceId'),
            'storeId'       => ArrayHelper::get($data, 'storeId'),
        ];
    }
}
