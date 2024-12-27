<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects;

/**
 * Data transfer object for representing a simple currency amount.
 *
 * @link https://godaddy-corp.atlassian.net/wiki/spaces/GAT/pages/15600908/Monetary+Values+Localization+and+Rounding#MonetaryValues%2CLocalization%2CandRounding-Money
 *
 * @method static static getNewInstance(array $data)
 */
class SimpleMoney extends AbstractDataObject
{
    /** @var string 3-letter currency code */
    public string $currencyCode;

    /** @var int value in cents */
    public int $value;

    /**
     * Creates a new data object.
     *
     * @param array{
     *     currencyCode: string,
     *     value: int
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Generates new instance from the given values.
     *
     * @param string $currencyCode
     * @param int $value
     * @return SimpleMoney
     */
    public static function from(string $currencyCode, int $value) : SimpleMoney
    {
        return static::getNewInstance([
            'currencyCode' => $currencyCode,
            'value'        => $value,
        ]);
    }
}
