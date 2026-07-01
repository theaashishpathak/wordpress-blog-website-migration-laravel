<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WpPosts extends Model
{
    /**
     * WordPress database connection
     */
    protected $connection = 'wordpress';

    /**
     * WordPress table
     */
    protected $table = 'posts';

    /**
     * WordPress primary key
     */
    protected $primaryKey = 'ID';

    /**
     * Disable timestamps
     */
    public $timestamps = false;

    /**
     * Allow all columns
     */
    protected $guarded = [];

    /**
     * Published posts only
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('post_type', 'post')
            ->where('post_status', 'publish');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    | These make WordPress look like NewsPilot.
    */

    public function getTitleAttribute()
    {
        return $this->post_title;
    }

    public function getSlugAttribute()
    {
        return $this->post_name;
    }

    public function getContentAttribute()
    {
        return $this->post_content;
    }

    public function getExcerptAttribute()
    {
        return $this->post_excerpt;
    }

    public function getPublishedAtAttribute()
    {
        return $this->post_date;
    }
}