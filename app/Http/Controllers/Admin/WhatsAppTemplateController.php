<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\MetaTemplateSyncService;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppTemplateBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $provider = $this->metaProvider();
        $templates = WhatsAppMessageTemplate::query()
            ->with('provider')
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.marketing.whatsapp-templates.index', [
            'provider' => $provider,
            'templates' => $templates,
        ]);
    }

    public function create(WhatsAppTemplateBuilder $builder): View
    {
        return view('admin.marketing.whatsapp-templates.form', [
            'provider' => $this->metaProvider(),
            'template' => null,
            'presets' => $this->presets(),
            'examples' => WhatsAppTemplateBuilder::EXAMPLES,
            'readiness' => $builder->readiness('UTILITY', ''),
        ]);
    }

    public function store(Request $request, WhatsAppTemplateBuilder $builder): RedirectResponse
    {
        $provider = $this->metaProvider();

        if ($provider === null) {
            return back()->with('error', 'Hubungkan WhatsApp Business Cloud API terlebih dahulu.')->withInput();
        }

        $data = $this->validatedData($request, $builder);
        $readiness = $builder->readiness($data['category'], $data['body']);

        if ($readiness['blocking']) {
            return back()->withErrors(['body' => implode(' ', $readiness['reasons'])])->withInput();
        }

        $converted = $builder->convertVariables($data['body']);
        $metaResult = $builder->submitToMeta($provider, $data + ['safe_name' => $data['safe_name']]);

        $template = WhatsAppMessageTemplate::query()->updateOrCreate(
            [
                'provider_id' => $provider->id,
                'name' => $data['safe_name'],
                'language' => $data['language'],
            ],
            [
                'template_id' => $metaResult['template_id'],
                'safe_name' => $data['safe_name'],
                'category' => $data['category'],
                'status' => 'PENDING',
                'header' => $data['header'],
                'body' => $data['body'],
                'body_meta' => $converted['meta_text'],
                'footer' => $data['footer'],
                'buttons' => null,
                'variable_mapping' => $converted['mapping'],
                'raw' => $metaResult['raw'],
                'source' => 'manual',
                'submitted_at' => now(),
                'last_synced_at' => now(),
            ],
        );

        return redirect()
            ->route('admin.marketing.whatsapp-templates.show', $template)
            ->with('success', 'Template berhasil dikirim ke Meta untuk ditinjau.');
    }

    public function show(WhatsAppMessageTemplate $whatsappTemplate): View
    {
        return view('admin.marketing.whatsapp-cloud-api.show', [
            'template' => $whatsappTemplate->load('provider'),
        ]);
    }

    public function edit(WhatsAppMessageTemplate $whatsappTemplate, WhatsAppTemplateBuilder $builder): View
    {
        return view('admin.marketing.whatsapp-templates.form', [
            'provider' => $whatsappTemplate->provider,
            'template' => $whatsappTemplate,
            'presets' => $this->presets(),
            'examples' => WhatsAppTemplateBuilder::EXAMPLES,
            'readiness' => $builder->readiness($whatsappTemplate->category ?: 'UTILITY', $whatsappTemplate->body ?: ''),
        ]);
    }

    public function update(Request $request, WhatsAppMessageTemplate $whatsappTemplate, WhatsAppTemplateBuilder $builder): RedirectResponse
    {
        $data = $this->validatedData($request, $builder);
        $converted = $builder->convertVariables($data['body']);

        $whatsappTemplate->update([
            'name' => $data['safe_name'],
            'safe_name' => $data['safe_name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'header' => $data['header'],
            'body' => $data['body'],
            'body_meta' => $converted['meta_text'],
            'footer' => $data['footer'],
            'variable_mapping' => $converted['mapping'],
            'source' => $whatsappTemplate->source ?: 'manual',
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-templates.show', $whatsappTemplate)
            ->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(WhatsAppMessageTemplate $whatsappTemplate): RedirectResponse
    {
        $whatsappTemplate->delete();

        return redirect()
            ->route('admin.marketing.whatsapp-templates.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    public function sync(MetaTemplateSyncService $syncService): RedirectResponse
    {
        $provider = $this->metaProvider();

        if ($provider === null) {
            return back()->with('error', 'Hubungkan WhatsApp Business Cloud API terlebih dahulu.');
        }

        try {
            $result = $syncService->sync($provider);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "{$result['synced']} template berhasil disinkronkan.");
    }

    public function setDefault(WhatsAppMessageTemplate $whatsappTemplate): RedirectResponse
    {
        $whatsappTemplate->provider->messageTemplates()
            ->whereKeyNot($whatsappTemplate->id)
            ->update(['is_default' => false]);
        $whatsappTemplate->update(['is_default' => true]);
        $whatsappTemplate->provider->update([
            'meta_template_name' => $whatsappTemplate->name,
            'meta_template_language' => $whatsappTemplate->language,
        ]);

        return back()->with('success', 'Template dijadikan default.');
    }

    public function sendTest(Request $request, WhatsAppMessageTemplate $whatsappTemplate): JsonResponse
    {
        $validated = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $result = (new MetaWhatsAppService($whatsappTemplate->provider))->sendTemplateMessage(
            $validated['phone'],
            $whatsappTemplate->name,
            $whatsappTemplate->language,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function validatedData(Request $request, WhatsAppTemplateBuilder $builder): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['MARKETING', 'UTILITY', 'AUTHENTICATION'])],
            'language' => ['required', Rule::in(['id', 'en_US'])],
            'header' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1024'],
            'footer' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['safe_name'] = $builder->safeName($validated['name']);

        if ($validated['safe_name'] === '') {
            abort(422, 'Nama template tidak valid.');
        }

        return $validated;
    }

    private function metaProvider(): ?WhatsAppProvider
    {
        return WhatsAppProvider::query()
            ->where('provider', 'meta')
            ->where('is_default', true)
            ->first()
            ?? WhatsAppProvider::query()->where('provider', 'meta')->where('status', 'active')->latest()->first();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function presets(): array
    {
        return [
            'notifikasi_pelanggan' => ['category' => 'UTILITY', 'body' => 'Halo {{nama}}, ada notifikasi baru terkait layanan Anda.'],
            'konfirmasi_permintaan' => ['category' => 'UTILITY', 'body' => 'Halo {{nama}}, permintaan Anda dengan kode {{kode}} sudah kami terima.'],
            'pengingat_jadwal' => ['category' => 'UTILITY', 'body' => 'Halo {{nama}}, jadwal Anda pada {{tanggal}}.'],
            'ucapan_terima_kasih' => ['category' => 'UTILITY', 'body' => 'Terima kasih {{nama}}, kami sudah menerima konfirmasi Anda.'],
            'informasi_layanan' => ['category' => 'UTILITY', 'body' => 'Halo {{nama}}, berikut informasi terbaru mengenai layanan Anda.'],
            'selamat_datang' => ['category' => 'UTILITY', 'body' => 'Selamat datang {{nama}}, akun Anda sudah aktif.'],
            'otp_verifikasi' => ['category' => 'AUTHENTICATION', 'body' => 'Kode OTP verifikasi Anda adalah {{otp}}.'],
            'undangan_acara' => ['category' => 'MARKETING', 'body' => 'Halo {{nama}}, Anda diundang ke acara kami pada {{tanggal}}.'],
        ];
    }
}
