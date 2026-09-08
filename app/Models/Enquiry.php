<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'type', 'location', 'message', 'consented_at', 'consent_version', 'status'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }
}
