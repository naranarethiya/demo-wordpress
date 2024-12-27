<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\API\Response as ControllerResponse;
use GoDaddy\WordPress\MWC\Common\DataSources\Request\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\AddressHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\AdaptsShipmentDataTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use Throwable;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Orders controller.
 */
class OrdersController extends AbstractController
{
    use AdaptsShipmentDataTrait;
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'orders';

    /**
     * Registers the API routes for the orders endpoint.
     *
     * @internal
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'args' => [
                'include' => [
                    'required'          => false,
                    'type'              => 'string',
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
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?P<orderId>[0-9]+)", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'updateOrder'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
            'args' => [
                'orderId' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @internal
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'orders',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('The order ID.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'fulfilled' => [
                        'description' => __('Whether or not the order has been fulfilled.', 'mwc-dashboard'),
                        'type'        => 'bool',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'shipments' => [
                        'description' => __('The shipments for the order.', 'mwc-dashboard'),
                        'type'        => 'array',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'id' => [
                                    'description' => __('The shipment ID.', 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'orderId' => [
                                    'description' => __('The order ID.', 'mwc-dashboard'),
                                    'type'        => 'integer',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'createdAt' => [
                                    'description' => __("The shipment's creation date.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'updatedAt' => [
                                    'description' => __("The shipment's last updated date.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'shippingProvider' => [
                                    'description' => __('The shipping provider for the shipment.', 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'trackingNumber' => [
                                    'description' => __("The shipment's tracking number.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'trackingUrl' => [
                                    'description' => __("The shipment's tracking URL.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'items' => [
                                    'description' => __('The items included in the shipment.', 'mwc-dashboard'),
                                    'type'        => 'array',
                                    'items'       => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id' => [
                                                'description' => __("The item's ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'productId' => [
                                                'description' => __("The product's ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'variationId' => [
                                                'description' => __("The product's variation ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'quantity' => [
                                                'description' => __("The item's quantity.", 'mwc-dashboard'),
                                                'type'        => 'number',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                        ],
                                    ],
                                    'context'  => ['view', 'edit'],
                                    'readonly' => true,
                                ],
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'emailAddress' => [
                        'description' => __('The order email address.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'number' => [
                        'description' => __('The order number, distinct from the order ID.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'paid' => [
                        'description' => __('Whether the order is considered "paid."', 'mwc-dashboard'),
                        'type'        => 'bool',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'paymentProviderName' => [
                        'description' => __('The payment provider name (in Woo terms, gateway ID).', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'status' => [
                        'description' => __('The overall order status.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'totalAmount' => [
                        'description' => __('The order total amount', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'  => [
                            'amount' => [
                                'description' => __('The full order amount, in the smallest unit of the given currency code.', 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'currencyCode' => [
                                'description' => __('The currency code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'billingAddress' => [
                        'description' => __('The order billing address.', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'  => [
                            'administrativeDistricts' => [
                                'description' => __('An array of administrative districts.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'businessName' => [
                                'description' => __('The billing address business name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'countryCode' => [
                                'description' => __('The billing address country code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'firstName' => [
                                'description' => __('The billing address customers first name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lastName' => [
                                'description' => __('The billing address customers last name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lines' => [
                                'description' => __('The billing address lines.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'locality' => [
                                'description' => __('The billing address locality.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'phoneNumber' => [
                                'description' => __('The billing address phone number.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'postalCode' => [
                                'description' => __('The billing address postal code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'subLocalities' => [
                                'description' => __('The billing address sub localities.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'billingAddressFormatted' => [
                        'description' => __('String representation for the billing address', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'shippingAddress' => [
                        'description' => __('The order shipping address.', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'  => [
                            'administrativeDistricts' => [
                                'description' => __('An array of administrative districts.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'businessName' => [
                                'description' => __('The shipping address business name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'countryCode' => [
                                'description' => __('The shipping address country code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'firstName' => [
                                'description' => __('The shipping address customers first name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lastName' => [
                                'description' => __('The shipping address customers last name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lines' => [
                                'description' => __('The shipping address lines.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'locality' => [
                                'description' => __('The shipping address locality.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'phoneNumber' => [
                                'description' => __('The shipping address phone number.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'postalCode' => [
                                'description' => __('The shipping address postal code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'subLocalities' => [
                                'description' => __('The shipping address sub localities.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'shippingAddressFormatted' => [
                        'description' => __('String representation for the shipping address', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'marketplaces' => [
                        'description' => __('Marketplaces information for the order.', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'  => [
                            'orderNumber' => [
                                'description' => __('The corresponding Marketplaces order number.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'channelUuid' => [
                                'description' => __('The Marketplaces channel unique identifier.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'channelName' => [
                                'description' => __('The Marketplaces channel name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'channelType' => [
                                'description' => __('The Marketplaces channel display type name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Sends a REST response with orders.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        $orderIds = [];
        $resources = [];

        if (! empty($queryParam = $request->get_param('query'))) {
            $query = json_decode($queryParam, true);
            $orderIds = ArrayHelper::wrap(ArrayHelper::get($query, 'ids'));
            $resources = ArrayHelper::wrap(ArrayHelper::get($query, 'includes'));
        }

        if (empty($orderIds)) {
            $orderIds = wc_get_orders([
                'limit'   => 20,
                'orderby' => 'date',
                'order'   => 'DESC',
                'return'  => 'ids',
            ]);
        }

        $responseData = [];
        $dataStore = new OrderFulfillmentDataStore();

        foreach ($orderIds as $orderId) {
            $fulfillment = $dataStore->read($orderId);

            if ($fulfillment) {
                $responseData[] = $this->prepareItem($fulfillment, $resources);
            }
        }

        (new Response)
            ->setBody(['orders' => $responseData])
            ->success(200)
            ->send();
    }

    /**
     * Sends a REST response with order based on id.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $order = $this->getOrderFulfillmentFromRequest($request);

            (new Response)
                ->setBody($this->prepareOrderResponseData($order))
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
     * Prepares the given order fulfillment object to included in the response.
     *
     * @param OrderFulfillment $orderFulfillment
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function prepareOrderResponseData(OrderFulfillment $orderFulfillment) : array
    {
        return [
            'order' => $this->prepareItem($orderFulfillment),
        ];
    }

    /**
     * Prepares the given order object for API response.
     *
     * @param OrderFulfillment $fulfillment
     * @param array $resources
     * @return array
     * @throws Exception
     */
    protected function prepareItem(OrderFulfillment $fulfillment, array $resources = []) : array
    {
        $order = $fulfillment->getOrder();
        $orderId = $order->getId();

        $status = $order->getStatus();
        $totalAmount = $order->getTotalAmount();

        $itemData = [
            'id'          => $orderId,
            'fulfilled'   => $order->getFulfillmentStatus() instanceof FulfilledFulfillmentStatus,
            'number'      => $order->getNumber(),
            'status'      => $status ? strtoupper($status->getName()) : '',
            'totalAmount' => $totalAmount ? [
                'amount'       => $totalAmount->getAmount(),
                'currencyCode' => $totalAmount->getCurrencyCode(),
            ] : '',
            'billingAddress'           => $this->prepareItemAddress($order->getBillingAddress()),
            'billingAddressFormatted'  => AddressHelper::format($order->getBillingAddress()),
            'shippingAddress'          => $this->prepareItemAddress($order->getShippingAddress()),
            'shippingAddressFormatted' => AddressHelper::format($order->getShippingAddress()),
        ];

        if ($wooOrder = OrdersRepository::get($orderId)) {
            $itemData = ArrayHelper::combine($itemData, $this->getWooOrderData($wooOrder));
            $itemData = ArrayHelper::combine($itemData, $this->getMarketplacesOrderData($wooOrder));
        }

        if (ArrayHelper::contains($resources, 'shipments')) {
            $itemData['shipments'] = $this->prepareShipmentItems($fulfillment);
        }

        return $itemData;
    }

    /**
     * Prepares an address data array.
     *
     * @param Address $address
     * @return array<string, mixed>|null
     */
    protected function prepareItemAddress(Address $address) : ?array
    {
        $data = AddressAdapter::getNewInstance([])->convertToSource($address);

        if (null === $data) {
            return null;
        }

        $phoneNumber = ArrayHelper::get($data, 'phoneNumber');

        if (is_string($phoneNumber)) {
            ArrayHelper::set($data, 'phoneNumber', preg_replace('/[^+0-9]/', '', $phoneNumber));
        }

        return $data;
    }

    /**
     * Returns data associated with WooCommerce order objects.
     *
     * @param WC_Order $order
     * @return array
     */
    protected function getWooOrderData(WC_Order $order) : array
    {
        return [
            'emailAddress'        => $this->getEmail($order),
            'paid'                => $this->isPaid($order),
            'paymentProviderName' => $this->getProviderName($order),
        ];
    }

    /**
     * Returns data associated with Marketplaces orders.
     *
     * @param WC_Order $wcOrder
     * @return array<string, mixed>
     */
    protected function getMarketplacesOrderData(WC_Order $wcOrder) : array
    {
        $marketplaceData = [
            // @TODO: update this to use the getters from the MWC Core Order model when the MWC Dashboard is merged into MWC Core - MWC-1989 {dmagalhaes 2022-05-26}
            'orderNumber' => $wcOrder->get_meta('marketplaces_order_number') ?: null,
            'channelUuid' => $wcOrder->get_meta('marketplaces_channel_uuid') ?: null,
            'channelName' => $wcOrder->get_meta('marketplaces_channel_name') ?: null,
            'channelType' => $wcOrder->get_meta('marketplaces_channel_type') ?: null,
        ];

        return [
            'marketplaces' => count(array_filter($marketplaceData)) > 0 ? $marketplaceData : null,
        ];
    }

    /**
     * Returns the email address associated with the order.
     *
     * @param WC_Order $order
     * @return string|null
     */
    protected function getEmail(WC_Order $order)
    {
        return $order->get_billing_email();
    }

    /**
     * Returns whether the order is paid for based on the order status.
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function isPaid(WC_Order $order) : bool
    {
        return $order->is_paid();
    }

    /**
     * Returns whether the order is paid for based on the order status.
     *
     * @param WC_Order $order
     * @return string
     */
    protected function getProviderName(WC_Order $order) : string
    {
        return $order->get_payment_method();
    }

    /**
     * Prepares the shipment items in the given fulfillment object for API response.
     *
     * @param OrderFulfillment $fulfillment
     * @return array
     *
     * @throws Exception
     */
    protected function prepareShipmentItems(OrderFulfillment $fulfillment) : array
    {
        $shipmentData = [];

        foreach ($fulfillment->getShipments() as $shipment) {
            $shipmentData[] = $this->getShipmentData($shipment);
        }

        return $shipmentData;
    }

    /**
     * Gets an OrderFulfillment object with the order ID included in the request.
     *
     * @param WP_REST_Request $request
     * @return OrderFulfillment
     * @throws OrderNotFoundException
     * @throws Exception
     */
    protected function getOrderFulfillmentFromRequest(WP_REST_Request $request) : OrderFulfillment
    {
        $orderId = (int) $request->get_param('orderId');

        return $this->getOrderFulfillment($orderId);
    }

    /**
     * Gets an OrderFulfillment object with the given order id.
     *
     * @param int $orderId
     * @return OrderFulfillment
     *
     * @throws OrderNotFoundException
     * @throws Exception
     */
    protected function getOrderFulfillment(int $orderId) : OrderFulfillment
    {
        $fulfillment = ($this->getOrderFulfillmentDataStore())->read($orderId);

        if (empty($fulfillment)) {
            throw new OrderNotFoundException("Order not found with ID {$orderId}");
        }

        return $fulfillment;
    }

    /**
     * Returns an instance of OrderFulfillmentDataStore.
     *
     * @return OrderFulfillmentDataStore
     */
    protected function getOrderFulfillmentDataStore() : OrderFulfillmentDataStore
    {
        return new OrderFulfillmentDataStore();
    }

    /**
     * Updates the order identified with the ID included in the request.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function updateOrder(WP_REST_Request $request) : WP_REST_Response
    {
        $payload = $request->get_json_params();

        try {
            $orderFulfillment = $this->getOrderFulfillmentFromRequest($request);

            $this->processOrderUpdate($payload, $orderFulfillment);

            return $this->getOrderResponse($orderFulfillment);
        } catch (OrderNotFoundException $exception) {
            return $this->getOrderNotFoundErrorResponse($exception);
        } catch (Exception $exception) {
            return $this->getGenericErrorResponse($exception);
        }
    }

    /**
     * Updates the given order fulfillment object using the given payload.
     *
     * @param array<string, mixed> $payload
     * @param OrderFulfillment $orderFulfillment
     */
    protected function processOrderUpdate(array $payload, OrderFulfillment $orderFulfillment) : void
    {
        if ($this->shouldMarkOrderAsCompleted($payload)) {
            $this->markOrderAsCompleted($orderFulfillment);
        }
    }

    /**
     * Determines whether the request payload includes data to mark the order as completed.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    protected function shouldMarkOrderAsCompleted(array $payload) : bool
    {
        return 'completed' === strtolower(TypeHelper::string(ArrayHelper::get($payload, 'status'), ''));
    }

    /**
     * Marks the order associated with the given order fulfillment object as completed.
     *
     * @param OrderFulfillment $orderFulfillment
     * @return void
     */
    protected function markOrderAsCompleted(OrderFulfillment $orderFulfillment) : void
    {
        $order = OrdersRepository::get((int) $orderFulfillment->getOrder()->getId());

        if (! $order) {
            return;
        }

        $order->set_status('wc-completed');
        $order->save();
    }

    /**
     * @throws Exception
     */
    protected function getOrderResponse(OrderFulfillment $orderFulfillment) : WP_REST_Response
    {
        $data = $this->prepareOrderResponseData($orderFulfillment);

        return $this->getWordPressResponse(ControllerResponse::getNewInstance()->setBody($data));
    }

    /**
     * Gets a {@see WP_REST_Response} object that represents the error described by the given exception.
     *
     * @param OrderNotFoundException $exception
     * @return WP_REST_Response
     */
    protected function getOrderNotFoundErrorResponse(OrderNotFoundException $exception) : WP_REST_Response
    {
        return $this->getWordPressResponse(
            ControllerResponse::getNewInstance()
                ->setStatus($exception->getCode())
                ->addError($exception->getMessage(), 'NOT_FOUND')
        );
    }

    /**
     * Gets a {@see WP_REST_Response} object that represents the error described by the given exception.
     *
     * @param Throwable $throwable
     * @return WP_REST_Response
     */
    protected function getGenericErrorResponse(Throwable $throwable) : WP_REST_Response
    {
        return $this->getWordPressResponse(
            ControllerResponse::getNewInstance()
                ->setStatus(500)
                ->addError($throwable->getMessage(), 'UNKNOWN_ERROR')
        );
    }
}
