<?php

use GoDaddy\WordPress\MWC\Common\Providers\Extensions\ManagedExtensionsRuntimeConfigurationServiceProvider;

return [
    /*
     *--------------------------------------------------------------------------
     * Service Provider Information
     *--------------------------------------------------------------------------
     *
     * Information related to configured service providers.
     *
     */
    'service' => [
        'managedExtensionsRuntimeConfiguration' => ManagedExtensionsRuntimeConfigurationServiceProvider::class,
    ],
];
