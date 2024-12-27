<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\ImageSizeNotFound;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Product event transformer.
 */
class ProductEventTransformer extends AbstractEventTransformer
{
    /**
     * Determines whether the event must be transformed or not.
     *
     * @param ModelEvent|EventContract $event
     * @return bool
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ModelEvent && 'product' === $event->getResource();
    }

    /**
     * Handles and perhaps modifies the event.
     *
     * @param ModelEvent&EventContract $event the event, perhaps modified by the method
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        $data = $event->getData();

        ArrayHelper::set($data, 'resource.currency', WooCommerceRepository::getCurrency());

        if ($event instanceof ModelEvent && $event->getModel() instanceof Product) {
            $data = $this->transformImageData($data, $event->getModel());
            $data = $this->handleLegacyAttributeValues($data);
        }

        $event->setData($data);
    }

    /**
     * Handles legacy attribute values for variants.
     *
     * @TODO: for now attributes data is not sent to EventBridge to keep backwards compatibility with the legacy property {unfulvio 2023-03-29}
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    protected function handleLegacyAttributeValues(array $data) : array
    {
        $variants = [];

        foreach (TypeHelper::array(ArrayHelper::get($data, 'resource.variants'), []) as $variant) {
            if (is_array($variant) && isset($variant['attributeData'])) {
                $variant['attributes'] = $variant['attributeData'];

                unset($variant['attributeData']);
            }

            $variants[] = $variant;
        }

        if (! empty($variants)) {
            ArrayHelper::set($data, 'resource.variants', $variants);
        }

        return $data;
    }

    /**
     * Adds product image data to the event data, if we have it.
     *
     * @param array<string, mixed> $eventData
     * @param Product $product
     * @return array<string, mixed>
     */
    protected function transformImageData(array $eventData, Product $product) : array
    {
        // remove the image keys that were auto-added from the toArray() call
        ArrayHelper::remove($eventData, ['resource.mainImageId', 'resource.imageIds']);

        // build data for the main image
        $mainImage = $product->getMainImage();
        ArrayHelper::set($eventData, 'resource.mainImage', $mainImage ? $this->transformImage($mainImage) : null);

        // build data for all other gallery images
        $images = $product->getImages();
        ArrayHelper::set($eventData, 'resource.images', array_filter(array_map([$this, 'transformImage'], $images)));

        return $eventData;
    }

    /**
     * Builds an array of image data for events.
     *
     * @param Image $image
     * @return array<string, int|null|string>|null
     */
    public function transformImage(Image $image) : ?array
    {
        try {
            return [
                'id'  => $image->getId(),
                'url' => $image->getSize('full')->getUrl(),
            ];
        } catch (ImageSizeNotFound $e) {
            return null;
        }
    }
}
