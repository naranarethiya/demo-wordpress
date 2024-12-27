<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT;

use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\TokenContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Object representation of a JWT.
 */
class Token implements Contracts\TokenContract
{
    /** @var array<string, mixed> token's payload */
    protected $claims;

    /**
     * {@inheritDoc}
     */
    public function getClaims() : array
    {
        return $this->claims;
    }

    /**
     * {@inheritDoc}
     */
    public function setClaims(array $value) : TokenContract
    {
        $this->claims = $value;

        return $this;
    }

    /**
     * Gets data from token payload.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|mixed[]
     */
    protected function getData(string $key, $default = null)
    {
        return ArrayHelper::get($this->getClaims(), $key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiration() : int
    {
        return (int) $this->getData('exp', 0);
    }

    /**
     * @{inheritDoc}
     */
    public function getIssuedAt() : int
    {
        return (int) $this->getData('iat', 0);
    }

    /**
     * {@inheritDoc}
     */
    public function getIssuer() : string
    {
        return $this->getData('iss', '');
    }
}
