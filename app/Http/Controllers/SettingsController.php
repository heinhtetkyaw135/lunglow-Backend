<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $settings = $request->user()->settings()->firstOrCreate(
            [],
            ['daily_limit' => 20, 'cig_price' => 0.50]
        );

        return response()->json(['settings' => [
            'daily_limit' => $settings->daily_limit,
            'cig_price' => (float) $settings->cig_price,
        ]]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'daily_limit' => 'required|integer|min:1',
            'cig_price' => 'required|numeric|min:0',
        ]);

        $settings = $request->user()->settings()->firstOrCreate([]);
        $settings->update($data);

        return response()->json(['settings' => [
            'daily_limit' => $settings->daily_limit,
            'cig_price' => (float) $settings->cig_price,
        ]]);
    }
}
