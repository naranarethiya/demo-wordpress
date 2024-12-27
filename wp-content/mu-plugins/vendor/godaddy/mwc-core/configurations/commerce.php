<?php

return [
    'customers' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_COMMERCE_CUSTOMERS_SERVICE_URL') ? MWC_COMMERCE_CUSTOMERS_SERVICE_URL : 'https://api.mwc.secureserver.net',
                'dev'  => defined('MWC_COMMERCE_CUSTOMERS_SERVICE_URL') ? MWC_COMMERCE_CUSTOMERS_SERVICE_URL : 'https://api-test.mwc.secureserver.net',
            ],
        ],
    ],
    'catalog' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_COMMERCE_CATALOG_SERVICE_URL') ? MWC_COMMERCE_CATALOG_SERVICE_URL : 'https://api.mwc.secureserver.net',
                'dev'  => defined('MWC_COMMERCE_CATALOG_SERVICE_URL') ? MWC_COMMERCE_CATALOG_SERVICE_URL : 'https://api-test.mwc.secureserver.net',
            ],
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT') ? MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT : 4,
                'dev'  => defined('MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT') ? MWC_COMMERCE_CATALOG_SERVICE_TIMEOUT : 20,
            ],
            /*
             * Maximum number of UUIDs to include in an `ids` filter.
             *
             * This value is set based on a combination of factors:
             *
             * 1. If a query string gets too long, the request won't go through. Max UUIDs in this scenario is 100.
             * 2. The API also has its own lower limitation of 50.
             *
             * @link https://godaddy-corp.atlassian.net/browse/MWC-15546
             * @link https://godaddy-corp.atlassian.net/browse/MWC-15544
             */
            'maxIdsPerRequest' => 50,
        ],
        'website' => [
            'categoriesUrl' => [
                'prod' => 'https://spa.commerce.godaddy.com/categories',
                'dev'  => 'https://spa.commerce.test-godaddy.com/categories',
            ],
        ],
        'assets' => [
            'user' => [
                'login'        => 'gd_commerce_assets',
                'emailAddress' => 'gd-commerce-assets@godaddy.com',
                'displayName'  => 'GoDaddy Commerce Assets',
            ],
            'hideAssetUser' => true,
        ],
    ],
    'inventory' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_COMMERCE_INVENTORY_SERVICE_URL') ? MWC_COMMERCE_INVENTORY_SERVICE_URL : 'https://api.mwc.secureserver.net/v1/commerce/proxy',
                'dev'  => defined('MWC_COMMERCE_INVENTORY_SERVICE_URL') ? MWC_COMMERCE_INVENTORY_SERVICE_URL : 'https://api-test.mwc.secureserver.net/v1/commerce/proxy',
            ],
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT') ? MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT : 4,
                'dev'  => defined('MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT') ? MWC_COMMERCE_INVENTORY_SERVICE_TIMEOUT : 10,
            ],
        ],
    ],
    'gateway' => [
        'api' => [
            'url' => [
                'prod' => defined('MWC_COMMERCE_API_GATEWAY_URL') ? MWC_COMMERCE_API_GATEWAY_URL : 'https://api.mwc.secureserver.net',
                'dev'  => defined('MWC_COMMERCE_API_GATEWAY_URL') ? MWC_COMMERCE_API_GATEWAY_URL : 'https://api-test.mwc.secureserver.net',
            ],
            'timeout' => [
                'prod' => defined('MWC_COMMERCE_API_GATEWAY_TIMEOUT') ? MWC_COMMERCE_API_GATEWAY_TIMEOUT : 4,
                'dev'  => defined('MWC_COMMERCE_API_GATEWAY_TIMEOUT') ? MWC_COMMERCE_API_GATEWAY_TIMEOUT : 10,
            ],
        ],
    ],
];
