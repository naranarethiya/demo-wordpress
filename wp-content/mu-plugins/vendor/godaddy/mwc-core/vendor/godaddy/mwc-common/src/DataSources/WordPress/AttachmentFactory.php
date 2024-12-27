<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Contracts\AttachmentAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractAttachment;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\MediaRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WP_Post;

/**
 * Factory for retrieving attachment models.
 */
class AttachmentFactory
{
    use CanGetNewInstanceTrait;

    /**
     * Gets an attachment model.
     *
     * @param int $identifier
     * @return AbstractAttachment|null
     */
    public function getAttachment(int $identifier) : ?AbstractAttachment
    {
        if (! $source = MediaRepository::get($identifier)) {
            return null;
        }

        try {
            return $this->maybeAdaptAttachment($source);
        } catch (AdapterException $exception) {
            return null;
        }
    }

    /**
     * Maybe adapts the attachment to its corresponding model instance.
     *
     * @param WP_Post $attachment
     * @return AbstractAttachment
     * @throws AdapterException
     */
    protected function maybeAdaptAttachment(WP_Post $attachment) : AbstractAttachment
    {
        /** @var class-string<AttachmentAdapterContract>|null $adapter */
        $adapter = $this->getAdapterForMimeType($attachment->post_mime_type);

        if (! $adapter) {
            throw new AdapterException(sprintf('Adapter missing or invalid for %s mime type', $attachment->post_mime_type));
        }

        return $adapter::getNewInstance($attachment)->convertFromSource();
    }

    /**
     * Gets the adapter for a given attachment mime type.
     *
     * @param string $mimeType
     * @return string|null
     */
    protected function getAdapterForMimeType(string $mimeType) : ?string
    {
        $mediaType = strstr($mimeType, '/', true);

        return TypeHelper::string(Configuration::get('wordpress.media.adapters.'.$mediaType), '') ?: null;
    }
}
