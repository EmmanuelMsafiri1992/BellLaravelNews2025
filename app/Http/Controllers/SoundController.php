<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class SoundController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all available sound files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $audioPath = public_path('audio');

        // Create audio directory if it doesn't exist
        if (!File::exists($audioPath)) {
            File::makeDirectory($audioPath, 0755, true);
        }

        $files = File::files($audioPath);
        $sounds = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());

            // Only include audio files
            if (in_array($extension, ['mp3', 'wav', 'ogg'])) {
                $sounds[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'extension' => $extension,
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'sounds' => $sounds
        ]);
    }

    /**
     * Upload a new sound file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:mp3,wav,ogg|max:2048', // max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Sanitize filename to prevent path traversal
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);

        // Ensure filename has proper extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['mp3', 'wav', 'ogg'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type. Only MP3, WAV, and OGG files are allowed.'
            ], 400);
        }

        $audioPath = public_path('audio');

        // Create audio directory if it doesn't exist
        if (!File::exists($audioPath)) {
            File::makeDirectory($audioPath, 0755, true);
        }

        $destinationPath = $audioPath . '/' . $filename;

        // Check if file already exists
        if (File::exists($destinationPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File already exists: ' . $filename
            ], 409);
        }

        try {
            $file->move($audioPath, $filename);

            return response()->json([
                'success' => true,
                'message' => 'Sound file uploaded successfully',
                'filename' => $filename,
                'size' => File::size($destinationPath)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a sound file.
     *
     * @param  string  $filename
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($filename)
    {
        // Sanitize filename to prevent path traversal
        $filename = basename($filename);

        $filePath = public_path('audio/' . $filename);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Sound file not found: ' . $filename
            ], 404);
        }

        // Check if file is being used by any alarms
        $alarmsUsingSound = \App\Alarm::where('sound', $filename)->count();

        if ($alarmsUsingSound > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sound file. It is being used by ' . $alarmsUsingSound . ' alarm(s).'
            ], 400);
        }

        try {
            File::delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Sound file deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test a sound file (validation only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $filename = basename($request->filename);
        $filePath = public_path('audio/' . $filename);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Sound file not found: ' . $filename
            ], 404);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, ['mp3', 'wav', 'ogg'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid audio file format'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sound file is valid and ready for playback',
            'filename' => $filename,
            'size' => File::size($filePath),
            'extension' => $extension
        ]);
    }
}
