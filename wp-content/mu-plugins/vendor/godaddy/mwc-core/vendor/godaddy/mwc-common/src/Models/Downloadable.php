<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasUrlTrait;

/**
 * An object representation of a downloadable file.
 *
 * @method static static getNewInstance(array $data)
 */
class Downloadable extends AbstractModel
{
    use HasLabelTrait;
    use HasStringIdentifierTrait;
    use HasUrlTrait;

    /**
     * Downloadable constructor.
     *
     * @param array{
     *     id: string,
     *     name?: ?string,
     *     label?: ?string,
     *     url: string,
     * } $data
     */
    public function __construct(array $data)
    {
        // infer the name from the URL component if not defined in the downloadable data
        if (! isset($data['name'])) {
            $data['name'] = basename($data['url']);
        }

        if (! isset($data['label'])) {
            $data['label'] = $data['name'];
        }

        $this->setProperties($data);
    }
}
