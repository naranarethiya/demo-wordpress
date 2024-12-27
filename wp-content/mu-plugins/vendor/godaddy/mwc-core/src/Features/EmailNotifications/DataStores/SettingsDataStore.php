<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use InvalidArgumentException;

/**
 * Data store for settings.
 */
class SettingsDataStore
{
    /** @var array available setting groups */
    protected $settings = [
        GeneralSettings::GROUP_ID => GeneralSettings::class,
    ];

    /**
     * Reads the values of the settings from database.
     *
     * @param string $id
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    public function read(string $id) : ConfigurableContract
    {
        $setting = $this->getSettingInstance($id);

        OptionsSettingsDataStore::getNewInstance($this->getOptionNameTemplate($id))->read($setting);

        return $setting;
    }

    /**
     * Gets the setting instance from the given id.
     *
     * @param string $id
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    protected function getSettingInstance(string $id) : ConfigurableContract
    {
        if (! ArrayHelper::exists($this->settings, $id)) {
            throw new InvalidArgumentException(sprintf(
                __('No settings found with the ID %s.', 'mwc-core'),
                $id
            ));
        }

        $class = TypeHelper::string(ArrayHelper::get($this->settings, $id), '');

        if (! is_a($class, ConfigurableContract::class, true)) {
            throw new InvalidArgumentException(sprintf(
                __('The class name for %s must implement ConfigurableContract', 'mwc-core'),
                $id
            ));
        }

        return new $class();
    }

    /**
     * Saves the settings values to database.
     *
     * @param ConfigurableContract $generalSettings
     * @return ConfigurableContract
     */
    public function save(ConfigurableContract $generalSettings) : ConfigurableContract
    {
        OptionsSettingsDataStore::getNewInstance($this->getOptionNameTemplate($generalSettings->getId()))->save($generalSettings);

        return $generalSettings;
    }

    /**
     * Gets the option name template.
     *
     * @param string $settingId
     * @return string
     */
    protected function getOptionNameTemplate(string $settingId) : string
    {
        return 'mwc_'.$settingId.'_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }
}
