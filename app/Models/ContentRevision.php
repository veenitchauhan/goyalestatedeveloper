<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRevision extends Model
{
    protected $fillable = ['content_entry_id', 'version', 'payload', 'author_id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
