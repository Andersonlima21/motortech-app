<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\OsController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\InsumoController;
use Illuminate\Http\Request;
use App\Http\Controllers\ServicoController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Webhook externo (sem auth) para aprovação/recusa de orçamento.
Route::post('webhooks/orcamento/aprovacao', [WebhookController::class, 'orcamentoAprovacao']);

Route::middleware('auth:api')->group(function () {

    Route::get('me', [AuthController::class, 'me']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('/ping', fn() => response()->json(['teste-auth' => true]));

    Route::prefix('cliente')->group(function () {
        Route::controller(ClienteController::class)->group(function () {
            Route::post('/create', 'create');
            Route::get('/read/{id?}', 'read');
            Route::put('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
        });
    });


    Route::prefix('veiculo')->group(function () {
        Route::controller(VeiculoController::class)->group(function () {
            Route::post('/create', 'create');
            Route::get('/read/{id?}', 'read');
            Route::put('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
        });
    });


    Route::prefix('servico')->group(function () {
        Route::controller(ServicoController::class)->group(function () {
            Route::post('/create', 'create');
            Route::get('/read/{id?}', 'read');
            Route::put('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
        });
    });


    Route::prefix('insumo')->group(function () {
        Route::controller(InsumoController::class)->group(function () {
            Route::post('/create', 'create');
            Route::get('/read/{id?}', 'read');
            Route::put('/update/{id?}', 'update');
            Route::delete('/delete/{id?}', 'delete');
            Route::post('/estoque', 'estoque');
        });
    });


    Route::prefix('os')->group(function () {
        Route::controller(OsController::class)->group(function () {
            Route::post('/createOs', 'createOs');
            Route::put('/aprovar/{id}', 'aprovar');
            Route::post('/diagnosticar/{id}','diagnosticar');
            Route::put('/orcamento/{id}/{status}','orcamento');
            Route::put('/finalizar/{id}','finalizar');
            Route::get('/read/{id?}', 'read');

            // Endpoints exigidos na Fase 2
            Route::get('/status/{id}', 'status');
            Route::get('/list', 'listagemFase2');
            Route::post('/{id}/send-entrega-email', 'sendEntregaEmail');
        });
    });

});


