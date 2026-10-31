<?php

namespace App\Http\Controllers;

use App\Services\PublicMediaUrl;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Serves only explicitly allow-listed public image uploads. */
class PublicMediaController extends Controller
{
    public function __invoke(string $path, PublicMediaUrl $media): Response
    {
        $path = $media->publicPath($path);
        abort_if($path === null, 404);

        try {
            /** @var FilesystemAdapter $disk Laravel's disk manager returns this adapter. */
            $disk = Storage::disk('public');
            $exists = $disk->exists($path);
            $size = $exists ? $disk->size($path) : 0;

            // Do not trust a host's fileinfo/MIME configuration. Inspect the
            // bounded bytes themselves, which works on restrictive shared hosts.
            $image = $size > 0 && $size <= config('public-media.max_bytes')
                ? @getimagesizefromstring($disk->get($path))
                : false;
            $mimeType = is_array($image) ? ($image['mime'] ?? null) : null;
        } catch (Throwable $exception) {
            report($exception);
            abort(404);
        }

        abort_unless($exists && in_array($mimeType, config('public-media.mime_types', []), true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => config('public-media.cache_control'),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], 'inline');
    }
}
