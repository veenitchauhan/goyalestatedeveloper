<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevelopmentSpace extends Model
{
    use HasFactory;

    public const STATUSES = ['Available', 'Hold', 'Booked', 'Sold', 'Blocked'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['details' => 'array', 'is_public' => 'boolean'];
    }
}
