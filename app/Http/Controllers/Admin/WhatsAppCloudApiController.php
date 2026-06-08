<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPreference;
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
    public function index(): View
    {
        $provider = $this->metaProvider();
        $templates = $provider
            ? $provider->messageTemplates()->latest('last_synced_at')->latest()->paginate(10)
            : WhatsAppMessageTemplate::query()->whereRaw('1 = 0')->paginate(10);

        return view('admin.marketing.whatsapp-cloud-api.index', [
            'provider' => $provider,
            'templates' => $templates,
            'stats' => $this->stats($provider),
        ]);
    }

    public function sync(MetaTemplateSyncService $syncService): RedirectResponse
    {
        $provider = $this->metaProvider();

        if ($provider === null) {
            return back()->with('error', 'Provider Meta WhatsApp belum tersedia.');
        }

        try {
            $result = $syncService->sync($provider);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "{$result['synced']} template berhasil disinkronkan dari Meta.");
    }

    public function setDefault(WhatsAppMessageTemplate $template): RedirectResponse
    {
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

    private function metaProvider(): ?WhatsAppProvider
    {
        return WhatsAppProvider::query()
            ->where('provider', 'meta')
            ->where('is_default', true)
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
            'opt_in_contacts' => CustomerPreference::query()
                ->where('preferred_channel', 'whatsapp')
                ->where('communication_consent', true)
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
