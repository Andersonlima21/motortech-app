<?php

namespace App\DTOs;

class ServicoDTO
{
    public ?int $id;
    public string $nome;
    public ?string $descricao;
    public float $preco_base;
    public ?int $tempo_estimado_minutos;
    public ?string $criado_em;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->nome = $data['nome'];
        $this->descricao = $data['descricao'] ?? null;
        $this->preco_base = (float) $data['preco_base'];
        $this->tempo_estimado_minutos = $data['tempo_estimado_minutos'] ?? null;
        $this->criado_em = $data['criado_em'] ?? null;
    }

    public static function fromRequest(\App\Http\Requests\ServicoRequest $request): self
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
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco_base' => $this->preco_base,
            'tempo_estimado_minutos' => $this->tempo_estimado_minutos,
            'criado_em' => $this->criado_em,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
