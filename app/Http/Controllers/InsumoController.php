<?php

namespace App\Http\Controllers;

use App\DTOs\PecaDTO;
use App\DTOs\PecaQuantidadeDTO;
use App\Http\Requests\PecaQuantidadeRequest;
use App\Http\Requests\PecaRequest;
use App\Services\Contracts\InsumoServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InsumoController extends Controller
{
    private InsumoServiceInterface $service;

    public function __construct(InsumoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function create(PecaRequest $request)
    {
        try {

            $dto = PecaDTO::fromArray($request->validated());

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->create($dto)
            ], 200);

        } catch (ValidationException $e) {
            // Captura erros de validação do FormRequest
            return response()->json([
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Captura erros gerais do seu serviço ou outros
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function read($id = null)
    {
        try {

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->read($id)
            ], 200);

        } catch (Exception $e) {
            // Captura erros gerais do seu serviço ou outros
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(PecaRequest $request, $id)
    {
        try {

            $dto = PecaDTO::fromArray($request->validated());
            $dto->id = $id;

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->update($dto)
            ], 200);

        } catch (ValidationException $e) {
            // Captura erros de validação do FormRequest
            return response()->json([
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Captura erros gerais do seu serviço ou outros
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
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->delete($id)
            ], 200);

        } catch (Exception $e) {
            // Captura erros gerais do seu serviço ou outros
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function estoque(PecaQuantidadeRequest $request)
    {
        try {

            $dto = PecaQuantidadeDTO::fromArray($request->validated());

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->estoque($dto)
            ], 200);

        } catch (ValidationException $e) {
            // Captura erros de validação do FormRequest
            return response()->json([
                'success' => false,
                'type' => 'validation_error',
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Captura erros gerais do seu serviço ou outros
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

}
