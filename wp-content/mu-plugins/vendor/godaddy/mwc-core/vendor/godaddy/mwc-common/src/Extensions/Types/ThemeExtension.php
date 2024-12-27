<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Types;

use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;

/**
 * The theme extension class.
 */
class ThemeExtension extends AbstractExtension
{
    /** @var string asset type */
    const TYPE = 'theme';

    /** @var array<string, string> key-value list of available icon URLs */
    protected $imageUrls = [];

    /**
     * Theme constructor.
     */
    public function __construct()
    {
        $this->type = self::TYPE;
    }

    /**
     * Gets the image URLs.
     *
     * @return array<string, string>
     */
    public function getImageUrls() : array
    {
        return $this->imageUrls;
    }

    /**
     * Gets the currently installed version or returns null.
     *
     * @return string|null
     */
    public function getInstalledVersion() : ?string
    {
        // @TODO implement this method {JO 2021-02-12}
        return null;
    }

    /**
     * Sets the image URLs.
     *
     * @param string[] $urls URLs to set
     * @return $this
     */
    public function setImageUrls(array $urls) : AbstractExtension
    {
        $this->imageUrls = $urls;

        return $this;
    }

    /**
     * Activates the theme.
     *
     * @return void
     */
    public function activate() : void
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Determines whether the theme is active.
     *
     * @return bool
     */
    public function isActive() : bool
    {
        // @TODO implement this method {FN 2021-01-12}
        return false;
    }

    /**
     * Deactivates the theme.
     *
     * @return void
     */
    public function deactivate() : void
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Installs the theme.
     *
     * @return void
     */
    public function install() : void
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Determines if the theme is installed.
     *
     * @return bool
     */
    public function isInstalled() : bool
    {
        // @TODO implement this method {FN 2021-01-12}
        return false;
    }

    /**
     * Uninstall the theme.
     *
     * @return void
     */
    public function uninstall() : void
    {
        // @TODO implement this method {JO 2021-02-12}
    }

    /**
     * Gets a theme extension instance.
     *
     * @param string $identifier theme basename
     * @return ThemeExtension|null
     */
    public static function get($identifier) : ?ThemeExtension
    {
        /* @TODO implement this method {unfulvio 2021-01-12} */
        /* @phpstan-ignore-next-line  */
        return parent::get($identifier);
    }
}
