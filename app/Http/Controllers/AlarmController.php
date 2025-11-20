<?php

namespace App\Http\Controllers;

use App\Alarm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlarmController extends Controller
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
     * Display a listing of all alarms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $alarms = Alarm::orderBy('day')
            ->orderBy('time')
            ->get();

        return response()->json([
            'success' => true,
            'alarms' => $alarms
        ]);
    }

    /**
     * Store a newly created alarm.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'time' => 'required|date_format:H:i',
            'label' => 'nullable|string|max:255',
            'sound' => 'required|string',
            'enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Check if sound file exists
        $soundPath = public_path('audio/' . $request->sound);
        if (!file_exists($soundPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Sound file not found: ' . $request->sound
            ], 400);
        }

        $alarm = Alarm::create([
            'day' => $request->day,
            'time' => $request->time,
            'label' => $request->label,
            'sound' => $request->sound,
            'enabled' => $request->has('enabled') ? $request->enabled : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alarm created successfully',
            'alarm' => $alarm
        ], 201);
    }

    /**
     * Update the specified alarm.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $alarm = Alarm::find($id);

        if (!$alarm) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'time' => 'required|date_format:H:i',
            'label' => 'nullable|string|max:255',
            'sound' => 'required|string',
            'enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Check if sound file exists
        $soundPath = public_path('audio/' . $request->sound);
        if (!file_exists($soundPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Sound file not found: ' . $request->sound
            ], 400);
        }

        $alarm->update([
            'day' => $request->day,
            'time' => $request->time,
            'label' => $request->label,
            'sound' => $request->sound,
            'enabled' => $request->has('enabled') ? $request->enabled : $alarm->enabled,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alarm updated successfully',
            'alarm' => $alarm
        ]);
    }

    /**
     * Partial update (e.g., enable/disable).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function patch(Request $request, $id)
    {
        $alarm = Alarm::find($id);

        if (!$alarm) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm not found'
            ], 404);
        }

        // Only allow updating specific fields
        $allowedFields = ['enabled', 'label'];
        $updates = [];

        foreach ($allowedFields as $field) {
            if ($request->has($field)) {
                $updates[$field] = $request->$field;
            }
        }

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid fields to update'
            ], 400);
        }

        $alarm->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Alarm updated successfully',
            'alarm' => $alarm
        ]);
    }

    /**
     * Remove the specified alarm.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $alarm = Alarm::find($id);

        if (!$alarm) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm not found'
            ], 404);
        }

        $alarm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alarm deleted successfully'
        ]);
    }

    /**
     * Get alarms for a specific day.
     *
     * @param  string  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByDay($day)
    {
        $alarms = Alarm::forDay($day)
            ->orderBy('time')
            ->get();

        return response()->json([
            'success' => true,
            'day' => $day,
            'alarms' => $alarms
        ]);
    }

    /**
     * Get only enabled alarms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEnabled()
    {
        $alarms = Alarm::enabled()
            ->orderBy('day')
            ->orderBy('time')
            ->get();

        return response()->json([
            'success' => true,
            'alarms' => $alarms
        ]);
    }
}
