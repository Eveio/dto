<?php

namespace Eve\DTO;

use ReflectionClass;

final class ReflectionResolver
{
    private static array $resolved = [];

    public static function resolve(string $className): ReflectionClass
    {
        if (!isset(self::$resolved[$className])) {
            self::$resolved[$className] = new ReflectionClass($className);
        }

        return self::$resolved[$className];
    }
}
