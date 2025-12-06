<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload.
     * // BUG-003 FIX: Align Laravel validation (initially 20MB)
     * // with the actual PHP Dockerfile limit (10MB) for consistent error messages.
     * // Load the image *once* per size iteration (prevents memory issues vs loading 6 times)
     * // Resize: Adjust width to $width while maintaining aspect ratio
     *
     */
    public function upload(Request $request)
    {

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10240 KB = 10MB
        ]);


        $uploadedFile = $request->file('image');
        $directory = 'images';

        $baseName = Str::uuid()->toString();

        $sizes = [
            'thumb' => 300,
            'medium' => 800,
            'large' => 1200,
        ];


        $formats = ['webp', 'jpg'];


        $urls = [];

        foreach ($sizes as $prefix => $width) {


            $baseImage = Image::make($uploadedFile);


            $baseImage->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            foreach ($formats as $format) {
                $filename = $baseName . '-' . $prefix . '.' . $format;
                $storagePath = $directory . '/' . $filename;

                $encodedImage = $baseImage->encode($format, 80);

                Storage::disk('public')->put($storagePath, (string) $encodedImage, 'public');

                $urls[$prefix][$format] = Storage::disk('public')->url($storagePath);
            }
        }


        return response()->json([
            'message' => 'Images generated, resized, and compressed successfully.',
            'base_name' => $baseName,
            'max_size' => Storage::disk('public')->size($directory . '/' . $baseName . '-large.webp'),
            'urls' => $urls,
        ], 201);
    }



    /**
     * Delete an uploaded image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['error' => 'Image not found'], 404);
    }
}

