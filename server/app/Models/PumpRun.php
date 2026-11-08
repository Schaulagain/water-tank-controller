<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PumpRun extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public static function open(): ?self
    {
        return static::query()->whereNull('ended_at')->latest('started_at')->first();
    }

    public function finish(?string $reason): void
    {
        $this->update(['ended_at' => now(), 'stop_reason' => $reason]);
    }

    public function minutes(): ?int
    {
        return $this->ended_at ? (int) round($this->started_at->diffInMinutes($this->ended_at)) : null;
    }
}
