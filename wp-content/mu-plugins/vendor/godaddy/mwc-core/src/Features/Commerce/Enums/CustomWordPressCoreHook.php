<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

/**
 * Custom hooks that have been added to the GoDaddy version of WordPress core.
 * This allows us to see all our modifications easily in one place.
 * These hooks are added to WordPress from a patch file via @link https://github.com/gdcorp-partners/wordpress-patches.
 *
 * Constant (case) naming convention:
 *  - PascalCase.
 *  - Roughly match the name of the hook, but without the "godaddy" prefix.
 *  - Use underscores as a namespace separator
 *  - e.g. "FunctionName_HookName"
 *
 * Hook (value) naming convention:
 *  - Namespaced with slashes.
 *  - Always start with `godaddy` prefix, to help us identify custom hooks that we own that are not in normal WordPress.
 *  - Include the function or method name that the hook resides in.
 *  - Include the actual filter/action name last.
 *  - e.g. filter: "godaddy/{function name}/{thing being filtered}"
 *          "godaddy/prime_post_caches/posts" <-- the "posts" are what's being filtered
 *  - e.g. action: "godaddy/{function name}/{when the action triggers}"
 *          "godaddy/wp_insert_post/before_get_post_instance" <-- the action fires before a "get post instance" call
 *
 * Docblock convention:
 *  - First word should note if the hook is an "Action" or "Filter".
 *  - Briefly explain when the hook is executed and how it benefits us.
 *  - Cross-reference the function or method where the hook is fired.
 */
class CustomWordPressCoreHook
{
    use EnumTrait;

    /**
     * Filter applied to array of posts retrieved directly from the database, which allows us to modify post
     * data (usually from queries) right before it's cached.
     * @see _prime_post_caches()
     */
    public const PrimePostCaches_Posts = 'godaddy/prime_post_caches/posts';

    /**
     * Filter applied to array of terms retrieved directly from the database, which allows us to modify term
     * data (usually from queries) right before it's cached.
     * @see _prime_term_caches()
     */
    public const PrimeTermCaches_Terms = 'godaddy/prime_term_caches/terms';

    /**
     * Filter applied to metadata that's retrieved from the database, which allows us to make modifications
     * right before it's cached.
     * @see update_meta_cache()
     */
    public const UpdateMetaCache = 'godaddy/update_meta_cache';

    /**
     * Action that triggers right before a `get_post()` call, after inserting a new post. This allows us
     * to temporarily disable reads right before the call, which prevents bugs that occur while reading while
     * in the process of a write.
     * @see wp_insert_post()
     */
    public const WpInsertPost_BeforeGetPostInstance = 'godaddy/wp_insert_post/before_get_post_instance';

    /**
     * Action that triggers right after a `get_post()` call, after inserting a new post. This allows us
     * to re-enable reads right after the call.
     * @see wp_insert_post()
     */
    public const WpInsertPost_AfterGetPostInstance = 'godaddy/wp_insert_post/after_get_post_instance';

    /**
     * Filter applied right after reading a post from the database, which allows us to modify the data.
     * @see \WP_Post::get_instance()
     */
    public const WpPost_GetInstance = 'godaddy/wp_post/get_instance';

    /**
     * Action that triggers before `get_post()` is called, which allows us to pre-emptively prime post caches for
     * improved performance.
     * @see \WP_Query::get_posts()
     */
    public const WpQuery_BeforeGetPost = 'godaddy/wp_query/get_posts/before_get_post';
}
