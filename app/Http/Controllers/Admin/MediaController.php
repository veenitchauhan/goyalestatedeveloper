<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:80', 'location' => 'nullable|string|max:100', 'project' => 'nullable|string|max:100', 'mime' => 'nullable|string|max:100', 'date' => 'nullable|date', 'uploaded_by' => 'nullable|integer']);
        $query = Media::query();
        if ($filters['q'] ?? null) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['q'].'%')->orWhere('alt', 'like', '%'.$filters['q'].'%');
            });
        }
        foreach (['category', 'location', 'project', 'mime', 'uploaded_by'] as $key) {
            if ($filters[$key] ?? null) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($filters['date'] ?? null) {
            $query->whereDate('created_at', $filters['date']);
        }

        return view('admin.media.index', ['items' => $query->orderBy('sort_order')->latest()->paginate(24)->withQueryString()]);
    }

    public function edit(Media $media): View
    {
        return view('admin.media.edit', compact('media'));
    }

    public function create(): View
    {
        return view('admin.media.edit', ['media' => new Media(['category' => 'Company', 'sort_order' => 0, 'is_public' => false, 'watermark' => ['enabled' => false, 'position' => 'bottom-right', 'opacity' => 70, 'size' => 3, 'padding' => 20]])]);
    }

    public function store(StoreMediaRequest $request, AuditRecorder $audit): RedirectResponse
    {
        $file = $request->file('file');
        $data = $request->safe()->except('file');
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'video/mp4'])) {
            throw ValidationException::withMessages(['file' => 'Unsupported file contents.']);
        }
        if (str_starts_with($mime, 'image/')) {
            $this->checkImage($file->getPathname(), $data['alt'] ?? '');
        }
        $path = $file->store('media/originals', 'local');
        $media = new Media([...$data, 'original_name' => $file->getClientOriginalName(), 'original_path' => $path, 'mime' => $mime, 'uploaded_by' => auth()->id()]);
        try {
            $this->derive($media);
            $media->save();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$path, $media->web_path]));
            throw $e;
        }
        $audit->record('media.uploaded', $media);

        return redirect()->route('admin.media.edit', $media)->with('status', 'Media uploaded. The original is preserved privately.');
    }

    public function update(StoreMediaRequest $request, Media $media, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->safe()->except('file');
        if (str_starts_with($media->mime, 'image/')) {
            $this->checkImage(Storage::disk('local')->path($media->original_path), $data['alt'] ?? '');
        }
        $oldPath = $media->web_path;
        $media->fill($data);
        $this->derive($media);
        $media->save();
        if ($oldPath && $oldPath !== $media->web_path) {
            Storage::disk('local')->delete($oldPath);
        }
        $audit->record('media.updated', $media);

        return back()->with('status', 'Media settings saved; the web version was regenerated from the original.');
    }

    private function checkImage(string $path, string $alt): void
    {
        $size = @getimagesize($path);
        if (! $size || $size[0] * $size[1] > 24000000 || max($size[0], $size[1]) > 10000) {
            throw ValidationException::withMessages(['file' => 'Use an image below 24 megapixels and 10,000 pixels per side.']);
        }
        if (trim($alt) === '') {
            throw ValidationException::withMessages(['alt' => 'Describe the image for visitors who cannot see it.']);
        }
    }

    private function derive(Media $media): void
    {
        if (! str_starts_with($media->mime, 'image/')) {
            return;
        }
        $source = @imagecreatefromstring(Storage::disk('local')->get($media->original_path));
        if (! $source) {
            throw ValidationException::withMessages(['file' => 'This image could not be decoded.']);
        }
        $ratio = min(1, 1920 / max(imagesx($source), imagesy($source)));
        $width = max(1, (int) (imagesx($source) * $ratio));
        $height = max(1, (int) (imagesy($source) * $ratio));
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        imagedestroy($source);
        imagealphablending($image, true);
        $settings = $media->watermark;
        if ($settings['enabled'] ?? false) {
            $font = (int) $settings['size'];
            $text = config('app.name');
            $textWidth = imagefontwidth($font) * strlen($text);
            $textHeight = imagefontheight($font);
            $stamp = imagecreatetruecolor($textWidth + 8, $textHeight + 8);
            imagealphablending($stamp, false);
            imagesavealpha($stamp, true);
            imagefill($stamp, 0, 0, imagecolorallocatealpha($stamp, 0, 0, 0, 65));
            imagestring($stamp, $font, 4, 4, $text, imagecolorallocatealpha($stamp, 255, 255, 255, (int) (127 * (1 - $settings['opacity'] / 100))));
            $padding = min((int) $settings['padding'], (int) (min($width, $height) / 8));
            $scale = min(1, ($width - 2 * $padding) / imagesx($stamp), ($height - 2 * $padding) / imagesy($stamp));
            $sw = max(1, (int) (imagesx($stamp) * $scale));
            $sh = max(1, (int) (imagesy($stamp) * $scale));
            $position = $settings['position'];
            $x = str_contains($position, 'right') ? $width - $sw - $padding : $padding;
            $y = str_contains($position, 'bottom') ? $height - $sh - $padding : $padding;
            if ($position === 'center') {
                $x = (int) (($width - $sw) / 2);
                $y = (int) (($height - $sh) / 2);
            }
            imagecopyresampled($image, $stamp, $x, $y, 0, 0, $sw, $sh, imagesx($stamp), imagesy($stamp));
            imagedestroy($stamp);
        }
        ob_start();
        imagewebp($image, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $path = 'media/web/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $bytes);
        $media->web_path = $path;
    }

    public function original(Media $media): StreamedResponse
    {
        return Storage::disk('local')->download($media->original_path, $media->original_name, ['Content-Type' => 'application/octet-stream']);
    }

    public function show(Request $request, Media $media): StreamedResponse
    {
        abort_unless($media->is_public || ($request->user()?->can('media.manage') || $request->user()?->can('pages.edit')), 404);
        $path = $media->web_path ?? $media->original_path;
        if ($media->mime === 'application/pdf') {
            return Storage::disk('local')->download($path,'document-'.$media->id.'.pdf',['Content-Type' => 'application/pdf']);
        }

        return Storage::disk('local')->response($path,null,['Content-Type' => $media->web_path ? 'image/webp' : $media->mime]);
    }
}
