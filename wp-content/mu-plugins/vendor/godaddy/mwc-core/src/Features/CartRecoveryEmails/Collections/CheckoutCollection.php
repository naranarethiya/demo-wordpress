<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Collections;

use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

class CheckoutCollection
{
    /** @var Checkout[] */
    protected $items = [];

    /**
     * Creates a new instance of the collection with the given {@see Checkout} items.
     *
     * @param Checkout[] $items
     * @return static
     */
    public static function seed(array $items)
    {
        return new static($items);
    }

    /**
     * Constructor.
     *
     * @param Checkout[] $items
     */
    final public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Gets the most recently updated {@see Checkout} item from the collection that still doesn't have a scheduled email.
     *
     * Any items in the collection whose ID matches the given checkout ID won't be considered to get the result.
     *
     * @param int $checkoutId
     * @return Checkout|null
     */
    public function getMostRecentCheckoutWithScheduledEmailsExcluding(int $checkoutId) : ?Checkout
    {
        $mostRecentCheckout = null;

        foreach ($this->items as $item) {
            if ($item->getId() === $checkoutId) {
                continue;
            }

            if (! $item->getUpdatedAt() || ! $item->getEmailScheduledAt()) {
                continue;
            }

            if (! $mostRecentCheckout) {
                $mostRecentCheckout = $item;
            }

            if ($item->getUpdatedAt() > $mostRecentCheckout->getUpdatedAt()) {
                $mostRecentCheckout = $item;
            }
        }

        return $mostRecentCheckout;
    }

    /**
     * Gets the most recently updated {@see Checkout} item from the collection.
     *
     * @return Checkout|null
     */
    public function getMostRecentCheckout() : ?Checkout
    {
        $mostRecentCheckout = null;

        foreach ($this->items as $item) {
            if (! $item->getUpdatedAt()) {
                continue;
            }

            if (! $mostRecentCheckout) {
                $mostRecentCheckout = $item;
            }

            if ($item->getUpdatedAt() > $mostRecentCheckout->getUpdatedAt()) {
                $mostRecentCheckout = $item;
            }
        }

        return $mostRecentCheckout;
    }
}
