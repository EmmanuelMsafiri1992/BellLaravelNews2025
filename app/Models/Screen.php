<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Screen extends Model
{

    protected $fillable = ['name', 'unique_code', 'location', 'status'];

    /**
     * Generate a unique code for the screen
     */
    public static function generateUniqueCode($name)
    {
        $code = strtoupper(Str::slug($name)) . '-' . strtoupper(Str::random(4));

        // Ensure uniqueness
        while (self::where('unique_code', $code)->exists()) {
            $code = strtoupper(Str::slug($name)) . '-' . strtoupper(Str::random(4));
        }

        return $code;
    }

    /**
     * Relationship: Screen has many News
     */
    public function news()
    {
        return $this->belongsToMany(News::class, 'news_screen');
    }

    /**
     * Scope: Get only active screens
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
