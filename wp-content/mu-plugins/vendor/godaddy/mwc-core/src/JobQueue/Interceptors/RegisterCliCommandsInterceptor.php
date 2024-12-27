<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Interceptors;

use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\JobQueue\Commands\DispatchJobCommand;
use WP_CLI;

class RegisterCliCommandsInterceptor extends AbstractInterceptor
{
    /**
     * Registers hooks.
     *
     * @return void
     */
    public function addHooks() : void
    {
        $this->registerCommand();
    }

    /**
     * Determines whether the interceptor should load -- only in CLI mode.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return WordPressRepository::isCliMode();
    }

    /**
     * Registers a CLI command to dispatch a job.
     *
     * @return void
     */
    protected function registerCommand() : void
    {
        if (class_exists('WP_CLI')) {
            /* @phpstan-ignore-next-line passing in a class name instead of a callable is valid as per WP-CLI docs */
            WP_CLI::add_command('mwc dispatch', DispatchJobCommand::class);
        }
    }
}
