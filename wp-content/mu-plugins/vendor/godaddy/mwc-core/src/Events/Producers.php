<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

/**
 * Producers.
 */
class Producers implements ConditionalComponentContract
{
    use IsConditionalFeatureTrait;
    use HasComponentsTrait;

    /**
     * Class constructor.
     *
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * TODO: remove this method when {@see Package} is converted to use {@see HasComponentsTrait} {nmolham 2021-10-27}
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return static::shouldLoad();
    }

    /**
     * {@inheritdoc}
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }

    /**
     * Determines whether the class should load.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function shouldLoad() : bool
    {
        return WooCommerceRepository::isWooCommerceActive()
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }

    /**
     * Gets list of events producers classes.
     *
     * @return array
     */
    protected function getComponentClasses() : array
    {
        return Configuration::get('events.producers', []);
    }
}
