<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'read', 'BackofficeInvoice');

        $query = Invoice::with('client');

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('id', 'like', "%{$q}%")
                   ->orWhereHas('client', function ($cq) use ($q) {
                       $cq->where('name', 'ilike', "%{$q}%")
                          ->orWhere('company_email', 'ilike', "%{$q}%");
                   });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('invoice_status', $status);
        }

        if ($dateRange = $request->input('selectedDateRange')) {
            if (! empty($dateRange['start'])) {
                $query->where('issued_date', '>=', $dateRange['start']);
            }
            if (! empty($dateRange['end'])) {
                $query->where('issued_date', '<=', $dateRange['end']);
            }
        }

        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'desc');

        $sortColumn = match ($sortBy) {
            'client' => 'client_id',
            'total' => 'total',
            'date' => 'issued_date',
            'balance' => 'balance',
            default => 'id',
        };

        $query->orderBy($sortColumn, $orderBy);

        $itemsPerPage = (int) $request->input('itemsPerPage', 10);
        $page = (int) $request->input('page', 1);

        $total = $query->count();
        $invoices = $query->skip(($page - 1) * $itemsPerPage)
                         ->take($itemsPerPage)
                         ->get();

        return response()->json([
            'invoices' => $invoices->map->toApiResponse()->values(),
            'totalInvoices' => $total,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorizeAbility(request(), 'read', 'BackofficeInvoice');

        $invoice = Invoice::with('client')->find($id);

        if (! $invoice) {
            return response()->json('No invoice found with this id', 404);
        }

        return response()->json([
            'invoice' => $invoice->toApiResponse(),
            'paymentDetails' => [
                'totalDue' => '$'.number_format((float) $invoice->total, 2),
                'bankName' => 'American Bank',
                'country' => 'United States',
                'iban' => 'ETD95476213874685',
                'swiftCode' => 'BR91905',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'create', 'BackofficeInvoice');

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issued_date' => 'required|date',
            'due_date' => 'required|date',
            'service' => 'required|string',
            'total' => 'required|numeric',
        ]);

        $invoice = Invoice::create([
            'client_id' => $request->input('client_id'),
            'issued_date' => $request->input('issued_date'),
            'due_date' => $request->input('due_date'),
            'service' => $request->input('service'),
            'total' => $request->input('total'),
            'avatar' => $request->input('avatar', ''),
            'invoice_status' => $request->input('invoice_status', 'Draft'),
            'balance' => $request->input('balance', 0),
        ]);

        $invoice->load('client');

        return response()->json($invoice->toApiResponse(), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAbility($request, 'update', 'BackofficeInvoice');

        $invoice = Invoice::find($id);

        if (! $invoice) {
            return response()->json(['error' => 'Something went wrong'], 404);
        }

        $invoice->update($request->only([
            'client_id', 'issued_date', 'due_date', 'service',
            'total', 'avatar', 'invoice_status', 'balance',
        ]));

        $invoice->load('client');

        return response()->json($invoice->fresh()->toApiResponse());
    }

    public function destroy(int $id): Response|JsonResponse
    {
        $this->authorizeAbility(request(), 'delete', 'BackofficeInvoice');

        $invoice = Invoice::find($id);

        if (! $invoice) {
            return response()->json(['error' => 'Something went wrong'], 404);
        }

        $invoice->delete();

        return response()->noContent();
    }

    public function clients(): JsonResponse
    {
        $this->authorizeAbility(request(), 'read', 'BackofficeInvoice');

        $clients = Client::take(5)->get();

        return response()->json($clients->map->toApiResponse()->values());
    }
}
