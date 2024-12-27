<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Contracts\EnqueuableContract;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingFunctionException;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingHandleException;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingSourceException;

/**
 * Style enqueueable.
 */
final class EnqueueStyle extends Enqueue implements EnqueuableContract
{
    /** @var string context the stylesheet applies to */
    protected $media = 'all';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setType('style');
    }

    /**
     * Sets the media context.
     *
     * @param string $media the media type the stylesheet applies to
     * @return $this
     */
    public function setMedia(string $media) : EnqueueStyle
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Loads the stylesheet in WordPress.
     *
     * @return void
     * @throws Exception
     */
    public function execute() : void
    {
        $this->validate();
        $this->register();
        $this->enqueue();
    }

    /**
     * Registers the asset in WordPress.
     *
     * @return void
     */
    private function register() : void
    {
        /* @phpstan-ignore-next-line */
        wp_register_style(
            $this->handle,
            $this->source,
            $this->dependencies,
            $this->version,
            $this->media
        );
    }

    /**
     * Enqueues the stylesheet in WordPress.
     *
     * @return void
     */
    private function enqueue() : void
    {
        if (! $this->shouldEnqueue()) {
            return;
        }

        /* @phpstan-ignore-next-line */
        wp_enqueue_style($this->handle);
    }

    /**
     * Validates the current instance.
     *
     * @return void
     * @throws Exception
     */
    public function validate() : void
    {
        if (! $this->handle) {
            throw new MissingHandleException('You must provide a handle name for the stylesheet to be enqueued.');
        }

        if (! $this->source) {
            throw new MissingSourceException("You must provide a URL to enqueue the stylesheet `{$this->handle}`.");
        }

        if (! function_exists('wp_register_style')) {
            throw new MissingFunctionException("Cannot register the stylesheet `{$this->handle}`: the function `wp_register_style()` does not exist.");
        }

        if (! function_exists('wp_enqueue_style')) {
            throw new MissingFunctionException("Cannot enqueue the stylesheet `{$this->handle}`: the function `wp_enqueue_style()` does not exist.");
        }
    }
}
