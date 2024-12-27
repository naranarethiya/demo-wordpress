<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;

interface ShippingLabelContract extends ModelContract, HasStringIdentifierContract, HasStringRemoteIdentifierContract
{
    /**
     * Gets shipping label status.
     *
     * @return LabelStatusContract
     */
    public function getStatus() : LabelStatusContract;

    /**
     * Sets shipping label status.
     *
     * @param LabelStatusContract $value
     * @return $this
     */
    public function setStatus(LabelStatusContract $value);

    /**
     * Checks if shipping label is trackable.
     *
     * @return bool
     */
    public function getIsTrackable() : bool;

    /**
     * Sets shipping label trackable status.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsTrackable(bool $value);

    /**
     * Gets shipping label documents.
     *
     * @return LabelDocumentContract[]
     */
    public function getDocuments() : array;

    /**
     * Sets shipping label documents.
     *
     * @param LabelDocumentContract ...$value
     * @return $this
     */
    public function setDocuments(LabelDocumentContract ...$value);
}
