<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling;

use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;

/**
 * Commerce polling feature.
 */
class CommercePolling extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use IntegrationEnabledOnTestTrait;

    /** @var class-string[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        PollingSupervisor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_polling';
    }

    /**
     * Determines whether the feature should load.
     *
     * It will only load if there's at least one enabled job.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (! static::hasEnabledJobs()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Determines whether any jobs are enabled.
     *
     * @return bool
     */
    protected static function hasEnabledJobs() : bool
    {
        $jobs = TypeHelper::array(static::getConfiguration('jobs'), []);

        return count(array_filter(ArrayHelper::pluck($jobs, 'enabled'))) > 0;
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
