<?php

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaStorageService
{
    public function storeUploadedFile(
        UploadedFile $file,
        string $folder,
        string $fallbackDisk = 'public',
        string $resourceType = 'auto'
    ): string {
        if ($this->shouldUseCloudinary()) {
            try {
                $this->configureCloudinary();
                $result = (new UploadApi())->upload($file->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => $resourceType,
                    'use_filename' => true,
                    'unique_filename' => true,
                    'overwrite' => false,
                ]);

                $url = (string) ($result['secure_url'] ?? $result['url'] ?? '');
                if ($url !== '') {
                    return $url;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $file->store($folder, $fallbackDisk);
    }

    public function deleteStoredFile(
        ?string $storedPath,
        string $fallbackDisk = 'public',
        string $resourceType = 'auto'
    ): void {
        if (! $storedPath) {
            return;
        }

        if ($this->isCloudinaryUrl($storedPath) && $this->shouldUseCloudinary()) {
            try {
                $this->configureCloudinary();
                $publicId = $this->extractPublicIdFromUrl($storedPath);
                if ($publicId) {
                    (new UploadApi())->destroy($publicId, [
                        'resource_type' => $resourceType,
                        'invalidate' => true,
                    ]);

                    return;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        Storage::disk($fallbackDisk)->delete($storedPath);
    }

    public function isCloudinaryUrl(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        $host = (string) parse_url($value, PHP_URL_HOST);

        return str_contains($host, 'res.cloudinary.com');
    }

    private function shouldUseCloudinary(): bool
    {
        if (! class_exists(UploadApi::class)) {
            return false;
        }

        $credentials = $this->resolveCloudinaryCredentials();

        return $credentials['cloud_name'] !== ''
            && $credentials['api_key'] !== ''
            && $credentials['api_secret'] !== '';
    }

    private function configureCloudinary(): void
    {
        $credentials = $this->resolveCloudinaryCredentials();

        Configuration::instance([
            'cloud' => [
                'cloud_name' => $credentials['cloud_name'],
                'api_key' => $credentials['api_key'],
                'api_secret' => $credentials['api_secret'],
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * @return array{cloud_name:string,api_key:string,api_secret:string}
     */
    private function resolveCloudinaryCredentials(): array
    {
        $cloudName = (string) config('services.cloudinary.cloud_name', '');
        $apiKey = (string) config('services.cloudinary.api_key', '');
        $apiSecret = (string) config('services.cloudinary.api_secret', '');

        if ($cloudName !== '' && $apiKey !== '' && $apiSecret !== '') {
            return [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ];
        }

        $url = (string) config('services.cloudinary.url', '');
        if ($url !== '') {
            $parsed = parse_url($url);
            if (is_array($parsed)) {
                $urlCloudName = (string) ($parsed['host'] ?? '');
                $urlApiKey = urldecode((string) ($parsed['user'] ?? ''));
                $urlApiSecret = urldecode((string) ($parsed['pass'] ?? ''));
                if ($urlCloudName !== '' && $urlApiKey !== '' && $urlApiSecret !== '') {
                    return [
                        'cloud_name' => $urlCloudName,
                        'api_key' => $urlApiKey,
                        'api_secret' => $urlApiSecret,
                    ];
                }
            }
        }

        return [
            'cloud_name' => '',
            'api_key' => '',
            'api_secret' => '',
        ];
    }

    private function extractPublicIdFromUrl(string $url): ?string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path), static fn ($segment) => $segment !== ''));
        $uploadIndex = array_search('upload', $segments, true);
        if ($uploadIndex === false) {
            return null;
        }

        $postUploadSegments = array_slice($segments, $uploadIndex + 1);
        $versionIndex = null;
        foreach ($postUploadSegments as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $versionIndex = $index;
                break;
            }
        }

        $filteredSegments = $versionIndex === null
            ? $postUploadSegments
            : array_slice($postUploadSegments, $versionIndex + 1);

        if (empty($filteredSegments)) {
            return null;
        }

        $publicIdWithExt = implode('/', $filteredSegments);

        return preg_replace('/\.[^\/.]+$/', '', $publicIdWithExt) ?: null;
    }
}
