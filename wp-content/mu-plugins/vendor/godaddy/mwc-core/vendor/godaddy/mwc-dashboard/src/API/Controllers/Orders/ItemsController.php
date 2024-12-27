<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Product\ProductAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\Orders\AbstractOrderItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\AdaptsShipmentDataTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use WC_Order_Item_Product;
use WC_Product;
use WP_REST_Request;

class ItemsController extends AbstractController
{
    use AdaptsShipmentDataTrait;
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'orders/(?P<orderId>[0-9]+)/items';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since x.y.z
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'args' => [
                    'orderId' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                    'query' => [
                        'required'          => false,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @since x.y.z
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'items',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('The item ID.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'productId' => [
                        'description' => __('The product ID.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'variationId' => [
                        'description' => __('The variation ID.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'name' => [
                        'description' => __('The name of the product.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'quantity' => [
                        'description' => __('The item quantity.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'unfulfilled' => [
                        'description' => __('The number of unfulfilled items.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'productImage' => [
                        'description' => __('The product image.', 'mwc-dashboard'),
                        'type'        => ['object', 'null'],
                        'properties'  => [
                            'height' => [
                                'description' => __('Image height.', 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'width' => [
                                'description' => __('Image width.', 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'src' => [
                                'description' => __('The image source url', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'srcSet' => [
                                'description' => __('The image source url list', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'sizes' => [
                                'description' => __('Image sizes', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];
    }

    /**
     * Gets a REST response with order items.
     *
     * Defaults to line items, but accepts a query param to support other item types.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            // there is no need to check if the orderId is an integer,
            // the pattern on the register_rest_route() call already guarantees that
            $orderId = (int) SanitizationHelper::input($request->get_param('orderId'));
            $wcOrder = OrdersRepository::get((int) $orderId);

            if (! $wcOrder) {
                throw new OrderNotFoundException("Order not found with ID {$orderId}");
            }

            $order = (new OrderAdapter($wcOrder))->convertFromSource();

            if (! empty($queryParam = $request->get_param('query'))) {
                $query = json_decode($queryParam, true);
                $type = TypeHelper::string(ArrayHelper::get($query, 'type.eq'), '');
            }

            if (empty($type) || ! ArrayHelper::contains(['line', 'shipping', 'fee', 'tax'], $type)) {
                $type = 'line';
            }

            $method = 'get'.ucfirst($type).'Items';
            $itemsData = $this->{$method}($order);

            $responseData = [
                'type'  => $type,
                'items' => $itemsData,
            ];

            (new Response)
                ->setBody($responseData)
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response)
            ->error([$exception->getMessage()], $exception->getCode())
            ->send();
        } catch (Exception $exception) {
            (new Response)
            ->error([$exception->getMessage()], 400)
            ->send();
        }
    }

    /**
     * Gets the data for the order's line items.
     *
     * @since x.y.z
     *
     * @param Order $order
     * @return array
     * @throws OrderNotFoundException
     */
    protected function getLineItems(Order $order) : array
    {
        $fulfillment = (new OrderFulfillmentDataStore())->read($order->getId());
        $itemsData = [];

        foreach ($fulfillment->getLineItemsThatNeedShipping() as $item) {
            $itemsData[] = $this->prepareLineItem($fulfillment, $item);
        }

        return $itemsData;
    }

    /**
     * Builds the array of data for the given line item.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @param LineItem $item
     * @return array
     */
    protected function prepareLineItem(OrderFulfillment $fulfillment, LineItem $item) : array
    {
        $product = $this->getSourceProduct($item);

        return [
            'id'           => $item->getId(),
            'name'         => $item->getLabel(),
            'quantity'     => $item->getQuantity(),
            'unfulfilled'  => $item->getQuantity() - $fulfillment->getFulfilledQuantityForLineItem($item),
            'weight'       => $product ? $this->getProductWeightData($product) : null,
            'productImage' => $product ? $this->getProductImageData($product) : null,
        ];
    }

    /**
     * Gets the WooCommerce product associated with the given line item.
     *
     * @param LineItem $item
     * @return WC_Product|null
     */
    protected function getSourceProduct(LineItem $item) : ?WC_Product
    {
        $wcOrderItem = new WC_Order_Item_Product($item->getId());
        $product = $wcOrderItem->get_product();

        return $product instanceof WC_Product ? $product : null;
    }

    /**
     * Gets the weight data from the given product.
     *
     * @param WC_Product $sourceProduct
     * @return array{value: float, unit: string}|null
     */
    protected function getProductWeightData(WC_Product $sourceProduct) : ?array
    {
        $product = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();

        if (! $weight = $product->getWeight()) {
            return null;
        }

        return [
            'value' => $weight->getValue(),
            'unit'  => $weight->getUnitOfMeasurement(),
        ];
    }

    /**
     * Gets the image data for the given product.
     *
     * @param WC_Product $sourceProduct
     * @return array{height: int, width: int, src: string, srcSet: string, sizes: string}|null
     */
    protected function getProductImageData(WC_Product $sourceProduct) : ?array
    {
        if (! $imageId = (int) $sourceProduct->get_image_id()) {
            return null;
        }

        if (! $imageData = ArrayHelper::wrap(wp_get_attachment_image_src($imageId, 'thumbnail'))) {
            return null;
        }

        $imageSrcSet = wp_get_attachment_image_srcset($imageId, 'thumbnail');
        $imageSizesAttr = wp_get_attachment_image_sizes($imageId, 'thumbnail');

        return [
            'height' => ArrayHelper::get($imageData, '2'),
            'width'  => ArrayHelper::get($imageData, '1'),
            'src'    => ArrayHelper::get($imageData, '0'),
            'srcSet' => ! empty($imageSrcSet) ? $imageSrcSet : '',
            'sizes'  => ! empty($imageSizesAttr) ? $imageSizesAttr : '',
        ];
    }

    /**
     * Gets the data for the order's shipping items.
     *
     * @since x.y.z
     *
     * @param Order $order
     * @return array
     */
    protected function getShippingItems(Order $order) : array
    {
        $itemsData = [];
        foreach ($order->getShippingItems() as $item) {
            $itemsData[] = $this->prepareAbstractItem($item);
        }

        return $itemsData;
    }

    /**
     * Builds the array of data for the given item.
     *
     * @since x.y.z
     *
     * @param AbstractOrderItem $item
     * @return array
     */
    protected function prepareAbstractItem(AbstractOrderItem $item) : array
    {
        return [
            'id'   => $item->getId(),
            'name' => $item->getName(),
        ];
    }

    /**
     * Gets the data for the order's fee items.
     *
     * @since x.y.z
     *
     * @param Order $order
     * @return array
     */
    protected function getFeeItems(Order $order) : array
    {
        $itemsData = [];
        foreach ($order->getFeeItems() as $item) {
            $itemsData[] = $this->prepareAbstractItem($item);
        }

        return $itemsData;
    }

    /**
     * Gets the data for the order's tax items.
     *
     * @since x.y.z
     *
     * @param Order $order
     * @return array
     */
    protected function getTaxItems(Order $order) : array
    {
        $itemsData = [];
        foreach ($order->getTaxItems() as $item) {
            $itemsData[] = $this->prepareAbstractItem($item);
        }

        return $itemsData;
    }
}
