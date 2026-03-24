<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrdemServicoDetalhesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'servico' => 'required|array|min:1',
            'servico.*.servico_id' => 'required|integer',
            'servico.*.servico_qtd' => 'required|integer|min:1',
            'servico.*.insumos' => 'nullable|array',
            'servico.*.insumos.*.peca_id' => 'required|integer',
            'servico.*.insumos.*.peca_qtd' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'servico.required' => 'É necessário informar ao menos um serviço.',
            'servico.*.servico_id.required' => 'O ID do serviço é obrigatório.',
            'servico.*.servico_qtd.required' => 'A quantidade do serviço é obrigatória.',
            'servico.*.servico_qtd.min' => 'A quantidade do serviço deve ser pelo menos 1.',
            'servico.*.insumos.*.peca_id.required' => 'O ID da peça é obrigatório.',
            'servico.*.insumos.*.peca_qtd.required' => 'A quantidade da peça é obrigatória.',
            'servico.*.insumos.*.peca_qtd.min' => 'A quantidade da peça deve ser pelo menos 1.',
        ];
    }
}
