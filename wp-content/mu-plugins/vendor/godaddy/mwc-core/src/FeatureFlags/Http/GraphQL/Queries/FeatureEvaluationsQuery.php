<?php

namespace GoDaddy\WordPress\MWC\Core\FeatureFlags\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * GraphQL query used to get featured evaluations for this site.
 */
class FeatureEvaluationsQuery extends AbstractGraphQLOperation
{
    /** @var string GraphQL operation */
    protected $operation = '
        query evaluateFeatures($entityId: ID!, $featureIds: [ID], $first: Int) {
            featureEvaluations(entityId: $entityId, featureIds: $featureIds, first: $first) {
                nodes {
                    id
                    reason
                    value {
                        boolValue
                        stringValue
                        longValue
                        doubleValue
                    }
                    variation
                }
                pageInfo {
                    nextToken
                }
            }
        }';
}
