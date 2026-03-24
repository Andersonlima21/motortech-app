<?php

namespace App\DTOs;

class PecaQuantidadeDTO
{
    public int $id;
    public int $qtd;
    public string $acao; // "adicionar" ou "remover"

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->qtd = $data['qtd'] ?? null;
        $this->acao = $data['acao'] ?? null;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'qtd' => $this->qtd,
            'acao' => $this->acao
        ];
    }

    public function isValidAcao(): bool
    {
        return in_array($this->acao, ['adicionar', 'remover']);
    }
}
