<?php

namespace App\Services;

use App\Http\Requests\SaveProjectRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Support\Arr;

class ProjectImages
{
    public static function selectedIds(array $payload): array
    {
        return array_values(array_unique(array_filter([$payload['cover_media_id'] ?? null, ...array_column($payload['gallery'] ?? [], 'media_id')])));
    }

    public function apply(SaveProjectRequest $request, ContentEntry $entry, array $payload, array $previous, array &$createdPaths): array
    {
        if (! $request->boolean('image_selection')) {
            return $payload;
        }
        $existing = collect($previous['gallery'] ?? [])->keyBy('media_id');
        $gallery = [];
        foreach ($request->validated('keep_images', []) as $id) {
            $media = Media::findOrFail($id);
            $gallery[] = $existing->get($id) ?? ['media_id' => (int) $id, 'category' => 'Site Progress', 'caption' => '', 'alt' => $media->alt, 'visible' => true];
        }
        foreach ($request->file('images', []) as $index => $file) {
            $alt = $payload['title'].' — project image '.(count($gallery) + 1);
            app(MediaImages::class)->checkImage($file->getPathname(), $alt);
            $path = $file->store('media/originals', 'local');
            $createdPaths[] = $path;
            $media = new Media(['title' => $alt, 'alt' => $alt, 'original_name' => $file->getClientOriginalName(), 'original_path' => $path, 'mime' => $file->getMimeType(), 'category' => 'Projects', 'project' => $entry->slug, 'sort_order' => count($gallery), 'uploaded_by' => auth()->id(), 'watermark' => SiteSetting::current()['watermark'], 'is_public' => $request->user()->can('media.publish'), 'publication_status' => $request->user()->can('media.publish') ? 'published' : 'draft']);
            app(MediaImages::class)->derive($media);
            $createdPaths[] = $media->web_path;
            $media->save();
            app(AuditRecorder::class)->record('media.uploaded', $media);
            $gallery[] = ['media_id' => $media->id, 'category' => 'Site Progress', 'caption' => '', 'alt' => $alt, 'visible' => true];
        }
        $payload['gallery'] = $gallery;
        $payload['cover_media_id'] = $gallery[0]['media_id'] ?? null;

        return Arr::except($payload, ['images', 'keep_images', 'image_selection']);
    }
}
