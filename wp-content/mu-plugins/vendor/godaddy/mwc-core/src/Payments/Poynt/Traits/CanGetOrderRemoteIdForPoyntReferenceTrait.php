<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Traits;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Enums\TransactionReferenceType;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * @phpstan-type TOrderReference array{type: TransactionReferenceType::*, customType?: TransactionReferenceType::*, id: non-empty-string}
 */
trait CanGetOrderRemoteIdForPoyntReferenceTrait
{
    /**
     * Gets the remote ID for the given order to be used as the POYNT_ORDER reference in Poynt transactions.
     *
     * This method returns the Commerce ID if available and falls back to the Poynt order ID.
     *
     * @return non-empty-string|null
     */
    protected function getOrderRemoteIdForPoyntReference(Order $order) : ?string
    {
        return $this->getCommerceRemoteId($order) ?: ($order->getRemoteId() ?: null);
    }

    /**
     * Gets the ID of the given order in the Commerce platform.
     */
    protected function getCommerceRemoteId(Order $order) : ?string
    {
        if (! Commerce::shouldLoad()) {
            return null;
        }

        return $this->getCommerceRemoteIdUsingMappingService($order);
    }

    /**
     * Uses an instance of {@see OrdersMappingServiceContract} to get the remote ID of the given order.
     */
    protected function getCommerceRemoteIdUsingMappingService(Order $order) : ?string
    {
        try {
            /** @var OrdersMappingServiceContract $ordersMappingService */
            $ordersMappingService = ContainerFactory::getInstance()->getSharedContainer()->get(OrdersMappingServiceContract::class);
        } catch (ContainerException $exception) {
            return null;
        }

        return $ordersMappingService->getRemoteId($order);
    }

    /**
     * Gets the order reference for the given order to be used in Poynt transactions.
     *
     * This method returns a central order reference if the Commerce ID is available and falls back to
     * a Poynt order reference with the Poynt order ID.
     *
     * @return TOrderReference|null
     */
    protected function getOrderReferenceForPoynt(Order $order) : ?array
    {
        if ($remoteId = $this->getCommerceRemoteId($order)) {
            return $this->buildCentralOrderReference($remoteId);
        }

        if ($remoteId = $order->getRemoteId()) {
            return $this->buildPoyntOrderReference($remoteId);
        }

        return null;
    }

    /**
     * Builds a central order reference entry for a Poynt transaction with the given ID.
     *
     * @param non-empty-string $id
     * @return TOrderReference
     */
    protected function buildCentralOrderReference(string $id) : array
    {
        return [
            'type'       => TransactionReferenceType::Custom,
            'customType' => TransactionReferenceType::CentralOrder,
            'id'         => $id,
        ];
    }

    /**
     * Builds a Poynt order reference entry for a Poynt transaction with the given ID.
     *
     * @param non-empty-string $id
     * @return TOrderReference
     */
    protected function buildPoyntOrderReference(string $id) : array
    {
        return [
            'type' => TransactionReferenceType::PoyntOrder,
            'id'   => $id,
        ];
    }
}
