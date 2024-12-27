<?php

use GoDaddy\WordPress\MWC\Common\HostingPlans\Enums\HostingPlanNamesEnum;

return [
    /*
     *--------------------------------------------------------------------------
     * Managed WordPress General Settings
     *--------------------------------------------------------------------------
     *
     * The following configuration items are general settings or high level
     * configurations for Managed WordPress.
     */

    /*
     * Information about Managed WordPress hosting plans
     */
    'hosting' => [
        'plans' => [
            HostingPlanNamesEnum::Basic => [
                'name' => 'Basic Managed WordPress',
            ],
            HostingPlanNamesEnum::Deluxe => [
                'name' => 'Deluxe Managed WordPress',
            ],
            HostingPlanNamesEnum::Ecommerce => [
                'name' => 'eCommerce Managed WordPress',
            ],
            HostingPlanNamesEnum::Pro5 => [
                'name' => 'Managed WordPress - Pro 5',
            ],
            HostingPlanNamesEnum::Pro10 => [
                'name' => 'Managed WordPress - Pro 10',
            ],
            HostingPlanNamesEnum::Pro25 => [
                'name' => 'Managed WordPress - Pro 25',
            ],
            HostingPlanNamesEnum::Pro50 => [
                'name' => 'Managed WordPress - Pro 50',
            ],
            HostingPlanNamesEnum::Ultimate => [
                'name' => 'Ultimate Managed WordPress',
            ],
        ],
    ],
];
