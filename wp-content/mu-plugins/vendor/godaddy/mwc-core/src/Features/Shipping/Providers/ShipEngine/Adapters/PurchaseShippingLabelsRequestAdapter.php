<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Exceptions\AccountHasInsufficientBalanceException;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Exceptions\ShippingLabelsPurchaseNotAllowedException;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Response;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PurchaseShippingLabelsOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\CompletedLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\ProcessingLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\VoidedLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\RemoteLabelDocument;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;

class PurchaseShippingLabelsRequestAdapter extends AbstractGatewayRequestAdapter
{
    /** @var PurchaseShippingLabelsOperationContract */
    protected $operation;

    public function __construct(PurchaseShippingLabelsOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        $package = array_values($this->operation->getShipment()->getPackages())[0];
        $shippingRate = $package->getShippingRate();
        $rateId = $shippingRate ? $shippingRate->getRemoteId() : '';

        return Request::withAuth()
            ->setPath('/shipping/proxy/shipengine/v1/labels/rates/'.$rateId)
            ->setMethod('post')
            ->setBody([
                'externalAccountId' => $this->operation->getAccount()->getId(),
                'data'              => [
                    'validate_address'    => 'no_validation',
                    'label_layout'        => $this->operation->getLayout(),
                    'label_format'        => $this->operation->getFormat(),
                    'label_download_type' => 'url',
                    'display_scheme'      => 'label',
                ],
            ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response)
    {
        $responseBody = ArrayHelper::wrap($response->getBody());

        if (! ArrayHelper::get($responseBody, 'label_id')) {
            throw new ShippingException('Response does not include a label_id');
        }

        $package = array_values($this->operation->getShipment()->getPackages())[0];

        $documents = [];

        if (ArrayHelper::get($responseBody, 'label_download.pdf')) {
            $documents[] = (new RemoteLabelDocument())
                ->setFormat('pdf')
                ->setUrl(ArrayHelper::get($responseBody, 'label_download.pdf'));
        }

        if (ArrayHelper::get($responseBody, 'label_download.png')) {
            $documents[] = (new RemoteLabelDocument())
                ->setFormat('png')
                ->setUrl(ArrayHelper::get($responseBody, 'label_download.png'));
        }

        if (ArrayHelper::get($responseBody, 'label_download.href')) {
            $documents[] = (new RemoteLabelDocument())
                ->setFormat('href')
                ->setUrl(ArrayHelper::get($responseBody, 'label_download.href'));
        }

        $package->setShippingLabel(
            (new ShippingLabel())
                ->setId(ArrayHelper::get($responseBody, 'label_id'))
                ->setDocuments(...$documents)
                ->setStatus($this->getLabelStatus($responseBody))
                ->setIsTrackable(ArrayHelper::get($responseBody, 'trackable') ?: false)
                ->setRemoteId(ArrayHelper::get($responseBody, 'label_id'))
        );

        if (ArrayHelper::get($responseBody, 'tracking_number')) {
            $package->setTrackingNumber($responseBody['tracking_number']);
        }

        $package->setStatus(new LabelCreatedPackageStatus);

        return $this->operation;
    }

    /**
     * Helper method to get status from responseBody.
     *
     * @param array<string, string> $responseBody
     *
     * @return LabelStatusContract
     */
    protected function getLabelStatus(array $responseBody) : LabelStatusContract
    {
        switch (ArrayHelper::get($responseBody, 'status', '')) {
            case 'processing':
                $result = new ProcessingLabelStatus;
                break;
            case 'voided':
                $result = new VoidedLabelStatus;
                break;
            default:
                $result = new CompletedLabelStatus;
        }

        return $result;
    }

    /**
     * May throws an exception if given response contains any errors.
     *
     * @param ResponseContract|Response $response
     * @throws ShippingException
     * @throws ShippingLabelsPurchaseNotAllowedException
     * @throws AccountHasInsufficientBalanceException
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        /* @phpstan-ignore-next-line */
        foreach ($response->getErrors() as $errorResponse) {
            $this->throwExceptionForError($errorResponse);
        }

        // If we could not detect a specific error type, fallback to the generic error.
        parent::throwIfIsErrorResponse($response);
    }

    /**
     * Throws specialized exceptions for error types.
     *
     * @param array<string, mixed> $errorResponse
     *
     * @throws AccountHasInsufficientBalanceException
     * @throws ShippingLabelsPurchaseNotAllowedException
     */
    protected function throwExceptionForError(array $errorResponse) : void
    {
        $message = TypeHelper::string(ArrayHelper::get($errorResponse, 'message'), '');

        if (StringHelper::contains(strtolower($message), 'you cannot print or purchase funds')) {
            throw new ShippingLabelsPurchaseNotAllowedException($message);
        }

        if (StringHelper::contains(strtolower($message), 'insufficient account balance')) {
            throw new AccountHasInsufficientBalanceException($message);
        }
    }
}
