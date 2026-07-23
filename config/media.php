<?php

return [
    /*
     * Base URL under which talk MP3 files are hosted. The database stores
     * relative paths like "8/original.mp3"; the public download endpoints
     * redirect to "<base_url>/<path>" after recording a download metric.
     * Defaults to /media on the app itself (public/media symlinks into
     * storage/app/media, which on production points at the MP3 archive).
     */
    'base_url' => rtrim((string) env('MEDIA_BASE_URL', '/media'), '/'),

    /*
     * Optional local filesystem root, if the MP3s are served from the same
     * host instead of a separate media domain. When set, downloads stream
     * from disk instead of redirecting.
     */
    'root' => env('MEDIA_ROOT') ?: null,
];
