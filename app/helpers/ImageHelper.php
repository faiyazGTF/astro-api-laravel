<?php

namespace App\helpers;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
//     public static function getImageUrl($path = '', $path2 = '', $filename = '')
// {
//     $baseUrl = rtrim(env('IMAGE_BASE_URL2', asset('/')), '/');

//     $fullPath = trim($path, '/');
//     if (!empty($path2)) {
//         $fullPath .= '/' . trim($path2, '/');
//     }
//     $fullPath .= '/' . ltrim($filename, '/');

//     // This line removes double slashes
//     return rtrim($baseUrl, '/') . '/' . ltrim($fullPath, '/');
// }



public static function getImageUrl($filename,$path = '',)
{
    // if (env('FILESYSTEM_DISK') === 's3') {
    //     $s3Path = implode('/', array_map(fn($p) => trim($p, '/'), array_filter([$path, $path2, $filename])));
    //     return Storage::disk('s3')->url($s3Path);
    // }

    // $pathParts = array_filter([$path, $path2, $filename]);
    // $fullPath = implode('/', $pathParts);
    // return $fullPath;
    $baseUrl = env('AWS_CLOUDFRONT_URL');

    return $baseUrl.$path.$filename;
}



}
