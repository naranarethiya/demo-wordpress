<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

class ConfigHelper
{
    /**
     * Finds keys that reference the given class or interfaces that implemented by the class.
     *
     * @param string $configKey
     * @param object $class
     * @return array<mixed>
     * @throws BaseException
     */
    public static function getClassNamesUsingClassOrInterfacesAsKeys(string $configKey, object $class) : array
    {
        $keys = Configuration::get($configKey);

        $classes = ArrayHelper::get($keys, get_class($class), []);

        if ($classImplements = class_implements($class)) {
            foreach ($classImplements as $interface) {
                $interfaces = ArrayHelper::get($keys, $interface, []);

                $classes = ArrayHelper::combine($classes, $interfaces);
            }
        }

        //dedupe in case any subscribers are set in both class and interface and/or multiple interfaces {WS - 2022-01-24}
        return array_unique($classes);
    }
}
