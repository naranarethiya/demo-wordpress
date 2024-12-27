<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Contracts\AttachmentAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Models\ImageSize;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\MediaRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WP_Post;

/**
 * Adapts a WordPress image attachment into a native {@see Image} model.
 */
class ImageAdapter implements AttachmentAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WP_Post */
    protected $source;

    /**
     * Constructor.
     *
     * @param WP_Post $image
     */
    public function __construct(WP_Post $image)
    {
        $this->source = $image;
    }

    /**
     * Converts a WordPress image attachment to a native {@see Image} model.
     *
     * @return Image
     * @throws AdapterException
     */
    public function convertFromSource() : Image
    {
        if (! MediaRepository::isImage($this->source)) {
            throw new AdapterException('The source item to adapt is not a valid image.');
        }

        $sizes = [];
        $image = Image::getNewInstance()
            ->setId(TypeHelper::int($this->source->ID, 0))
            ->setName(TypeHelper::string($this->source->post_name ?: '', ''))
            ->setLabel(TypeHelper::string($this->source->post_title ?: '', ''))
            ->setAuthorId(TypeHelper::int($this->source->post_author, 0))
            ->setParentPostId(TypeHelper::int($this->source->post_parent, 0))
            ->setMimeType(TypeHelper::string($this->source->post_mime_type, ''))
            ->setGuid(TypeHelper::string($this->source->guid, ''));

        try {
            foreach ($this->getImageSizesToAdapt() as $imageSize) {
                if ($size = $this->convertSizeFromSource($imageSize)) {
                    $sizes[$size->getId()] = $size;
                }
            }
        } catch (Exception $exception) {
            throw new AdapterException($exception->getMessage(), $exception);
        }

        return $image->setSizes($sizes);
    }

    /**
     * Gets a list of image sizes to adapt.
     *
     * Will always include the 'full' original image size.
     *
     * @return string[]
     * @throws Exception
     */
    protected function getImageSizesToAdapt() : array
    {
        /** @var string[] $imageSizes */
        $imageSizes = ArrayHelper::combine(['full'], array_keys(MediaRepository::getAvailableImageSizes()));

        return array_unique($imageSizes);
    }

    /**
     * Converts a source size into a native {@see ImageSize}.
     *
     * @param string $sizeName
     * @return ImageSize|null
     */
    protected function convertSizeFromSource(string $sizeName) : ?ImageSize
    {
        $sizeData = MediaRepository::getImageSize((int) $this->source->ID, $sizeName);

        return null === $sizeData || ! isset($sizeData[0], $sizeData[1], $sizeData[2]) ? null : ImageSize::getNewInstance()
            ->setId($sizeName)
            ->setUrl((string) $sizeData[0])
            ->setHeight((int) $sizeData[2])
            ->setWidth((int) $sizeData[1]);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // TODO: Implement convertToSource() method.
        return null;
    }
}
