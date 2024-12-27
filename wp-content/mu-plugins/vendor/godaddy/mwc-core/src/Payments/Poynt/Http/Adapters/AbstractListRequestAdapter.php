<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidSourceException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\Traits\CanListResourcesTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\AbstractResourceRequest;

/**
 * The base request for all list request adapters.
 */
abstract class AbstractListRequestAdapter implements DataSourceAdapterContract
{
    /** @var CanListResourcesTrait|null object using the CanListResourcesTrait trait */
    protected $source;

    /** @var string array key containing data in the Poynt API response */
    protected $responseBodyKey;

    /**
     * Returns the request used to get a remote resource.
     *
     * @return AbstractResourceRequest
     */
    abstract protected function getRequest() : AbstractResourceRequest;

    /**
     * Returns the adapter for converting the core model object to and from Poynt API data.
     *
     * @return DataSourceAdapterContract
     */
    abstract protected function getAdapter() : DataSourceAdapterContract;

    /**
     * Ensures the supplied source is valid for this class.
     *
     * @return void
     * @throws InvalidSourceException
     */
    private function validateSource()
    {
        if (! $this->source || ! in_array(CanListResourcesTrait::class, class_uses($this->source), true)) {
            throw new InvalidSourceException('The source must use the CanListResourcesTrait trait');
        }
    }

    /**
     * Converts the source to a Poynt API request.
     *
     * @return AbstractResourceRequest
     * @throws Exception
     */
    public function convertFromSource() : AbstractResourceRequest
    {
        $this->validateSource();

        $request = $this->getRequest();

        if (! empty($this->source->modifiedSince)) {
            $request->addHeaders([
                'If-Modified-Since' => $this->source->modifiedSince,
            ]);
        }

        $query = [];

        if (! empty($this->source->offset)) {
            $query['startOffset'] = $this->source->offset;
        }

        if (! empty($this->source->limit)) {
            $query['limit'] = $this->source->limit;
        }

        return $request->setQuery($query);
    }

    /**
     * Converts the Poynt API response to an array of model objects.
     *
     * @param Response|null $response
     * @return AbstractModel[]
     */
    public function convertToSource(Response $response = null) : array
    {
        if ($response && $body = $response->getBody()) {
            return array_map(function ($modelData) {
                return $this->getAdapter()->convertToSource($modelData);
            }, $body[$this->responseBodyKey] ?? []);
        }

        return [];
    }
}
