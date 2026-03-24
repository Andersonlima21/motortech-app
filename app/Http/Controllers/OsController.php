<?php

namespace App\Http\Controllers;

use App\DTOs\OrdemServicoDetalhesDTO;
use App\DTOs\OrdemServicoDTO;
use App\Http\Requests\OrdemServicoDetalhesRequest;
use App\Http\Requests\OrdemServicoRequest;
use App\Services\Contracts\OsServiceInteface;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class OsController extends Controller
{
    private $service;
    public function __construct(OsServiceInteface $service)
    {
        $this->service = $service;
    }
    public function createOs(OrdemServicoRequest $request)
    {
        try {

            $dto = OrdemServicoDTO::fromArray($request->validated());

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

    /**
     * Fase 2: Consulta status da OS + histórico.
     */
    public function status(int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->status($id)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Fase 2: Listagem com ordenação e exclusão de FINALIZADA/ENTREGUE.
     */
    public function listagemFase2()
    {
        try {
            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->listagemFase2()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Fase 2: Envia email com link assinado para marcar OS como ENTREGUE.
     * Por padrão usa o email do cliente, mas permite sobrescrever via body {"email": "..."}.
     */
    public function sendEntregaEmail(Request $request, int $id)
    {
        try {
            $email = $request->input('email');

            return response()->json([
                'success' => true,
                'type' => 'success',
                'data' => $this->service->enviarEmailEntrega($id, $email)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function aprovar($id)
    {
        try {

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->aprovar($id)
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

    public function diagnosticar(OrdemServicoDetalhesRequest $request, $id)
    {
        try {
            $dto = OrdemServicoDetalhesDTO::fromArray($request->validated());
            $dto->id = $id;

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->diagnosticar($dto)
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

    public function orcamento($id, $status){

        try {

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->orcamento($id,$status)
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

    public function finalizar($id)
    {
        try {

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Operação realizada com sucesso',
                'data' => $this->service->finalizar($id)
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
}
