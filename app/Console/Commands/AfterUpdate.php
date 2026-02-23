<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AfterUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:after-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs after an Update';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
