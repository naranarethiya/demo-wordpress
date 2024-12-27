<?php

namespace GoDaddy\WordPress\MWC\Common\HostingPlans\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;

class HostingPlanNamesEnum
{
    use EnumTrait;

    public const Basic = 'basic';
    public const Deluxe = 'deluxe';
    public const Ecommerce = 'ecommerce';
    public const Essentials = 'essentials';
    public const EssentialsCA = 'essentialsCA';
    public const EssentialsWorldpay = 'essentials_GDGCPP';
    public const Expand = 'expand';
    public const ExpandCA = 'expandCA';
    public const ExpandWorldpay = 'expand_GDGCPP';
    public const Flex = 'flex';
    public const FlexCA = 'flexCA';
    public const FlexWorldpay = 'flex_GDGCPP';
    public const Premier = 'premier';
    public const Pro5 = 'pro-5';
    public const Pro10 = 'pro-10';
    public const Pro25 = 'pro-25';
    public const Pro50 = 'pro-50';
    public const Ultimate = 'ultimate';

    /**
     * Gets all Canada plan names enums.
     *
     * @return array<static::*>
     */
    public static function getAllCanadaPlanNames() : array
    {
        return [
            static::EssentialsCA,
            static::ExpandCA,
            static::FlexCA,
        ];
    }

    /**
     * Gets all e-commerce plan names enums.
     *
     * @return array<static::*>
     */
    public static function getAllEcommercePlanNames() : array
    {
        return array_merge(
            static::getDefaultMwcPlanNames(),
            static::getAllMwcsPlanNames()
        );
    }

    /**
     * Gets all essentials plan names enums.
     *
     * @return array<static::*>
     */
    public static function getAllEssentialsPlanNames() : array
    {
        return [
            static::Essentials,
            static::EssentialsCA,
            static::EssentialsWorldpay,
        ];
    }

    /**
     * Gets all MWCS plan names enums.
     *
     * @return array<static::*>
     */
    public static function getAllMwcsPlanNames() : array
    {
        return array_merge(
            static::getDefaultMwcsPlanNames(),
            static::getAllCanadaPlanNames(),
            static::getAllWorldpayPlanNames()
        );
    }

    /**
     * Gets all plan name enums that are associated with Worldpay.
     *
     * @return array<static::*>
     */
    public static function getAllWorldpayPlanNames() : array
    {
        return [
            static::EssentialsWorldpay,
            static::ExpandWorldpay,
            static::FlexWorldpay,
        ];
    }

    /**
     * Gets the default MWCS plan names, excluding any Canada or Worldpay name enums.
     *
     * @return array<static::*>
     */
    public static function getDefaultMwcsPlanNames() : array
    {
        return [
            static::Essentials,
            static::Expand,
            static::Flex,
            static::Premier,
        ];
    }

    /**
     * Gets the default MWC plan name enums, excluding any Canada or Worldpay name enums.
     *
     * @return array<static::*>
     */
    public static function getDefaultMwcPlanNames() : array
    {
        return [static::Ecommerce];
    }

    /**
     * Checks if the given plan name is an Essentials plan.
     *
     * @param string $planName
     *
     * @return bool
     */
    public static function isEssentialsPlan(string $planName) : bool
    {
        $planName = strtolower($planName);

        foreach (static::getAllEssentialsPlanNames() as $essentialsPlanName) {
            if ($planName === strtolower($essentialsPlanName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if given plan is a MWCS plan.
     *
     * @param string $planName
     *
     * @return bool
     */
    public static function isManagedWooCommerceStoresPlan(string $planName) : bool
    {
        $planName = strtolower($planName);

        foreach (static::getAllMwcsPlanNames() as $mwcsPlanName) {
            if ($planName === strtolower($mwcsPlanName)) {
                return true;
            }
        }

        return false;
    }
}
