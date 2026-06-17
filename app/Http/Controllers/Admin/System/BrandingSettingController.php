<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.system.branding.edit', [
            'branding' => BrandingSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:100'],
            'primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'sidebar_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'login_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'favicon' => ['nullable', 'mimes:ico,png,jpg,jpeg,webp', 'max:1024'],
        ]);

        $branding = BrandingSetting::current();
        $payload = [
            'app_name' => $validated['app_name'] ?? null,
            'primary_color' => $validated['primary_color'] ?? null,
        ];

        foreach ([
            'sidebar_logo' => 'sidebar_logo_path',
            'login_logo' => 'login_logo_path',
            'favicon' => 'favicon_path',
        ] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $this->deletePublicFile($branding->{$column});
            $payload[$column] = $request->file($input)->store('branding', 'public');
        }

        $branding->fill($payload)->save();

        return redirect()
            ->route('admin.system.branding.edit')
            ->with('success', 'Branding aplikasi berhasil diperbarui.');
    }

    protected function deletePublicFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
