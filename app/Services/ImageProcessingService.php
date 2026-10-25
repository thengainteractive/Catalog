<?php

namespace Modules\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageProcessingService
{
    /**
     * Convert an uploaded image to WebP with max width 1024px and 60% quality.
     *
     * @param UploadedFile $file
     * @param string $directory Directory inside the disk (e.g. 'catalog/products')
     * @param string $disk Storage disk name (e.g. 'public')
     * @param int $maxWidth Max width in pixels (default 1024)
     * @param int $quality WebP quality 1-100 (default 60)
     * @return string Relative stored file path
     */
    public static function processAndStore(
        UploadedFile $file,
        string $directory = 'catalog/products',
        string $disk = 'public',
        int $maxWidth = 1024,
        int $quality = 60
    ): string {
        $realPath = $file->getRealPath();
        $imageContent = file_get_contents($realPath);

        // Load image using GD
        $sourceImage = @imagecreatefromstring($imageContent);

        $filename = Str::uuid()->toString() . '.webp';
        $relativeFilePath = trim($directory, '/') . '/' . $filename;
        $diskInstance = Storage::disk($disk);

        if (!$sourceImage) {
            // Failsafe: if GD cannot parse the image, store as-is
            $fallbackFilename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $fallbackPath = trim($directory, '/') . '/' . $fallbackFilename;
            $diskInstance->put($fallbackPath, $imageContent);
            return $fallbackPath;
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate target dimensions maintaining aspect ratio, never upscaling
        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($origHeight / $origWidth) * $newWidth);
            $scaledImage = imagescale($sourceImage, $newWidth, $newHeight);
        } else {
            $scaledImage = $sourceImage;
        }

        // Enable transparency support for WebP
        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);

        // Capture WebP buffer
        ob_start();
        imagewebp($scaledImage, null, $quality);
        $webpData = ob_get_clean();

        // Free memory
        if ($scaledImage !== $sourceImage) {
            imagedestroy($scaledImage);
        }
        imagedestroy($sourceImage);

        // Save to storage
        $diskInstance->put($relativeFilePath, $webpData);

        return $relativeFilePath;
    }

    /**
     * Process raw binary image content into WebP (max 1024px width, 60% quality) and save to storage.
     */
    public static function processAndStoreFromContent(
        string $imageContent,
        string $directory = 'catalog/products',
        string $disk = 'public',
        int $maxWidth = 1024,
        int $quality = 60
    ): ?string {
        if (blank($imageContent)) {
            return null;
        }

        $sourceImage = @imagecreatefromstring($imageContent);
        if (!$sourceImage) {
            return null;
        }

        $filename = Str::uuid()->toString() . '.webp';
        $relativeFilePath = trim($directory, '/') . '/' . $filename;
        $diskInstance = Storage::disk($disk);

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($origHeight / $origWidth) * $newWidth);
            $scaledImage = imagescale($sourceImage, $newWidth, $newHeight);
        } else {
            $scaledImage = $sourceImage;
        }

        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);

        ob_start();
        imagewebp($scaledImage, null, $quality);
        $webpData = ob_get_clean();

        if ($scaledImage !== $sourceImage) {
            imagedestroy($scaledImage);
        }
        imagedestroy($sourceImage);

        $diskInstance->put($relativeFilePath, $webpData);

        return $relativeFilePath;
    }

    /**
     * Download an image from an HTTP/HTTPS URL or local path and optimize it to WebP.
     */
    public static function processAndStoreFromUrl(
        string $urlOrPath,
        string $directory = 'catalog/products',
        string $disk = 'public'
    ): ?string {
        $urlOrPath = trim($urlOrPath);
        if (blank($urlOrPath)) {
            return null;
        }

        // If it's already an existing relative path on disk, retain it
        if (Storage::disk($disk)->exists($urlOrPath)) {
            return $urlOrPath;
        }

        $content = null;
        if (Str::startsWith($urlOrPath, ['http://', 'https://'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(15)->get($urlOrPath);
                if ($response->successful()) {
                    $content = $response->body();
                }
            } catch (\Throwable $e) {
                return null;
            }
        } elseif (file_exists($urlOrPath)) {
            $content = file_get_contents($urlOrPath);
        }

        if (!$content) {
            return null;
        }

        return static::processAndStoreFromContent($content, $directory, $disk);
    }
}
