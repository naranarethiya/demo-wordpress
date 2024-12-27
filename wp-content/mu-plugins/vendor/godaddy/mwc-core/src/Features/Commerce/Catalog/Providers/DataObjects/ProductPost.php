<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanConvertToWordPressDatabaseArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use stdClass;
use WP_Post;

/**
 * Product post data object.
 *
 * This object is used to overlay {@see ProductBase} data over a WordPress post object or database data.
 * Through {@see ProductPostAdapter} it takes the properties of a {@see ProductBase} to convert it to an object that can be used to create a WordPress post.
 *
 * @method static static getNewInstance(array $data)
 */
class ProductPost extends AbstractDataObject
{
    use CanConvertToWordPressDatabaseArrayTrait;

    /** @var string {@see WP_Post::$post_title} */
    public string $postTitle;

    /** @var string|null {@see WP_Post::$post_content} */
    public ?string $postContent = null;

    /** @var string|null {@see WP_Post::$post_date} */
    public ?string $postDate = null;

    /** @var string|null {@see WP_Post::$post_date_gmt} */
    public ?string $postDateGmt = null;

    /** @var string|null {@see WP_Post::$post_modified} */
    public ?string $postModified = null;

    /** @var string|null {@see WP_Post::$post_modified_gmt} */
    public ?string $postModifiedGmt = null;

    /** @var string|null {@see WP_Post::$post_name} */
    public ?string $postName = null;

    /** @var int|null {@see WP_Post::$post_parent} */
    public ?int $postParent = null;

    /** @var string|null {@see WP_Post::$post_status} */
    public ?string $postStatus = null;

    /** @var string|null {@see WP_Post::$post_type} */
    public ?string $postType = null;

    /**
     * Constructor.
     *
     * @see ProductBase::__construct() for equivalent nullable properties
     *
     * @param array{
     *     postTitle: string,
     *     postContent?: ?string,
     *     postDate?: ?string,
     *     postDateGmt?: ?string,
     *     postModified ?: ?string,
     *     postModifiedGmt?: ?string,
     *     postName?: ?string,
     *     postParent?: ?int,
     *     postStatus?: ?string,
     *     postType?: string,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Maps the DTO data to a WordPress post object.
     *
     * This will overlay the DTO data over the provided WordPress post object.
     *
     * @param WP_Post|null $wpPost if the post is not specified a new one will be instantiated
     * @return WP_Post
     */
    public function toWordPressPost(?WP_Post $wpPost = null) : WP_Post
    {
        if (! $wpPost) {
            $wpPost = new WP_Post((object) []);
        }

        // overlays the DTO data over the WordPress post object
        foreach ($this->toDatabaseArray() as $wpProperty => $value) {
            if (null !== $value) {
                $wpPost->{$wpProperty} = $value;
            }
        }

        // force publish status if the post has a password ("password protected")
        if (! empty($wpPost->post_password)) {
            $wpPost->post_status = 'publish';
        }

        return $wpPost;
    }

    /**
     * Converts the object to a stdClass with snake-case properties.
     *
     * This will result in an object that is compatible with the output of a WPDB wp_posts row as an object.
     *
     * @param stdClass|null $object optional object to overlay data upon
     * @return stdClass
     */
    public function toDatabaseObject(?stdClass $object = null) : stdClass
    {
        if (! $object) {
            $object = new stdClass();
        }

        return (object) $this->toDatabaseArray((array) $object);
    }
}
