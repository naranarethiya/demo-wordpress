<?php

namespace GoDaddy\WordPress\MWC\Common\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Emails class.
 */
class Emails
{
    use HasComponentsTrait;

    /**
     * Sends the email based on the EmailService stored in configuration.
     *
     * @param EmailContract $email
     */
    public static function send(EmailContract $email) : void
    {
        try {
            if (! $email->getContentType()) {
                throw new EmailSendFailedException(__('The email does not have content type set', 'mwc-common'));
            }

            if (empty($email->getTo())) {
                throw new EmailSendFailedException(__('The email does not have a recipient set', 'mwc-common'));
            }

            static::trySendEmail($email);
        } catch (SentryException $exception) {
            // do nothing - the exception will be automatically reported to Sentry
        } catch (Exception $exception) {
            // no need to throw because new instances of EmailSendFailedException are automatically reported
            new EmailSendFailedException($exception->getMessage(), $exception);
        }
    }

    /**
     * Tries to send email via all possible email services for the content type.
     *
     * @param EmailContract $email
     * @throws ComponentLoadFailedException
     * @throws Exception
     */
    protected static function trySendEmail(EmailContract $email)
    {
        foreach (static::getPossibleEmailServices($email->getContentType()) as $emailServiceClass) {
            if (static::isEmailService($emailServiceClass) &&
                $emailService = static::maybeLoadComponent($emailServiceClass)) {
                try {
                    $emailService->send($email);

                    return;
                } catch (SentryException $exception) {
                    // do nothing - the exception will be automatically reported to Sentry
                } catch (Exception $exception) {
                    // no need to throw because new instances of EmailSendFailedException are automatically reported
                    new EmailSendFailedException($exception->getMessage(), $exception);
                }
            }
        }

        throw new EmailSendFailedException(sprintf(__('A usable email service could not be found for %s', 'mwc-common'), $email->getContentType()));
    }

    /**
     * Determines if a class is a valid email service.
     *
     * @param string $class
     * @return bool
     */
    protected static function isEmailService(string $class) : bool
    {
        return is_a($class, EmailServiceContract::class, true);
    }

    /**
     * Returns the email services which can possibly be instantiated for a given content type.
     *
     * @param string $contentType
     * @return array
     */
    protected static function getPossibleEmailServices(string $contentType) : array
    {
        return ArrayHelper::wrap(Configuration::get('email.services.'.$contentType));
    }
}
