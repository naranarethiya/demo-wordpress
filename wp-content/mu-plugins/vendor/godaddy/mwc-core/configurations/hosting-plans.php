<?php

use GoDaddy\WordPress\MWC\Common\HostingPlans\Enums\HostingPlanNamesEnum;

return [
    // implementation of the HostingPlanRepositoryContract interface
    'repository' => GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories\HostingPlanRepository::class,

    // maximum number of plans history to keep
    'max_plans_to_keep' => 3,

    // hosting plan grades for all known plans in MWP and MWCS
    'mappings' => [
        [
            'name'  => HostingPlanNamesEnum::Pro5,
            'grade' => 100,
        ],
        [
            'name'  => HostingPlanNamesEnum::Pro10,
            'grade' => 200,
        ],
        [
            'name'  => HostingPlanNamesEnum::Pro25,
            'grade' => 300,
        ],
        [
            'name'  => HostingPlanNamesEnum::Pro50,
            'grade' => 400,
        ],
        [
            'name'  => HostingPlanNamesEnum::Basic,
            'grade' => 500,
        ],
        [
            'name'  => HostingPlanNamesEnum::Deluxe,
            'grade' => 600,
        ],
        [
            'name'  => HostingPlanNamesEnum::Ultimate,
            'grade' => 700,
        ],
        [
            'name'  => HostingPlanNamesEnum::Ecommerce,
            'grade' => 800,
        ],
        [
            'name'  => HostingPlanNamesEnum::Essentials,
            'grade' => 850,
        ],
        [
            'name'  => HostingPlanNamesEnum::EssentialsCA,
            'grade' => 850,
        ],
        [
            'name'  => HostingPlanNamesEnum::EssentialsWorldpay,
            'grade' => 850,
        ],
        [
            'name'  => HostingPlanNamesEnum::Flex,
            'grade' => 900,
        ],
        [
            'name'  => HostingPlanNamesEnum::FlexCA,
            'grade' => 900,
        ],
        [
            'name'  => HostingPlanNamesEnum::FlexWorldpay,
            'grade' => 900,
        ],
        [
            'name'  => HostingPlanNamesEnum::Expand,
            'grade' => 1000,
        ],
        [
            'name'  => HostingPlanNamesEnum::ExpandCA,
            'grade' => 1000,
        ],
        [
            'name'  => HostingPlanNamesEnum::ExpandWorldpay,
            'grade' => 1000,
        ],
        [
            'name'  => HostingPlanNamesEnum::Premier,
            'grade' => 1100,
        ],
    ],
];
