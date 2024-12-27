<?php

namespace GoDaddy\WordPress\MWC\Core\Plugin;

use GoDaddy\WordPress\MWC\Common\Plugin\BaseSystemPlugin;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Package;
use GoDaddy\WordPress\MWC\Dashboard\Dashboard;

/**
 * System Plugin Loader.
 *
 * This class is a patch that makes the MWC core and Dashboard loader objects compatible with BaseSystemPlugin.
 */
class SystemPluginPatchLoader extends BaseSystemPlugin
{
    use IsSingletonTrait;

    /** @var array list of components to instantiate */
    protected $componentClasses = [
        Package::class,
        Dashboard::class,
    ];
}
