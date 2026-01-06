<?php

use App\helpers\ImageHelper;

if (!function_exists('image_url')) {
    function image_url($path = '', $path2 = '', $filename = '')
    {
        return ImageHelper::getImageUrl($path, $path2, $filename);
    }
}
