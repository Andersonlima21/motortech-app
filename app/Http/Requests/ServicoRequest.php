<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autoriza todos por enquanto, você pode customizar depois
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'preco_base' => 'required|numeric|min:0',
            'tempo_estimado_minutos' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do serviço é obrigatório.',
            'nome.max' => 'O nome do serviço não pode ter mais de 100 caracteres.',
            'preco_base.required' => 'O preço base é obrigatório.',
            'preco_base.numeric' => 'O preço base deve ser um número.',
            'preco_base.min' => 'O preço base não pode ser negativo.',
            'tempo_estimado_minutos.integer' => 'O tempo estimado deve ser um número inteiro.',
            'tempo_estimado_minutos.min' => 'O tempo estimado não pode ser negativo.',
        ];
    }
}
