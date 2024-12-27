<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Contracts\EnqueuableContract;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingFunctionException;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingHandleException;
use GoDaddy\WordPress\MWC\Common\Enqueue\Exceptions\MissingSourceException;

/**
 * Script enqueueable.
 */
final class EnqueueScript extends Enqueue implements EnqueuableContract
{
    /** @var string|null optional JavaScript object name to be added inline after successful enqueue */
    protected $scriptObject;

    /** @var array<string|int, mixed> optional JavaScript object variables to be added inline */
    protected $scriptVariables = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setType('script');
    }

    /**
     * Instructs to add an inline JavaScript object with a name.
     *
     * @param string $objectName
     * @return $this
     */
    public function attachInlineScriptObject(string $objectName) : EnqueueScript
    {
        $this->scriptObject = $objectName;

        return $this;
    }

    /**
     * Adds script variables to the inline JavaScript object, if set.
     *
     * @param array<string|int, mixed> $variables associative array
     * @return $this
     */
    public function attachInlineScriptVariables(array $variables) : EnqueueScript
    {
        $this->scriptVariables = array_merge($this->scriptVariables, $variables);

        return $this;
    }

    /**
     * Loads the script in WordPress.
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
        wp_register_script(
            $this->handle,
            $this->source,
            $this->dependencies,
            $this->version,
            $this->deferred
        );
    }

    /**
     * Enqueues the script in WordPress.
     *
     * @return void
     */
    private function enqueue() : void
    {
        if (! $this->shouldEnqueue()) {
            return;
        }

        /* @phpstan-ignore-next-line */
        wp_enqueue_script($this->handle);

        if ($this->scriptObject) {
            /* @phpstan-ignore-next-line */
            wp_localize_script(
                $this->handle,
                $this->scriptObject,
                $this->scriptVariables
            );
        }
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
            throw new MissingHandleException('You must provide a handle name for the script to be enqueued.');
        }

        if (! $this->source) {
            throw new MissingSourceException("You must provide a URL to enqueue the script `{$this->handle}`.");
        }

        if (! function_exists('wp_register_script')) {
            throw new MissingFunctionException("Cannot register the script `{$this->handle}`: the function `wp_register_script()` does not exist.");
        }

        if ($this->scriptObject && ! function_exists('wp_localize_script')) {
            throw new MissingFunctionException("Cannot add an inline script object `{$this->scriptObject}` for the script `{$this->handle}`: the function `wp_localize_script()` does not exist.");
        }

        if (! function_exists('wp_enqueue_script')) {
            throw new MissingFunctionException("Cannot enqueue the script `{$this->handle}`: the function `wp_enqueue_script()` does not exist.");
        }
    }
}
