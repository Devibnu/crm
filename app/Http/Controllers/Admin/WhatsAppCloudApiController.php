<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\MetaTemplateSyncService;
use App\Services\WhatsApp\MetaWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppCloudApiController extends Controller
{
    public function index(Request $request): View
    {
        $provider = $this->metaProvider($request->integer('provider_id') ?: null);
        $providers = WhatsAppProvider::query()
            ->where('provider', 'meta')
            ->orderByDesc('is_default')
            ->orderByRaw("CASE WHEN LOWER(name) = 'meta primary' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        $templates = $provider
            ? $provider->messageTemplates()->latest('last_synced_at')->latest()->paginate(10)->withQueryString()
            : WhatsAppMessageTemplate::query()->whereRaw('1 = 0')->paginate(10)->withQueryString();

        return view('admin.marketing.whatsapp-cloud-api.index', [
            'provider' => $provider,
            'providers' => $providers,
            'templates' => $templates,
            'stats' => $this->stats($provider),
        ]);
    }

    public function sync(Request $request, MetaTemplateSyncService $syncService): RedirectResponse
    {
        $provider = $this->metaProvider($request->integer('provider_id') ?: null);

        if ($provider === null) {
            return back()->with('error', 'Provider Meta WhatsApp belum tersedia.');
        }

        try {
            $result = $syncService->sync($provider);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id])
            ->with('success', "{$result['synced']} template berhasil disinkronkan dari Meta. WABA ID: {$result['waba_id']}.");
    }

    public function refreshConnection(Request $request, MetaTemplateSyncService $syncService): RedirectResponse
    {
        $provider = $this->metaProvider($request->integer('provider_id') ?: null);

        if ($provider === null) {
            return back()->with('error', 'Provider Meta WhatsApp belum tersedia.');
        }

        try {
            $result = $syncService->refreshConnection($provider);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id])
            ->with('success', 'Koneksi Meta berhasil direfresh untuk '.$result['display_phone_number'].'.');
    }

    public function setDefault(WhatsAppMessageTemplate $template): RedirectResponse
    {
        $template->provider->messageTemplates()
            ->whereKeyNot($template->id)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);
        $template->provider->update([
            'meta_template_name' => $template->name,
            'meta_template_language' => $template->language,
        ]);

        return back()->with('success', "Template {$template->name} dijadikan default.");
    }

    public function sendTest(Request $request, WhatsAppMessageTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        if ($template->status !== 'APPROVED') {
            return response()->json([
                'success' => false,
                'reason' => 'Hanya template APPROVED yang bisa dikirim untuk test.',
            ], 422);
        }

        $result = (new MetaWhatsAppService($template->provider))->sendTemplateMessage(
            $validated['phone'],
            $template->name,
            $template->language,
            [
                'components' => $this->exampleComponents($template),
            ],
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function show(WhatsAppMessageTemplate $template): View
    {
        return view('admin.marketing.whatsapp-cloud-api.show', [
            'template' => $template->load('provider'),
        ]);
    }

    private function metaProvider(?int $providerId = null): ?WhatsAppProvider
    {
        if ($providerId !== null) {
            $provider = WhatsAppProvider::query()
                ->where('provider', 'meta')
                ->whereKey($providerId)
                ->first();

            if ($provider !== null) {
                return $provider;
            }
        }

        return WhatsAppProvider::query()
            ->where('provider', 'meta')
            ->where('is_default', true)
            ->first()
            ?? WhatsAppProvider::query()
                ->where('provider', 'meta')
                ->whereRaw('LOWER(name) = ?', ['meta primary'])
                ->first()
            ?? WhatsAppProvider::query()
                ->where('provider', 'meta')
                ->where('status', 'active')
                ->latest()
                ->first()
            ?? WhatsAppProvider::query()
                ->where('provider', 'meta')
                ->latest()
                ->first();
    }

    /**
     * @return array<string, int>
     */
    private function stats(?WhatsAppProvider $provider): array
    {
        $templateQuery = WhatsAppMessageTemplate::query()
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id));

        return [
            'total_templates' => (clone $templateQuery)->count(),
            'approved_templates' => (clone $templateQuery)->where('status', 'APPROVED')->count(),
            'pending_templates' => (clone $templateQuery)->where('status', 'PENDING')->count(),
            'rejected_templates' => (clone $templateQuery)->where('status', 'REJECTED')->count(),
            'sent_messages' => WhatsAppMessage::query()
                ->where('provider', 'meta')
                ->where('direction', 'outbound')
                ->whereIn('status', ['sent', 'delivered', 'read'])
                ->count(),
            'delivered_messages' => WhatsAppMessage::query()
                ->where('provider', 'meta')
                ->where('direction', 'outbound')
                ->where('status', 'delivered')
                ->count(),
            'read_messages' => WhatsAppMessage::query()
                ->where('provider', 'meta')
                ->where('direction', 'outbound')
                ->where('status', 'read')
                ->count(),
            'failed_messages' => WhatsAppMessage::query()
                ->where('provider', 'meta')
                ->where('direction', 'outbound')
                ->where('status', 'failed')
                ->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exampleComponents(WhatsAppMessageTemplate $template): array
    {
        if (! $template->hasVariables()) {
            return [];
        }

        return [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'Ibnu',
                    ],
                ],
            ],
        ];
    }
}
