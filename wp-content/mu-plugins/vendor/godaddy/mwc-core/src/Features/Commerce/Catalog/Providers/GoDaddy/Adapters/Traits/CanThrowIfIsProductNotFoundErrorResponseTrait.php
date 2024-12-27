<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Adapters\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;

trait CanThrowIfIsProductNotFoundErrorResponseTrait
{
    /**
     * Throws an exception on error responses.
     *
     * @param ResponseContract $response
     * @throws CommerceExceptionContract
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        $this->throwIfIsProductNotFoundErrorResponse($response);

        parent::throwIfIsErrorResponse($response);
    }

    /**
     * @throws ProductNotFoundException
     */
    protected function throwIfIsProductNotFoundErrorResponse(ResponseContract $response) : void
    {
        if ($this->isProductNotFoundError($response)) {
            throw new ProductNotFoundException($this->getErrorMessageFromResponse($response));
        }
    }

    /**
     * Determines whether the given response indicates that the requested product is not available.
     *
     * We expect the Catalog Service to respond with `{"code":"NOT_FOUND","message":"Not found"}`
     * when we try to access a product that was deleted or is no longer available.
     */
    protected function isProductNotFoundError(ResponseContract $response) : bool
    {
        if (! $response->isError() || $response->getStatus() !== 404) {
            return false;
        }

        $code = ArrayHelper::get($response->getBody(), 'code');
        $message = ArrayHelper::get($response->getBody(), 'message');

        return $code === 'NOT_FOUND' && $message === 'Not found';
    }
}
