<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\WebhookValidationStrategy;

/**
 * A helper for validating value types.
 */
class ValidationHelper
{
    /**
     * Determines whether a value is an email.
     *
     * @see is_email() as an alternative WordPress function
     *
     * @param mixed $value
     * @return bool
     */
    public static function isEmail($value) : bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Determines whether a value is a URL.
     *
     * This function does not evaluate the validity of a URL protocol.
     *
     * @param mixed $value)
     * @param string[] $protocols optional protocols to validate the URL (default none)
     * @return bool
     */
    public static function isUrl($value, array $protocols = []) : bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return empty($protocols) || array_filter($protocols, static function ($protocol) use ($value) {
            return StringHelper::startsWith(parse_url($value, PHP_URL_SCHEME), $protocol);
        });
    }

    /**
     * Check for invalid email addresses in given list of email recipients.
     *
     * @param string|string[] $emailAddresses Array or comma-separated list of email addresses.
     * @return string[] The invalid email addresses.
     */
    public static function findInvalidEmailRecipients($emailAddresses) : array
    {
        if (empty($emailAddresses)) {
            return [];
        }

        if (is_string($emailAddresses)) {
            $emailAddresses = explode(',', $emailAddresses);
        }

        return array_filter(
            array_map('trim', $emailAddresses),
            function ($emailAddress) {
                return ! ValidationHelper::isEmailRecipient($emailAddress);
            }
        );
    }

    /**
     * Checks if an email recipient is valid.
     *
     * A valid email recipient can contain a name and email address, or just an email address.
     *
     * @param string $email
     * @return bool
     */
    public static function isEmailRecipient(string $email) : bool
    {
        $email = preg_match('/<([^>]+)>/', $email, $matches) ? $matches[1] : $email;

        return ValidationHelper::isEmail($email);
    }

    /**
     * Determines if a webhooks payload is valid, using the Standard Webhooks standard.
     *
     * @param string $secret
     * @param string $payload
     * @param mixed[] $headers
     * @return bool
     */
    public static function isValidWebhook(string $secret, string $payload, array $headers) : bool
    {
        try {
            return WebhookValidationStrategy::getNewInstance()->setSecret($secret)->isValid($payload, $headers);
        } catch (Exception $e) {
            return false;
        }
    }
}
