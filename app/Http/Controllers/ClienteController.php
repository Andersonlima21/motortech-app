<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ClienteServiceInterface;
use Exception;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    private ClienteServiceInterface $service;

    public function __construct(ClienteServiceInterface $service)
    {
        $this->service = $service;
    }

    public function create(Request $request)
    {
        try {
            $data = $request->only([
                'nome',
                'cpf_cnpj',
                'telefone',
                'email',
                'endereco',
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
            $cpf = $request->query('cpf');
            $cnpj = $request->query('cnpj');
            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->read($id, $cpf, $cnpj)
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
