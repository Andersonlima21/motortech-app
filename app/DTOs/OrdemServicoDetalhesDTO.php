<?php

namespace App\DTOs;

class OrdemServicoDetalhesDTO
{
    /** @var array<int, array{servico_id:int, servico_qtd:int, insumos:array<int, array{peca_id:int, peca_qtd:int}>}> */
    public array $servico;
    public int $id;

    public function __construct(array $data)
    {
        $this->servico = [];

        foreach ($data['servico'] as $servico) {
            $insumos = [];

            if (!empty($servico['insumos'])) {
                foreach ($servico['insumos'] as $insumo) {
                    $insumos[] = [
                        'peca_id' => (int) $insumo['peca_id'],
                        'peca_qtd' => (int) $insumo['peca_qtd'],
                    ];
                }
            }

            $this->servico[] = [
                'servico_id' => (int) $servico['servico_id'],
                'servico_qtd' => (int) $servico['servico_qtd'],
                'insumos' => $insumos,
            ];
        }
    }

    /**
     * Cria uma instância do DTO a partir de um array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Cria uma instância do DTO a partir de um FormRequest validado.
     *
     * @param \App\Http\Requests\OrdemServicoDetalhesRequest $request
     * @return self
     */
    public static function fromRequest(\App\Http\Requests\OrdemServicoDetalhesRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * Converte o DTO para array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'servico' => $this->servico,
        ];
    }

    /**
     * Converte o DTO para JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
