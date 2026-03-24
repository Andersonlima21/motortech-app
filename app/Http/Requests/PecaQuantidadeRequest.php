<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PecaQuantidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // alterar se precisar de autenticação
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'qtd' => 'required|integer|min:1',
            'acao' => 'required|string|in:adicionar,remover',
        ];
    }

    public function messages(): array
    {
        return [
            'acao.in' => 'A ação deve ser "adicionar" ou "remover".',
        ];
    }
}
