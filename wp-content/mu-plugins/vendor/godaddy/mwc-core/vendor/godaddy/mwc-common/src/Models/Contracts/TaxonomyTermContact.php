<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;

/**
 * A contract for taxonomy terms.
 */
interface TaxonomyTermContact extends ModelContract, HasLabelContract, HasNumericIdentifierContract
{
    /**
     * Gets the term description.
     *
     * @return string
     */
    public function getDescription() : string;

    /**
     * Sets the term description.
     *
     * @param string $value
     * @return $this
     */
    public function setDescription(string $value) : TaxonomyTermContact;

    /**
     * Gets the ID of the parent term.
     *
     * @return int|null
     */
    public function getParentId() : ?int;

    /**
     * Sets the ID of the parent term.
     *
     * @param int|null $value
     * @return $this
     */
    public function setParentId(?int $value) : TaxonomyTermContact;

    /**
     * Gets the term taxonomy.
     *
     * @return TaxonomyContract
     */
    public function getTaxonomy() : TaxonomyContract;

    /**
     * Sets the term taxonomy.
     *
     * @param TaxonomyContract $value
     * @return $this
     */
    public function setTaxonomy(TaxonomyContract $value);
}
