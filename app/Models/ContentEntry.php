<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentEntry extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'slug', 'title', 'status', 'author_id'];

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_entry_user');
    }
}
