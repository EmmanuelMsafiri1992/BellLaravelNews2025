<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ScreenController extends Controller
{
    public function index()
    {
        try {
            $screens = Screen::latest()->paginate(10);
            return view('admin.screens.index', compact('screens'));
        } catch (\Exception $e) {
            Log::error('Error fetching screens: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load screens. Please try again.');
        }
    }

    public function create()
    {
        try {
            return view('admin.screens.create');
        } catch (\Exception $e) {
            Log::error('Error loading screen creation form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load the creation form. Please try again.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive',
            ]);

            // Generate unique code
            $uniqueCode = Screen::generateUniqueCode($request->name);

            $screen = Screen::create([
                'name' => $request->name,
                'unique_code' => $uniqueCode,
                'location' => $request->location,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Screen created successfully!',
                'redirect' => route('admin.screens.index')
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation error creating screen: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating screen: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create screen. Please try again.'], 500);
        }
    }

    public function edit(Screen $screen)
    {
        try {
            return view('admin.screens.edit', compact('screen'));
        } catch (\Exception $e) {
            Log::error('Error loading screen edit form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load the edit form. Please try again.');
        }
    }

    public function update(Request $request, Screen $screen)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive',
            ]);

            $screen->update([
                'name' => $request->name,
                'location' => $request->location,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Screen updated successfully!',
                'redirect' => route('admin.screens.index')
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation error updating screen: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating screen: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update screen. Please try again.'], 500);
        }
    }

    public function destroy(Screen $screen)
    {
        try {
            $screen->delete();

            return redirect()->route('admin.screens.index')
                           ->with('success', 'Screen deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting screen: ' . $e->getMessage());

            return redirect()->route('admin.screens.index')
                           ->with('error', 'Failed to delete screen. Please try again.');
        }
    }
}
