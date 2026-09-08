<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends CorporateRecord
{
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
