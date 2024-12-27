<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Http\GraphQL\Fragments;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Contracts\GraphQLFragmentContract;

class OrderFieldsFragment implements GraphQLFragmentContract
{
    /**
     * {@inheritDoc}
     */
    public function __toString() : string
    {
        return 'fragment orderFields on Order {
            id
            cartId
            context {
                channelId
                owner
                storeId
            }
            lineItems {
                details {
                    productAssetUrl
                    sku
                    unitOfMeasure
                    selectedOptions {
                        attribute
                        values
                    }
                }
                fulfillmentMode
                id
                name
                quantity
                status
                totals {
                    discountTotal {
                        currencyCode
                        value
                    }
                    feeTotal {
                        currencyCode
                        value
                    }
                    subTotal {
                        currencyCode
                        value
                    }
                    taxTotal {
                        currencyCode
                        value
                    }
                }
                type
                unitAmount {
                    currencyCode
                    value
                }
            }
            notes {
                author
                authorType
                content
                createdAt
                deletedAt
                id
                shouldNotifyCustomer
            }
            processedAt
            statuses {
                fulfillmentStatus
                paymentStatus
                status
            }
            totals {
                discountTotal {
                    currencyCode
                    value
                }
                feeTotal {
                    currencyCode
                    value
                }
                shippingTotal {
                    currencyCode
                    value
                }
                subTotal {
                    currencyCode
                    value
                }
                taxTotal {
                    currencyCode
                    value
                }
                total {
                    currencyCode
                    value
                }
            }
        }';
    }
}
