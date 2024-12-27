<?php

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductCategoriesJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Jobs\BatchCreateOrUpdateProductsJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Jobs\PatchProductCategoryAssociationsJob;
use GoDaddy\WordPress\MWC\Core\JobQueue\DataObjects\BatchJobSettings;

return [
    /*
     * Registry of `QueueableJobContract` implementations.
     * Each job must have a registered, unique string key so that class names can be "serialized" upon insertion into
     * the Action Scheduler database.
     */
    'jobs' => [
        BackfillProductCategoriesJob::JOB_KEY => [
            'job'      => BackfillProductCategoriesJob::class,
            'settings' => [
                'class'  => BatchJobSettings::class,
                'values' => [
                ],
            ],
        ],
        BackfillProductsJob::JOB_KEY => [
            'job'      => BackfillProductsJob::class,
            'settings' => [
                'class'  => BatchJobSettings::class,
                'values' => [
                    // this is lower, as we're backfilling products and inventory in one job (double the API requests)
                    'maxPerBatch' => 30,
                ],
            ],
        ],
        BatchCreateOrUpdateProductsJob::JOB_KEY => [
            'job'      => BatchCreateOrUpdateProductsJob::class,
            'settings' => [
                'class'  => BatchJobSettings::class,
                'values' => [
                    'maxPerBatch' => defined('MWC_MAX_PRODUCTS_PER_WRITE_BATCH') ? MWC_MAX_PRODUCTS_PER_WRITE_BATCH : 10,
                ],
            ],
        ],
        PatchProductCategoryAssociationsJob::JOB_KEY => [
            'job'      => PatchProductCategoryAssociationsJob::class,
            'settings' => [
                'class'  => BatchJobSettings::class,
                'values' => [
                    'maxPerBatch' => 50,
                ],
            ],
        ],
    ],
];
