<?php

namespace App\Console;

use App\Console\Commands\MarfilClientCrackCommand;
use App\Console\Commands\MarfilClientWorkCommand;
use App\Console\Commands\MarfilServerDictionaryCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        MarfilClientCrackCommand::class,
        MarfilClientWorkCommand::class,
        MarfilServerDictionaryCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
