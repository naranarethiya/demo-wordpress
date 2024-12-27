<?php

namespace GoDaddy\WordPress\MWC\Common\EmailsService\Http\GraphQL;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Request as GraphQLRequest;

class Request extends GraphQLRequest
{
    public function __construct(GraphQLOperationContract $operation)
    {
        parent::__construct($operation);

        $this->setUrl($this->getApiUrl());
    }

    /**
     * Retrieves service API URL from configuration.
     *
     * @return string
     */
    protected function getApiUrl() : string
    {
        return Configuration::get('mwc.emails_service.api.url', '');
    }
}
