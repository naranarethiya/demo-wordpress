<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;

/**
 * Trait for manipulating order meta data.
 */
trait CanManipulateOrderMetaDataTrait
{
    /**
     * Deletes meta data from an order item for the given meta key.
     *
     * @param int $id
     * @param string $metaKey
     * @return void
     */
    protected function deleteOrderItemMetaData(int $id, string $metaKey) : void
    {
        try {
            wc_delete_order_item_meta($id, $metaKey);
        } catch (Exception $exception) {
            SentryException::getNewInstance('There was an error trying to delete order item meta.', $exception);
        }
    }

    /**
     * Deletes the given meta keys from the order.
     *
     * @param int $id
     * @param string[] $metaKeys
     * @return void
     */
    protected function deleteOrderMetaData(int $id, array $metaKeys) : void
    {
        if (empty($metaKeys)) {
            return;
        }

        if ($order = OrdersRepository::get($id)) {
            foreach ($metaKeys as $metaKey) {
                $order->delete_meta_data($metaKey);
            }

            // TODO: replace this call to WC_Order::save() with a call to WC_Order::save_meta_data() in https://godaddy-corp.atlassian.net/browse/MWC-13394
            $order->save();
        }
    }
}
