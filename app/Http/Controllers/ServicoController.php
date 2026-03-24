<?php

namespace App\Http\Controllers;

use App\DTOs\ServicoDTO;
use App\Http\Requests\ServicoRequest;
use App\Services\Contracts\ServicoServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServicoController extends Controller
{
    private ServicoServiceInterface $service;

    public function __construct(ServicoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function create(ServicoRequest $request)
    {
        try {
            $dto = ServicoDTO::fromArray($request->validated());

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

    public function update(ServicoRequest $request, $id)
    {
        try {

            $dto = ServicoDTO::fromArray($request->validated());
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
}
