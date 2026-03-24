<?php

namespace App\Http\Controllers;

use App\Services\Contracts\OsServiceInteface;
use Exception;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private readonly OsServiceInteface $osService)
    {
    }

    /**
     * Webhook externo: aprovação/recusa do orçamento.
     * Header obrigatório: X-Webhook-Token
     */
    public function orcamentoAprovacao(Request $request)
    {
        try {
            $token = (string) $request->header('X-Webhook-Token');
            $expected = (string) env('WEBHOOK_TOKEN', '');

            if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
                return response()->json([
                    'success' => false,
                    'type' => 'unauthorized',
                    'message' => 'Token do webhook inválido.'
                ], 401);
            }

            $result = $this->osService->processarWebhookOrcamento($request->all());

            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $result
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
