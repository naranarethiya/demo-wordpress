<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\LineItemReservedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ProductInventoryCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\CreateOrUpdateReservationOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\WooOrderCartIdProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

class OrderReservationsService implements OrderReservationsServiceContract
{
    const TRANSIENT_RESERVATIONS_FAILED = 'godaddy_mwc_commerce_reservations_failed';

    protected ReservationsServiceContract $reservationsService;
    protected ProductInventoryCachingServiceContract $inventoryCachingService;
    protected WooOrderCartIdProvider $cartIdProvider;

    /**
     * @param ReservationsServiceContract $reservationsService
     * @param ProductInventoryCachingServiceContract $inventoryCachingService
     * @param WooOrderCartIdProvider $cartIdProvider
     */
    public function __construct(
        ReservationsServiceContract $reservationsService,
        ProductInventoryCachingServiceContract $inventoryCachingService,
        WooOrderCartIdProvider $cartIdProvider
    ) {
        $this->reservationsService = $reservationsService;
        $this->inventoryCachingService = $inventoryCachingService;
        $this->cartIdProvider = $cartIdProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateReservations(Order &$order) : void
    {
        $reservations = $notes = [];

        foreach ($order->getLineItems() as $lineItem) {
            try {
                if (! $product = $lineItem->getProduct()) {
                    continue;
                }

                if (! $product->managing_stock()) {
                    continue;
                }

                $lineReservations = $this->reservationsService->createOrUpdateReservation(new CreateOrUpdateReservationOperation($lineItem, $order))->getReservations();

                Events::broadcast(new LineItemReservedEvent($lineItem, $lineReservations, $order));

                array_push($reservations, ...$lineReservations);
            } catch (MissingProductRemoteIdException $exception) {
                // No-op. This is expected for a site that has not pushed all its products up to commerce platform.
                // The order should be allowed to proceed as if there was inventory available. See MWC-12739.
            } catch (Exception $exception) {
                $this->markOrderReservationsFailed($order);

                $notes[] = Note::seed([
                    'authorName' => Note::SYSTEM_AUTHOR_NAME,
                    'content'    => sprintf(
                        /* translators: Placeholders: %1$s - a product name, %2$s - a product SKU, %3$s - an API error message */
                        __('An error occurred while reserving stock for %1$s (%2$s). %3$s', 'mwc-core'),
                        $lineItem->getName(),
                        $lineItem->getSku(),
                        $exception->getMessage()
                    ),
                ]);
            }
        }

        // refresh the inventory cache for all products that were reserved
        $this->refreshCache($reservations);

        // add any failure notes to the order
        $order->addNotes(...$notes);
    }

    /**
     * Refreshes the product inventory caches for the given reservations.
     *
     * @param Reservation[] $reservations
     */
    protected function refreshCache(array $reservations) : void
    {
        if ($productIds = array_unique(TypeHelper::arrayOfStrings(array_map(static fn (Reservation $reservation) => $reservation->productId, $reservations)))) {
            $this->inventoryCachingService->refreshCache($productIds);
        }
    }

    /**
     * Marks an order as having failed reservations.
     *
     * @param Order $order
     */
    public function markOrderReservationsFailed(Order $order) : void
    {
        set_transient(static::TRANSIENT_RESERVATIONS_FAILED.$order->getCartId(), 'yes', 10);
    }

    /**
     * Determines if an order has failed reservations.
     *
     * @param WC_Order $order
     *
     * @return bool
     */
    public function orderHasFailedReservations(WC_Order $order) : bool
    {
        return wc_string_to_bool(TypeHelper::string(get_transient(static::TRANSIENT_RESERVATIONS_FAILED.$this->cartIdProvider->getCartId($order)), ''));
    }
}
