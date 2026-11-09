<?php

namespace App\Console\Commands;

use App\Services\ComplaintService;
use Illuminate\Console\Command;

class EscalateComplaints extends Command
{
    protected $signature = 'complaints:escalate {--hours=48 : Age in hours before a complaint is escalated}';

    protected $description = 'Escalate complaints that have been unassigned past the SLA window';

    public function handle(ComplaintService $complaints): int
    {
        $hours = (int) $this->option('hours');

        $count = $complaints->escalateStale($hours);

        $this->info("Escalated {$count} complaint(s) older than {$hours} hours.");

        return self::SUCCESS;
    }
}
