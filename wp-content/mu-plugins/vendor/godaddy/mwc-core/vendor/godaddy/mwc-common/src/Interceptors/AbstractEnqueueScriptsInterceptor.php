<?php

namespace GoDaddy\WordPress\MWC\Common\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Contracts\EnqueueScriptsInterceptorContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * The abstraction of an interceptor that enqueues scripts wrapped in a safe load technique.
 */
abstract class AbstractEnqueueScriptsInterceptor extends AbstractInterceptor implements EnqueueScriptsInterceptorContract
{
    /** @var string the WordPress hook that should time the script initialization handler */
    protected $initScriptGroup = 'wp';

    /**
     * Conditionally enqueues the JS scripts.
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup($this->initScriptGroup)
            ->setHandler([$this, 'enqueueScriptInitializer'])
            ->execute();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'maybeEnqueueScripts'])
            ->execute();
    }

    /**
     * Enqueues the script that safely loads the JS handler.
     *
     * @internal
     */
    public function enqueueScriptInitializer()
    {
        if ($this->shouldEnqueueJs()) {
            wc_enqueue_js($this->getSafeHandlerJs());
        }
    }

    /**
     * Enqueues the script.
     *
     * @internal
     */
    public function maybeEnqueueScripts()
    {
        if ($this->shouldEnqueueJs()) {
            $this->enqueueJs();
        }
    }

    /**
     * Returns true by default.
     *
     * @return bool
     */
    public function shouldEnqueueJs() : bool
    {
        return true;
    }

    /**
     * Returns an empty array by default.
     */
    public function getJsHandlerArgs() : array
    {
        return [];
    }

    /**
     * Gets the handler instantiation JS wrapped in a safe load technique.
     *
     * @return string
     */
    public function getSafeHandlerJs() : string
    {
        $handlerName = $this->getJsHandlerClassName();
        $loadFunctionName = "load{$handlerName}";

        ob_start(); ?>

        function <?php echo esc_js($loadFunctionName) ?>() { <?php echo $this->getHandlerJs(); ?> }

        try {
            if ( 'undefined' !== typeof <?php echo esc_js($handlerName); ?> ) {
                <?php echo esc_js($loadFunctionName); ?>();
            } else {
                window.jQuery( document.body ).on( '<?php echo esc_js($this->getJsLoadedEventName()); ?>', <?php echo esc_js($loadFunctionName); ?> );
            }
        } catch ( error ) {
            console.log( error );
        }
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the handler instantiation JS.
     *
     * @return string
     */
    public function getHandlerJs() : string
    {
        return sprintf(
            'window.%1$s = new %2$s(%3$s);',
            esc_js($this->getJsHandlerObjectName()),
            esc_js($this->getJsHandlerClassName()),
            ArrayHelper::jsonEncode($this->getJsHandlerArgs())
        );
    }
}
