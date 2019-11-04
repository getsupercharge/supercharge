<?php

/**
 * Create the URL for an image using the Supercharge CDN.
 */
function supercharge(string $imagePath)
{
    $baseUrl = config('supercharge.url');

    if (! $baseUrl) {
        return $imagePath;
    }

    return $baseUrl . '/' . ltrim($imagePath, '/');
}
