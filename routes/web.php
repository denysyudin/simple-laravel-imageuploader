<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
});

Route::post('/upload', function (Request $request) {
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer sk-CnyqUIgVZR5uKlrMK39ERsftNbMu6L3Q98lYe0C6NvjylPB8',
            ])->attach(
                'image', file_get_contents($image->getRealPath()), $image->getClientOriginalName(), [
                    'Content-Type' => $image->getMimeType(),
                ]
            )->post('https://api.stability.ai/v2beta/stable-image/generate/sd3', [
                'prompt' => 'A beautiful image of a cat',
                'output_format' => 'png',
                'strength' => 0.5,
                'mode' => 'image-to-image',
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                $imageData = base64_decode($responseData['image']);
                
                return response($imageData)
                    ->header('Content-Type', 'image/png')
                    ->header('Content-Disposition', 'attachment; filename="generated_image.png"');
            } else {
                return back()->with('error', 'Failed to process image: ' . $response->body());
            }
        } catch (\Exception $e) {
            return back()->with('error', 'API connection error: ' . $e->getMessage());
        }
    }
    return back()->with('error', 'No image selected');
})->name('upload');
