<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;

/**
 * Trait for converting a DTO to an array that is compatible with the output of a WPDB wp_posts row as array data.
 */
trait CanConvertToWordPressDatabaseArrayTrait
{
    /**
     * Converts the object to array with snake-case properties.
     *
     * This will result in data that is compatible with the output of a WPDB wp_posts row as array data.
     *
     * @param array<string, mixed>|null $data optional array to overlay data upon
     * @return array<string, mixed>
     */
    public function toDatabaseArray(?array $data = null) : array
    {
        if (! $data) {
            $data = [];
        }

        foreach (parent::toArray() as $dtoProperty => $dtoValue) {
            if (null !== $dtoValue) {
                $data[StringHelper::snakeCase($dtoProperty)] = $dtoValue;
            }
        }

        return $data;
    }
}
