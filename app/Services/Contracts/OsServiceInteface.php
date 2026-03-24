<?php

namespace App\Services\Contracts;

use App\DTOs\OrdemServicoDetalhesDTO;
use App\DTOs\OrdemServicoDTO;

interface OsServiceInteface
{
    public function create(OrdemServicoDTO $data);
    public function aprovar($id);
    public function diagnosticar(OrdemServicoDetalhesDTO $data);
    public function orcamento($id,$status);
    public function finalizar($id);
    public function read($id);

    // Fase 2
    public function status(int $osId): array;
    public function listagemFase2(): array;
    public function processarWebhookOrcamento(array $payload): array;
    public function enviarEmailEntrega(int $osId, ?string $emailOverride = null): array;
    public function marcarEntregue(int $osId): array;

}
