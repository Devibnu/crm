<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $provider = trim((string) $request->query('provider', ''));
        $status = trim((string) $request->query('status', ''));

        $providers = WhatsAppProvider::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . mb_strtolower($search) . '%';

                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery
                        ->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(provider) LIKE ?', [$term]);
                });
            })
            ->when(in_array($provider, $this->providerOptions(), true), fn ($query) => $query->where('provider', $provider))
            ->when(in_array($status, $this->statusOptions(), true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $defaultProvider = WhatsAppProvider::query()->default()->first();

        return view('admin.system.whatsapp-providers.index', [
            'providers' => $providers,
            'search' => $search,
            'selectedProvider' => $provider,
            'selectedStatus' => $status,
            'providerOptions' => $this->providerOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => [
                'total' => WhatsAppProvider::query()->count(),
                'active' => WhatsAppProvider::query()->active()->count(),
                'default' => $defaultProvider?->name ?? '-',
                'last_connected' => WhatsAppProvider::query()->whereNotNull('last_connected_at')->max('last_connected_at'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.system.whatsapp-providers.create', [
            'whatsappProvider' => null,
            'providerOptions' => $this->providerOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $provider = DB::transaction(function () use ($request) {
            $data = $this->validatedData($request);

            if ($data['is_default']) {
                WhatsAppProvider::query()->update(['is_default' => false]);
            }

            return WhatsAppProvider::create($data);
        });

        return redirect()
            ->route('admin.system.whatsapp-providers.show', $provider)
            ->with('success', 'WhatsApp provider berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(WhatsAppProvider $whatsappProvider): View
    {
        return view('admin.system.whatsapp-providers.show', [
            'whatsappProvider' => $whatsappProvider,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WhatsAppProvider $whatsappProvider): View
    {
        return view('admin.system.whatsapp-providers.edit', [
            'whatsappProvider' => $whatsappProvider,
            'providerOptions' => $this->providerOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WhatsAppProvider $whatsappProvider): RedirectResponse
    {
        DB::transaction(function () use ($request, $whatsappProvider) {
            $data = $this->validatedData($request);

            if ($data['is_default']) {
                WhatsAppProvider::query()
                    ->whereKeyNot($whatsappProvider->id)
                    ->update(['is_default' => false]);
            }

            $whatsappProvider->update($data);
        });

        return redirect()
            ->route('admin.system.whatsapp-providers.show', $whatsappProvider)
            ->with('success', 'WhatsApp provider berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WhatsAppProvider $whatsappProvider): RedirectResponse
    {
        $whatsappProvider->delete();

        return redirect()
            ->route('admin.system.whatsapp-providers.index')
            ->with('success', 'WhatsApp provider berhasil dihapus.');
    }

    public function testSend(Request $request, WhatsAppManager $manager): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
            'send_mode' => ['nullable', Rule::in(['text', 'template_hello_world'])],
        ]);
        $sendMode = $validated['send_mode'] ?? 'text';

        if ($sendMode === 'text' && trim((string) ($validated['message'] ?? '')) === '') {
            return response()->json([
                'success' => false,
                'provider' => null,
                'message_id' => null,
                'raw' => [
                    'error' => 'Message is required for free text test send.',
                ],
            ], 422);
        }

        try {
            $result = $sendMode === 'template_hello_world'
                ? $manager->sendTemplateMessage($validated['phone'], 'hello_world', 'en_US')
                : $manager->sendMessage($validated['phone'], (string) ($validated['message'] ?? ''));

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'provider' => null,
                'message_id' => null,
                'raw' => [
                    'error' => $exception->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', Rule::in($this->providerOptions())],
            'api_url' => ['nullable', 'string', 'max:255'],
            'api_token' => ['nullable', 'string'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'business_account_id' => ['nullable', 'string', 'max:255'],
            'graph_api_version' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'last_connected_at' => ['nullable', 'date'],
        ]);

        if ($validated['provider'] === 'meta') {
            $validated['api_url'] = $validated['api_url'] ?: 'https://graph.facebook.com';
            $validated['graph_api_version'] = $validated['graph_api_version'] ?: 'v23.0';
        }

        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function providerOptions(): array
    {
        return ['fonnte', 'wablas', 'meta'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['active', 'inactive'];
    }
}
