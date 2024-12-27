<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Services;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google\Interceptors\GoogleProductIdInterceptor;

/**
 * Service class for Google-specific functionality.
 */
class GoogleProductService
{
    /**
     * Schedules a single action to fetch Google product IDs for the supplied Woo product IDs.
     *
     * @param int[] $productIds WooCommerce product IDs to fetch Google product IDs for.
     * @param int $attemptNumber Attempt number for the provided set of products.
     * @return void
     * @throws InvalidScheduleException|Exception
     */
    public static function scheduleProductIdRequest(array $productIds, int $attemptNumber = 1) : void
    {
        $delayInMinutes = ($attemptNumber - 1) * TypeHelper::int(Configuration::get('marketplaces.channels.google.productIdRequestRetryIntervalMinutes'), 5);

        Schedule::singleAction()
            ->setName(GoogleProductIdInterceptor::FETCH_GOOGLE_PRODUCT_IDS_ACTION)
            ->setArguments($productIds, $attemptNumber)
            ->setScheduleAt(new DateTime("+{$delayInMinutes} minutes"))
            ->schedule();
    }
}
