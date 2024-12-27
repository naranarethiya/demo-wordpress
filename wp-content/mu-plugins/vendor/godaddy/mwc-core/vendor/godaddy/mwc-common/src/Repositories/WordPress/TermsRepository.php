<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WP_Error;
use WP_Term;

/**
 * A repository for handling WordPress terms & taxonomies.
 */
class TermsRepository
{
    /**
     * Gets a {@see WP_Term} from an ID or slug, for a given taxonomy.
     *
     * @param int|string $identifier term ID or slug
     * @param string $taxonomy the name/slug of the taxonomy the term belongs to - required if retrieving by slug
     * @return WP_Term|null
     */
    public static function getTerm($identifier, string $taxonomy = '') : ?WP_Term
    {
        $term = null;

        if (is_int($identifier)) {
            $term = get_term($identifier, $taxonomy);
        } elseif (is_string($identifier)) {
            $term = get_term_by('slug', $identifier, $taxonomy);
        }

        return $term instanceof WP_Term ? $term : null;
    }

    /**
     * Inserts a {@see WP_Term} into the local database.
     *
     * @param string $label
     * @param string $taxonomy
     * @param array{
     *     alias_of?: string,
     *     description?: string,
     *     parent?: int,
     *     slug?: string
     * } $args
     * @return int the inserted term ID
     * @throws WordPressRepositoryException
     */
    public static function insertTerm(string $label, string $taxonomy, array $args = []) : int
    {
        if (empty($label)) {
            throw new WordPressRepositoryException('Cannot create a term with an empty label.');
        }

        if (empty($taxonomy)) {
            throw new WordPressRepositoryException('A valid taxonomy is required when creating a term.');
        }

        $termData = wp_insert_term($label, $taxonomy, $args);

        if (WordPressRepository::isError($termData)) {
            /** @var WP_Error $error */
            $error = $termData;

            throw new WordPressRepositoryException(sprintf('Could not create term %1$s for taxonomy %2$s: %3$s', $label, $taxonomy, $error->get_error_message()));
        }

        /** @var array<string, mixed> $termData */
        $termId = TypeHelper::int(ArrayHelper::get($termData, 'term_id'), 0);

        if (! $termId) {
            throw new WordPressRepositoryException('Failed to insert term.');
        }

        return $termId;
    }

    /**
     * Updates a {@see WP_Term} in the local database.
     *
     * @param int|string|WP_Term $identifier
     * @param string|null $taxonomy required if the identifier is a term name string
     * @param array{
     *      alias_of?: string,
     *      description?: string,
     *      parent?: int,
     *      slug?: string
     *  } $args
     * @return void
     * @throws WordPressRepositoryException
     */
    public static function updateTerm($identifier, ?string $taxonomy, array $args = [])
    {
        if ($identifier instanceof WP_Term) {
            $termId = $identifier->term_id;
            $taxonomy = $identifier->taxonomy;
        } else {
            $taxonomy = $taxonomy ?: '';
            $term = static::getTerm($identifier, $taxonomy);
            $termId = TypeHelper::int($term ? $term->term_id : 0, 0);
        }

        $termData = wp_update_term($termId, $taxonomy, $args);

        if (WordPressRepository::isError($termData)) {
            /** @var WP_Error $error */
            $error = $termData;

            throw new WordPressRepositoryException(sprintf('Could not update term %1$s for taxonomy %2$s: %3$s', $termId, $taxonomy, $error->get_error_message()));
        }
    }

    /**
     * Deletes a {@see WP_Term} from the local database.
     *
     * @param int|string|WP_Term $identifier term ID, slug or term object
     * @param string|null $taxonomy required if the identifier is a term name string
     * @return void
     * @throws WordPressRepositoryException
     */
    public static function deleteTerm($identifier, ?string $taxonomy)
    {
        if ($identifier instanceof WP_Term) {
            $termId = $identifier->term_id;
            $taxonomy = $identifier->taxonomy;
        } else {
            $taxonomy = $taxonomy ?: '';
            $term = static::getTerm($identifier, $taxonomy);
            $termId = TypeHelper::int($term ? $term->term_id : 0, 0);
        }

        $result = wp_delete_term($termId, $taxonomy);

        if (WordPressRepository::isError($result)) {
            /** @var WP_Error $error */
            $error = $result;

            throw new WordPressRepositoryException(sprintf('Could not delete term %1$s for taxonomy %2$s: %3$s', $termId, $taxonomy, $error->get_error_message()));
        }

        if (! $result) {
            throw new WordPressRepositoryException(sprintf('Could not delete term %1$s for taxonomy %2$s: term does not exist', $termId, $taxonomy));
        }
    }

    /**
     * Gets WordPress terms based on query arguments.
     *
     * @link https://developer.wordpress.org/reference/classes/wp_term_query/__construct/ for accepted args
     *
     * @param array{
     *     taxonomy?: array<string>|string,
     *     object_ids?: array<int>|int,
     *     orderby?: string,
     *     order?: string,
     *     hide_empty?: bool|int,
     *     include?: array<int>|string,
     *     exclude?: array<int>|string,
     *     exclude_tree?: array<int>|string
     * } $args
     * @return WP_Term[]|int[]|string[]
     * @throws WordPressRepositoryException
     */
    public static function getTerms(array $args) : array
    {
        $terms = get_terms($args);

        if (WordPressRepository::isError($terms)) {
            /** @var WP_Error $error */
            $error = $terms;

            throw new WordPressRepositoryException($error->get_error_message());
        }

        /** @var WP_Term[]|int[]|string[] $terms */
        $terms = ArrayHelper::wrap($terms);

        return $terms;
    }

    /**
     * Gets the children of a given taxonomy term.
     *
     * @param int $termId
     * @param string $taxonomy
     * @return WP_Term[]
     * @throws WordPressRepositoryException
     */
    public static function getTermChildren(int $termId, string $taxonomy) : array
    {
        $childrenIds = get_term_children($termId, $taxonomy);

        if (WordPressRepository::isError($childrenIds)) {
            /** @var WP_Error $error */
            $error = $childrenIds;

            throw new WordPressRepositoryException($error->get_error_message());
        }

        if (empty($childrenIds)) {
            return [];
        }

        /** @var WP_Term[] $terms */
        $terms = static::getTerms([
            'object_ids' => TypeHelper::arrayOfIntegers($childrenIds),
            'taxonomy'   => $taxonomy,
        ]);

        return $terms;
    }
}
