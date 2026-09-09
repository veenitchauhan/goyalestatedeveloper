<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaImages
{
    public function checkImage(string $path, string $alt): void
    {
        $size = @getimagesize($path);
        if (! $size || $size[0] * $size[1] > 24000000 || max($size[0], $size[1]) > 10000) {
            throw ValidationException::withMessages(['file' => 'Use an image below 24 megapixels and 10,000 pixels per side.']);
        }
        if (trim($alt) === '') {
            throw ValidationException::withMessages(['alt' => 'Describe the image for visitors who cannot see it.']);
        }
    }

    public function derive(Media $media): void
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
}
