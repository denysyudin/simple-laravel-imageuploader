<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

// First route - Generate image using API
Route::post('/upload', function (Request $request) {
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $description = $request->input('description');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => env('STABILITY_API_KEY'),
                'accept' => 'application/json',
            ])->attach(
                'image', file_get_contents($image->getRealPath()), $image->getClientOriginalName(), [
                    'Content-Type' => $image->getMimeType(),
                ]
            )->post(env('API_ENDPOINT'), [
                'prompt' => $description,
                'output_format' => 'png',
                'fidelity' => 1,
                'mode' => 'image-to-image',
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::debug('API Response:', $responseData);
                
                if (!isset($responseData['image']) || !isset($responseData['finish_reason'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid API response structure'
                    ], 400);
                }

                if ($responseData['finish_reason'] !== 'SUCCESS') {
                    return response()->json([
                        'success' => false,
                        'error' => 'API processing failed: ' . $responseData['finish_reason']
                    ], 400);
                }
                
                $imageData = base64_decode($responseData['image']);
                $filename = 'generated_' . time() . '_' . uniqid() . '.png';
                
                if (!Storage::disk('public')->exists('generated')) {
                    Storage::disk('public')->makeDirectory('generated');
                }
                
                Storage::disk('public')->put('generated/' . $filename, $imageData);
                
                return response()->json([
                    'success' => true,
                    'image_path' => Storage::url('generated/' . $filename)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to process image: ' . $response->body()
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'API connection error: ' . $e->getMessage()
            ], 500);
        }
    }
    return response()->json([
        'success' => false,
        'error' => 'No image selected'
    ], 400);
})->name('upload');

// Second route - Merge images and download
Route::post('/merge', function (Request $request) {
    if ($request->hasFile('image')) {
        try {
            $manager = new ImageManager(new Driver());
            
            // Get the overlay image
            $overlayImage = $request->file('image');
            
            // Get the base image path
            $baseImagePath = str_replace('/storage/', '', $request->input('base_image'));
            $baseImagePath = storage_path('app/public/' . $baseImagePath);
            
            // Get positioning data
            $x = (int)$request->input('overlay_x', 0);
            $y = (int)$request->input('overlay_y', 0);
            $width = (int)$request->input('overlay_width', 200);
            $height = (int)$request->input('overlay_height', 200);

            // Create image instances and merge
            $baseImg = $manager->read($baseImagePath);
            Log::debug('Base Image:');
            $overlayImg = $manager->read($overlayImage->getRealPath())
                ->resize($width, $height);
            Log::debug('Overlay Image:');
            $mergedImg = $baseImg->place($overlayImg, 'top-left', abs($x), abs($y));
            // Generate merged image data
            if ($mergedImg == $baseImg) {
                Log::debug('Failed to merge images');
            }
            $imageData = $mergedImg->encode()->toString();
            Storage::disk('public')->put('generated/' . "merged_image.png", base64_decode($imageData));
            // Return image for download
            return response($imageData)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="merged_image.png"');

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error processing images: ' . $e->getMessage()
            ], 500);
        }
    }
    return response()->json([
        'success' => false,
        'error' => 'No image selected'
    ], 400);
})->name('merge');

Route::get('/results', function (Request $request) {
    return view('results')->with([
        'generatedImage' => $request->query('image')
    ]);
})->name('results');