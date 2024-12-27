<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\TaxonomyTermContact;
use GoDaddy\WordPress\MWC\Common\Models\Taxonomy;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WP_Term;

/**
 * Adapter for taxonomy terms.
 *
 * @method static static getNewInstance(WP_Term $term)
 */
class TaxonomyTermAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WP_Term */
    protected $source;

    /**
     * Constructor.
     *
     * @param WP_Term $term
     */
    public function __construct(WP_Term $term)
    {
        $this->source = $term;
    }

    /**
     * Converts a source taxonomy term to a native taxonomy term.
     *
     * @return Term
     */
    public function convertFromSource() : Term
    {
        return Term::getNewInstance(Taxonomy::getNewInstance()->setName($this->source->taxonomy ?: ''))
            ->setId((int) $this->source->term_id)
            ->setLabel($this->source->name ?: '')
            ->setName($this->source->slug ?: '')
            ->setDescription($this->source->description ?: '')
            ->setParentId((int) $this->source->parent);
    }

    /**
     * Converts a native taxonomy term to a source taxonomy term.
     *
     * @param TaxonomyTermContact|null $term
     * @return WP_Term
     */
    public function convertToSource(?TaxonomyTermContact $term = null) : WP_Term
    {
        if (null === $term) {
            $termData = $this->source;
        } else {
            $termData = (object) [
                'term_id'     => (int) $term->getId(),
                'name'        => $term->getLabel() ?: '',
                'slug'        => $term->getName() ?: '',
                'taxonomy'    => $term->getTaxonomy()->getName() ?: '',
                'description' => $term->getDescription() ?: '',
                'parent'      => (int) $term->getParentId(),
            ];
        }

        return $this->getNewSourceTermInstance($termData);
    }

    /**
     * Gets a new instance of a source term object.
     *
     * @param object|mixed $termData
     * @return WP_Term
     */
    protected function getNewSourceTermInstance($termData) : WP_Term
    {
        return new WP_Term($termData);
    }
}
