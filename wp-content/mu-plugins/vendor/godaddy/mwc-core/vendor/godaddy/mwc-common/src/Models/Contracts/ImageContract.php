<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;

/**
 * A contract for object representations of image files.
 */
interface ImageContract extends HasLabelContract, HasNumericIdentifierContract
{
    /**
     * Gets the sizes associated with the image.
     *
     * @return ImageSizeContract[]
     */
    public function getSizes() : array;

    /**
     * Determines if the image has a given size.
     *
     * @param string $sizeIdentifier
     * @return bool
     */
    public function hasSize(string $sizeIdentifier) : bool;

    /**
     * Gets a given size.
     *
     * @param string $sizeIdentifier
     * @return ImageSizeContract|null
     */
    public function getSize(string $sizeIdentifier);

    /**
     * Sets the sizes associated with the image.
     *
     * @param ImageSizeContract[] $value
     * @return $this
     */
    public function setSizes(array $value);
}
