<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;

class RemoveOrphanedInstitutions extends Command
{
    protected $signature = 'dym:remove-orphan-institutions';
    protected $description = 'Institutions with no accounts are deleted';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $institutions = Institution::has('institutionAccount', '=', 0)->get();
        foreach ($institutions as $institution) {
            $institution->delete();
        }
    }
}
