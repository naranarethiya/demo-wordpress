<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasCreatedAtContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * Represents a single order note.
 */
interface NoteContract extends HasCreatedAtContract, ModelContract
{
    /**
     * Gets note's content.
     *
     * @return string|null
     */
    public function getContent() : ?string;

    /**
     * Sets note's content.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setContent(?string $value);

    /**
     * Gets note's author name.
     *
     * @return string|null
     */
    public function getAuthorName() : ?string;

    /**
     * Sets note's author name.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setAuthorName(?string $value);

    /**
     * Gets if the note was sent to the customer.
     *
     * @return bool
     */
    public function getShouldNotifyCustomer() : bool;

    /**
     * Sets if the note was sent to the customer.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setShouldNotifyCustomer(bool $value);

    /**
     * Determines if note added by the system.
     *
     * @return bool
     */
    public function isAddedBySystem() : bool;
}
