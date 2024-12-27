<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use GoDaddy\WordPress\MWC\Common\Email\Email;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;

class RenderableEmail extends Email implements RenderableEmailContract, ConditionalEmailContract, DelayableEmailContract
{
    /**
     * @var array assoc. array of variables used to substitute merge tags when rendering the email.
     */
    protected $variables = [];

    // @TODO: Review CreateScheduledEmailInput parameters structure (MWC-5004) {acastro1 2022-03-22}
    /** @var int|null timestamp at which the email should be sent, or null for immediately */
    protected $sendAt = null;

    /**
     * @var array conditions under which the email should be sent.
     */
    protected $conditions = [];

    /**
     * {@inheritdoc}
     */
    public function getVariables() : array
    {
        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function setVariables(array $value) : RenderableEmail
    {
        $this->variables = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions() : array
    {
        return $this->conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function setConditions(array $conditions) : ConditionalEmailContract
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyFormat() : string
    {
        return ArrayHelper::get([
            'text/html'  => 'html',
            'text/mjml'  => 'mjml',
            'text/plain' => 'plain',
        ], $this->getContentType(), 'html');
    }

    /**
     * {@inheritdoc}
     */
    public function getSendAt() : ?int
    {
        return $this->sendAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setSendAt(?int $value) : DelayableEmailContract
    {
        $this->sendAt = $value;

        return $this;
    }
}
