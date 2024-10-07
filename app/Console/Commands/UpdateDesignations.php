<?php

namespace App\Console\Commands;

use App\Http\Controllers\DesignationController;
use Illuminate\Console\Command;

class UpdateDesignations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-designations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update Designation Data';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $controller = new DesignationController();
        $controller->UpdateDesignation();
        $this->info('Designations data successfully Update.');
    }
}
