<?php

use App\Console\Commands\SimulateAisTelemetryCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule simulated AIS telemetry updates every 5 minutes
Schedule::command(SimulateAisTelemetryCommand::class)->everyFiveMinutes();
