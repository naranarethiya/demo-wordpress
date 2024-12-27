<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasCreatedAtContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasUpdatedAtContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

interface AccountContract extends
    ModelContract,
    HasStringIdentifierContract,
    HasStringRemoteIdentifierContract,
    HasCreatedAtContract,
    HasUpdatedAtContract
{
    /**
     * Gets the first name.
     *
     * @return string
     */
    public function getFirstName() : string;

    /**
     * Sets the first name.
     *
     * @param string $value
     * @return $this
     */
    public function setFirstName(string $value);

    /**
     * Gets the last name.
     *
     * @return string
     */
    public function getLastName() : string;

    /**
     * Sets the last name.
     *
     * @param string $value
     * @return $this
     */
    public function setLastName(string $value);

    /**
     * Gets the company name.
     *
     * @return string
     */
    public function getCompanyName() : string;

    /**
     * Sets the company name.
     *
     * @param string $value
     * @return $this
     */
    public function setCompanyName(string $value);

    /**
     * Gets the origin country code.
     *
     * @return string
     */
    public function getOriginCountryCode() : string;

    /**
     * Sets the origin country code.
     *
     * @param string $value
     * @return $this
     */
    public function setOriginCountryCode(string $value);

    /**
     * Gets the status.
     *
     * @return AccountStatusContract
     */
    public function getStatus() : AccountStatusContract;

    /**
     * Sets the status.
     *
     * @param AccountStatusContract $value
     * @return $this
     */
    public function setStatus(AccountStatusContract $value);
}
