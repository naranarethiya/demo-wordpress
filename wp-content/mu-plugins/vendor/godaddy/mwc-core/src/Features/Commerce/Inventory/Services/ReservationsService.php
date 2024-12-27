<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ReadReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Reservation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertReservationInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataSources\Adapters\ReservationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateReservationOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadReservationOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateReservationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadReservationResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\CreateOrUpdateReservationResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadReservationResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ReservationsService implements ReservationsServiceContract
{
    protected CommerceContextContract $commerceContext;
    protected InventoryProviderContract $provider;
    protected ReservationMappingServiceContract $reservationMappingService;
    protected ProductsMappingServiceContract $productMappingService;

    /**
     * The Reservations Service constructor.
     */
    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        ReservationMappingServiceContract $reservationMappingService,
        ProductsMappingServiceContract $productMappingService
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->reservationMappingService = $reservationMappingService;
        $this->productMappingService = $productMappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateReservation(CreateOrUpdateReservationOperationContract $operation) : CreateOrUpdateReservationResponseContract
    {
        $reservation = $this->instantiateReservation($operation);
        $response = new CreateOrUpdateReservationResponse([]);

        if ($reservation) {
            $reservations = $this->provider->reservations()->createOrUpdate(new UpsertReservationInput([
                'storeId'     => $this->commerceContext->getStoreId(),
                'reservation' => $reservation,
            ]));

            $response->setReservations($reservations);
        }

        return $response;
    }

    /**
     * Instantiates a reservation object that will be different if a remote ID can be found or not.
     *
     * @param CreateOrUpdateReservationOperationContract $operation
     *
     * @return ?Reservation
     * @throws MissingProductRemoteIdException
     * @throws Exception
     */
    protected function instantiateReservation(CreateOrUpdateReservationOperationContract $operation) : ?Reservation
    {
        $reservation = ReservationAdapter::getNewInstance()->convertToSource($operation->getLineItem());

        if (! $reservation) {
            return null;
        }

        $remoteReservationId = $this->reservationMappingService->getRemoteId($operation->getLineItem());

        if ($remoteReservationId) {
            $reservation->inventoryReservationId = $remoteReservationId;
        } else {
            if (! $product = $this->getProductForReservation($operation->getLineItem())) {
                return null;
            }

            $remoteProductId = $this->getProductRemoteId($product);

            $reservation->productId = $remoteProductId;

            if ($cartId = $operation->getOrder()->getCartId()) {
                $reservation->externalIds[] = new ExternalId([
                    'type'  => 'CART',
                    'value' => $cartId,
                ]);
            }
        }

        return $reservation;
    }

    /**
     * Get remote ID for the given product, or throw if there is no remote ID for it.
     *
     * @throws MissingProductRemoteIdException
     */
    protected function getProductRemoteId(Product $product) : string
    {
        if ($remoteId = $this->productMappingService->getRemoteId($product)) {
            return $remoteId;
        }

        throw new MissingProductRemoteIdException('Product remote ID was not found.');
    }

    /**
     * Gets the product for creating/updating a reservation.
     *
     * @param LineItem $lineItem
     *
     * @return Product|null
     * @throws Exception
     */
    protected function getProductForReservation(LineItem $lineItem) : ?Product
    {
        $wooProduct = $lineItem->getProduct();

        // if the stock is managed by another product, get that product instead
        if ($wooProduct && $wooProduct->get_id() !== $wooProduct->get_stock_managed_by_id()) {
            $wooProduct = ProductsRepository::get($wooProduct->get_stock_managed_by_id());
        }

        return $wooProduct ? ProductAdapter::getNewInstance($wooProduct)->convertFromSource() : null;
    }

    /**
     * {@inheritDoc}
     * @throws CommerceException
     */
    public function readReservation(ReadReservationOperationContract $operation) : ReadReservationResponseContract
    {
        $lineItem = $operation->getLineItem();

        if (! $existingRemoteId = $this->reservationMappingService->getRemoteId($lineItem)) {
            throw new CommerceException('Could not get the remote ID for given line item');
        }

        $reservation = $this->provider->reservations()->read(
            ReadReservationInput::getNewInstance([
                'storeId'                => $this->commerceContext->getStoreId(),
                'inventoryReservationId' => $existingRemoteId,
            ])
        );

        return new ReadReservationResponse([$reservation]);
    }
}
