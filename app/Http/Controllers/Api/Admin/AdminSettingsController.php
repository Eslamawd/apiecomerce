<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->groupBy('group')->map(
            fn ($group) => $group->pluck('value', 'key')
        );

        return response()->json(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings'         => 'required|array',
            'settings.*.key'   => 'required|string|max:100',
            'settings.*.value' => 'nullable',
            'settings.*.type'  => 'nullable|in:string,boolean,integer,json',
            'settings.*.group' => 'nullable|string|max:50',
        ]);

        $updated = [];

        foreach ($request->settings as $item) {
            $setting = Setting::set(
                $item['key'],
                $item['value'] ?? null,
                $item['type'] ?? 'string',
                $item['group'] ?? 'general'
            );

            $updated[] = $setting;
        }

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $updated,
        ]);
    }
}
