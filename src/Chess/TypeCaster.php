<?php

namespace Chess;

class TypeCaster {
    /**
     * @param mixed $value
     */
    public static function bool($value): ?bool {
        if ($value === null) {
            return null;
        }
        return (bool) $value;
    }

    /**
     * @param mixed $value
     */
    public static function int($value): ?int {
        if ($value === null) {
            return null;
        }
        return (int) $value;
    }

    /**
     * @param mixed $value
     */
    public static function string($value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return $value;
    }

    /**
     * @param mixed $value
     */
    public static function id($value): ?int {
        if (empty($value)) {
            return null;
        }
        return static::int($value);
    }

    /**
     * @param mixed $value
     */
    public static function date($value): ?string {
        if (empty($value) || !strtotime($value) || strpos($value, '0000-00-00') !== false) {
            return null;
        }
        return date('Y-m-d', strtotime($value));
    }

    /**
     * @param mixed $value
     */
    public static function datetime($value): ?string {
        if (empty($value) || strpos($value, '0000-00-00') !== false) {
            return null;
        }
        // Если на входе число, считаем его как timestamp и его не надо преобразовывать
        if (is_int($value)) {
            return date('c', $value);
        }
        $value = strtotime($value);
        if ($value === false) {
            return null;
        }
        return date('c', $value);
    }

    /**
     * @param mixed $value
     */
    public static function list($value): ?array {
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values($value);
    }

    /**
     * @param mixed $value
     */
    public static function num2color($value): ?string {
        return ColorEnum::getSystemName($value);
    }

    /**
     * @param mixed $value
     */
    public static function color2num($value): ?int {
        return ColorEnum::getIdBySystemName($value);
    }

    /**
     * @param mixed $value
     */
    public static function color($value): int {
        return ColorEnum::validColorId($value);
    }

    /**
     * @param mixed $value
     */
    public static function num2letter($value): ?string {
        return LetterTypesEnum::getSystemName($value);
    }

    /**
     * @param mixed $value
     */
    public static function letter2num($value): ?int {
        return LetterTypesEnum::getIdBySystemName($value);
    }

    /**
     * @param mixed $value
     */
    public static function letter($value): string {
        return LetterTypesEnum::validLetter($value);
    }
}
