<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\AttachmentFactory;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\AttachmentCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\AttachmentDeleteFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\AttachmentReadFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\AttachmentUpdateFailedException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\MediaRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * A model for handling attachments.
 *
 * @method static static getNewInstance(array $properties = [])
 */
abstract class AbstractAttachment extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use CanGetNewInstanceTrait;
    use HasLabelTrait;
    use HasNumericIdentifierTrait;

    /** @var int ID of the user who authored the attachment */
    protected int $authorId = 0;

    /** @var string GUID */
    protected string $guid = '';

    /** @var string attachment MIME type */
    protected string $mimeType = '';

    /** @var int ID of the post parent */
    protected int $parentPostId = 0;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        $this->setProperties($properties);
    }

    /**
     * Determines if the attachment exists.
     *
     * @return bool
     * @phpstan-assert-if-true !null $this->getId()
     */
    public function exists() : bool
    {
        return ! empty($this->getId());
    }

    /**
     * Gets the post author ID.
     *
     * @return int
     */
    public function getAuthorId() : int
    {
        return $this->authorId;
    }

    /**
     * Sets the post author ID.
     *
     * @param int $value
     * @return $this
     */
    public function setAuthorId(int $value) : AbstractAttachment
    {
        $this->authorId = $value;

        return $this;
    }

    /**
     * Gets the post guid.
     *
     * @return string
     */
    public function getGuid() : string
    {
        return $this->guid;
    }

    /**
     * Sets the post guid.
     *
     * @param string $value
     * @return $this
     */
    public function setGuid(string $value) : AbstractAttachment
    {
        $this->guid = $value;

        return $this;
    }

    /**
     * Gets the mime type for the attachment.
     *
     * @return string
     */
    public function getMimeType() : string
    {
        return $this->mimeType;
    }

    /**
     * Sets the mime type for the attachment.
     *
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType(string $mimeType) : AbstractAttachment
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Gets the parent post ID for the attachment (if set).
     *
     * @return int
     */
    public function getParentPostId() : int
    {
        return $this->parentPostId;
    }

    /**
     * Sets the parent post ID for the attachment.
     *
     * @param int $value
     * @return $this
     */
    public function setParentPostId(int $value) : AbstractAttachment
    {
        $this->parentPostId = $value;

        return $this;
    }

    /**
     * Fetches an attachment by its identifier.
     *
     * @param int $identifier Attachment ID
     * @return AbstractAttachment|null
     */
    public static function get($identifier)
    {
        return AttachmentFactory::getNewInstance()->getAttachment($identifier);
    }

    /**
     * Updates the attachment.
     *
     * @return $this
     * @throws AttachmentUpdateFailedException
     */
    public function update() : AbstractAttachment
    {
        if (! $this->exists()) {
            throw new AttachmentUpdateFailedException('Cannot update an attachment without an ID.');
        }

        try {
            MediaRepository::update($this->getId(), [
                'parentPostId' => $this->getParentPostId(),
                'authorId'     => $this->getAuthorId(),
                'name'         => $this->getName(),
                'label'        => $this->getLabel(),
                'mimeType'     => $this->getMimeType(),
                'guid'         => $this->getGuid(),
            ]);
        } catch (Exception $exception) {
            throw new AttachmentUpdateFailedException(sprintf('Failed to update attachment %1$s: %2$s', $this->getId(), $exception->getMessage()), $exception);
        }

        return $this;
    }

    /**
     * Saves the attachment record.
     *
     * @return AbstractAttachment
     * @throws AttachmentCreateFailedException|AttachmentUpdateFailedException|AttachmentReadFailedException
     */
    public function save() : AbstractAttachment
    {
        if ($this->exists()) {
            return $this->update();
        }

        try {
            $attachmentId = MediaRepository::insert([
                'postParentId' => $this->getParentPostId(),
                'authorId'     => $this->getAuthorId(),
                'name'         => $this->getName(),
                'label'        => $this->getLabel(),
                'mimeType'     => $this->getMimeType(),
                'guid'         => $this->getGuid(),
            ]);
        } catch(Exception $exception) {
            throw new AttachmentCreateFailedException(sprintf('Failed to create attachment: %s', $exception->getMessage()), $exception);
        }

        $attachment = static::get($attachmentId);
        if (! $attachment) {
            throw new AttachmentReadFailedException('Failed to read the attachment after successful insert.');
        }

        return $this->setProperties($attachment->toArray());
    }

    /**
     * Deletes the attachment record.
     *
     * @return void
     * @throws AttachmentDeleteFailedException
     */
    public function delete() : void
    {
        if (! $this->exists()) {
            throw new AttachmentDeleteFailedException('Cannot delete an attachment without an ID.');
        }

        try {
            MediaRepository::delete($this->getId());
        } catch (Exception $exception) {
            throw new AttachmentDeleteFailedException(sprintf('Failed to delete attachment %1$s: %2$s', $this->getId(), $exception->getMessage()), $exception);
        }
    }
}
