<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeeController;

class InactiveEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:inactive-employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'inactive employees data from API';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $controller = new EmployeeController();
        $controller->EmployeeInactive();
        $this->info('Inactive employees data successfully Update.');
    }
}
