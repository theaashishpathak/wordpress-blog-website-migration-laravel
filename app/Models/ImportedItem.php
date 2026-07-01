<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ImportedItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dedup log entry for a feed item. The (source_id, guid) unique index
 * prevents the importer from creating the same post twice across
 * repeated fetches, even when the source feed reshuffles its order.
 */
class ImportedItem extends Model
{
    /** @use HasFactory<ImportedItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'source_id',
        'guid',
        'item_url',
        'title',
        'post_id',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImportSource, ImportedItem>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'source_id');
    }

    /**
     * @return BelongsTo<Post, ImportedItem>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
