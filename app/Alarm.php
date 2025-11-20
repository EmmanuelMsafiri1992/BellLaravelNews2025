<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Alarm extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'day', 'time', 'label', 'sound', 'enabled',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Boot method to auto-generate UUID
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alarm) {
            if (empty($alarm->id)) {
                $alarm->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope: Get only enabled alarms
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Get alarms for a specific day
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $day
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Get the sound file path
     *
     * @return string
     */
    public function getSoundPathAttribute()
    {
        return public_path('audio/' . $this->sound);
    }

    /**
     * Check if sound file exists
     *
     * @return bool
     */
    public function soundExists()
    {
        return file_exists($this->sound_path);
    }
}
