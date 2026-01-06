<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


// save code    
public function uploadToS3()
{
    try {
        $path = 'astrology-blog/test1.txt';
        $content = 'This is a test new file';

        $uploaded = Storage::disk('s3')->put($path, $content);

        if ($uploaded && Storage::disk('s3')->exists($path)) {
            return response()->json(['message' => 'File uploaded successfully!']);
        } else {
            return response()->json(['error' => 'Upload failed or file not accessible after upload.']);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
}


// fetch code
public function checkS3Files()
{
    $files = Storage::disk('s3')->files('astrology-blog');

    if (empty($files)) {
        return response()->json(['message' => 'No files found in S3.']);
    }

    return response()->json(['files' => $files]);
}


}
