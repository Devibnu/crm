<?php

use App\Http\Controllers\Api\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('/crm/menus', [MenuController::class, 'index'])->name('api.crm.menus.index');
