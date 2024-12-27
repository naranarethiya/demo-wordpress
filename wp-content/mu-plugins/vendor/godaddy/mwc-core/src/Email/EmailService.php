<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Email\DataSources\Adapters\ScheduledEmailAdapter;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\CreateScheduledEmailMutation;
use GoDaddy\WordPress\MWC\Core\Email\Models\EmailSender;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;

class EmailService implements EmailServiceContract, ConditionalComponentContract
{
    use CanGetNewInstanceTrait;

    /** @var bool whether we can use this email service */
    protected static $enabled = true;

    /**
     * Determines whether the email service can be used.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return static::$enabled && static::isSenderAddressVerified();
    }

    /**
     * Determines whether the configured sender address is verified.
     *
     * @return bool
     */
    protected static function isSenderAddressVerified() : bool
    {
        if (! $emailAddress = EmailNotifications::getSenderAddress()) {
            return false;
        }

        return static::isEmailAddressVerified($emailAddress);
    }

    /**
     * Determines whether the given email address is verified.
     *
     * @param string $emailAddress email address to verify
     * @return bool
     */
    protected static function isEmailAddressVerified(string $emailAddress) : bool
    {
        $emailSender = EmailSender::get($emailAddress);

        return $emailSender && $emailSender->isVerified();
    }

    /**
     * Initializes the email service.
     */
    public function load()
    {
        // TODO: Implement load() method.
    }

    /**
     * Sends an email.
     *
     * @param EmailContract $email
     * @throws EmailSendFailedException
     */
    public function send(EmailContract $email)
    {
        if (! $email instanceof RenderableEmailContract) {
            throw new EmailSendFailedException(sprintf(
                '%1$s does not support sending an email of the class %2$s.',
                static::class,
                get_class($email)
            ));
        }

        $this->sendEmail($email);
    }

    /**
     * Sends an email using our emails service.
     *
     * @param RenderableEmailContract $email
     * @throws EmailSendFailedException
     */
    protected function sendEmail(RenderableEmailContract $email)
    {
        try {
            $response = $this->buildRequest($email)->send();
        } catch (Exception $exception) {
            throw new EmailSendFailedException($exception->getMessage(), $exception);
        }

        if ($response->isError()) {
            throw new EmailSendFailedException((string) $response->getErrorMessage());
        }
    }

    /**
     * Prepares a GraphQL request object from the given email.
     *
     * @param RenderableEmailContract $email
     * @return EmailsServiceRequest
     * @throws Exception
     */
    protected function buildRequest(RenderableEmailContract $email) : EmailsServiceRequest
    {
        return (new EmailsServiceRequest())->setOperation($this->buildMutation($email));
    }

    /**
     * Prepares a {@see CreateScheduledEmailMutation} from the given email.
     *
     * @param RenderableEmailContract $email
     * @return CreateScheduledEmailMutation
     * @throws Exception
     */
    protected function buildMutation(RenderableEmailContract $email) : CreateScheduledEmailMutation
    {
        return (new CreateScheduledEmailMutation())->setVariables([
            'input' => ScheduledEmailAdapter::getNewInstance($email)->convertFromSource(),
        ]);
    }
}
