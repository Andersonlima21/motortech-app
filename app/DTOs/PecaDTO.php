<?php

namespace App\DTOs;

class PecaDTO
{
    public ?int $id;
    public string $nome;
    public ?string $descricao;
    public float $preco_unitario;
    public int $quantidade_estoque;
    public ?string $criado_em;

    public function __construct(
        ?int $id,
        string $nome,
        ?string $descricao,
        float $preco_unitario,
        int $quantidade_estoque,
        ?string $criado_em = null
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->preco_unitario = $preco_unitario;
        $this->quantidade_estoque = $quantidade_estoque;
        $this->criado_em = $criado_em;
    }

    /**
     * Cria o DTO a partir de um array (ex: Request ou Model)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['nome'],
            $data['descricao'] ?? null,
            (float) $data['preco_unitario'],
            (int) ($data['quantidade_estoque'] ?? 0),
            $data['criado_em'] ?? null
        );
    }

    /**
     * Retorna os dados em formato de array (para salvar ou responder)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco_unitario' => $this->preco_unitario,
            'quantidade_estoque' => $this->quantidade_estoque,
            'criado_em' => $this->criado_em,
        ];
    }
}
