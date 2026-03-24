<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PecaRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // Você pode trocar por alguma regra de permissão depois
        return true;
    }

    /**
     * Define as regras de validação.
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'preco_unitario' => ['required', 'numeric', 'min:0'],
            'quantidade_estoque' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Mensagens personalizadas (opcional)
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => 'O nome não pode ultrapassar 100 caracteres.',
            'preco_unitario.required' => 'O preço unitário é obrigatório.',
            'preco_unitario.numeric' => 'O preço unitário deve ser um número válido.',
            'quantidade_estoque.integer' => 'A quantidade em estoque deve ser um número inteiro.',
            'quantidade_estoque.min' => 'A quantidade mínima é zero.',
        ];
    }
}
