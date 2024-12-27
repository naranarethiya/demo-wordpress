<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\UpsertLevelInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Traits\CanConvertLevelResponseTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;

abstract class AbstractUpsertLevelRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;
    use CanConvertLevelResponseTrait;

    protected UpsertLevelInput $input;

    /**
     * UpsertLevelRequestAdapter constructor.
     *
     * @param UpsertLevelInput $input
     */
    public function __construct(UpsertLevelInput $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    protected function convertResponse(ResponseContract $response) : Level
    {
        $data = ArrayHelper::wrap(ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'inventoryLevel', []));

        return $this->convertLevelResponse($data);
    }

    /**
     * Gets the base upsert request class.
     *
     * @return Request
     */
    protected function getBaseRequest() : Request
    {
        return Request::withAuth()->setStoreId($this->input->storeId);
    }

    /**
     * @param ResponseContract $response
     *
     * @throws NotUniqueException|CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->getStatus() === 409 || ArrayHelper::get($response->getBody(), 'code') === 'NOT_UNIQUE_ERROR') {
            throw NotUniqueException::getNewInstance($response->getErrorMessage() ?? 'Record is not unique');
        }

        parent::throwIfIsErrorResponse($response);
    }
}
