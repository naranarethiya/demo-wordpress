<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Methods;

use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;

/**
 * Token authentication method.
 */
class TokenAuthMethod implements AuthMethodContract
{
    /** @var string token type */
    protected $type;

    /** @var string token value */
    protected $token;

    /**
     * Gets token's type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Sets token type.
     *
     * @param string $value
     *
     * @return TokenAuthMethod
     */
    public function setType(string $value) : TokenAuthMethod
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Gets token value.
     *
     * @return string
     */
    public function getToken() : string
    {
        return $this->token;
    }

    /**
     * Sets token value.
     *
     * @param string $value
     *
     * @return TokenAuthMethod
     */
    public function setToken(string $value) : TokenAuthMethod
    {
        $this->token = $value;

        return $this;
    }

    /**
     * Prepares the given request using this auth method.
     *
     * @param RequestContract $request
     * @return RequestContract
     */
    public function prepare(RequestContract $request) : RequestContract
    {
        return $request->addHeaders(['Authorization' => $this->getType().' '.$this->getToken()]);
    }
}
