<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\CaptureTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Events\RefundTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Events\VoidTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueCapturesTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueRefundsTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueVoidsTrait;
use Stripe\Exception\ApiErrorException;

class TransactionsGateway extends StripeGateway
{
    use CanIssueCapturesTrait;
    use CanIssueVoidsTrait;
    use CanIssueRefundsTrait;

    /**
     * Issues capture transaction request.
     *
     * @param CaptureTransaction $transaction
     *
     * @return CaptureTransaction
     * @throws ApiErrorException
     */
    public function capture(CaptureTransaction $transaction) : CaptureTransaction
    {
        if (! $remoteId = $transaction->getRemoteParentId()) {
            return $transaction;
        }

        $this->maybeLogApiRequest(__METHOD__, ['id' => $remoteId], $transaction);
        $response = $this->getClient()->paymentIntents->capture($remoteId);
        $this->maybeLogApiResponse(__METHOD__, $response);

        $transaction->setStatus(new ApprovedTransactionStatus());

        Events::broadcast(new CaptureTransactionEvent($transaction));

        return $transaction;
    }

    /**
     * Issues void transaction request.
     *
     * @param VoidTransaction $transaction
     *
     * @return VoidTransaction
     * @throws ApiErrorException
     */
    public function void(VoidTransaction $transaction) : VoidTransaction
    {
        if (! $remoteId = $transaction->getRemoteParentId()) {
            return $transaction;
        }

        $this->maybeLogApiRequest(__METHOD__, ['id' => $remoteId], $transaction);
        $response = $this->getClient()->paymentIntents->cancel($remoteId);
        $this->maybeLogApiResponse(__METHOD__, $response);

        $transaction->setStatus(new ApprovedTransactionStatus());

        Events::broadcast(new VoidTransactionEvent($transaction));

        return $transaction;
    }

    /**
     * Creates refund request.
     *
     * @param RefundTransaction $transaction
     *
     * @return RefundTransaction
     * @throws Exception
     */
    public function refund(RefundTransaction $transaction) : RefundTransaction
    {
        $args = [
            'payment_intent' => $transaction->getRemoteParentId(),
        ];

        if ($amount = $transaction->getTotalAmount()) {
            $args['amount'] = $amount->getAmount();
        }

        $this->maybeLogApiRequest(__METHOD__, $args, $transaction);
        $response = $this->getClient()->refunds->create($args);
        $this->maybeLogApiResponse(__METHOD__, $response);

        $transaction->setStatus(new ApprovedTransactionStatus());

        Events::broadcast(new RefundTransactionEvent($transaction));

        return $transaction;
    }
}
