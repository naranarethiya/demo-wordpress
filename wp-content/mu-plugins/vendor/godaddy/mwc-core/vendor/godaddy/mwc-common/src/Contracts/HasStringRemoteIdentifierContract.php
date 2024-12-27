<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

interface HasStringRemoteIdentifierContract
{
    /**
     * Gets the remote ID.
     *
     * @return string
     */
    public function getRemoteId() : ?string;

    /**
     * Sets the remote ID.
     *
     * @param string $value
     *
     * @return mixed
     */
    public function setRemoteId(string $value);
}
