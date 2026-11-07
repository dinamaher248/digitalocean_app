<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->orderByDesc('id')->first();

        return $row?->value ?? $default;
    }

    public static function setValue(string $key, string $value): self
    {
        $row = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        $ids = static::query()->where('key', $key)->orderByDesc('id')->pluck('id');
        if ($ids->count() > 1) {
            static::query()->where('key', $key)->whereNotIn('id', [$ids->first()])->delete();
        }

        return $row;
    }
}
