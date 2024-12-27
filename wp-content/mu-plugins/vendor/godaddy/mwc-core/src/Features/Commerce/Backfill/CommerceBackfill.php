<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill;

use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Interceptors\InitiateBackfillInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;

/**
 * Feature responsible for adding all un-mapped local resources to the Commerce Platform.
 */
class CommerceBackfill extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use IntegrationEnabledOnTestTrait;

    /** @var class-string[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        InitiateBackfillInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_backfill';
    }

    /**
     * Loads the feature.
     *
     * @return void
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }
}
