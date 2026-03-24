<?php

namespace App\Http\Controllers;

use App\Services\Contracts\OsServiceInteface;
use Exception;
use Illuminate\Http\Request;

class PublicOsController extends Controller
{
    public function __construct(private readonly OsServiceInteface $osService)
    {
    }

    /**
     * Endpoint público (assinado) para confirmar entrega da OS.
     * Fluxo: o usuário recebe o link por email e ao clicar a OS é marcada como ENTREGUE.
     */
    public function confirmEntrega(Request $request, int $id)
    {
        try {
            $result = $this->osService->marcarEntregue($id);

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
