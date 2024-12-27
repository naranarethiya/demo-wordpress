<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers\EmailNotificationsController;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\ConditionalEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DelayableEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use ReflectionException;

/**
 * A base builder for converting email notification definitions into email objects.
 */
abstract class AbstractEmailBuilder
{
    /** @var EmailNotificationContract */
    protected $emailNotification;

    /** @var string[] */
    protected $attachments = [];

    /** @var array */
    protected $data;

    /** @var array */
    protected $configuration = [];

    /** @var string[] */
    protected $recipients = [];

    /**
     * Constructor.
     *
     * @param EmailNotificationContract $emailNotification
     */
    public function __construct(EmailNotificationContract $emailNotification)
    {
        $this->emailNotification = $emailNotification;
    }

    /**
     * Get the email's attachments.
     *
     * @return array<string> file paths
     */
    public function getAttachments() : array
    {
        return $this->attachments;
    }

    /**
     * Sets the email's attachments.
     *
     * @param array $value
     * @return self
     */
    public function setAttachments(array $value) : AbstractEmailBuilder
    {
        $this->attachments = $value;

        return $this;
    }

    /**
     * Sets the email's recipient addresses.
     *
     * @param array $value
     * @return $this
     */
    public function setRecipients(array $value) : AbstractEmailBuilder
    {
        $this->recipients = ArrayHelper::where($value, static function ($address) {
            return ValidationHelper::isEmail($address);
        });

        return $this;
    }

    /**
     * Gets the email's recipient addresses.
     *
     * @return string[]
     */
    public function getRecipients() : array
    {
        return $this->recipients;
    }

    /**
     * Builds the email object.
     *
     * @return RenderableEmail
     * @throws Exception
     */
    public function build() : RenderableEmail
    {
        $variables = $this->getVariables();

        /* @phpstan-ignore-next-line */
        return $this->getNewInstance()
            ->setEmailName($this->getName())
            ->setFrom(EmailNotifications::getSenderAddress())
            ->setFromName(EmailNotifications::getSenderName())
            ->setVariables($variables)
            ->setSubject(ArrayHelper::get($variables, 'subject', $this->emailNotification->getSubject()))
            ->setBody($this->getFormattedBody())
            ->setAttachments($this->attachments)
            ->setContentType($this->getContentType())
            ->setSendAt($this->getSendAt())
            ->setConditions($this->getEmailConditions());
    }

    /**
     * Gets the email data.
     *
     * @return array
     */
    protected function getData() : array
    {
        if (null === $this->data) {
            $this->data = $this->getEmailNotificationData();
        }

        return $this->data;
    }

    /**
     * Gets the email notification data.
     *
     * @return array
     */
    abstract protected function getEmailNotificationData() : array;

    /**
     * Gets the formatted body with custom component placeholders replaced.
     *
     * @return string
     */
    protected function getFormattedBody() : string
    {
        $structuredBody = $this->emailNotification->getStructuredBody();

        foreach (ArrayHelper::get($this->getData(), 'internal.custom_components', []) as $componentKey => $componentContent) {
            $structuredBody = str_replace('<mj-'.str_replace('_', '-', $componentKey).'>', $componentContent, $structuredBody);
        }

        return $structuredBody;
    }

    /**
     * Adds configuration data that will be merged with the configuration of the email notification.
     *
     * @param array $configuration associative array of configuration settings and values
     * @return self
     * @throws Exception
     */
    public function addConfiguration(array $configuration) : AbstractEmailBuilder
    {
        $this->configuration = ArrayHelper::combineRecursive($this->configuration, $configuration);

        return $this;
    }

    /**
     * Gets all formatted configurations and data, combining it into a single array.
     *
     * @return array The list of merged variable values keyed by the variable names.
     * @throws Exception
     */
    protected function getVariables() : array
    {
        return ArrayHelper::combine($this->getFormattedConfiguration(), $this->getData());
    }

    /**
     * Gets the content type for the email.
     *
     * @return string
     */
    protected function getContentType() : string
    {
        return $this->emailNotification->getContentType();
    }

    /**
     * Gets the emails name.
     *
     * @return string
     */
    protected function getName() : string
    {
        return $this->emailNotification->getName();
    }

    /**
     * Gets the timestamp the email should be sent at (or null for immediate).
     *
     * @return int|null
     */
    protected function getSendAt() : ?int
    {
        if ($this->emailNotification instanceof DelayableEmailNotificationContract && $sendAt = $this->emailNotification->sendAt()) {
            return $sendAt->getTimestamp();
        }

        return null;
    }

    /**
     * Gets a new renderable email instance.
     *
     * @return RenderableEmail
     */
    abstract protected function getNewInstance() : RenderableEmail;

    /**
     * Gets the email configuration with placeholders replaced with email data.
     *
     * @return array
     * @throws Exception
     */
    protected function getFormattedConfiguration() : array
    {
        $configuration = ArrayHelper::combineRecursive($this->emailNotification->getConfiguration(), $this->configuration);
        $placeholders = ArrayHelper::combine($this->emailNotification->getPlaceholders(), $this->getHiddenPlaceholders());
        $data = ArrayHelper::combine($this->getData(), $this->getHiddenPlaceholdersData());

        return $this->formatConfiguration($configuration, $placeholders, $data);
    }

    /**
     * Gets a list of placeholders that are supported but are not offered to merchants.
     *
     * @return array
     */
    protected function getHiddenPlaceholders() : array
    {
        return [
            'site_address',
            'woocommerce',
            'WooCommerce',
        ];
    }

    /**
     * Gets a data used to replace the placeholders that are supported but are not offered to merchants.
     *
     * @return array
     */
    protected function getHiddenPlaceholdersData() : array
    {
        return [
            'woocommerce' => '<a href="https://woocommerce.com">WooCommerce</a>',
            'WooCommerce' => '<a href="https://woocommerce.com">WooCommerce</a>',
        ];
    }

    /**
     * Formats configuration values recursively.
     *
     * @param array $configuration
     * @param array $placeholders
     * @param array $data
     * @return array
     */
    protected function formatConfiguration(array $configuration, array $placeholders, array $data) : array
    {
        foreach ($configuration as $key => $value) {
            if (ArrayHelper::accessible($value)) {
                $configuration[$key] = $this->formatConfiguration($value, $placeholders, $data);
                continue;
            }

            foreach ($placeholders as $placeholder) {
                $configuration[$key] = $this->replacePlaceholder(ArrayHelper::get($configuration, $key), $placeholder, $data);
            }
        }

        return $configuration;
    }

    /**
     * Replaces a placeholder with a value present in data.
     *
     * @param bool|float|int|string|array $value
     * @param string $placeholder
     * @param array $data
     * @return bool|float|int|string|array
     */
    protected function replacePlaceholder($value, $placeholder, array $data)
    {
        if (! is_string($value) || empty($placeholder) || empty($data)) {
            return $value;
        }

        $placeholderValue = TypeHelper::string(ArrayHelper::get($data, $placeholder), '');

        return preg_replace(sprintf('/[\{]{1,2}\s*(%s)\s*[\}]{1,2}/', preg_quote($placeholder, '/')), $placeholderValue, $value);
    }

    /**
     * Gets a list of email conditions to be sent.
     *
     * @return array
     *
     * @throws ReflectionException
     */
    protected function getEmailConditions() : array
    {
        $conditions = [];

        if (is_a($this->emailNotification, ConditionalEmailNotificationContract::class)) {
            $conditions[] = [
                'conditionType' => 'confirm_sending',
                'conditionData' => $this->getConditionRoute('should-send'),
            ];
        }

        if (is_a($this->emailNotification, DelayableEmailNotificationContract::class)) {
            $conditions[] = [
                'conditionType' => 'retrieve_updated_data',
                'conditionData' => $this->getConditionRoute('data'),
            ];
        }

        return $conditions;
    }

    /**
     * A helper method to build a condition route.
     *
     * @param string $path
     * @return string
     *
     * @throws ReflectionException
     */
    protected function getConditionRoute(string $path) : string
    {
        return EmailNotificationsController::getNewInstance()->getRoute($this->emailNotification->getId(), $path, ['email_address' => $this->getEmailParamForConditionRoutes()]);
    }

    /**
     * Extracts the email parameter for condition routes.
     *
     * @return string
     */
    protected function getEmailParamForConditionRoutes() : string
    {
        return ! empty($this->getRecipients()) ? $this->getRecipients()[0] : '';
    }
}
