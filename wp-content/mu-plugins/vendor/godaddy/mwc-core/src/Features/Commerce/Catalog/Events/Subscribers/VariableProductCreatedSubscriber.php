<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CreateOrUpdateRemoteVariantsInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Subscribe to variable product created events.
 */
class VariableProductCreatedSubscriber implements SubscriberContract
{
    /**
     * Handle the ProductCreatedEvent.
     *
     * @param ProductCreatedEvent $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        if (! $this->isValid($event)) {
            return;
        }

        CatalogIntegration::withoutReads(function () use ($event) {
            /** @var Product[] $variants */
            $variants = TypeHelper::array($event->product->getVariants(), []);

            if (count($variants) > 0) {
                $this->scheduleVariantsCreation($variants);
            }
        });
    }

    /**
     * Valid events are {@see ProductCreatedEvent} where the {@see Product} is variable.
     *
     * @param EventContract $event
     * @return bool
     */
    public function isValid(EventContract $event) : bool
    {
        return $event instanceof ProductCreatedEvent && $event->product->getType() === 'variable';
    }

    /**
     * Schedule jobs to create variants.
     *
     * @param Product[] $variants
     * @return void
     */
    public function scheduleVariantsCreation(array $variants) : void
    {
        $variantIds = array_map(function ($variant) {
            return $variant->getId();
        }, $variants);

        try {
            Schedule::singleAction()
                ->setName(CreateOrUpdateRemoteVariantsInterceptor::JOB_NAME)
                ->setScheduleAt(new DateTime())
                ->setArguments($variantIds)
                ->schedule();
        } catch (InvalidScheduleException $e) {
            SentryException::getNewInstance('Failed to schedule variant creation.', $e);
        }
    }
}
