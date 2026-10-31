<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Publicly deliverable uploads
    |--------------------------------------------------------------------------
    |
    | These are the only directories on the public disk that may be served by
    | the unauthenticated media endpoint. Application and identification files
    | intentionally live on private disks and must use their authorized routes.
    |
    */
    'directories' => [
        'profile-images',
        'job-logos',
        'organization-branding',
        'platform-branding',
    ],

    /* Only image types accepted by the corresponding upload requests. */
    'mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    // Every current public image upload is limited to 2 MiB. The response
    // endpoint verifies image bytes rather than trusting host MIME metadata.
    'max_bytes' => 3 * 1024 * 1024,

    // Uploaded filenames are random, immutable keys. A bounded cache avoids
    // repeatedly streaming them through PHP while still allowing replacements.
    'cache_control' => 'public, max-age=86400, stale-while-revalidate=604800',

];
