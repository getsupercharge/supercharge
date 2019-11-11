<?php

use Supercharge\SuperchargeUrl;

/**
 * Create the URL for an image using the Supercharge CDN.
 */
function supercharge(string $imagePath)
{
    return new SuperchargeUrl($imagePath);
}
