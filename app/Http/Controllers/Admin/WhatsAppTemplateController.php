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
        $summaryQuery = WhatsAppMessageTemplate::query()
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id));
        $templates = WhatsAppMessageTemplate::query()
            ->with('provider')
            ->when($provider, fn ($query) => $query->where('provider_id', $provider->id))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.marketing.whatsapp-templates.index', [
            'provider' => $provider,
            'templates' => $templates,
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'approved' => (clone $summaryQuery)->where('status', 'APPROVED')->count(),
                'pending' => (clone $summaryQuery)->where('status', 'PENDING')->count(),
                'rejected' => (clone $summaryQuery)->where('status', 'REJECTED')->count(),
                'missing_on_meta' => (clone $summaryQuery)->where('status', WhatsAppMessageTemplate::STATUS_NOT_FOUND_ON_META)->count(),
            ],
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

        if ($readiness['blocking'] || $readiness['level'] === 'Risky') {
            return back()->withErrors(['body' => implode(' ', $readiness['reasons'])])->withInput();
        }

        $converted = $builder->convertVariables($data['body']);

        try {
            $metaResult = $builder->submitToMeta($provider, $data + ['safe_name' => $data['safe_name']]);
        } catch (\Throwable $exception) {
            return back()
                ->with('error', $this->metaSubmissionError($provider, $exception))
                ->withInput();
        }

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
            ->route('admin.marketing.whatsapp-templates.index')
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
        if (! $whatsappTemplate->isAvailableForMetaUse()) {
            return back()->with('error', 'Template tidak tersedia di Meta dan tidak bisa dijadikan default.');
        }

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

        if (! $whatsappTemplate->isAvailableForMetaUse()) {
            return response()->json([
                'success' => false,
                'reason' => 'Template tidak tersedia di Meta dan tidak bisa dikirim untuk test.',
            ], 422);
        }

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

    private function metaSubmissionError(WhatsAppProvider $provider, \Throwable $exception): string
    {
        $rawMessage = $exception->getMessage();
        $message = preg_replace('/\s+Provider:.*$/', '', $rawMessage) ?: $rawMessage;
        preg_match('/Error code:\s*([^\.]+)\./', $rawMessage, $codeMatch);
        preg_match('/Error type:\s*([^\.]+)\./', $rawMessage, $typeMatch);

        return implode("\n", [
            'Gagal submit template ke Meta.',
            'Provider: '.$provider->name,
            'WABA ID: '.($provider->business_account_id ?: '-'),
            'Endpoint: '.$this->metaTemplateEndpoint($provider),
            'Error code: '.trim($codeMatch[1] ?? '-'),
            'Error type: '.trim($typeMatch[1] ?? '-'),
            'Message: '.$message,
        ]);
    }

    private function metaTemplateEndpoint(WhatsAppProvider $provider): string
    {
        return rtrim((string) ($provider->api_url ?: 'https://graph.facebook.com'), '/')
            .'/'.trim((string) ($provider->graph_api_version ?: 'v23.0'), '/')
            .'/'.trim((string) $provider->business_account_id)
            .'/message_templates';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function presets(): array
    {
        return [
            'notifikasi_pelanggan' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, kami ingin menginformasikan bahwa permintaan Anda telah kami terima dan sedang diproses oleh tim kami. Terima kasih.'],
            'konfirmasi_permintaan' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, permintaan Anda dengan nomor {{no_order}} telah kami terima. Tim kami akan segera melakukan tindak lanjut. Terima kasih.'],
            'pengingat_jadwal' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, ini adalah pengingat untuk jadwal Anda pada {{tanggal}}. Mohon pastikan Anda hadir sesuai waktu yang telah ditentukan. Terima kasih.'],
            'informasi_layanan' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, kami ingin menyampaikan informasi terkait layanan Anda. Silakan hubungi tim kami jika membutuhkan bantuan lebih lanjut. Terima kasih.'],
            'status_pesanan' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, pesanan Anda dengan nomor {{no_order}} sedang kami proses. Kami akan menginformasikan pembaruan berikutnya setelah tersedia. Terima kasih.'],
            'konfirmasi_pembayaran' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, pembayaran untuk nomor transaksi {{no_order}} telah kami terima. Terima kasih atas kerja samanya.'],
            'jadwal_kunjungan' => ['category' => 'UTILITY', 'label' => 'Direkomendasikan / Approval lebih aman', 'body' => 'Halo {{nama}}, jadwal kunjungan Anda telah ditetapkan pada {{tanggal}}. Tim kami akan menghubungi Anda untuk konfirmasi lebih lanjut. Terima kasih.'],
            'promo_produk' => ['category' => 'MARKETING', 'label' => 'Perlu review lebih ketat', 'body' => 'Halo {{nama}}, kami memiliki informasi produk terbaru yang mungkin sesuai dengan kebutuhan Anda. Silakan hubungi tim kami untuk mengetahui detail penawaran yang tersedia.'],
            'promo_layanan' => ['category' => 'MARKETING', 'label' => 'Perlu review lebih ketat', 'body' => 'Halo {{nama}}, kami ingin memperkenalkan layanan terbaru dari tim kami yang dapat membantu kebutuhan Anda. Tim kami siap memberikan informasi lebih lanjut.'],
            'penawaran_spesial' => ['category' => 'MARKETING', 'label' => 'Perlu review lebih ketat', 'body' => 'Halo {{nama}}, ada penawaran khusus yang dapat Anda pertimbangkan untuk kebutuhan Anda saat ini. Hubungi tim kami untuk mendapatkan informasi selengkapnya.'],
            'follow_up_pelanggan' => ['category' => 'MARKETING', 'label' => 'Perlu review lebih ketat', 'body' => 'Halo {{nama}}, kami ingin menindaklanjuti ketertarikan Anda terhadap layanan kami. Tim kami siap membantu menjawab pertanyaan yang Anda miliki.'],
            'otp_verifikasi' => ['category' => 'AUTHENTICATION', 'label' => 'Khusus OTP/kode', 'body' => 'Kode verifikasi Anda adalah {{otp}}. Jangan bagikan kode ini kepada siapa pun.'],
            'kode_login' => ['category' => 'AUTHENTICATION', 'label' => 'Khusus OTP/kode', 'body' => 'Gunakan kode {{otp}} untuk masuk ke akun Anda. Kode ini bersifat rahasia dan hanya berlaku sementara.'],
        ];
    }
}
