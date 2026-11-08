<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('device', fn ($user) => true);
