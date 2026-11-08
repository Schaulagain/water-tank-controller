<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'roof' => 'float',
            'underground' => 'float',
            'pump' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('recorded_at', '<', now()->subDays(config('device.keep_readings_days')));
    }
}
