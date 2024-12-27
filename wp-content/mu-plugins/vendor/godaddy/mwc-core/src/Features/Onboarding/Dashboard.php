<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;

class Dashboard extends AbstractFeature
{
    use HasComponentsTrait;
    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        HomePage::class,
    ];

    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @return string
     */
    public static function getName() : string
    {
        return 'onboarding_dashboard';
    }

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
    }
}
