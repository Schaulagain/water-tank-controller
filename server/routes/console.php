<?php

use App\Models\Reading;
use Illuminate\Support\Facades\Schedule;

Schedule::command('device:check')->everyMinute();
Schedule::command('model:prune', ['--model' => [Reading::class]])->daily();
