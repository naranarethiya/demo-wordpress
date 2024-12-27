<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers\Models;

use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * A common token representation.
 */
class Token extends AbstractModel implements AuthCredentialsContract
{
    /** @var string the access token */
    protected $accessToken = '';

    /** @var string the list of scopes for the token separated by space */
    protected $scope = '';

    /** @var string the ID for the token */
    protected $tokenId = '';

    /** @var string the type of token used */
    protected $tokenType = 'Bearer';

    /** @var int the expiration timestamp */
    protected $expiration = 0;

    /**
     * Retrieves the access token.
     *
     * @return string The token value.
     */
    public function getAccessToken() : string
    {
        return $this->accessToken;
    }

    /**
     * Retrieves the list of scopes, as a string.
     *
     * @return string List of scopes, separated by a space.
     */
    public function getScope() : string
    {
        return $this->scope;
    }

    /**
     * Retrieves the access token ID.
     *
     * @return string The ID.
     */
    public function getTokenId() : string
    {
        return $this->tokenId;
    }

    /**
     * Retrieves the access token type.
     *
     * @return string The type.
     */
    public function getTokenType() : string
    {
        return $this->tokenType;
    }

    /**
     * Retrieves the expiration timestamp.
     *
     * @return int The expiration.
     */
    public function getExpiration() : int
    {
        return $this->expiration;
    }

    /**
     * Sets the access token.
     *
     * @param string $value the access token
     * @return $this The token instance.
     */
    public function setAccessToken(string $value) : Token
    {
        $this->accessToken = $value;

        return $this;
    }

    /**
     * Sets the scope.
     *
     * @param string $value the scope for the token
     * @return $this The token instance.
     */
    public function setScope(string $value) : Token
    {
        $this->scope = $value;

        return $this;
    }

    /**
     * Sets the token ID.
     *
     * @param string $value the ID for the token
     * @return $this The token instance.
     */
    public function setTokenId(string $value) : Token
    {
        $this->tokenId = $value;

        return $this;
    }

    /**
     * Sets the token type.
     *
     * @param string $value the token type
     * @return $this The token instance.
     */
    public function setTokenType(string $value) : Token
    {
        $this->tokenType = $value;

        return $this;
    }

    /**
     * Sets the expiration timestamp.
     *
     * @param int $value the expiration timestamp
     * @return $this The token instance.
     */
    public function setExpiration(int $value) : Token
    {
        $this->expiration = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray() : array
    {
        $data = parent::toArray();
        $data['expiresIn'] = $this->getExpiresIn();

        return $data;
    }

    /**
     * Retrieves the number of seconds before this token expires, based on the expiration date.
     *
     * @return int Seconds before this token expires.
     */
    public function getExpiresIn() : int
    {
        return $this->getExpiration() - time();
    }
}
