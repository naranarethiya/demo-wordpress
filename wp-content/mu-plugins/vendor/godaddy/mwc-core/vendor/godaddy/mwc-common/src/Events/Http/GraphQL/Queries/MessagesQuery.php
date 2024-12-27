<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class MessagesQuery extends AbstractGraphQLOperation
{
    /** @var string GraphQL operation */
    protected $operation = <<<'GQL'
query getMessages {
  messages(filter: { context: "MAIN" }, limit: 255) {
    nodes {
      id
      subject
      body
      status
      createdBy
      createdAt
      updatedBy
      updatedAt
      contexts
      contextStatus
      publishedAt
      expiredAt
      actions {
        type
        href
        text
        extensionSlug
        successMessage
      }
      links {
        href
        rel
        href
      }
      rules {
        all {
          label
          name
          type
          rel
          comparator
          operator
          value
        }
        any {
          label
          name
          type
          rel
          comparator
          operator
          value
        }
      }
      type
    }
  }
}
GQL;
}
