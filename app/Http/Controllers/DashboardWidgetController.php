<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    public function index(Request $request)
    {
        $widgets = $request->user()->dashboardWidgets()->orderBy('position')->get();

        return response()->json($widgets, 200);
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'widgets' => 'required|array|min:1',
            'widgets.*.widget_key' => 'required|string|max:100',
            'widgets.*.position' => 'required|integer|min:0',
            'widgets.*.enabled' => 'required|boolean',
            'widgets.*.settings' => 'nullable|array',
        ]);

        foreach ($validated['widgets'] as $widgetData) {
            $request->user()->dashboardWidgets()->updateOrCreate(
                ['widget_key' => $widgetData['widget_key']],
                [
                    'position' => $widgetData['position'],
                    'enabled' => $widgetData['enabled'],
                    'settings' => $widgetData['settings'] ?? null,
                ]
            );
        }

        return response()->json(
            $request->user()->dashboardWidgets()->orderBy('position')->get(),
            200
        );
    }
}
