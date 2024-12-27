<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\WordPress;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\WordPress\Overrides\ReadingSettingsInterceptor;

/**
 * WordPress overrides.
 */
class Overrides implements ComponentContract
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [
        ReadingSettingsInterceptor::class,
    ];

    /**
     * Loads overrides components.
     *
     * @throws Exception
     * @return void
     */
    public function load() : void
    {
        // Load defined components
        $this->loadComponents();
    }
}
