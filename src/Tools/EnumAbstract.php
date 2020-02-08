<?php

namespace Tools;

abstract class EnumAbstract {
    /**
     * @return string[] id => constant name
     */
    public static function getConstantNames(): array {
        return array_flip((new \ReflectionClass(get_called_class()))->getConstants());
    }

    /**
     * @return int[]|string[]
     */
    public static function getEnumIds(): array {
        return array_keys(static::getConstantNames());
    }

    /**
     * @param int|string|null $enumId
     * @return bool
     */
    public static function isValidId($enumId): bool {
        $constantNames = static::getConstantNames();
        return isset($constantNames[$enumId]);
    }

    /**
     * @return string[]
     */
    public static function getSystemNames(): array {
        return array_map('strtolower', static::getConstantNames());
    }

    /**
     * @return int|string|null
     */
    public static function getIdBySystemName(?string $name) {
        return array_flip(static::getSystemNames())[$name] ?? null;
    }

    /**
     * @param int|string|null $enumId
     */
    public static function getSystemName($enumId): ?string {
        return static::getSystemNames()[$enumId] ?? null;
    }

    /**
     * @return string[] id => english name (to be used in dispatcher UI)
     */
    public static function getEnglishNames(): array {
        return static::getSystemNames();
    }

    /**
     * @param int|string|null $enumId
     */
    public static function getEnglishName($enumId): ?string {
        return static::getEnglishNames()[$enumId] ?? null;
    }
}
