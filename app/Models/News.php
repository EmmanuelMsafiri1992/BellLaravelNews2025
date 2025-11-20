<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = ['title', 'description', 'category_id', 'status', 'date'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Relationship: News belongs to many Screens
     */
    public function screens()
    {
        return $this->belongsToMany(Screen::class, 'news_screen');
    }
}
