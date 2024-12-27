<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers\Traits;

use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token;

trait CanBuildTokenCredentialsTrait
{
    /**
     * Builds token credentials based on the given data.
     *
     * @param array $data
     * @return AuthCredentialsContract
     */
    protected function buildCredentials(array $data) : AuthCredentialsContract
    {
        return Token::seed($data);
    }
}
