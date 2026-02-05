<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class BannerImageService
{
    /**
     * Image sizes configuration for desktop banners.
     *
     * @var array<string, int>
     */
    private const DESKTOP_SIZES = [
        'large'  => 1920,
        'medium' => 1024,
    ];

    /**
     * Image sizes configuration for mobile banners.
     *
     * @var array<string, int>
     */
    private const MOBILE_SIZES = [
        'large'  => 1024,
        'medium' => 768,
    ];

    /**
     * WebP quality setting.
     */
    private const WEBP_QUALITY = 85;

    /**
     * Store a desktop banner image, creating multiple size variants.
     *
     * @return string Path to the large image
     */
    public function storeDesktop(UploadedFile $file): string
    {
        return $this->store($file, 'desktop', self::DESKTOP_SIZES);
    }

    /**
     * Store a mobile banner image, creating multiple size variants.
     *
     * @return string Path to the large image
     */
    public function storeMobile(UploadedFile $file): string
    {
        return $this->store($file, 'mobile', self::MOBILE_SIZES);
    }

    /**
     * Store an image with the given sizes.
     *
     * @param  array<string, int>  $sizes
     * @return string Path to the large image
     */
    private function store(UploadedFile $file, string $type, array $sizes): string
    {
        $filename  = uniqid() . '_' . time() . '.webp';
        $largePath = '';

        foreach ($sizes as $size => $width) {
            $image = Image::read($file->getRealPath())
                ->scaleDown(width: $width)
                ->toWebp(quality: self::WEBP_QUALITY);

            $path = "{$type}/{$size}/{$filename}";
            Storage::disk('banners')->put($path, $image->toString());

            if ($size === 'large') {
                $largePath = "banners/{$type}/{$size}/{$filename}";
            }
        }

        return $largePath;
    }

    /**
     * Delete all size variants of a banner image.
     */
    public function delete(string $path): void
    {
        $filename = basename($path);
        $type     = str_contains($path, '/desktop/') ? 'desktop' : 'mobile';
        $sizes    = $type === 'desktop' ? self::DESKTOP_SIZES : self::MOBILE_SIZES;

        foreach (array_keys($sizes) as $size) {
            Storage::disk('banners')->delete("{$type}/{$size}/{$filename}");
        }
    }

    /**
     * Delete all images associated with a banner.
     */
    public function deleteBannerImages(Banner $banner): void
    {
        $this->delete($banner->image_desktop);

        if ($banner->image_mobile !== null) {
            $this->delete($banner->image_mobile);
        }
    }

    /**
     * Duplicate an existing banner image (all sizes) and return the new large path.
     */
    public function duplicate(string $path): string
    {
        $filename    = basename($path);
        $type        = str_contains($path, '/desktop/') ? 'desktop' : 'mobile';
        $sizes       = $type === 'desktop' ? self::DESKTOP_SIZES : self::MOBILE_SIZES;
        $newFilename = uniqid() . '_' . time() . '.webp';

        foreach (array_keys($sizes) as $size) {
            $source = "{$type}/{$size}/{$filename}";
            $target = "{$type}/{$size}/{$newFilename}";

            if (Storage::disk('banners')->exists($source)) {
                Storage::disk('banners')->copy($source, $target);
            }
        }

        return "banners/{$type}/large/{$newFilename}";
    }

    /**
     * Get the URL for a specific size of an image path.
     */
    public function getUrl(string $path, string $size = 'large'): string
    {
        $filename = basename($path);
        $type     = str_contains($path, '/desktop/') ? 'desktop' : 'mobile';

        return Storage::disk('banners')->url("{$type}/{$size}/{$filename}");
    }

    /**
     * Get all URLs for a desktop image.
     *
     * @return array<string, string>
     */
    public function getDesktopUrls(string $path): array
    {
        $filename = basename($path);
        $urls     = [];

        foreach (array_keys(self::DESKTOP_SIZES) as $size) {
            $urls[$size] = Storage::disk('banners')->url("desktop/{$size}/{$filename}");
        }

        return $urls;
    }

    /**
     * Get all URLs for a mobile image.
     *
     * @return array<string, string>
     */
    public function getMobileUrls(string $path): array
    {
        $filename = basename($path);
        $urls     = [];

        foreach (array_keys(self::MOBILE_SIZES) as $size) {
            $urls[$size] = Storage::disk('banners')->url("mobile/{$size}/{$filename}");
        }

        return $urls;
    }

    /**
     * Check if an image exists.
     */
    public function exists(string $path): bool
    {
        $filename = basename($path);
        $type     = str_contains($path, '/desktop/') ? 'desktop' : 'mobile';

        return Storage::disk('banners')->exists("{$type}/large/{$filename}");
    }

    /**
     * Get desktop sizes.
     *
     * @return array<string, int>
     */
    public static function getDesktopSizes(): array
    {
        return self::DESKTOP_SIZES;
    }

    /**
     * Get mobile sizes.
     *
     * @return array<string, int>
     */
    public static function getMobileSizes(): array
    {
        return self::MOBILE_SIZES;
    }
}
