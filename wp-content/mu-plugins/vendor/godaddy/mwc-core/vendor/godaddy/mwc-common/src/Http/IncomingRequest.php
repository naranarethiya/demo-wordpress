<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\IncomingRequestContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Incoming HTTP request.
 */
class IncomingRequest implements IncomingRequestContract
{
    use CanGetNewInstanceTrait;

    /** @var string the HTTP request method */
    protected string $method;

    /** @var array<string, string> the request headers */
    protected array $headers = [];

    /** @var string|null body of the request */
    protected ?string $body = null;

    /** {@inheritDoc} */
    public function getMethod() : string
    {
        return $this->method;
    }

    /** {@inheritDoc} */
    public function setMethod(string $value) : IncomingRequestContract
    {
        $this->method = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /** {@inheritDoc} */
    public function setHeaders(array $value) : IncomingRequestContract
    {
        $this->headers = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getBody() : ?string
    {
        return $this->body;
    }

    /** {@inheritDoc} */
    public function setBody(?string $value) : IncomingRequestContract
    {
        $this->body = $value;

        return $this;
    }
}
