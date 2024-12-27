<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\WordPress;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits\CanConvertToWordPressDatabaseArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use WP_Term;

/**
 * Data object for a {@see WP_Term} WordPress term.
 *
 * @method static WpTerm getNewInstance(array $data)
 */
class WpTerm extends AbstractDataObject
{
    use CanConvertToWordPressDatabaseArrayTrait;
    use CanGetNewInstanceTrait;

    /** @var string|null term description (optional) */
    public ?string $description = null;

    /** @var string term name */
    public string $name;

    /** @var int term parent */
    public int $parent = 0;

    /**
     * Constructor.
     *
     * @param array{
     *     description?: string|null,
     *     name: string,
     *     parent?: int,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Converts this class instance into a {@see WP_Term} object from properties.
     *
     * @param WP_Term|null $wpTerm
     * @return WP_Term
     */
    public function toWordPressTerm(?WP_Term $wpTerm) : WP_Term
    {
        if (! $wpTerm) {
            $wpTerm = new WP_Term((object) []);
        }

        // overlays the DTO data over the WordPress term object
        foreach ($this->toDatabaseArray() as $wpProperty => $value) {
            if (null !== $value) {
                $wpTerm->{$wpProperty} = $value;
            }
        }

        return $wpTerm;
    }
}
