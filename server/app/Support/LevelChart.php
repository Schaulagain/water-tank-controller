<?php

namespace App\Support;

use App\Models\Reading;
use Illuminate\Support\Carbon;

/**
 * Turns the last day of readings into what the dashboard chart draws:
 * 10-minute averages for each tank, and the stretches when the pump ran.
 */
class LevelChart
{
    public const HOURS = 24;

    public const BUCKET_MINUTES = 10;

    public const WIDTH = 720;

    public const HEIGHT = 200;

    /** @var list<array{time: Carbon, roof: ?float, underground: ?float, pump: bool}> */
    public array $points = [];

    public static function lastDay(): self
    {
        $chart = new self;
        $end = now()->startOfMinute();
        $start = $end->copy()->subHours(self::HOURS);
        $count = self::HOURS * 60 / self::BUCKET_MINUTES;

        $buckets = array_fill(0, $count, []);

        Reading::query()
            ->where('recorded_at', '>=', $start)
            ->orderBy('recorded_at')
            ->get()
            ->each(function (Reading $r) use (&$buckets, $start, $count) {
                $i = (int) floor($start->diffInMinutes($r->recorded_at) / self::BUCKET_MINUTES);
                if ($i >= 0 && $i < $count) {
                    $buckets[$i][] = $r;
                }
            });

        foreach ($buckets as $i => $readings) {
            $avg = function (string $field) use ($readings): ?float {
                $values = array_filter(array_map(fn ($r) => $r->$field, $readings), fn ($v) => $v !== null);

                return $values ? round(array_sum($values) / count($values), 1) : null;
            };

            $chart->points[] = [
                'time' => $start->copy()->addMinutes(($i + 1) * self::BUCKET_MINUTES),
                'roof' => $avg('roof'),
                'underground' => $avg('underground'),
                'pump' => collect($readings)->contains('pump', true),
            ];
        }

        return $chart;
    }

    public function isEmpty(): bool
    {
        return collect($this->points)->every(fn ($p) => $p['roof'] === null && $p['underground'] === null);
    }

    public function x(int $i): float
    {
        return round(($i + 0.5) / count($this->points) * self::WIDTH, 1);
    }

    public function y(float $level): float
    {
        return round(self::HEIGHT - $level / 100 * self::HEIGHT, 1);
    }

    /** An SVG path for one tank. Gaps with no readings break the line. */
    public function path(string $tank): string
    {
        $path = '';
        $drawing = false;

        foreach ($this->points as $i => $point) {
            if ($point[$tank] === null) {
                $drawing = false;

                continue;
            }

            $path .= ($drawing ? 'L' : 'M').$this->x($i).','.$this->y($point[$tank]).' ';
            $drawing = true;
        }

        return trim($path);
    }

    /** @return list<array{x: float, width: float}> */
    public function pumpBands(): array
    {
        $step = self::WIDTH / count($this->points);
        $bands = [];

        foreach ($this->points as $i => $point) {
            if (! $point['pump']) {
                continue;
            }

            $last = array_key_last($bands);
            if ($last !== null && abs($bands[$last]['x'] + $bands[$last]['width'] - $i * $step) < 0.01) {
                $bands[$last]['width'] += $step;
            } else {
                $bands[] = ['x' => $i * $step, 'width' => $step];
            }
        }

        return $bands;
    }

    /** The newest reading for a tank, for the label at the end of its line. */
    public function latest(string $tank): ?float
    {
        foreach (array_reverse($this->points) as $point) {
            if ($point[$tank] !== null) {
                return $point[$tank];
            }
        }

        return null;
    }

    /** Points for the hover tooltip. */
    public function forTooltip(): array
    {
        return array_map(fn ($p) => [
            'time' => $p['time']->format('g:i a'),
            'roof' => $p['roof'],
            'underground' => $p['underground'],
            'pump' => $p['pump'],
        ], $this->points);
    }

    /** One row per hour, for the table view. */
    public function hourly(): array
    {
        return collect($this->points)
            ->chunk(60 / self::BUCKET_MINUTES)
            ->map(function ($chunk) {
                $avg = fn ($field) => ($v = $chunk->pluck($field)->filter(fn ($x) => $x !== null))->isEmpty() ? null : round($v->avg());

                return [
                    'hour' => $chunk->last()['time']->format('g a'),
                    'roof' => $avg('roof'),
                    'underground' => $avg('underground'),
                    'pump' => $chunk->contains('pump', true),
                ];
            })
            ->reverse()
            ->values()
            ->all();
    }
}
