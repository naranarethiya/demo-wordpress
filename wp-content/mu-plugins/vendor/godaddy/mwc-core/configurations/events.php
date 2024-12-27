<?php

use GoDaddy\WordPress\MWC\Core\Email\Events\Subscribers\EmailNotificationsSettingsUpdatedSubscriber;
use GoDaddy\WordPress\MWC\Core\Events\Subscribers\EventBridgeSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\RegisterWebhooksSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\WebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ApplePayEnabledSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\CaptureTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\CompleteSetUpPaymentsTaskSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\PaymentTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RefundTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RequestDebugNoticeSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RequestLogSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ResponseDebugNoticeSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ResponseLogSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\VoidTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\ShipmentEventsSubscriber;

return [
    /*
     *--------------------------------------------------------------------------
     * General Settings
     *--------------------------------------------------------------------------
     *
     * The following are general settings needed for the operation and use of the overall
     * event system
     */
    'auth' => [
        'type'  => 'Bearer',
        'token' => defined('MWC_EVENTS_AUTH_TOKEN') ? MWC_EVENTS_AUTH_TOKEN : '',
    ],

    'send_local_events' => defined('MWC_SEND_LOCAL_EVENTS') ? MWC_SEND_LOCAL_EVENTS : false,

    /*
     *--------------------------------------------------------------------------
     * Event Transformers
     *--------------------------------------------------------------------------
     *
     * The following array contains events and a list of their transformers. In order
     * to have a cached transformer for a given event at optimal performance, the
     * transformer should be listed under the events key below.
     *
     * Event with Namespace => transformer class
     */
    'transformers' => [
        GoDaddy\WordPress\MWC\Common\Events\ModelEvent::class => [
            GoDaddy\WordPress\MWC\Core\Events\Transformers\EventContextTransformer::class,
            GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\Transformers\OrderEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Payments\Events\Transformers\OrderEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\Transformers\ProductEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers\ProductEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers\AddProductSummaryProductEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Transformers\CheckoutEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Transformers\OrderEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Channels\Events\Transformers\OrderEventTransformer::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Transformers\MarketplacesProductTransformer::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\SettingGroupEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\Transformers\EmailNotificationsSettingsUpdatedEventTransformer::class,
        ],
        GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract::class => [
            GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Events\Transformers\CareAgentUserFlagTransformer::class,
            GoDaddy\WordPress\MWC\Core\Events\Transformers\SitePropertiesTransformer::class,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Event Listeners / Subscribers
     *--------------------------------------------------------------------------
     *
     * The following array contains events and a list of their subscribers.  In order
     * to have a cached subscriber for a given event at optimal performance, the
     * subscriber should be listed under the events key below.
     *
     * Event with Namespace => subscriber class
     *
     * All subscribers will receive the full event object by default.  Determination
     * of if the event is queued before triggering the listener should/is done
     * via declaration on the Event itself.
     *
     */
    'listeners' => [
        GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent::class => [
            CompleteSetUpPaymentsTaskSubscriber::class,
            ApplePayEnabledSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\CaptureTransactionEvent::class => [
            CaptureTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\PaymentTransactionEvent::class => [
            PaymentTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\BeforeCreateRefundEvent::class => [
            GoDaddyPaymentsGateway::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\BeforeCreateVoidEvent::class => [
            GoDaddyPaymentsGateway::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\RefundTransactionEvent::class => [
            RefundTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\VoidTransactionEvent::class => [
            VoidTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent::class => [
            RequestDebugNoticeSubscriber::class,
            RequestLogSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent::class => [
            ResponseDebugNoticeSubscriber::class,
            ResponseLogSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent::class => [
            ShipmentEventsSubscriber::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent::class => [
            ShipmentEventsSubscriber::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Shipping\Events\ShipmentDeletedEvent::class => [
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\AccountUpdatedEvent::class => [
            RegisterWebhooksSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent::class => [
            WebhookSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\SettingGroupEvent::class => [
            EmailNotificationsSettingsUpdatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Common\Events\ModelEvent::class => [
            GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\PoyntOrderPushSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\OrderUpdatedSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\ProductUpdatedSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Subscribers\CheckoutSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderChannelSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers\CustomerSubscriber::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Subscribers\DeleteWPNUXProductMetaDataSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\EmailNotificationSentEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Subscribers\EmailNotificationSentSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ChannelConnectedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\MerchantAccountLevelDataUpdatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ProductBulkSyncEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ProductBulkSyncSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingCreatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ListingCreatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ListingDeletedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ListingDeletedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\WebhookReceivedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ChannelWebhookSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\GoogleVerificationWebhookSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\ListingWebhookSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\MerchantProvisionedViaChatterboxWebhookSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderWebhookSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\GoogleAdsTrackingWebhookSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantAccountLevelDataUpdatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\MerchantAccountLevelDataUpdatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Shipping\Events\ShipmentQuoteEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Shipping\Events\Subscribers\StorePhoneNumberSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantProvisionedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\MerchantProvisionedSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\MerchantAccountLevelDataUpdatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\HostingPlans\Events\HostingPlanChangeEvent::class => [
            GoDaddy\WordPress\MWC\Core\HostingPlans\Subscribers\HostingPlanChangedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushRequestedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers\CustomerPushRequestedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushSuccessfulEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers\CustomerPushSuccessfulSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\CustomerPushFailedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Subscribers\CustomerPushFailedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\LineItemReservedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers\LineItemReservedNotificationsSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers\ProductsListedPrimeCacheSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\MaybeDetectProductChangesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\UpdateLevelFailedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Subscribers\UpdateLevelFailedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductCreatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\UpdateProductLastKnownUpdatedAtTimeSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\VariableProductCreatedSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductUpdatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\UpdateProductLastKnownUpdatedAtTimeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductReadEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\MaybeDetectProductChangesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Events\CareUserLogInEvent::class => [
            GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Events\Subscribers\ForceCareAgentAdminRoleSubscriber::class,
            GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Events\Subscribers\ScheduleCareAgentDeleteSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobDoneEvent::class => [
            GoDaddy\WordPress\MWC\Core\JobQueue\Events\Subscribers\QueueNextJobSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\JobQueue\Events\QueuedJobCreatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\JobQueue\Events\Subscribers\QueueNextJobSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\InvalidTransactionAvsEvent::class => [
            GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\TransactionAvsOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\RemoteProductUpdatedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\DispatchJobToSaveLocalProductSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsInsertedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers\ProductsInsertedEventSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\AttachmentsInsertedEvent::class => [
            GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\Subscribers\DownloadRemoteAssetDataSubscriber::class,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Event Producers
     *--------------------------------------------------------------------------
     *
     * The following array contains event producers that will be instantiated when
     * the package loads and are expected to broadcast events when the appropriate
     * action occurs.
     *
     * Please use the fully qualified namespace of the producer to avoid creating a long
     * list of use statements at the top of this file and allow to easily identify
     * the location of a given producer class within the application structure or its
     * dependencies.
     *
     * Use
     *
     * GoDaddy\WordPress\MWC\Core\Events\Producers\ProductEventsProducer::class
     *
     * instead of
     *
     * ProductEventsProducer::class
     */
    'producers' => [
        GoDaddy\WordPress\MWC\Core\Events\Producers\PluginLifecycleEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\OrderTrackingEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\PageEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\ShippingZoneMethodEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\SiteHeartbeatEventProducer::class,
        GoDaddy\WordPress\MWC\Core\Sync\Events\Producers\PullEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\WebhookEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\PushOrdersProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\PushTransactionsProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\RegisterWebhooksProducer::class,
        WebhookSubscriber::class,
    ],
];
