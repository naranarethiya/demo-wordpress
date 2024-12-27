<?php

namespace GoDaddy\WordPress\MWC\Common\Register\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Contracts\RegistrableContract;
use GoDaddy\WordPress\MWC\Common\Register\Exceptions\InvalidFilterException;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * WordPress filter registration wrapper.
 */
class RegisterFilter extends Register implements RegistrableContract
{
    /**
     * Registrable filter constructor.
     */
    public function __construct()
    {
        $this->setType('filter');
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
        remove_filter($this->groupName, $this->handler, $this->processPriority);
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
            add_filter($this->groupName, $this->handler, $this->processPriority, $this->numberOfArguments);
        }
    }

    /**
     * Validates the current instance settings.
     *
     * @return void
     * @throws InvalidFilterException
     */
    public function validate() : void
    {
        if (! $this->groupName) {
            throw new InvalidFilterException('Cannot register a filter: the group to assign the filter to is not specified.');
        }

        if (! $this->hasHandler()) {
            throw new InvalidFilterException("Cannot register a filter for `{$this->groupName}`: the provided handler does not exist or is not callable.");
        }
    }
}
