<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Jobs\PatchProductCategoryAssociationsJob;

/**
 * Helper class to check the status of the {@see PatchProductCategoryAssociationsJob}.
 */
class PatchProductCategoryIdsJobStatusHelper
{
    /** @var string name for whether the job has completed */
    protected const JOB_HAS_RUN_OPTION_NAME = 'mwc_patch_product_category_ids_job_has_run';

    /**
     * Has the patch job ever run?
     *
     * @return bool
     */
    public static function hasRun() : bool
    {
        return ! empty(get_option(static::JOB_HAS_RUN_OPTION_NAME, false));
    }

    /**
     * Sets the patch job has run status.
     *
     * @param bool $value
     * @return void
     */
    public static function setHasRun(bool $value = true) : void
    {
        update_option(self::JOB_HAS_RUN_OPTION_NAME, $value);
    }
}
