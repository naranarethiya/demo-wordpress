<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\TaxonomyTermAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\TaxonomyContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\TaxonomyTermContact;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermDeleteFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermReadFailedException;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomies\Terms\Exceptions\TermUpdateFailedException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;
use WP_Term;

/**
 * A base abstract class for taxonomy term models.
 *
 * @property ?string $name the term slug in WordPress
 * @property ?string $label the term display name in WordPress
 * @method static static getNewInstance(TaxonomyContract $taxonomy, array $properties = [])
 * @method $this setProperties(array $properties)
 */
class Term extends AbstractModel implements TaxonomyTermContact
{
    use CanBulkAssignPropertiesTrait;
    use HasNumericIdentifierTrait;
    use HasLabelTrait;

    /** @var string the term's description */
    protected string $description = '';

    /** @var int|null ID of the parent term */
    protected ?int $parentId = null;

    /** @var TaxonomyContract */
    protected TaxonomyContract $taxonomy;

    /**
     * Term constructor.
     *
     * @param TaxonomyContract $taxonomy
     * @param array<string, mixed> $properties
     */
    public function __construct(TaxonomyContract $taxonomy, array $properties = [])
    {
        $this->setTaxonomy($taxonomy);
        $this->setProperties($properties);
    }

    /**
     * Determines if the term exists.
     *
     * @return bool
     */
    public function exists() : bool
    {
        return ! empty($this->getId());
    }

    /**
     * Gets a term instance.
     *
     * @NOTE if querying a term only by its ID without specifying a taxonomy, you should check if the taxonomy is the expected type as any term with the given ID will be returned, if found {unfulvio 2022-09-08}
     *
     * @param int|string $identifier term ID (integer) or slug (string)
     * @param TaxonomyContract|null $taxonomy taxonomy is required if the identifier is a string/slug
     * @return Term|null
     */
    public static function get($identifier, ?TaxonomyContract $taxonomy = null) : ?Term
    {
        $term = TermsRepository::getTerm($identifier, $taxonomy ? $taxonomy->getName() : '');

        if ($term instanceof WP_Term) {
            return TaxonomyTermAdapter::getNewInstance($term)->convertFromSource();
        }

        return null;
    }

    /**
     * Gets a term by its ID.
     *
     * @param int $id
     * @return Term|null
     */
    public static function getById(int $id) : ?Term
    {
        return static::get($id);
    }

    /**
     * Gets a term by its name (WordPress slug).
     *
     * @param string $name
     * @param TaxonomyContract $taxonomy
     * @return Term|null
     */
    public static function getByName(string $name, TaxonomyContract $taxonomy) : ?Term
    {
        return static::get($name, $taxonomy);
    }

    /**
     * Creates a new term.
     *
     * @param array<string, mixed> $data
     * @return Term
     * @throws TermCreateFailedException|TermReadFailedException
     */
    public static function create(array $data = []) : Term
    {
        $taxonomy = $data['taxonomy'] ?? null;

        if (! $taxonomy instanceof TaxonomyContract) {
            throw new TermCreateFailedException('A taxonomy is required when creating a term.');
        }

        $term = Term::getNewInstance($taxonomy, $data);
        $args = static::getUpsertArguments($term);

        try {
            $termId = TermsRepository::insertTerm(TypeHelper::string($term->getLabel() ?: $term->getName(), ''), $term->getTaxonomy()->getName(), $args);
        } catch (Exception $exception) {
            throw new TermCreateFailedException(sprintf('Failed to create term %1$s for taxonomy %2$s: %3$s', $term->getName(), $term->getTaxonomy()->getName(), $exception->getMessage()), $exception);
        }

        $wpTerm = TermsRepository::getTerm($termId, $term->getTaxonomy()->getName());

        if (! $wpTerm instanceof WP_Term) {
            throw new TermReadFailedException(sprintf('Failed to read the term %1$s for taxonomy %2$s after successful insert.', $term->getName(), $term->getTaxonomy()->getName()));
        }

        return TaxonomyTermAdapter::getNewInstance($wpTerm)->convertFromSource();
    }

    /**
     * Updates the term.
     *
     * @return $this
     * @throws TermUpdateFailedException
     */
    public function update() : Term
    {
        if (! $this->exists() || ! $this->getId()) {
            throw new TermUpdateFailedException('Cannot update a term without an ID.');
        }

        $args = static::getUpsertArguments($this);

        try {
            TermsRepository::updateTerm($this->getId(), $this->getTaxonomy()->getName(), $args);
        } catch (Exception $exception) {
            throw new TermUpdateFailedException(sprintf('Failed to update term %1$s for taxonomy %2$s: %3$s', $this->getName(), $this->getTaxonomy()->getName(), $exception->getMessage()), $exception);
        }

        return $this;
    }

    /**
     * Builds the WordPress-style arguments to be used when creating or updating a term.
     *
     * @param Term $term
     * @return array{
     *     parent?: int,
     *     description?: string,
     *     slug?: string
     * }
     */
    protected static function getUpsertArguments(Term $term) : array
    {
        $args = [];

        if ($parentId = $term->getParentId()) {
            $args['parent'] = $parentId;
        }

        if ($description = $term->getDescription()) {
            $args['description'] = $description;
        }

        if ($name = $term->getName()) {
            $args['slug'] = $name;
        }

        return $args;
    }

    /**
     * Saves the term.
     *
     * @return Term|$this
     * @throws TermCreateFailedException|TermUpdateFailedException|TermReadFailedException
     */
    public function save() : Term
    {
        if ($this->exists()) {
            return $this->update();
        }

        $term = static::create([
            'taxonomy'    => $this->getTaxonomy(),
            'name'        => $this->getName(),
            'label'       => $this->getLabel(),
            'description' => $this->getDescription(),
            'parentId'    => $this->getParentId(),
        ]);

        return $this->setPropertiesFromObject($term);
    }

    /**
     * Sets a different instance property values to the current instance.
     *
     * This will sync that object's term data with the current instance such as when the non-static {@see Term::save()} creates a new instance.
     *
     * @param Term $term
     * @return $this
     */
    protected function setPropertiesFromObject(Term $term) : Term
    {
        $termData = $term->toArray();
        // we need to convert the taxonomy back into an object as the toArray method will convert it into an array format
        $termData['taxonomy'] = $term->getTaxonomy();

        return $this->setProperties($termData);
    }

    /**
     * Deletes the term.
     *
     * @return void
     * @throws TermDeleteFailedException
     */
    public function delete() : void
    {
        if (! $this->exists() || ! $this->getId()) {
            throw new TermDeleteFailedException('Cannot delete a term without an ID.');
        }

        try {
            TermsRepository::deleteTerm($this->getId(), $this->getTaxonomy()->getName());
        } catch (Exception $exception) {
            throw new TermDeleteFailedException(sprintf('Failed to delete term %1$s for taxonomy %2$s: %3$s', $this->getName(), $this->getTaxonomy()->getName(), $exception->getMessage()), $exception);
        }
    }

    /**
     * Gets the term description.
     *
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * Sets the term description.
     *
     * @param string $value
     * @return $this
     */
    public function setDescription(string $value) : Term
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Gets the ID of the parent term.
     *
     * @return int|null
     */
    public function getParentId() : ?int
    {
        return $this->parentId;
    }

    /**
     * Gets the term children.
     *
     * @return Term[]
     * @throws TermReadFailedException
     */
    public function getChildren() : array
    {
        if (! $termId = $this->getId()) {
            return [];
        }

        $childTerms = [];

        try {
            foreach (TermsRepository::getTermChildren($termId, $this->getTaxonomy()->getName()) as $term) {
                $childTerms[] = TaxonomyTermAdapter::getNewInstance($term)->convertFromSource();
            }
        } catch (Exception $exception) {
            throw new TermReadFailedException(sprintf('Failed to get term %1$s children for taxonomy %2$s: %3$s', $this->getName(), $this->getTaxonomy()->getName(), $exception->getMessage()), $exception);
        }

        return $childTerms;
    }

    /**
     * Sets the ID of the parent term.
     *
     * @param int|null $value
     * @return $this
     */
    public function setParentId(?int $value) : Term
    {
        $this->parentId = $value;

        return $this;
    }

    /**
     * Gets the term taxonomy.
     *
     * @return TaxonomyContract
     */
    public function getTaxonomy() : TaxonomyContract
    {
        return $this->taxonomy;
    }

    /**
     * Sets the term taxonomy.
     *
     * @param TaxonomyContract $value
     * @return $this
     */
    public function setTaxonomy(TaxonomyContract $value) : Term
    {
        $this->taxonomy = $value;

        return $this;
    }
}
