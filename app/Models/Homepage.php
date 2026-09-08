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

    public static function editableContent(array $content): array
    {
        $content['hero'] += [
            'baseline' => 'TRICITY ROOTS. A FORWARD VISION.',
            'video_label' => 'Watch our construction film',
            'media_id' => null,
            'video_id' => null,
            'primary_cta_id' => null,
            'secondary_cta_id' => null,
        ];
        $content['statistics'] ??= ['mode' => 'all', 'ids' => []];
        foreach ($content['sections'] as &$section) {
            $section += ['artwork_enabled' => true, 'card_1_id' => null, 'card_2_id' => null, 'card_3_id' => null, 'media_id' => null, 'video_id' => null, 'cta_id' => null];
        }
        unset($section);

        return $content;
    }
}
