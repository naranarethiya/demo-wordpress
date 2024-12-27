<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\Contracts;

use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\Exceptions\WebhookValidationException;

interface WebhookValidationStrategyContract
{
    /**
     * Determines whether the webhook is valid.
     *
     * @param string $payload
     * @param ?mixed[] $headers
     * @return bool
     * @throws WebhookValidationException
     */
    public function isValid(string $payload, ?array $headers = null) : bool;
}
