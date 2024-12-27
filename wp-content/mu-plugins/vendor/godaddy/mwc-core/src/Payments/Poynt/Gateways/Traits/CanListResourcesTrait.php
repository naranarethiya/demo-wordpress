<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\Traits;

/**
 * Can list resources trait.
 */
trait CanListResourcesTrait
{
    /** @var int|null modifiedSince parameter */
    public $modifiedSince;

    /** @var int|null offset parameter */
    public $offset;

    /** @var int|null limit parameter */
    public $limit;

    /**
     * Sets the modifiedSince query parameter.
     *
     * @param int $timestamp
     * @return self
     */
    public function modifiedSince(int $timestamp) : self
    {
        $this->modifiedSince = $timestamp;

        return $this;
    }

    /**
     * Sets the offset query parameter.
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset) : self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Sets the limit query parameter.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit) : self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Gets a list of resources to return.
     *
     * @param array $selection
     */
    abstract public function getList(array $selection = []);
}
