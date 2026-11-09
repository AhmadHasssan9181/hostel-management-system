<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('complaints:escalate')->hourly();
Schedule::command('fees:mark-overdue')->dailyAt('00:10');
