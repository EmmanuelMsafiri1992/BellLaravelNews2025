<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DisplayController extends Controller
{
    /**
     * Display news for a specific screen using unique code
     */
    public function show($uniqueCode)
    {
        try {
            $screen = Screen::where('unique_code', $uniqueCode)
                           ->where('status', 'active')
                           ->firstOrFail();

            // Get published news assigned to this screen
            $news = $screen->news()
                          ->where('status', 'published')
                          ->with(['images', 'category'])
                          ->orderBy('date', 'desc')
                          ->get();

            // Fetch settings for header/footer (same as main screen)
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

            return view('display.show', compact('screen', 'news', 'settings'));

        } catch (\Exception $e) {
            Log::error('Error displaying screen: ' . $e->getMessage());
            return view('display.error')->with('message', 'Screen not found or inactive.');
        }
    }

    /**
     * API endpoint to get news for a specific screen (for auto-refresh)
     */
    public function getNews($uniqueCode)
    {
        try {
            $screen = Screen::where('unique_code', $uniqueCode)
                           ->where('status', 'active')
                           ->firstOrFail();

            $news = $screen->news()
                          ->where('status', 'published')
                          ->with(['images', 'category'])
                          ->orderBy('date', 'desc')
                          ->get();

            return response()->json([
                'success' => true,
                'screen' => $screen,
                'news' => $news
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching news for screen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Screen not found or inactive.'
            ], 404);
        }
    }
}
