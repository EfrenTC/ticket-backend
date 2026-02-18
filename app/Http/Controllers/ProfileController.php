<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $request->user()->id,
            'avatar_url' => 'sometimes|nullable|url|max:255',
            'currency' => 'sometimes|string|max:10',
            'date_format' => 'sometimes|string|max:20',
            'language' => 'sometimes|string|max:10',
            'dark_mode' => 'sometimes|boolean',
            'report_frequency' => 'sometimes|in:none,weekly,monthly',
            'budget_alerts_enabled' => 'sometimes|boolean',
        ]);

        $request->user()->update($validated);

        return response()->json($request->user()->fresh(), 200);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'La contraseña actual no es correcta'], 422);
        }

        $request->user()->update([
            'password' => $validated['new_password'],
        ]);

        return response()->json(['message' => 'Contraseña actualizada'], 200);
    }
}
