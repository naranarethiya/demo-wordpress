<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Repositories\FeatureFlagsRepository;

/**
 * Interceptor that tries to refresh the available feature flags at the end of admin requests.
 */
class RefreshFeatureEvaluationsInterceptor extends AbstractInterceptor
{
    /**
     * Registers hook handlers.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('shutdown')
            ->setHandler([FeatureFlagsRepository::class, 'refresh'])
            ->setCondition(function () {
                return WordPressRepository::isAdmin() && ! WordPressRepository::isAjax();
            })
            ->execute();
    }
}
