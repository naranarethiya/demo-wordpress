<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Traits\HasJobTrait;
use Throwable;

class CustomerPushFailedEvent extends AbstractCustomerPushEvent
{
    use HasJobTrait;
    use CanGetNewInstanceTrait;

    /** @var Throwable */
    protected Throwable $exception;

    /**
     * Gets the failure exception.
     *
     * @return Throwable
     */
    public function getException() : Throwable
    {
        return $this->exception;
    }

    /**
     * Sets fail exception.
     *
     * @param Throwable $exception
     *
     * @return CustomerPushFailedEvent
     */
    public function setException(Throwable $exception) : CustomerPushFailedEvent
    {
        $this->exception = $exception;

        return $this;
    }
}
