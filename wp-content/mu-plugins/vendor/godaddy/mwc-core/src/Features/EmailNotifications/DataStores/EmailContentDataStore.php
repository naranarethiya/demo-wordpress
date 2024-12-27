<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailContentContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailContentNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;
use InvalidArgumentException;

/**
 * Data store for email content settings.
 */
class EmailContentDataStore
{
    use CanGetNewInstanceTrait;

    /** @var string the base option name to be used for reading the email content settings */
    private $settingsOptionNameBaseTemplate = 'mwc_%s_email_notification_content';

    /**
     * Gets an email content with given ID and reads its settings.
     *
     * @param string $id
     * @return EmailContentContract
     * @throws EmailContentNotFoundException|InvalidArgumentException
     */
    public function read(string $id) : EmailContentContract
    {
        /** @var class-string<EmailContentContract> $contentClass */
        $contentClass = Configuration::get("email_notifications.notifications.{$id}.content_class", DefaultEmailContent::class);

        // fallback to default if the configured class does not exist
        if (DefaultEmailContent::class !== $contentClass && ! class_exists($contentClass)) {
            $contentClass = DefaultEmailContent::class;
        }

        $content = (new $contentClass())->setId($id);

        $this->setStructuredContentPath($content);

        OptionsSettingsDataStore::getNewInstance($this->getSettingsOptionNameTemplate($id))->read($content);

        return $content;
    }

    /**
     * Sets the structured content path for the given email content.
     *
     * This method expects to find an MJML content template file in the
     * templates/email-notifications/mjml/content directory.
     *
     * @param EmailContentContract $content the email content object
     * @throws EmailContentNotFoundException
     */
    protected function setStructuredContentPath(EmailContentContract $content)
    {
        $structuredContentPath = $this->getStructuredContentPath($content);

        if (! file_exists($structuredContentPath)) {
            throw new EmailContentNotFoundException(sprintf(
                __('No content template file found for the ID %s.', 'mwc-core'),
                $content->getId()
            ));
        }

        $content->setStructuredContentPath($structuredContentPath);
    }

    /**
     * Gets the structured content path for the given email content instance.
     *
     * @param EmailContentContract $content the email content object
     * @return string
     *
     * @throws Exception
     */
    protected function getStructuredContentPath(EmailContentContract $content) : string
    {
        if (! $filename = Configuration::get("email_notifications.notifications.{$content->getId()}.structured_content_path")) {
            return '';
        }

        return $this->getTemplatesDirectory("email-notifications/mjml/content/{$filename}");
    }

    /**
     * Gets the path to the plugin's templates directory.
     *
     * TODO: add this method to the WordPressRepository class in mwc-common {wvega 2021-10-05}
     *
     * @param string $path optional path
     * @return string
     */
    protected function getTemplatesDirectory(string $path = '') : string
    {
        if (! $config = Configuration::get('mwc.directory')) {
            return '';
        }

        $pluginDirectory = StringHelper::trailingSlash($config);

        return "{$pluginDirectory}templates/{$path}";
    }

    /**
     * Saves the settings of a given email content object.
     *
     * @param EmailContentContract $emailContent
     * @return EmailContentContract
     */
    public function save(EmailContentContract $emailContent) : EmailContentContract
    {
        OptionsSettingsDataStore::getNewInstance($this->getSettingsOptionNameTemplate($emailContent->getId()))->save($emailContent);

        return $emailContent;
    }

    /**
     * Deletes the settings of a given email content object.
     *
     * @since x.y.z
     *
     * @param EmailContentContract $emailContent
     * @return EmailContentContract
     */
    public function delete(EmailContentContract $emailContent) : EmailContentContract
    {
        OptionsSettingsDataStore::getNewInstance($this->getSettingsOptionNameTemplate($emailContent->getId()))->delete($emailContent);

        return $emailContent;
    }

    /**
     * Gets the option name template to access an email content's settings.
     *
     * @param string $emailContentId
     * @return string
     */
    private function getSettingsOptionNameTemplate(string $emailContentId) : string
    {
        return sprintf($this->settingsOptionNameBaseTemplate, $emailContentId).'_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }
}
