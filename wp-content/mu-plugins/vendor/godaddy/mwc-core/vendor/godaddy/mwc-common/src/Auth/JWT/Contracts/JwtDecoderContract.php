<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\JwtDecoderException;
use stdClass;

/**
 * Contract for JWT decoders.
 */
interface JwtDecoderContract
{
    /**
     * Sets the JSON web key set (JWKS) that will be used to decode the JWT.
     *
     * @param array<mixed> $value
     * @return JwtDecoderContract
     */
    public function setKeySet(array $value) : JwtDecoderContract;

    /**
     * Sets the default algorithm that'll be used to decode the JWT if the key (JWK) doesn't specify an alg value.
     *
     * @param string $value
     * @return JwtDecoderContract
     */
    public function setDefaultAlgorithm(string $value) : JwtDecoderContract;

    /**
     * Decodes a JWT.
     *
     * @param string $token
     * @return stdClass
     * @throws JwtDecoderException
     */
    public function decode(string $token) : stdclass;
}
