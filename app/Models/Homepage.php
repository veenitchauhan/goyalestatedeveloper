<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Homepage extends Model
{
    protected $fillable = ['key', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public static function main(): self
    {
        return static::where('key', 'main')->firstOrFail();
    }
}
