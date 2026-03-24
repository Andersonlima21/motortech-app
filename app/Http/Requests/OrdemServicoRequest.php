<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autoriza todos por enquanto
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|integer',
            'veiculo_id' => 'required|integer',
            'status' => [
                'nullable',
                Rule::in([
                    'RECEBIDA',
                    'EM_DIAGNOSTICO',
                    'AGUARDANDO_APROVACAO',
                    'EM_EXECUCAO',
                    'FINALIZADA',
                    'ENTREGUE'
                ]),
            ],
            'data_abertura' => 'nullable|date',
            'data_fechamento' => 'nullable|date|after_or_equal:data_abertura',
            'valor_total' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'O cliente é obrigatório.',
            'cliente_id.exists' => 'O cliente informado não existe.',
            'veiculo_id.required' => 'O veículo é obrigatório.',
            'veiculo_id.exists' => 'O veículo informado não existe.',
            'status.in' => 'O status informado é inválido.',
            'data_fechamento.after_or_equal' => 'A data de fechamento não pode ser anterior à data de abertura.',
            'valor_total.numeric' => 'O valor total deve ser um número.',
            'valor_total.min' => 'O valor total não pode ser negativo.',
        ];
    }
}
