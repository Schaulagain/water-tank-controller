<?php

namespace App\Device;

use App\Models\Setting;

/**
 * The thresholds and timers the device works to. They're stored here and
 * pushed to the device whenever they change.
 */
class Settings
{
    public const DEFAULTS = [
        'roof_on' => 25,
        'roof_off' => 95,
        'underground_block' => 15,
        'underground_resume' => 25,
        'max_run_minutes' => 30,
        'power_wait_seconds' => 120,
        'manual_timeout_minutes' => 120,
    ];

    public function __construct(private Device $device) {}

    public function all(): array
    {
        $saved = Setting::query()->pluck('value', 'key')->map(fn ($v) => (int) $v)->all();

        return array_merge(self::DEFAULTS, array_intersect_key($saved, self::DEFAULTS));
    }

    public function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => (int) $value]);
        }

        $this->push();
    }

    public function push(): void
    {
        $this->device->sendConfig($this->all());
    }

    public static function rules(): array
    {
        return [
            'roof_on' => ['required', 'integer', 'between:5,90'],
            'roof_off' => ['required', 'integer', 'max:100', 'gt:roof_on'],
            'underground_block' => ['required', 'integer', 'between:0,90'],
            'underground_resume' => ['required', 'integer', 'max:100', 'gt:underground_block'],
            'max_run_minutes' => ['required', 'integer', 'between:1,240'],
            'power_wait_seconds' => ['required', 'integer', 'between:0,900'],
            'manual_timeout_minutes' => ['required', 'integer', 'between:5,720'],
        ];
    }
}
