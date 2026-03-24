<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicOsController;

Route::get('/', function () {
    return view('welcome');
});

// Healthcheck para probes (Kubernetes)
Route::get('/health', function () {
    return response()->json(['ok' => true]);
});

// Endpoint público com assinatura para confirmar entrega da OS
Route::get('/public/os/{id}/confirm-entrega', [PublicOsController::class, 'confirmEntrega'])
    ->name('public.os.confirm-entrega')
    ->middleware('signed');
