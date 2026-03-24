<?php

namespace App\Http\Controllers;

use App\Services\Contracts\VeiculoServiceInterface;
use Exception;
use Illuminate\Http\Request;

class VeiculoController extends Controller
{
    private VeiculoServiceInterface $service;

    public function __construct(VeiculoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function create(Request $request)
    {
        try {
            $data = $request->only([
                'cliente_id',
                'placa',
                'marca',
                'modelo',
                'ano',
            ]);

            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->create($data)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function read(Request $request, $id = null)
    {
        try {
            $clienteId = $request->query('cliente_id');
            $cpf = $request->query('cpf');
            $cnpj = $request->query('cnpj');
            $placa = $request->query('placa');

            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->read($id, $clienteId, $cpf, $cnpj, $placa)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => $this->service->update($request->all(), $id)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function delete($id)
    {
        try {
            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => $this->service->delete($id)
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
