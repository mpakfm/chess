<?php

namespace Tools;

class ConsoleColors {
    public static function red(string $text): string {
        return self::color($text, "\e[0;31m", "\e[0m");
    }

    public static function green(string $text): string {
        return self::color($text, "\e[0;32m", "\e[0m");
    }

    private static function color(string $text, string $colorBegin, string $colorEnd) {
        // На будущее: В Teamcity не раскрашиваем строки
        $isTeamcity = (getenv('TEAMCITY_VERSION') !== false);
        return $isTeamcity ? $text : ($colorBegin . $text . $colorEnd);
    }
}
