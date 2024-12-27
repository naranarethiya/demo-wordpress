<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\ThemeRepository;
use GoDaddy\WordPress\MWC\Core\Events\Site\SiteLogoEvent;
use GoDaddy\WordPress\MWC\Core\Events\Traits\CanDetermineEventActionTrait;

/**
 * An interceptor to hook into theme and site logo changes/modifications.
 */
class ThemeCustomizationInterceptor extends AbstractInterceptor
{
    use CanDetermineEventActionTrait;

    /**
     * {@inheritDoc}
     */
    public function addHooks() : void
    {
        try {
            Register::filter()
                ->setGroup('customize_changeset_save_data')
                ->setHandler([$this, 'onClassicThemeModified'])
                ->setPriority(PHP_INT_MIN)
                ->execute();
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }

    /**
     * Attempts to broadcast events when specific theme customizer settings change.
     *
     * @param mixed $data
     * @return mixed
     */
    public function onClassicThemeModified($data)
    {
        if (! ArrayHelper::accessible($data)) {
            return $data;
        }

        if ($changes = $this->getClassicThemeChanges($data)) {
            $this->maybeBroadcastSiteLogoEventForClassicTheme($changes);
        }

        return $data;
    }

    /**
     * Converts given data into a list of changes.
     *
     * @param array<string, mixed> $data
     * @return array<string, scalar>
     */
    protected function getClassicThemeChanges(array $data) : array
    {
        return array_map(static fn ($setting) => TypeHelper::scalar(ArrayHelper::get($setting, 'value'), ''), $data);
    }

    /**
     * Tries to Broadcast site logo event.
     *
     * @param array<string, scalar> $changes
     * @return void
     */
    protected function maybeBroadcastSiteLogoEventForClassicTheme(array $changes) : void
    {
        $logoSettingName = ThemeRepository::getActiveThemeName().'::custom_logo';

        if (! ArrayHelper::has($changes, $logoSettingName)) {
            return;
        }

        $newValue = ArrayHelper::get($changes, $logoSettingName);
        $oldValue = ThemeRepository::getCustomLogo();

        if ($this->isIdenticalValue($oldValue, $newValue)) {
            return;
        }

        Events::broadcast(SiteLogoEvent::getNewInstance($this->determineEventAction($oldValue, $newValue)));
    }
}
