<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ContentEntry extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'slug', 'title', 'status', 'author_id', 'approver_id', 'published_revision_id', 'scheduled_revision_id', 'scheduled_at', 'published_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class);
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'published_revision_id');
    }

    public static function publishedItems(string $type): Collection
    {
        return static::where('type', $type)->whereNotNull('published_revision_id')->with('publishedRevision')->get()->map(fn ($entry) => ['id' => $entry->id, 'slug' => $entry->slug, ...$entry->publishedRevision->payload])->sortBy('order');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_entry_user');
    }
}
