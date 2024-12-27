<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasCreatedAtTrait;

/**
 * An object representation of an {@see Order} note.
 */
abstract class AbstractNote extends AbstractModel implements NoteContract
{
    use HasCreatedAtTrait;

    /** @var string note's author name that represents system */
    public const SYSTEM_AUTHOR_NAME = 'system';

    /** @var string|null The note content */
    protected ?string $content = null;

    /** @var string|null Who added the note. Can be {@see Note::SYSTEM_AUTHOR_NAME} or the name of the user that added the note */
    protected ?string $authorName = null;

    /** @var bool True if this is a note that was sent to the customer, otherwise false */
    protected bool $shouldNotifyCustomer = false;

    /**
     * Gets note's content.
     *
     * @return string|null
     */
    public function getContent() : ?string
    {
        return $this->content;
    }

    /**
     * Sets note's content.
     *
     * @param string|null $value
     * @return $this
     */
    public function setContent(?string $value) : AbstractNote
    {
        $this->content = $value;

        return $this;
    }

    /**
     * Gets note's author name.
     *
     * @return string|null
     */
    public function getAuthorName() : ?string
    {
        return $this->authorName;
    }

    /**
     * Sets note's author name.
     *
     * @param string|null $value
     * @return $this
     */
    public function setAuthorName(?string $value) : AbstractNote
    {
        $this->authorName = $value;

        return $this;
    }

    /**
     * Determine if the note was sent to the customer.
     *
     * @return bool
     */
    public function getShouldNotifyCustomer() : bool
    {
        return $this->shouldNotifyCustomer;
    }

    /**
     * Sets if the note was sent to the customer.
     *
     * @param bool $value
     * @return $this
     */
    public function setShouldNotifyCustomer(bool $value) : AbstractNote
    {
        $this->shouldNotifyCustomer = $value;

        return $this;
    }

    /**
     * Determines if note added by the system.
     *
     * @return bool
     */
    public function isAddedBySystem() : bool
    {
        return static::SYSTEM_AUTHOR_NAME === $this->getAuthorName();
    }
}
