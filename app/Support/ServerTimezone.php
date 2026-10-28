<?php

namespace App\Support;

use DateTimeZone;

class ServerTimezone
{
    /**
     * Detect the operating system timezone (TZ, then /etc/localtime, then /etc/timezone).
     */
    public static function detect(string $fallback = 'UTC'): string
    {
        $candidates = [getenv('TZ') ?: null];

        $localtime = is_link('/etc/localtime') ? readlink('/etc/localtime') : false;
        if ($localtime !== false && str_contains($localtime, 'zoneinfo/')) {
            $candidates[] = substr($localtime, strrpos($localtime, 'zoneinfo/') + strlen('zoneinfo/'));
        }

        if (is_readable('/etc/timezone')) {
            $candidates[] = trim((string) file_get_contents('/etc/timezone'));
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && self::isValid(ltrim($candidate, ':'))) {
                return ltrim($candidate, ':');
            }
        }

        return $fallback;
    }

    private static function isValid(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
    }
}
