<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;

/**
 * Abstract page class.
 *
 * Represents a base page for all pages to extend from
 */
abstract class AbstractPage implements RenderableContract
{
    /** @var string page screen identifier */
    protected $screenId;

    /** @var string page title */
    protected $title;

    /**
     * Abstract page constructor.
     */
    public function __construct()
    {
        $this->registerAssets();
    }

    /**
     * Determines if the current page is the page we want to enqueue the registered assets.
     *
     * @return bool default true
     */
    protected function shouldEnqueueAssets() : bool
    {
        return true;
    }

    /**
     * Renders the page HTML markup.
     *
     * @since 1.0.0
     */
    public function render()
    {
        //@NOTE implement render() method.
    }

    /**
     * Maybe enqueues the page necessary assets.
     */
    public function maybeEnqueueAssets()
    {
        if (! $this->shouldEnqueueAssets()) {
            return;
        }

        $this->enqueueAssets();
    }

    /**
     * Enqueues/loads registered page assets.
     */
    protected function enqueueAssets()
    {
        //@NOTE implement assets loading for the page.
    }

    /**
     * Registers any page assets.
     */
    protected function registerAssets()
    {
        //@NOTE implement assets registration for the page
    }

    /**
     * Sets the screen ID for the page.
     *
     * @param string $screenId
     * @return AbstractPage $this
     */
    public function setScreenId(string $screenId) : AbstractPage
    {
        $this->screenId = $screenId;

        return $this;
    }

    /**
     * Gets the screen ID for the page.
     *
     * @return string
     */
    public function getScreenId() : string
    {
        return $this->screenId;
    }

    /**
     * Sets the title for the page.
     *
     * @param string $title
     * @return AbstractPage $this
     */
    public function setTitle(string $title) : AbstractPage
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the page title.
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }
}
