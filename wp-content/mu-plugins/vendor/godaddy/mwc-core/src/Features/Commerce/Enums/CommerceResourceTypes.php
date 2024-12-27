<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

/**
 * Enum-like class for defining Commerce resource types.
 */
class CommerceResourceTypes
{
    use EnumTrait;

    public const CatalogAsset = 'catalog_asset'; // storing UUID associations in the future
    public const CatalogAssetUrl = 'catalog_asset_url'; // storing hashed asset URLs
    public const Customer = 'customer';
    public const CustomerOrderNote = 'customer_order_note';
    public const GuestOrderCustomer = 'guest_order_customer';
    public const InventoryLevel = 'inventory_level';
    public const InventoryLocation = 'inventory_location';
    public const InventoryReservation = 'inventory_reservation';
    public const LineItem = 'line_item';
    public const Order = 'order';
    public const OrderNote = 'order_note';
    public const Product = 'product';
    public const ProductCategory = 'product_category';
}
