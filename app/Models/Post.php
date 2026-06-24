<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Term;


class Post extends Model
{
    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function author()
    {
        return $this->belongsTo(User::class, 'post_author', 'ID');
    }

    public function scopePublished($query)
    {
        return $query->where('post_status', 'publish')
            ->where('post_type', 'post');
    }

    public function getFirstImageAttribute()
    {
        preg_match(
            '/<img.+?src=[\'"]([^\'"]+)[\'"].*?>/i',
            $this->post_content,
            $matches
        );

        return $matches[1] ?? null;
    }

    public function categories()
    {
        return $this->belongsToMany(
            Term::class,
            'wp_term_relationships',
            'object_id',
            'term_taxonomy_id',
            'ID',
            'term_id'
        )
            ->join(
                'wp_term_taxonomy',
                'wp_terms.term_id',
                '=',
                'wp_term_taxonomy.term_id'
            )
            ->where('wp_term_taxonomy.taxonomy', 'category');
    }

    public function getReadingTimeAttribute()
    {
        $words = str_word_count(strip_tags($this->post_content));

        return ceil($words / 200);
    }
}