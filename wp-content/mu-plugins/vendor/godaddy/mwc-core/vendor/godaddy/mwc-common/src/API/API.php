<?php

namespace GoDaddy\WordPress\MWC\Common\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Register\Register;

class API implements ComponentContract
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] */
    protected $componentClasses = [];

    /**
     * Registers the API routes.
     *
     * @throws ComponentLoadFailedException|ComponentClassesNotDefinedException
     * @internal
     */
    public function registerRoutes() : void
    {
        $this->loadComponents();
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function load() : void
    {
        Register::action()
            ->setGroup('rest_api_init')
            ->setHandler([$this, 'registerRoutes'])
            ->execute();
    }
}
