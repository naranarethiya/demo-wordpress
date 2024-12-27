<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts;

/**
 * Contract for object representations of a token.
 */
interface TokenContract
{
    /**
     * Get the token's claims as associative array.
     *
     * @return array<string, mixed>
     */
    public function getClaims() : array;

    /**
     * Set the token's claims as an associative array.
     *
     * @param array<string, mixed> $value
     * @return TokenContract
     */
    public function setClaims(array $value) : TokenContract;

    /**
     * Get the token issuer.
     *
     * @return string
     */
    public function getIssuer() : string;

    /**
     * Get the token's issued-at unix timestamp.
     *
     * @return int
     */
    public function getIssuedAt() : int;

    /**
     * Get the token's expiration unix timestamp.
     *
     * @return int
     */
    public function getExpiration() : int;
}
