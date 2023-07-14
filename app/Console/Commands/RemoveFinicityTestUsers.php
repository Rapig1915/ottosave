<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\External\FinicityService;

class RemoveFinicityTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dym:clean-finicity-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds and removes Finicity test customers from local DB and Finicity API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $finicityService = new FinicityService();
        $finicityService->deleteAllTestingCustomers();
    }
}
