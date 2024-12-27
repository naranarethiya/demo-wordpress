<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\Contracts\WebhookValidationStrategyContract;
use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\DataObjects\WebhookHeaders;
use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\Exceptions\WebhookValidationException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Implementation of the "standard webhook" validation algorithm.
 * {@link https://github.com/standard-webhooks/standard-webhooks/}.
 */
class WebhookValidationStrategy implements WebhookValidationStrategyContract
{
    use CanGetNewInstanceTrait;

    /** @var int acceptable timestamp age in seconds (webhooks older than this period will be considered invalid) */
    protected const TTL = 60;

    /** @var string expected signature version */
    protected const VERSION = 'v1';

    /** @var string the secret used to validate the signature */
    protected string $secret;

    /**
     * Validate the webhook.
     *
     * The payload is validated against the ID, signature, and timestamp headers as per the Standard Webhooks spec.
     *
     * This method will throw an exception if the validation fails.
     *
     * @param string $payload
     * @param ?mixed[] $headers
     * @return bool
     * @throws WebhookValidationException
     * @throws Exception
     */
    public function isValid(string $payload, ?array $headers = null) : bool
    {
        $headers = $this->validateHeaders(TypeHelper::array($headers, []));

        $expectedSignature = $this->getExpectedSignature($payload, $headers);

        if (! $this->headersContainValidSignature($headers, $expectedSignature)) {
            throw new WebhookValidationException('No matching signature found');
        }

        return true;
    }

    /**
     * Validates the webhook headers.
     *
     * @param mixed[] $headers
     * @return WebhookHeaders
     * @throws WebhookValidationException
     */
    protected function validateHeaders(array $headers) : WebhookHeaders
    {
        if (empty($headers)) {
            throw new WebhookValidationException('No headers found');
        }

        $headers = $this->normalizeHeaders($headers);

        if (! $this->hasRequiredHeaders($headers)) {
            throw new WebhookValidationException('Missing required headers');
        }

        $timestamp = TypeHelper::string($headers['HTTP_WEBHOOK_TIMESTAMP'], '');

        if (! $this->isValidTimestamp($timestamp)) {
            throw new WebhookValidationException('Invalid timestamp');
        }

        return WebhookHeaders::getNewInstance([
            'messageId'  => TypeHelper::string($headers['HTTP_WEBHOOK_ID'], ''),
            'signatures' => TypeHelper::arrayOfStrings(explode(' ', TypeHelper::string($headers['HTTP_WEBHOOK_SIGNATURE'], ''))),
            'timestamp'  => $timestamp,
        ]);
    }

    /**
     * Get the expected signature.
     *
     * @param string $payload
     * @param WebhookHeaders $headers
     * @return string
     */
    public function getExpectedSignature(string $payload, WebhookHeaders $headers) : string
    {
        return $this->sign($this->getSecret(), $payload, $headers);
    }

    /**
     * Normalizes the headers by converting all the keys to uppercase.
     *
     * For our purposes, the case of the header doesn't matter; we don't care of it's `HTTP_WEBHOOK_ID` or
     * `http_webhook_id`. So we convert them all to uppercase to more easily validate and reference them.
     *
     * @param mixed[] $headers
     * @return mixed[]
     */
    protected function normalizeHeaders(array $headers) : array
    {
        return array_change_key_case($headers, CASE_UPPER);
    }

    /**
     * Does the request have all required headers.
     *
     * @param mixed[] $headers
     * @return bool
     */
    protected function hasRequiredHeaders(array $headers) : bool
    {
        return isset($headers['HTTP_WEBHOOK_ID'], $headers['HTTP_WEBHOOK_TIMESTAMP'], $headers['HTTP_WEBHOOK_SIGNATURE']);
    }

    /**
     * Is the signature valid.
     *
     * Compares the signature passed via headers with the expected signature.
     *
     * @param string $suppliedSignature
     * @param string $expectedSignature
     * @return bool
     */
    protected function isValidSignature(string $suppliedSignature, string $expectedSignature) : bool
    {
        $expectedParts = explode(',', $expectedSignature, 2);
        $suppliedParts = explode(',', $suppliedSignature, 2);

        if (! $this->isValidVersion($suppliedParts[0])) {
            return false;
        }

        // we don't need to check the version number here, as that's already been validated separately
        if (hash_equals(($expectedParts[1] ?? ''), ($suppliedParts[1] ?? ''))) {
            return true;
        }

        return false;
    }

    /**
     * The signature version is correct.
     *
     * @param string $version
     * @return bool
     */
    protected function isValidVersion(string $version) : bool
    {
        if (strcmp(strtolower($version), self::VERSION) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Generate the expected signature.
     *
     * @param string $secret
     * @param string $payload
     * @param WebhookHeaders $headers
     * @return string
     */
    protected function sign(string $secret, string $payload, WebhookHeaders $headers) : string
    {
        $toSign = "{$headers->messageId}.{$headers->timestamp}.{$payload}";
        $hexHash = hash_hmac('sha256', $toSign, $secret);

        $encodedSignature = base64_encode(pack('H*', $hexHash));

        return self::VERSION.",{$encodedSignature}";
    }

    /**
     * Verify that the timestamp is within the acceptable range.
     *
     * @param string $timestamp
     * @return bool
     */
    protected function isValidTimestamp(string $timestamp) : bool
    {
        try {
            $timestamp = TypeHelper::int((new DateTimeImmutable($timestamp))->format('U'), 0);
            $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('U');
            $diff = $now - $timestamp;

            // Timestamp is invalid if it's in the future or too far in the past.
            if ($diff > self::TTL || $diff < 0) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets the webhook secret.
     *
     * @return string
     */
    public function getSecret() : string
    {
        return $this->secret;
    }

    /**
     * Set the secret.
     *
     * @param string $secret
     * @return $this
     */
    public function setSecret(string $secret) : WebhookValidationStrategy
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Does the headers contain a valid signature.
     *
     * @param WebhookHeaders $headers
     * @param string $expectedSignature
     * @return bool
     */
    public function headersContainValidSignature(WebhookHeaders $headers, string $expectedSignature) : bool
    {
        return array_reduce($headers->signatures, function ($carry, $signature) use ($expectedSignature) {
            return $carry || $this->isValidSignature($signature, $expectedSignature);
        }, false);
    }
}
