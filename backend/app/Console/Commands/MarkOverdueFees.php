<?php

namespace App\Console\Commands;

use App\Services\FeeService;
use Illuminate\Console\Command;

class MarkOverdueFees extends Command
{
    protected $signature = 'fees:mark-overdue';

    protected $description = 'Flag unpaid invoices whose due date has passed as overdue';

    public function handle(FeeService $fees): int
    {
        $count = $fees->markOverdue();

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
