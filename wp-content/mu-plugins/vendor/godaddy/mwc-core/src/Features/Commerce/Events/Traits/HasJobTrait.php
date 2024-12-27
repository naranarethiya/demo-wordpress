<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Events\Traits;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs\AbstractPushJob;

trait HasJobTrait
{
    /** @var AbstractPushJob|null a job instance associated with this class */
    protected ?AbstractPushJob $job = null;

    /**
     * Gets the job associated with this class.
     *
     * @return AbstractPushJob|null
     */
    public function getJob() : ?AbstractPushJob
    {
        return $this->job;
    }

    /**
     * Sets the job associated with this class.
     *
     * @param AbstractPushJob|null $value
     * @return $this
     */
    public function setJob(?AbstractPushJob $value)
    {
        $this->job = $value;

        return $this;
    }
}
