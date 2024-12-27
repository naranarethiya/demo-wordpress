<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\ReviewAdapter;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Models\Review;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WP_Comment;

/**
 * A WordPress interceptor to hook on review actions.
 */
class ReviewInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('comment_post')
            ->setHandler([$this, 'onCommentPost'])
            ->execute();

        Register::action()
            ->setGroup('transition_comment_status')
            ->setHandler([$this, 'onTransitionCommentStatus'])
            ->setArgumentsCount(3)
            ->execute();
    }

    /**
     * Handles the WordPress comments on a post.
     *
     * This can represent a product review, which is the one we're interested.
     *
     * @internal
     *
     * @param int $commentId
     *
     * @throws Exception
     */
    public function onCommentPost($commentId)
    {
        $wpReview = WordPressRepository::getComment($commentId);
        $review = $wpReview ? $this->convertReview($wpReview) : null;

        if ($review) {
            $review->save();
        }
    }

    /**
     * Handles the WordPress comments status transition.
     *
     * @internal
     *
     * @param int|string $newStatus
     * @param int|string $oldStatus
     * @param WP_Comment $wpReview
     *
     * @throws Exception
     */
    public function onTransitionCommentStatus($newStatus, $oldStatus, $wpReview)
    {
        if (! $review = $wpReview ? $this->convertReview($wpReview) : null) {
            return;
        }

        switch ($newStatus) {
            case 'approved':
            case 'unapproved':
                $review->update();
                break;

            case 'trash':
            case 'spam':
                $review->delete();
        }
    }

    /**
     * Converts a WordPress comment to a native review object.
     *
     * @param WP_Comment $wpComment
     * @return Review
     * @throws Exception
     */
    protected function convertReview(WP_Comment $wpComment)
    {
        return (new ReviewAdapter($wpComment))->convertFromSource();
    }
}
