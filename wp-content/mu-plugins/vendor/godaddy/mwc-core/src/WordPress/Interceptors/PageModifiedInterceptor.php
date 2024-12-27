<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\Enums\EventBridgeEventActionEnum;
use GoDaddy\WordPress\MWC\Core\Events\PostTypeEvent;
use WP_Post;

class PageModifiedInterceptor extends AbstractInterceptor
{
    protected const POST_TYPE = 'page';

    /**
     * {@inheritDoc}
     */
    public function addHooks() : void
    {
        try {
            Register::action()
                    ->setGroup('save_post_'.static::POST_TYPE)
                    ->setHandler([$this, 'handlePageSavedEvent'])
                    ->setPriority(PHP_INT_MIN)
                    ->setArgumentsCount(3)
                    ->execute();
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }

    /**
     * Returns true if the provided post is a valid post type.
     *
     * @param mixed $post
     *
     * @return bool True if this is a valid event, otherwise false.
     * @phpstan-assert-if-true WP_Post $post
     */
    protected function isValidEvent($post) : bool
    {
        return $post instanceof WP_Post && $post->post_type === static::POST_TYPE && $post->post_status !== 'auto-draft';
    }

    /**
     * Builds the post type event, setting the action based on if it is an update, or not.
     *
     * @param mixed  $update     the "update" argument from the post event.
     * @param string $postStatus The post status to which this post was set.
     *
     * @return PostTypeEvent
     */
    protected function buildPostTypeEvent($update, string $postStatus) : PostTypeEvent
    {
        $action = $update ? EventBridgeEventActionEnum::Update : EventBridgeEventActionEnum::Create;

        return PostTypeEvent::getNewInstance(static::POST_TYPE, $postStatus, $action);
    }

    /**
     * Handles the page saved event.
     *
     * @param mixed $postId the post Id.
     * @param mixed $post   The object that was saved.
     * @param mixed $update Whether the post was updated or created.
     *
     * @return void
     */
    public function handlePageSavedEvent($postId, $post, $update) : void
    {
        if ($this->isValidEvent($post)) {
            Events::broadcast(
                $this->buildPostTypeEvent(
                    (bool) StringHelper::ensureScalar($update),
                    TypeHelper::string($post->post_status, 'unknown')
                ),
            );
        }
    }
}
