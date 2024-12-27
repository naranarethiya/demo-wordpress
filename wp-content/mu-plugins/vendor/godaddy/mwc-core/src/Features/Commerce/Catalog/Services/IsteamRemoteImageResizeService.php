<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WordPress\Adapters\ImageDimensionsAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RemoteAssetDownloadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\XCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\Enums\YCropPosition;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\ImageCropAttributes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Assets\RemoteAssetProcessingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\RemoteImageResizeServiceContract;

/**
 * Service for dynamically resizing images using the "isteam" web server.
 *
 * This web server is used by the CDN that Commerce Home images are stored in.
 * Full documentation: {@link https://github.com/asilvas/node-image-steam}
 */
class IsteamRemoteImageResizeService implements RemoteImageResizeServiceContract
{
    protected ImageDimensionsAdapter $imageDimensionsAdapter;

    public function __construct(ImageDimensionsAdapter $imageDimensionsAdapter)
    {
        $this->imageDimensionsAdapter = $imageDimensionsAdapter;
    }

    /**
     * Images served via "image steam" can be identified by the presence of "isteam" in their URL, we ignore other image URLs.
     * A full image URL would look like: https://img1.wsimg.com/isteam/ip/<store id>/<filename>.
     *
     * Additionally, we should not be resizing images that are already being
     * resized by isteam (as indicated by the presence of "/:/" in the URL).
     *
     * Finally, full size images should not be resized.
     *
     * {@inheritDoc}
     */
    public function shouldResize(string $baseUrl, $size) : bool
    {
        return 'full' !== $size && $this->isIsteamUrl($baseUrl) && ! stristr($baseUrl, '/:/');
    }

    /**
     * Gets the "full" size image data in WordPress format.
     *
     * For full sizes we just fall back to using the dimensions saved in the database, and only change the URL.
     * The dimensions will have been retrieved via {@see RemoteAssetDownloadInterceptor} and {@see RemoteAssetProcessingService}.
     *
     * @param string $baseUrl
     * @param array<int, string|int|bool> $image
     * @return array{string, int, int, bool}
     */
    protected function getFullSizeImageData(string $baseUrl, array $image) : array
    {
        return [
            $baseUrl,
            TypeHelper::int(ArrayHelper::get($image, 1), 0), // width
            TypeHelper::int(ArrayHelper::get($image, 2), 0), // height
            false, // whether the image is a resized image
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function generateImageData(string $baseUrl, $size, array $image)
    {
        if (! $this->shouldResize($baseUrl, $size)) {
            return $this->getFullSizeImageData($baseUrl, $image);
        }

        if (! is_array($size)) {
            // If the size is a string, it's a registered image size name, we need to convert it to width, height, crop.
            $size = $this->convertSizeNameToWidthHeightCrop($size);
        } else {
            // If size is an array, we have width and height dimensions, find the registered image size that matches those
            // dimensions and use that as the size name.
            $size = $this->maybeInjectCropSettings($size);
        }

        $imageDimensions = $this->imageDimensionsAdapter->convertFromSource($size);

        // width and height should always be >= 0
        if ($imageDimensions->width < 0 || $imageDimensions->height < 0) {
            return [$baseUrl, 0, 0, false];
        }

        return [
            $this->appendParams($baseUrl, $imageDimensions->width, $imageDimensions->height, $imageDimensions->crop),
            $imageDimensions->width,
            $imageDimensions->height,
            true, // it was resized
        ];
    }

    /**
     * Append isteam params to the base URL.
     *
     * @param string $baseUrl
     * @param int $width
     * @param int $height
     * @param ImageCropAttributes $crop
     * @return string
     */
    protected function appendParams(string $baseUrl, int $width, int $height, ImageCropAttributes $crop) : string
    {
        // To mimic WordPress' behaviour, we always want to resize first...
        $params = $this->appendResizeParams($width, $height);

        // and then crop (if necessary).
        if ($crop->shouldCrop) {
            if (! empty($params)) {
                /*
                 * Append `m` to the resize param, this tells isteam to use the resize dimensions as "minimums,"
                 * meaning a rectangular image will overflow in its longest dimension.
                 *
                 * This mimics WordPress' crop behavior.
                 */
                $params .= ',m';
                $params .= '/'; // the separator isteam uses to delimit commands
            }

            // Then append crop params to instruct the isteam server to crop the image.
            $params .= $this->appendCropParams($width, $height, $crop);
        }

        // isteam functions are seperated by `/`.
        return sprintf('%s/:/%s', $baseUrl, $params);
    }

    /**
     * Append the params to generate a cropped image.
     * {@see https://github.com/asilvas/node-image-steam#crop-cr}.
     *
     * @param int $width
     * @param int $height
     * @param ImageCropAttributes $crop
     * @return string
     */
    protected function appendCropParams(int $width, int $height, ImageCropAttributes $crop) : string
    {
        // We need both width and height in order to crop.
        if (0 >= $width || 0 >= $height) {
            return '';
        }

        return sprintf('cr=w:%d,h:%d,a=%s', $width, $height, $this->convertCropPosition($crop));
    }

    /**
     * Append the params to generate dynamically resized image.
     * {@link https://github.com/asilvas/node-image-steam#resize-rs}.
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function appendResizeParams(int $width, int $height) : string
    {
        // If both width and height are 0, we don't need to resize.
        if ($width == 0 && $height == 0) {
            return '';
        }

        $params = [];

        // If width or height are 0, we are excluding that dimension, don't include them in the URL.
        if ($width > 0) {
            $params[] = 'w:'.$width;
        }

        if ($height > 0) {
            $params[] = 'h:'.$height;
        }

        return sprintf('rs=%s', implode(',', $params));
    }

    /**
     * Converts an image size name (e.g. `thumbnail`) to corresponding width and height integer values.
     *
     * @param string $sizeName
     * @return array{int,int,bool|mixed} [width, height, crop]
     */
    protected function convertSizeNameToWidthHeightCrop(string $sizeName) : array
    {
        $imageSizes = wp_get_registered_image_subsizes();

        $width = TypeHelper::int(
            ArrayHelper::get($imageSizes, "{$sizeName}.width"),
            0
        );

        $height = TypeHelper::int(
            ArrayHelper::get($imageSizes, "{$sizeName}.height"),
            0
        );

        $crop = ArrayHelper::get($imageSizes, "{$sizeName}.crop", false);

        return [$width, $height, $crop];
    }

    /**
     * Translate WordPress' crop positions to isteam param format.
     * {@link https://developer.wordpress.org/reference/functions/add_image_size/#crop-mode}.
     *
     * @param ImageCropAttributes $crop
     * @return string
     */
    protected function convertCropPosition(ImageCropAttributes $crop) : string
    {
        if (! $crop->shouldCrop) {
            return '';
        }

        // default crop position is center-center
        $cropPosition = 'cc';

        switch ($crop->xPosition) {
            case XCropPosition::Left:
                $cropPosition = 'l';
                break;
            case XCropPosition::Right:
                $cropPosition = 'r';
                break;
            case XCropPosition::Center:
                $cropPosition = 'c';
                break;
        }

        switch ($crop->yPosition) {
            case YCropPosition::Top:
                $cropPosition .= 't';
                break;
            case YCropPosition::Bottom:
                $cropPosition .= 'b';
                break;
            case YCropPosition::Center:
                $cropPosition .= 'c';
                break;
        }

        return $cropPosition;
    }

    /**
     * Search registered image sizes for given dimension and inject crop settings if found.
     *
     * This is needed because in some cases WooCommerce uses the dimension of an existing registered image size
     * to generate new thumbnails (ex. https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/wc-template-functions.php#L1593-L1624)
     * When this happens, Woo is effectively assume that the new image size physically exists on the server.
     * But since the `wp_get_attachment_image_src()` is only receiving the dimensions, it does not have the crop setting.
     *
     * @param int[] $size
     * @return array<mixed>
     */
    protected function maybeInjectCropSettings(array $size) : array
    {
        $registeredSizes = wp_get_registered_image_subsizes();

        $registeredSize = ArrayHelper::where($registeredSizes, function ($registeredSize) use ($size) {
            return $registeredSize['width'] === $size[0] && $registeredSize['height'] === $size[1];
        });

        if (! empty($registeredSize)) {
            $size[] = $registeredSize[array_key_first($registeredSize)]['crop'];
        }

        return $size;
    }

    /**
     * Check if the base URL is an isteam URL.
     *
     * @param string $baseUrl
     * @return bool
     */
    public function isIsteamUrl(string $baseUrl) : bool
    {
        return stripos(TypeHelper::string(parse_url($baseUrl, PHP_URL_PATH), ''), '/isteam') === 0;
    }
}
