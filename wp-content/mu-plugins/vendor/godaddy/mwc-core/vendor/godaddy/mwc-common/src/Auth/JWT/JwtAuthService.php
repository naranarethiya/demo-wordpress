<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT;

use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\JwtDecoderContract;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\KeySetProviderContract;
use GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts\TokenContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\JwtDecoderException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ValidationException;
use GoDaddy\WordPress\MWC\Common\Validation\Contracts\ValidatorContract;

/**
 * JWT Auth Service class.
 */
class JwtAuthService
{
    /** @var KeySetProviderContract */
    protected $keySetProvider;

    /** @var ValidatorContract */
    protected $validator;

    /** @var TokenContract */
    protected $token;

    /** @var JwtDecoderContract */
    protected $decoder;

    /**
     * Constructor.
     *
     * @param KeySetProviderContract $keySetProvider
     * @param ValidatorContract $validator
     * @param TokenContract $token
     * @param JwtDecoderContract $decoder
     */
    public function __construct(KeySetProviderContract $keySetProvider, ValidatorContract $validator, TokenContract $token, JwtDecoderContract $decoder)
    {
        $this->keySetProvider = $keySetProvider;
        $this->validator = $validator;
        $this->token = $token;
        $this->decoder = $decoder;
    }

    /**
     * Decodes the token.
     *
     * @param string $jwt
     *
     * @return TokenContract
     * @throws ValidationException|JwtDecoderException
     */
    public function decodeToken(string $jwt) : TokenContract
    {
        $decoded = $this->decoder
            ->setKeySet($this->keySetProvider->getKeySet())
            ->decode($jwt);

        $this->token->setClaims((array) $decoded);

        $this->validator->validate($this->token);

        return $this->token;
    }
}
