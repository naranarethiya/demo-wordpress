<?php

namespace GoDaddy\MWC\WordPress\Assistant;

class GPTFunctions {

    /**
     * @return array<array<string, mixed>>
     */
    public function getDocsFunctions() {

        $isMWP = class_exists('\WPaaS\Plugin') ? true : false; // mwp or mwcs
        return [
            [
                'name' => 'docs-getDocumentationV2',
                'description' => 'Search the documentation. This is a catch-all to use any time you cannot perform another action.',
                'parameters' => [
                    'required' => ['search', 'params'],
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'Summarize or rephrase the question succintly as a query to search the documentation.',
                        ],
                        'params' => [
                            'type' => 'string',
                            'description' => 'Use this exact value: ' . $isMWP == true ? '&metadata[productNumbers]="1000021"' : '&metadata[productNumbers]="1000070"' . '&metadata[productNumbers]="1000055"',
                        ]
                    ],
                ],
            ]
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getWPFunctions() {
        $wpFunctions = [
            [
                'name' => 'wp-supportTicket',
                'description' => 'Create a new support request. Use if the prompt includes support, assistance, agent, or representative.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'prompt' => [
                            'type' => 'string',
                            'description' => 'The body of the support request.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wp-createPost',
                'description' => 'Create a new blog post or article',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreatePostProperties(),
                    'required' => ['title', 'content'],
                ],
            ],
            [
                'name' => 'wp-getPosts',
                'description' => 'Get one or more blog posts or articles',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getPostProperties(),
                ],
            ],
            [
                'name' => 'wp-editPost',
                'description' => 'Edit an existing blog post. Use this if viewing a post, and the prompt refers to adding, changing, or updating the post.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreatePostProperties(),
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'wp-getPages',
                'description' => 'Get one or more pages',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getPostProperties(),
                ],
            ],
            [
                'name' => 'wp-createPage',
                'description' => 'Create a new page',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreatePostProperties(),
                    'required' => ['title', 'content'],
                ],
            ],
            [
                'name' => 'wp-editPage',
                'description' => 'Edit an existing page. Use this if viewing a page, and the prompt refers to adding, changing, or updating the page.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreatePostProperties(),
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'wp-getPlugins',
                'description' => 'Get a list of plugins. Only use if the word plugin is in the prompt.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'description' => 'Limit results to plugins with a certain status. Options are active and inactive.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wp-getMedia',
                'description' => 'Get one or more media items, such as an image or video.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'A search term to find a media item by.',
                        ],
                        'media_type' => [
                            'type' => 'string',
                            'description' => 'Limit result set to attachments of a particular media type. One of: image, video, text, application, or audio.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wp-sftp',
                'description' => 'How to setup sftp or add an sftp user to the store.',
            ],
        ];
        return $wpFunctions;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getCreatePostProperties() {
        $wpCreatePostProperties = [
            'id' => [
                'type' => 'number',
                'description' => 'The id of the post'
            ],
            'title' => [
                'type' => 'string',
                'description' => 'The title of the post'
            ],
            'content' => [
                'type' => 'string',
                'description' => 'The content of the post. Always use single quotes inside post content. Use WordPress blocks to format content.'
            ],
            'excerpt' => [
                'type' => 'string',
                'description' => 'The excerpt of the post'
            ],
            'status' => [
                'type' => 'string',
                'description' => 'The status of the post, options are publish or draft. Default is publish.'
            ]
        ];
        return $wpCreatePostProperties;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getPostProperties() {
        $wpGetPostProperties = [
            'id' => [
                'type' => 'number',
                'description' => 'The id of the post'
            ],
            'title' => [
                'type' => 'string',
                'description' => 'The title of the post'
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Limit results to those matching a string.'
            ],
            'status' => [
                'type' => 'string',
                'description' => 'The status of the post, options are publish or draft. Default is publish.',
                'enum' => ['publish', 'draft']
            ],
            'after' => [
                'type' => 'string',
                'description' => 'Limit response to posts published after a given ISO8601 compliant date.'
            ],
            'before' => [
                'type' => 'string',
                'description' => 'Limit response to posts published before a given ISO8601 compliant date.'
            ],
            'order' => [
                'type' => 'string',
                'description' => 'Order sort attribute ascending or descending. Options: asc, desc. Default is desc.'
            ],
            'orderby' => [
                'type' => 'string',
                'description' => 'Sort collection by object attribute. Options: author, date, id, include, modified, parent, relevance, slug, include_slugs, title. Default is date.'
            ]
        ];
        return $wpGetPostProperties;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getWooFunctions() {

        $WCFunctions = [
            [
                'name' => 'wc-createProduct',
                'description' => 'Create a WooCommerce product',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreateProductProperties(),
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'wc-createProducts',
                'description' => 'Create multiple products. Use if more than one product is being created.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreateProductsProperties(),
                ],
            ],
            // [
            //     'name' => 'wc-createProductVariation',
            //     'description' => 'Create a WooCommerce product variation',
            //     'parameters' => [
            //         'type' => 'object',
            //         'properties' => $this->getCreateProductProperties(),
            //         'required' => ['id', 'regular_price', 'attributes'],
            //     ],
            // ],
            [
                'name' => 'wc-editProduct',
                'description' => 'Edit an existing WooCommerce product. Use this if viewing a product, and the prompt refers to adding, changing, or updating a product.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getCreateProductProperties(),
                    'required' => ['id'],
                ],
            ],
            [
                'name' => 'wc-getProducts',
                'description' => 'Get a product or products',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getProductProperties(),
                    'required' => ['status'],
                ],
            ],
            [
                'name' => 'wc-getCustomers',
                'description' => 'Find a customer or customers. Do not use if the word order is in the prompt.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'A search term to find a customer by, such as name or email.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wc-getOrders',
                'description' => "Find one or more orders. Use if the word 'order' is in the prompt.",
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'A search term to find an order by.',
                        ],
                        'after' => [
                            'type' => 'string',
                            'description' => 'Limit response to resources published after a given ISO8601 compliant date, for example: `2022-09-27 18:00:00.000`',
                        ],
                        'before' => [
                            'type' => 'string',
                            'description' => 'Limit response to resources published before a given ISO8601 compliant date, for example: `2022-09-27 18:00:00.000`',
                        ],
                        'status' => [
                            'type' => 'string',
                            'description' => 'Limit result set to resources with a specific status. Options are: pending, processing, on-hold, completed, cancelled, refunded, failed, trash, or any. Default is any.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wc-getSalesReport',
                'description' => 'Get sales report by date range',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        // 'date_min' => [
                        //     'type' => 'string',
                        //     'description' => "The start date of the report in YYYY-MM-DD format. Default is " . $dateOneMonthAgoFormatted,
                        // ],
                        // 'date_max' => [
                        //     'type' => 'string',
                        //     'description' => "The end date of the report in YYYY-MM-DD format. Default is " . $dateTodayFormatted,
                        // ],
                        'period' => [
                            'type' => 'string',
                            'description' => 'The period to run the report for. Options are week, month, last_month, and year.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wc-updateCoupons',
                'description' => 'Enable or disable coupons',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => [
                            'type' => 'string',
                            'description' => 'yes or no',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'wc-createCoupon',
                'description' => 'Create a new coupon code, or discount code',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'string',
                            'description' => 'the coupon code',
                        ],
                        'discount_type' => [
                            'type' => 'string',
                            'description' => 'The type of discount. Options are percent and fixed_cart.',
                            'enum' => ['percent', 'fixed_cart'],
                        ],
                        'amount' => [
                            'type' => 'string',
                            'description' => 'The amount of the discount, such as 10',
                        ],
                        'individual_use' => [
                            'type' => 'boolean',
                            'description' => 'Whether the coupon can be used with other coupons',
                        ],
                        'exclude_sale_items' => [
                            'type' => 'boolean',
                            'description' => 'Whether the coupon can be used on sale items',
                        ],
                        'minimum_amount' => [
                            'type' => 'string',
                            'description' => 'The minimum amount required to use the coupon, such as 100.00',
                        ],
                    ],
                    'required' => ['code', 'discount_type', 'amount'],
                ],
            ],
            [
                'name' => 'wc-getShippingMethods',
                'description' => 'Get shipping methods',
            ],
            [
                'name' => 'wc-getPaymentGateways',
                'description' => 'Get payment gateways',
            ],
            [
                'name' => 'wc-getTaxes',
                'description' => 'Get tax settings and tax rates',
            ],
            [
                'name' => 'wc-generalSettings',
                'description' => 'Get the store settings such as the address, phone number, inventory management, emails, accounts, etc.',
            ],
        ];

        return $WCFunctions;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getCreateProductProperties() {
        $wcCreateProductProperties = [
            'id' => [
                'type' => 'number',
                'description' => 'The id of the product'
            ],
            'name' => [
                'type' => 'string',
                'description' => 'Product name or title'
            ],
            'status' => [
                'type' => 'string',
                'description' => 'The post status. Options: any, draft, pending, private, publish. Default is any.'
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Product type. Options: simple, grouped, external, variable. Default is simple.'
            ],
            'sku' => [
                'type' => 'string',
                'description' => 'The product SKU.'
            ],
            'featured' => [
                'type' => 'boolean',
                'description' => 'If the product is featured.'
            ],
            'on_sale' => [
                'type' => 'boolean',
                'description' => 'If the product is on sale.'
            ],
            'stock_status' => [
                'type' => 'string',
                'description' => 'Limit result set to products assigned a specific stock status. Options: instock, outofstock, onbackorder. Sold out is the same as outofstock.'
            ],
            'regular_price' => [
                'type' => 'string',
                'description' => 'Product regular price.'
            ],
            'sale_price' => [
                'type' => 'string',
                'description' => 'Product sale price.'
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Product description.'
            ],
            'short_description' => [
                'type' => 'string',
                'description' => 'Product short description.'
            ],
            'date_on_sale_from_gmt' => [
                'type' => 'string',
                'description' => 'Start date of sale price, as GMT.'
            ],
            'date_on_sale_to_gmt' => [
                'type' => 'string',
                'description' => 'End date of sale price, as GMT.'
            ],
            'virtual' => [
                'type' => 'boolean',
                'description' => 'If the product is virtual. Default is false.'
            ],
            'downloadable' => [
                'type' => 'boolean',
                'description' => 'If the product is downloadable. Default is false.'
            ],
            'external_url' => [
                'type' => 'string',
                'description' => 'Product external URL. Only for external products.'
            ],
            'button_text' => [
                'type' => 'string',
                'description' => 'Product external button text. Only for external products.'
            ],
            'tax_status' => [
                'type' => 'string',
                'description' => 'Tax status. Options: taxable, shipping, none. Default is taxable.'
            ],
            'manage_stock' => [
                'type' => 'boolean',
                'description' => 'If managing stock at a product level, default is false.'
            ],
            'stock_quantity' => [
                'type' => 'number',
                'description' => 'Stock quantity.'
            ],
            'sold_individually' => [
                'type' => 'boolean',
                'description' => 'Allow one item to be bought in a single order. Default is false.'
            ],
            'weight' => [
                'type' => 'string',
                'description' => 'Product weight.'
            ],
            'dimensions' => [
                'type' => 'object',
                'description' => 'Product dimensions.',
                'properties' => [
                    'length' => [
                        'type' => 'string',
                        'description' => 'Product length.'
                    ],
                    'width' => [
                        'type' => 'string',
                        'description' => 'Product width.'
                    ],
                    'height' => [
                        'type' => 'string',
                        'description' => 'Product height.'
                    ]
                ]
            ],
            'reviews_allowed' => [
                'type' => 'boolean',
                'description' => 'Allow reviews. Default is true.'
            ],
            'purchase_note' => [
                'type' => 'string',
                'description' => 'Optional note to send the customer after purchase.'
            ],
            'attributes' => [
                'type' => 'array',
                'description' => 'List of attributes.',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Attribute name.'
                        ],
                        'visible' => [
                            'type' => 'boolean',
                            'description' => 'Define if the attribute is visible on the product page. Default is false.'
                        ],
                        'variation' => [
                            'type' => 'boolean',
                            'description' => 'Define if the attribute can be used as variation. Default is false.'
                        ],
                        'options' => [
                            'type' => 'array',
                            'description' => 'List of available term names of the attribute.',
                            'items' => [
                                'type' => 'string',
                                'description' => 'The name of the attribute.'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $wcCreateProductProperties;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getCreateProductsProperties() {
        $wcCreateProductsProperties = [
            'products' => [
                'type' => 'array',
                'description' => 'The products to create.',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Product name or title'
                        ],
                        'regular_price' => [
                            'type' => 'string',
                            'description' => 'Product regular price.'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Product description.'
                        ],
                        'short_description' => [
                            'type' => 'string',
                            'description' => 'Product short description.'
                        ]
                    ]
                ]
            ]
        ];

        return $wcCreateProductsProperties;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getProductProperties() {

        $wcGetProductProperties = [
            'id' => [
                'type' => 'string',
                'description' => 'The id of the product'
            ],
            'search' => [
                'type' => 'string',
                'description' => 'Limit results to those matching a string.'
            ],
            'before' => [
                'type' => 'string',
                'description' => 'Limit response to resources published before a given ISO8601 compliant date.'
            ],
            'after' => [
                'type' => 'string',
                'description' => 'Limit response to resources published after a given ISO8601 compliant date.'
            ],
            'order' => [
                'type' => 'string',
                'description' => 'Order sort attribute ascending or descending. Options: asc, desc. Default is desc.'
            ],
            'orderby' => [
                'type' => 'string',
                'description' => 'Sort collection by object attribute. Options: date, id, include, title, slug, price, popularity, and rating. Default is date.'
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Limit result set to products assigned a specific status. Options: any, draft, pending, private, publish. Default is any.'
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Limit result set to products assigned a specific type. Options: simple, grouped, external, variable. Default is simple.'
            ],
            'sku' => [
                'type' => 'string',
                'description' => 'Limit result set to products with a specific SKU.'
            ],
            'featured' => [
                'type' => 'boolean',
                'description' => 'Limit result set to featured products.'
            ],
            'attribute' => [
                'type' => 'string',
                'description' => 'Limit result set to products with a specific attribute assigned.'
            ],
            'on_sale' => [
                'type' => 'boolean',
                'description' => 'Limit result set to products on sale.'
            ],
            'min_price' => [
                'type' => 'string',
                'description' => 'Limit result set to products based on a minimum price.'
            ],
            'max_price' => [
                'type' => 'string',
                'description' => 'Limit result set to products based on a maximum price.'
            ],
            'stock_status' => [
                'type' => 'string',
                'description' => 'Limit result set to products assigned a specific stock status. Options: instock, outofstock, onbackorder.'
            ]
        ];

        return $wcGetProductProperties;
    }
}
