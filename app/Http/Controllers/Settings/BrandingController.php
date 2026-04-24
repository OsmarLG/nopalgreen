<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBrandingRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('branding/index', [
            'branding' => AppSetting::current(),
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $branding = AppSetting::current();

        $branding->fill($request->safe()->only([
            'app_name',
            'app_tagline',
        ]));

        if ($request->hasFile('logo')) {
            if ($branding->logo_path !== null) {
                Storage::disk('public')->delete($branding->logo_path);
            }

            $branding->logo_path = $request->file('logo')->store('branding', 'public');
        }

        $branding->save();

        return to_route('branding.edit')->with('status', 'Marca actualizada correctamente.');
    }
}
