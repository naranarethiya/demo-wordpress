<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WP_Error;
use WP_Post;

/**
 * Repository to handle WordPress media, such as attachments and images.
 */
class MediaRepository
{
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';

    /**
     * Determines whether an item is a WordPress image attachment.
     *
     * @param WP_Post|mixed $item
     * @return bool
     */
    public static function isImage($item) : bool
    {
        return $item instanceof WP_Post
            && 'attachment' === $item->post_type
            // checks the mime type instead of using wp_attachment_is() as there may not be a local file.
            && self::TYPE_IMAGE === strstr($item->post_mime_type, '/', true);
    }

    /**
     * Determines whether an item is a WordPress video attachment.
     *
     * @param WP_Post|mixed $item
     * @return bool
     */
    public static function isVideo($item) : bool
    {
        return $item instanceof WP_Post
            && 'attachment' === $item->post_type
            // checks the mime type instead of using wp_attachment_is() as there may not be a local file.
            && self::TYPE_VIDEO === strstr($item->post_mime_type, '/', true);
    }

    /**
     * Gets a WordPress attachment object.
     *
     * @param int $id
     * @return WP_Post|null
     */
    public static function get(int $id) : ?WP_Post
    {
        $attachment = get_post($id);

        return is_object($attachment) && (static::isImage($attachment) || static::isVideo($attachment)) ? $attachment : null;
    }

    /**
     * Inserts an attachment into the local database.
     *
     * @param array{
     *     postParentId?: int,
     *     authorId?: int,
     *     name: string,
     *     label?: string,
     *     mimeType?: string,
     *     guid?: string,
     * } $args
     * @return int
     * @throws WordPressRepositoryException
     */
    public static function insert(array $args) : int
    {
        $fileName = ArrayHelper::getStringValueForKey($args, 'name');
        if (empty($fileName)) {
            throw new WordPressRepositoryException('Cannot create an attachment with an empty file name.');
        }

        $attachmentId = wp_insert_attachment([
            'post_author'    => ArrayHelper::getIntValueForKey($args, 'authorId'),
            'post_title'     => ArrayHelper::getStringValueForKey($args, 'label'),
            'post_mime_type' => ArrayHelper::getStringValueForKey($args, 'mimeType'),
            'guid'           => ArrayHelper::getStringValueForKey($args, 'guid'),
        ], $fileName, ArrayHelper::getIntValueForKey($args, 'postParentId'), true);

        if (WordPressRepository::isError($attachmentId)) {
            throw new WordPressRepositoryException('Failed to insert attachment.');
        }

        return $attachmentId;
    }

    /**
     * Updates an attachment in the local database.
     *
     * @param int|WP_Post $identifier
     * @param array{
     *     parentPostId?: int,
     *     authorId?: int,
     *     name?: string,
     *     label?: string,
     *     mimeType?: string,
     *     guid?: string,
     *  } $args
     * @return void
     * @throws WordPressRepositoryException
     */
    public static function update($identifier, array $args = []) : void
    {
        if ($identifier instanceof WP_Post) {
            $attachmentId = $identifier->ID;
        } else {
            $attachmentId = TypeHelper::int($identifier, 0);
        }

        $update = wp_update_post([
            'ID'             => $attachmentId,
            'post_parent'    => ArrayHelper::getIntValueForKey($args, 'parentPostId'),
            'post_author'    => ArrayHelper::getIntValueForKey($args, 'authorId'),
            'post_name'      => ArrayHelper::getStringValueForKey($args, 'name'),
            'post_title'     => ArrayHelper::getStringValueForKey($args, 'label'),
            'post_mime_type' => ArrayHelper::getStringValueForKey($args, 'mimeType'),
            'guid'           => ArrayHelper::getStringValueForKey($args, 'guid'),
        ], true);

        if (WordPressRepository::isError($update)) {
            /** @var WP_Error $error */
            $error = $update;

            throw new WordPressRepositoryException(sprintf('Could not update attachment %1$s: %2$s', $attachmentId, $error->get_error_message()));
        }
    }

    /**
     * Deletes an attachment from the local database.
     *
     * @param int|WP_Post $identifier
     * @return void
     * @throws WordPressRepositoryException
     */
    public static function delete($identifier) : void
    {
        if ($identifier instanceof WP_Post) {
            $attachmentId = $identifier->ID;
        } else {
            $attachmentId = TypeHelper::int($identifier, 0);
        }

        $result = wp_delete_post($attachmentId);

        if (empty($result)) {
            throw new WordPressRepositoryException(sprintf('Could not delete attachment %1$s.', $attachmentId));
        }
    }

    /**
     * Gets WordPress available image sizes.
     *
     * @return array<string, array<string, int|bool>> image size data
     */
    public static function getAvailableImageSizes() : array
    {
        $imageSizes = wp_get_registered_image_subsizes();

        return ArrayHelper::accessible($imageSizes) ? $imageSizes : [];
    }

    /**
     * Gets size data for an image.
     *
     * @param int $identifier
     * @param string $sizeName
     * @return array<bool|int|string>|null
     */
    public static function getImageSize(int $identifier, string $sizeName) : ?array
    {
        $imageData = wp_get_attachment_image_src($identifier, $sizeName);

        /* @phpstan-ignore-next-line */
        return ArrayHelper::accessible($imageData) ? $imageData : null;
    }
}
