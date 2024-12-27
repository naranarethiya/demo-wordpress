<?php

namespace GoDaddy\WordPress\MWC\Core\Email\DataSources\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Email\DataSources\WordPress\Adapters\AttachmentsAdapter;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailServiceRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;

/**
 * An adapter for handling scheduled emails data.
 */
class ScheduledEmailAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var RenderableEmail|RenderableEmailContract */
    protected $source;

    /**
     * Constructor.
     *
     * @param RenderableEmail $email
     */
    public function __construct(RenderableEmailContract $email)
    {
        $this->source = $email;
    }

    /**
     * Converts renderable email data to an array used to schedule emails.
     *
     * @return array
     * @throws Exception
     */
    public function convertFromSource() : array
    {
        $data = [
            'emailEventName' => $this->source->getEmailName(),
            'siteId'         => EmailServiceRepository::getSiteId(),
            'emailMessage'   => ArrayHelper::combine([
                'sender'       => $this->convertSenderData(),
                'toRecipients' => $this->convertRecipientsData($this->source),
                'subject'      => $this->source->getSubject(),
                'extraHeaders' => $this->convertHeadersData($this->source),
                'attachments'  => $this->convertAttachmentsData($this->source),
            ], $this->convertContentData($this->source)),
            'templateParamsJson' => json_encode($this->source->getVariables()),
            'conditions'         => $this->source->getConditions(),
        ];

        if ($sendAt = $this->source->getSendAt()) {
            $data['sendAt'] = $sendAt;
        }

        return $data;
    }

    /**
     * Converts the email's headers data.
     *
     * @param RenderableEmailContract $email
     * @return array
     */
    protected function convertHeadersData(RenderableEmailContract $email) : array
    {
        return array_map(static function (string $headerName, string $headerBody) {
            return [
                'headerName' => $headerName,
                'headerBody' => $headerBody,
            ];
        }, array_keys($email->getHeaders()), array_values($email->getHeaders()));
    }

    /**
     * Converts the sender data.
     *
     * @return string[]
     */
    protected function convertSenderData() : array
    {
        return [
            'name'    => (string) EmailNotifications::getSenderName(),
            'address' => (string) EmailNotifications::getSenderAddress(),
        ];
    }

    /**
     * Converts the email recipients' data.
     *
     * @param RenderableEmailContract $email
     * @return array
     */
    protected function convertRecipientsData(RenderableEmailContract $email) : array
    {
        return array_map(static function (string $to) : array {
            return [
                'address' => $to,
            ];
        }, ArrayHelper::wrap($email->getTo()));
    }

    /**
     * Converts the email attachments' data.
     *
     * @param RenderableEmailContract $email
     * @return array
     * @throws Exception
     */
    protected function convertAttachmentsData(RenderableEmailContract $email) : array
    {
        $attachmentsData = [];

        foreach ($email->getAttachments() as $attachmentFile) {
            try {
                $attachmentsData[] = AttachmentsAdapter::getNewInstance($attachmentFile)->convertFromSource();
            } catch (Exception $exception) {
                new SentryException($exception->getMessage(), $exception);
            }
        }

        return $attachmentsData;
    }

    /**
     * Converts the content data for the given email.
     *
     * @param RenderableEmailContract $email
     * @return string[] with key(s) mjml/html/plain key
     */
    protected function convertContentData(RenderableEmailContract $email) : array
    {
        return [
            $email->getBodyFormat() => $email->getBody(),
        ];
    }

    /**
     * Converts email data for email scheduling to a renderable email.
     *
     * @param array|null $data
     * @return RenderableEmailContract
     */
    public function convertToSource(array $data = null) : RenderableEmailContract
    {
        /* @TODO implement this method when needed {unfulvio 2022-03-14} */
        return new RenderableEmail('');
    }
}
