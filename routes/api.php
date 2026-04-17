<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppsEmailController;
use App\Http\Controllers\Api\CrmDashboardController;
use App\Http\Controllers\Api\CrmPelangganController;
use App\Http\Controllers\Api\CrmPercakapanController;
use App\Http\Controllers\Api\CrmTiketController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ForecastController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\SLAController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/users', [UserController::class, 'directory']);

    // Users CRUD
    Route::middleware('ability:manage,Admin')->group(function () {
        Route::get('/apps/users', [UserController::class, 'index']);
        Route::post('/apps/users', [UserController::class, 'store']);
        Route::get('/apps/users/{id}', [UserController::class, 'show']);
        Route::put('/apps/users/{id}', [UserController::class, 'update']);
        Route::delete('/apps/users/{id}', [UserController::class, 'destroy']);
    });

    // Email app
    Route::get('/apps/email', [AppsEmailController::class, 'index'])->middleware('ability:read,CrmInbox');
    Route::post('/apps/email', [AppsEmailController::class, 'update'])->middleware('ability:update,CrmInbox');
    Route::post('/apps/email/send', [AppsEmailController::class, 'send'])->middleware('ability:create,CrmInbox');

    // Invoice CRUD
    Route::get('/apps/invoice/clients', [InvoiceController::class, 'clients'])->middleware('ability:read,BackofficeInvoice');
    Route::get('/apps/invoice', [InvoiceController::class, 'index'])->middleware('ability:read,BackofficeInvoice');
    Route::post('/apps/invoice', [InvoiceController::class, 'store'])->middleware('ability:create,BackofficeInvoice');
    Route::get('/apps/invoice/{id}', [InvoiceController::class, 'show'])->middleware('ability:read,BackofficeInvoice');
    Route::put('/apps/invoice/{id}', [InvoiceController::class, 'update'])->middleware('ability:update,BackofficeInvoice');
    Route::delete('/apps/invoice/{id}', [InvoiceController::class, 'destroy'])->middleware('ability:delete,BackofficeInvoice');

    // CRM
    Route::get('/crm/dashboard', CrmDashboardController::class);
    Route::get('/crm/pelanggan', [CrmPelangganController::class, 'index'])->middleware('ability:read,CrmCustomers');
    Route::post('/crm/pelanggan', [CrmPelangganController::class, 'store'])->middleware('ability:create,CrmCustomers');
    Route::get('/crm/pelanggan/{pelanggan}', [CrmPelangganController::class, 'show'])->middleware('ability:read,CrmCustomers');
    Route::put('/crm/pelanggan/{pelanggan}', [CrmPelangganController::class, 'update'])->middleware('ability:update,CrmCustomers');
    Route::post('/crm/pelanggan/{pelanggan}/identities', [CrmPelangganController::class, 'storeIdentity'])->middleware('ability:update,CrmCustomers');
    Route::put('/crm/customer-identities/{customerIdentity}', [CrmPelangganController::class, 'updateIdentity'])->middleware('ability:update,CrmCustomers');
    Route::get('/crm/pelanggan/{pelanggan}/timeline', [CrmPelangganController::class, 'timeline'])->middleware('ability:read,CrmCustomers');
    Route::get('/crm/inbox/overview', [CrmPercakapanController::class, 'overview'])->middleware('ability:read,CrmInbox');
    Route::get('/crm/inbox/whatsapp', [CrmPercakapanController::class, 'whatsappInbox'])->middleware('ability:read,CrmWhatsapp');
    Route::post('/crm/inbox/conversations/{tiket}/reply', [CrmPercakapanController::class, 'reply'])->middleware('ability:update,CrmInbox');
    Route::get('/crm/tiket', [CrmTiketController::class, 'index'])->middleware('ability:read,CrmTickets');
    Route::post('/crm/tiket', [CrmTiketController::class, 'store'])->middleware('ability:create,CrmTickets');
    Route::patch('/crm/tiket/{tiket}/assign', [CrmTiketController::class, 'assign'])->middleware('ability:update,CrmTickets');
    Route::patch('/crm/tiket/{tiket}/status', [CrmTiketController::class, 'updateStatus'])->middleware('ability:update,CrmTickets');
    Route::post('/crm/tiket/{tiket}/balas', [CrmTiketController::class, 'balas'])->middleware('ability:update,CrmTickets');
    Route::get('/crm/percakapan', CrmPercakapanController::class)->middleware('ability:read,CrmInbox');

    // Service Management module
    Route::get('/tickets', [TicketController::class, 'index'])->middleware('ability:read,CrmTickets');
    Route::get('/tickets/options', [TicketController::class, 'options'])->middleware('ability:read,CrmTickets');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->middleware('ability:read,CrmTickets');
    Route::post('/tickets', [TicketController::class, 'store'])->middleware('ability:create,CrmTickets');
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->middleware('ability:update,CrmTickets');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->middleware('ability:update,CrmTickets');
    Route::match(['patch', 'post'], '/tickets/{ticket}/escalate', [TicketController::class, 'escalate'])->middleware('ability:update,CrmTickets');

    Route::get('/sla', [SLAController::class, 'index'])->middleware('ability:read,CrmTickets');
    Route::post('/sla', [SLAController::class, 'store'])->middleware('ability:create,CrmTickets');
    Route::put('/sla/{sla}', [SLAController::class, 'update'])->middleware('ability:update,CrmTickets');

    // Sales Enablement module
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::patch('/leads/{lead}', [LeadController::class, 'update']);
    Route::patch('/leads/{lead}/assign', [LeadController::class, 'assign']);
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);

    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::post('/opportunities', [OpportunityController::class, 'store']);
    Route::patch('/opportunities/{opportunity}', [OpportunityController::class, 'update']);
    Route::patch('/opportunities/{opportunity}/stage', [OpportunityController::class, 'updateStage']);
    Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy']);

    Route::get('/quotations', [QuotationController::class, 'index']);
    Route::post('/quotations', [QuotationController::class, 'store']);
    Route::patch('/quotations/{quotation}', [QuotationController::class, 'update']);

    Route::get('/forecast', [ForecastController::class, 'index']);
    Route::post('/forecast', [ForecastController::class, 'store']);
});
