<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;

trait CanHandleWordPressDatabaseExceptionTrait
{
    /**
     * @param WordPressDatabaseException $exception
     * @param string $featureName
     * @param string $transientDisableName
     */
    protected function handleWordPressDatabaseException(
        WordPressDatabaseException $exception,
        string $featureName,
        string $transientDisableName
    ) : void {
        new SentryException($exception->getMessage(), $exception);

        // disable the feature for the duration of this request
        Configuration::set('features.'.$featureName.'.enabled', false);

        // set a transient to temporarily disable the feature, so we don't try to run database actions on every request
        set_transient($transientDisableName, 1, 15 * MINUTE_IN_SECONDS);
    }
}
