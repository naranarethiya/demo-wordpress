<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Events\Http\GraphQL\Queries\TemplateAsHtmlQuery;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;

/**
 * Email service for rendering MJML emails using a remote service and sending the resulting HTML through WordPress.
 */
class RemoteRenderingEmailService implements EmailServiceContract
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
     * @throws EmailSendFailedException
     */
    public function send(EmailContract $email)
    {
        if (! $email instanceof RenderableEmailContract || $email->getBodyFormat() !== 'mjml') {
            throw new EmailSendFailedException(sprintf(
                '%1$s does not support sending emails with the %2$s content type.',
                static::class,
                $email->getContentType()
            ));
        }

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

        $this->sendMjmlEmail($email);
    }

    /**
     * Sends a renderable email that uses an MJML template as the body.
     *
     * @param RenderableEmailContract $email the email object
     * @throws EmailSendFailedException
     */
    protected function sendMjmlEmail(RenderableEmailContract $email) : void
    {
        (clone $email)
            ->setContentType('text/html')
            ->setBody($this->getBodyAsHtml($email))
            ->setAltBody('')
            ->send();
    }

    /**
     * Uses a remote service to convert the MJML body of the given email into HTML.
     *
     * @param RenderableEmailContract $email
     * @return string
     * @throws EmailSendFailedException
     */
    protected function getBodyAsHtml(RenderableEmailContract $email) : string
    {
        try {
            $response = $this->buildRequest($email)->send();
        } catch (Exception $exception) {
            throw new EmailSendFailedException($exception->getMessage(), $exception);
        }

        if ($errorMessage = $this->getResponseErrorMessage($response)) {
            throw new EmailSendFailedException($errorMessage);
        }

        if (! $html = ArrayHelper::get($response->getBody(), 'data.templateAsHtml.html')) {
            throw new EmailSendFailedException('The HTML template is missing or empty.');
        }

        return $html;
    }

    /**
     * Prepares a {@see Request} object with the body and variables from the given email.
     *
     * @param RenderableEmailContract $email
     * @return Request
     * @throws Exception
     */
    protected function buildRequest(RenderableEmailContract $email) : Request
    {
        return Request::withAuth($this->getTemplateAsHtmlQuery($email))
            ->setSiteId(PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId());
    }

    /**
     * Gets the error message from a response object.
     *
     * Returns null if the response doesn't include information to indicate that an error occurred.
     *
     * @param Response $response the response object
     * @return string|null
     */
    protected function getResponseErrorMessage(Response $response) : ?string
    {
        if ($response->isError()) {
            return $response->getErrorMessage() ?: __('Unknown error.', 'mwc-core');
        }

        return null;
    }

    /**
     * Gets the corresponding GraphQL operation to query email template as HTML.
     *
     * @param RenderableEmailContract $email
     * @return TemplateAsHtmlQuery
     */
    protected function getTemplateAsHtmlQuery(RenderableEmailContract $email) : TemplateAsHtmlQuery
    {
        return (new TemplateAsHtmlQuery())
            ->setTemplate($email->getBody())
            ->setTemplateParameters($email->getVariables());
    }
}
