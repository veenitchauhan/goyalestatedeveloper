<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function defaults(): array
    {
        $home = Homepage::where('key', 'main')->first()?->content ?? [];

        return [
            'company' => ['name' => config('app.name'), 'tagline' => $home['hero']['eyebrow'] ?? 'ENGINEERING · CONSTRUCTION · INFRASTRUCTURE', 'legal_information' => ''],
            'contact' => [...['phone' => '', 'whatsapp' => '', 'email' => '', 'address' => ''], ...($home['contact'] ?? []), 'hr_email' => '', 'sales_email' => '', 'office_hours' => '', 'map_url' => ''],
            'branding' => ['logo_id' => '', 'favicon_id' => '', 'og_image_id' => ''],
            'social' => ['instagram' => '', 'linkedin' => '', 'youtube' => '', 'facebook' => ''],
            'footer' => ['privacy_url' => '', 'terms_url' => '', 'cookies_url' => '', 'disclaimer_url' => ''],
            'seo' => ['title' => $home['seo']['title'] ?? config('app.name'), 'description' => $home['seo']['description'] ?? '', 'robots' => 'noindex,nofollow', 'analytics_id' => '', 'tag_manager_id' => '', 'meta_pixel_id' => '', 'search_console_verification' => ''],
            'watermark' => ['enabled' => false, 'position' => 'bottom-right', 'opacity' => 70, 'size' => 3, 'padding' => 20],
        ];
    }

    public static function current(): array
    {
        return static::where('key', 'global')->first()?->data ?? static::defaults();
    }

    public static function applyTo(array $content, ?array $settings = null): array
    {
        $settings ??= static::current();
        $content['contact'] = $settings['contact'];
        $content['hero']['eyebrow'] = $settings['company']['tagline'];

        return $content;
    }

    public static function image(mixed $id): ?Media
    {
        return $id ? Media::whereKey($id)->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'like', 'image/%')->first() : null;
    }
}
