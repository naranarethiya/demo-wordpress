<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Jobs\PushSyncJob;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\FailedTransactionException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidTransactionException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\PutTransactionRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Traits\CanGetOrderRemoteIdForPoyntReferenceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use WC_Order;

class PushTransactionsProducer implements ProducerContract
{
    use CanGetOrderRemoteIdForPoyntReferenceTrait;

    const SALE_ACTION = 'SALE';
    const AUTHORIZATION_ACTION = 'AUTHORIZE';
    const CAPTURE_ACTION = 'CAPTURE';

    /**
     * Sets up the producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('mwc_push_poynt_order_transaction_objects')
            ->setHandler([$this, 'handlePushTransactionsJob'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Handles the job to push transactions to the Poynt API.
     *
     * @param int $jobId
     * @param array $orderIds
     *
     * @return void
     */
    public function handlePushTransactionsJob(int $jobId, array $orderIds)
    {
        if (! $job = PushSyncJob::get($jobId)) {
            return;
        }

        if (
            ! WooCommerceRepository::isWooCommerceActive()
            || empty($orderIds)
            || 'order' !== $job->getObjectType()
        ) {
            $job->delete();

            return;
        }

        try {
            if (! $wcOrder = OrdersRepository::get(ArrayHelper::get($orderIds, 0))) {
                $job->delete();

                return;
            }

            $order = OrderAdapter::getNewInstance($wcOrder)->convertFromSource();

            // the order was not sent to Poynt yet, reschedule the action to try again later
            if ($this->shouldRescheduleJob($order)) {
                $this->rescheduleJob($order);

                $job->delete();

                return;
            }

            $this->maybePushTransactionToPoynt($order, $wcOrder);
        } catch (Exception $exception) {
            $job->update([
                'errors' => ArrayHelper::wrap($exception->getMessage()),
                'status' => 'failed',
            ]);

            return;
        }

        $job->delete();
    }

    /**
     * Checks if the order was already sent to Poynt.
     *
     * @param Order $order
     * @return bool
     */
    protected function shouldRescheduleJob(Order $order) : bool
    {
        return ! $this->getOrderRemoteIdForPoyntReference($order);
    }

    /**
     * Reschedule the action to try again later.
     *
     * @param Order $order
     * @throws Exception
     */
    protected function rescheduleJob(Order $order)
    {
        PushSyncJob::create([
            'owner'      => 'poynt_order_transaction',
            'batchSize'  => 1,
            'objectType' => 'order',
            'objectIds'  => ArrayHelper::wrap($order->getId()),
        ]);
    }

    /**
     * May push a transaction to Poynt.
     *
     * @param Order $order
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function maybePushTransactionToPoynt(Order $order, WC_Order $wcOrder)
    {
        if (Poynt\Events\Subscribers\OrderUpdatedSubscriber::shouldPushSaleTransactionToPoynt($wcOrder, $order)) {
            $this->pushPaymentTransactionToPoynt(static::SALE_ACTION, $order, $wcOrder);
        } elseif (Poynt\Events\Subscribers\OrderUpdatedSubscriber::shouldPushAuthorizationTransactionToPoynt($wcOrder, $order)) {
            $this->pushPaymentTransactionToPoynt(static::AUTHORIZATION_ACTION, $order, $wcOrder);
        } elseif (Poynt\Events\Subscribers\OrderUpdatedSubscriber::shouldPushCaptureTransactionToPoynt($wcOrder, $order)) {
            $this->pushCaptureTransactionToPoynt($order, $wcOrder);
        }
    }

    /**
     * Push payment transaction to the Poynt API when payment is made via a 3rd party gateway.
     *
     * @param string $action
     * @param Order $order
     * @param WC_Order $wcOrder
     * @return Response|void
     * @throws FailedTransactionException|Exception
     */
    protected function pushPaymentTransactionToPoynt(string $action, Order $order, WC_Order $wcOrder)
    {
        $remoteTransactionId = StringHelper::generateUuid4();

        $response = (new PutTransactionRequest($remoteTransactionId))
            ->setBody($this->buildTransactionRequestBody($action, $order, $wcOrder))
            ->send();

        if ($response->isError() || $response->getStatus() !== 201) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new FailedTransactionException("Could not send {$action} transaction to Poynt for order {$this->getOrderRemoteIdForPoyntReference($order)}: ({$response->getStatus()}) {$errorMessage}");
        }

        $wcOrder->update_meta_data('_poynt_payment_remoteId', $remoteTransactionId);
        $wcOrder->save_meta_data();

        return $response;
    }

    /**
     * Push capture transaction to the Poynt API when an order is captured via a 3rd party gateway.
     *
     * @param Order $order
     * @param WC_Order $wcOrder
     * @return Response|void
     * @throws InvalidTransactionException|Exception
     */
    protected function pushCaptureTransactionToPoynt(Order $order, WC_Order $wcOrder)
    {
        $remoteTransactionId = StringHelper::generateUuid4();

        $response = (new PutTransactionRequest($remoteTransactionId))
            ->setBody($this->buildTransactionRequestBody(static::CAPTURE_ACTION, $order, $wcOrder, $remoteTransactionId))
            ->send();

        if ($response->isError() || $response->getStatus() !== 201) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new InvalidTransactionException("Could not send CAPTURE transaction to Poynt for order {$this->getOrderRemoteIdForPoyntReference($order)}: ({$response->getStatus()}) {$errorMessage}");
        }

        $wcOrder->update_meta_data('_poynt_capture_remoteId', $remoteTransactionId);
        $wcOrder->save_meta_data();

        return $response;
    }

    /**
     * Builds the request body based on the action and order data.
     *
     * @param string $action the request action
     * @param Order $order order to build the transaction request body for
     * @param WC_Order $wcOrder WC order to build the transaction request body for
     * @param string|null $transactionId transaction ID generated for this new transaction
     *
     * @return array the transaction request body
     * @throws Exception
     */
    protected function buildTransactionRequestBody(string $action, Order $order, WC_Order $wcOrder, string $transactionId = null)
    {
        $orderTotal = $order->getTotalAmount();

        $body = [
            'action'  => $action,
            'amounts' => [
                'currency'          => $orderTotal->getCurrencyCode(),
                'transactionAmount' => $orderTotal->getAmount(),
                'orderAmount'       => $orderTotal->getAmount(),
            ],
            'fundingSource' => [
                'type'         => 'CUSTOM_FUNDING_SOURCE',
                'entryDetails' => [
                    'customerPresenceStatus' => 'ECOMMERCE',
                    'entryMode'              => 'KEYED',
                ],
                'customFundingSource' => [
                    'type'      => 'OTHER',
                    'provider'  => $wcOrder->get_payment_method(),
                    'accountId' => 'none',
                    'processor' => 'co.poynt.services',
                ],
            ],
            'processorResponse' => [
                'status'        => 'Successful',
                'statusCode'    => 1,
                'transactionId' => static::CAPTURE_ACTION !== $action ? $wcOrder->get_transaction_id() : $transactionId,
            ],
            'references' => [
                $this->getOrderReferenceForPoynt($order),
            ],
            'context' => [
                'storeId' => Configuration::get('payments.poynt.storeId', ''),
            ],
        ];

        switch ($action) {
            case static::AUTHORIZATION_ACTION:
                {
                    $body['processorResponse']['providerVerification'] = [
                        'signature' => 'none',
                    ];
                    /* translators: Placeholder: %s - payment method name */
                    $body['notes'] = sprintf(__('Paid in WooCommerce checkout by "%s"', 'mwc-core'), $wcOrder->get_payment_method_title());
                    break;
                }
            case static::SALE_ACTION:
                {
                    /* translators: Placeholder: %s - payment method name */
                    $body['notes'] = sprintf(__('Paid in WooCommerce checkout by "%s"', 'mwc-core'), $wcOrder->get_payment_method_title());
                    break;
                }
            case static::CAPTURE_ACTION:
                {
                    $body['parentId'] = $wcOrder->get_meta('_poynt_payment_remoteId');
                }
        }

        return $body;
    }
}
