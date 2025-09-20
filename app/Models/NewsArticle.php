<?php
// app/Models/NewsArticle.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'author_id', 'status', 'published_at', 'views', 'tags'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function incrementViews()
    {
        $this->increment('views');
    }
}
