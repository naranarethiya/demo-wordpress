<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailCatcher;

/**
 * Email service for sending emails through WordPress.
 */
class WordPressEmailService implements EmailServiceContract
{
    /**
     * Loads the component.
     */
    public function load()
    {
        // no-op, implements contract method
    }

    /**
     * Sends an email.
     *
     * @param EmailContract $email
     * @throws Exception
     */
    public function send(EmailContract $email)
    {
        // @TODO: abstract this so we don't have to repeat ourselves MWC-5074 {dmagalhaes - 2022-03-24}
        if ($email instanceof ConditionalEmailContract && ! empty($email->getConditions())) {
            throw new EmailSendFailedException(sprintf(
                '%1$s does not support sending conditional emails.',
                static::class
            ));
        }

        if ($email instanceof DelayableEmailContract && ! empty($email->getSendAt())) {
            throw new EmailSendFailedException(sprintf(
                '%1$s does not support sending delayed emails.',
                static::class
            ));
        }

        // set the content type for this email
        $filter = Register::filter()
            ->setGroup('wp_mail_content_type')
            ->setHandler([$email, 'getContentType'])
            ->setPriority(10)
            ->setArgumentsCount(1);

        $filter->execute();

        // prevent the email catcher from trying to send this email using our emails service
        EmailCatcher::disable();

        wp_mail($email->getTo(), $email->getSubject() ?: '', $email->getBody() ?: '', $this->getEmailHeaders($email), $email->getAttachments());

        // let the email catcher start trying to send emails using our emails service again
        EmailCatcher::enable();

        // clear the content type for other emails
        $filter->deregister();
    }

    /**
     * Gets the headers from the given email instance in the format that wp_mail() expects.
     *
     * @param EmailContract $email
     * @return array
     */
    protected function getEmailHeaders(EmailContract $email) : array
    {
        $headers = [];

        foreach ($email->getHeaders() as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        if ($email->getFromName() && $email->getFrom() && ! ArrayHelper::has($email->getHeaders(), 'From')) {
            $headers[] = "From: {$email->getFromName()} <{$email->getFrom()}>";
        } elseif ($email->getFrom() && ! ArrayHelper::has($email->getHeaders(), 'From')) {
            $headers[] = "From: {$email->getFrom()}";
        }

        return $headers;
    }
}
