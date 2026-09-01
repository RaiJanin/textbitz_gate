<?php

namespace App\Enums;

enum InertiaControllerNames: string
{
    case Home = 'Home';
    case History = 'History';
    case Alerts = 'Alerts';
    case Settings = 'Settings';
    case Profile = 'Profile';

    /**
     * @return class-string<\App\Http\Controllers\Controller>
     */
    public function controller(): string
    {
        return match ($this) {
            self::Home => \App\Http\Controllers\HomeController::class,
            self::History => \App\Http\Controllers\HistoryController::class,
            self::Alerts => \App\Http\Controllers\AlertsController::class,
            self::Settings => \App\Http\Controllers\SettingsController::class,
            self::Profile => \App\Http\Controllers\ProfileController::class,
        };
    }
}
