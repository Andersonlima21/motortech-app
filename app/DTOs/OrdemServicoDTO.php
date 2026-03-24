<?php

namespace App\DTOs;

class OrdemServicoDTO
{
    public ?int $id;
    public int $cliente_id;
    public int $veiculo_id;
    public string $status;
    public ?string $data_abertura;
    public ?string $data_fechamento;
    public float $valor_total;
    public ?string $observacoes;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->cliente_id = $data['cliente_id'];
        $this->veiculo_id = $data['veiculo_id'];
        $this->status = $data['status'] ?? 'RECEBIDA';
        $this->data_abertura = $data['data_abertura'] ?? null;
        $this->data_fechamento = $data['data_fechamento'] ?? null;
        $this->valor_total = isset($data['valor_total']) ? (float) $data['valor_total'] : 0.0;
        $this->observacoes = $data['observacoes'] ?? null;
    }

    public static function fromRequest(\App\Http\Requests\OrdemServicoRequest $request): self
    {
        return new self($request->validated());
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'veiculo_id' => $this->veiculo_id,
            'status' => $this->status,
            'data_abertura' => $this->data_abertura,
            'data_fechamento' => $this->data_fechamento,
            'valor_total' => $this->valor_total,
            'observacoes' => $this->observacoes,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
