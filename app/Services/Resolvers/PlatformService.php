<?php

namespace App\Services\Resolvers;

use Native\Mobile\Facades\System;

class PlatformService
{
    protected static ?array $platform = null;

    public static function isRunningNatively(): bool
    {
        return extension_loaded('nativephp');
    }

    public static function detect(): array
    {
        if (self::$platform !== null) {
            return self::$platform;
        }

        if(!self::isRunningNatively())
        {
            return self::$platform = ['isAndroid' => false, 'isIos' => false];
        }

        return self::$platform = [
            'isAndroid' => System::isAndroid(),
            'isIos' => System::isIos(),
        ];
    }
}
