<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

interface RemoteImageResizeServiceContract
{
    /**
     * Determines whether we should resize the supplied image URL.
     *
     * @param string $baseUrl
     * @param string|int[] $size
     * @return bool
     */
    public function shouldResize(string $baseUrl, $size) : bool;

    /**
     * Generates image data for the given base URL and at the given size.
     *
     * Returns the same data as {@link https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src/}
     * making it compatible with the hook.
     *
     * @param string $baseUrl
     * @param string|int[] $size array of width and height or string size name.
     * @param array<int, string|int|bool> $image original image data
     * @return array{string,int,int,bool}|false
     */
    public function generateImageData(string $baseUrl, $size, array $image);
}
