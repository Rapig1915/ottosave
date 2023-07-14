<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;

class MarkAssignmentsTransferred extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dym:mark-assignments-transferred';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find any assignments not marked as transferred and mark as transferred if allocated_amount is same as transaction amount.';

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
        $assignments = Assignment::where('transferred', 0)->with('transaction')->get();
        foreach ($assignments as $assignment) {
            if ($assignment->transaction && $assignment->transaction->amount == $assignment->allocated_amount) {
                $assignment->transferred = true;
                $assignment->save();
            }
        }
    }
}
