<?php

namespace GoDaddy\WordPress\MWC\Common\Register\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Contracts\RegistrableContract;
use GoDaddy\WordPress\MWC\Common\Register\Exceptions\InvalidActionException;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * WordPress action registration wrapper.
 */
class RegisterAction extends Register implements RegistrableContract
{
    /**
     * Registrable action constructor.
     */
    public function __construct()
    {
        $this->setType('action');
        $this->setPriority(10);
        $this->setArgumentsCount(1);
    }

    /**
     * Executes the deregistration.
     *
     * @return void
     * @throws Exception
     */
    public function deregister() : void
    {
        $this->validate();

        /* @phpstan-ignore-next-line */
        remove_action($this->groupName, $this->handler, $this->processPriority);
    }

    /**
     * Executes the registration.
     *
     * @return void
     * @throws Exception
     */
    public function execute() : void
    {
        $this->validate();

        if ($this->shouldRegister()) {
            /* @phpstan-ignore-next-line */
            add_action($this->groupName, $this->handler, $this->processPriority, $this->numberOfArguments);
        }
    }

    /**
     * Validates the current instance settings.
     *
     * @return void
     * @throws InvalidActionException
     */
    public function validate() : void
    {
        if (! $this->groupName) {
            throw new InvalidActionException('Cannot register an action: the group to assign the action to is not specified.');
        }

        if (! $this->hasHandler()) {
            throw new InvalidActionException("Cannot register an action for `{$this->groupName}`: the provided handler does not exist or is not callable.");
        }
    }
}
