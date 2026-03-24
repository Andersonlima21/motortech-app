<?php

namespace App\Services;

use App\Services\Contracts\ClienteServiceInterface;
use App\Utils\Utils;
use Exception;
use Illuminate\Support\Facades\DB;

class ClienteService implements ClienteServiceInterface
{
    protected Utils $util;

    public function __construct(Utils $util)
    {
        $this->util = $util;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): string
    {
        try {
            $cpfCnpj = preg_replace('/\D/', '', $data['cpf_cnpj']);

            $tipo = strlen($cpfCnpj) === 11 ? 'CPF' : (strlen($cpfCnpj) === 14 ? 'CNPJ' : null);

            if (!$tipo) {
                throw new Exception('O documento informado não é um CPF nem um CNPJ válido.');
            }

            if ($tipo === 'CPF' && !$this->util->validaCPF($cpfCnpj)) {
                throw new Exception('CPF inválido.');
            }

            if ($tipo === 'CNPJ' && !$this->util->validaCNPJ($cpfCnpj)) {
                throw new Exception('CNPJ inválido.');
            }

            $existe = DB::table('cliente')
                ->where('cpf_cnpj', $cpfCnpj)
                ->exists();

            if ($existe) {
                throw new Exception("Já existe um cliente cadastrado com este $tipo.");
            }

            $insert = [
                'nome' => $data['nome'],
                'cpf_cnpj' => $cpfCnpj,
                'telefone' => $data['telefone'] ?? null,
                'email' => $data['email'] ?? null,
                'endereco' => $data['endereco'] ?? null,
            ];

            DB::beginTransaction();
            DB::table('cliente')->insert($insert);
            DB::commit();

            return "Cadastro do cliente {$data['nome']} realizado com sucesso!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao cadastrar cliente: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function read($id = null, $cpf = null, $cnpj = null): object
    {
        try {
            if (!$id && !$cpf && !$cnpj) {
                throw new Exception('É necessário informar pelo menos um parâmetro: id, cpf ou cnpj.');
            }

            $query = DB::table('cliente')->select('nome', 'cpf_cnpj', 'telefone', 'email', 'endereco');
            $cliente = null;

            if (!is_null($id)) {
                $cliente = $query->where('id', $id)->first();
            } elseif (!is_null($cpf)) {
                $cpfLimpo = preg_replace('/\D/', '', $cpf);

                if (strlen($cpfLimpo) !== 11) {
                    throw new Exception('CPF inválido.');
                }

                $cliente = $query->where('cpf_cnpj', $cpfLimpo)->first();
            } elseif (!is_null($cnpj)) {
                $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

                if (strlen($cnpjLimpo) !== 14) {
                    throw new Exception('CNPJ inválido.');
                }

                $cliente = $query->where('cpf_cnpj', $cnpjLimpo)->first();
            }

            if (!$cliente) {
                throw new Exception('Cliente não encontrado.');
            }

            $cliente->cpf_cnpj = $this->util->formatarDocumento($cliente->cpf_cnpj);

            return $cliente;

        } catch (Exception $e) {
            throw new Exception('Erro ao buscar cliente: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function update(array $data, $id): string
    {
        try {
            $cliente = DB::table('cliente')->where('id', $id)->first();

            if (!$cliente) {
                throw new Exception('Cliente não encontrado.');
            }

            if (!empty($data['cpf_cnpj'])) {
                $novoDoc = preg_replace('/\D/', '', $data['cpf_cnpj']);
                $tipo = strlen($novoDoc) === 11 ? 'CPF' : (strlen($novoDoc) === 14 ? 'CNPJ' : null);

                if (!$tipo) {
                    throw new Exception('O documento informado não é um CPF nem um CNPJ válido.');
                }

                if ($tipo === 'CPF' && !$this->util->validaCPF($novoDoc)) {
                    throw new Exception('CPF inválido.');
                }

                if ($tipo === 'CNPJ' && !$this->util->validaCNPJ($novoDoc)) {
                    throw new Exception('CNPJ inválido.');
                }

                $existe = DB::table('cliente')
                    ->where('cpf_cnpj', $novoDoc)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($existe) {
                    throw new Exception("Já existe outro cliente cadastrado com este $tipo.");
                }

                $data['cpf_cnpj'] = $novoDoc;
            }

            $update = array_filter([
                'nome' => $data['nome'] ?? $cliente->nome,
                'cpf_cnpj' => $data['cpf_cnpj'] ?? $cliente->cpf_cnpj,
                'telefone' => $data['telefone'] ?? $cliente->telefone,
                'email' => $data['email'] ?? $cliente->email,
                'endereco' => $data['endereco'] ?? $cliente->endereco,
            ], fn($v) => !is_null($v));

            DB::beginTransaction();
            DB::table('cliente')->where('id', $id)->update($update);
            DB::commit();

            return "Cliente {$update['nome']} atualizado com sucesso!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao atualizar cliente: ' . $e->getMessage());
        }
    }


    /**
     * @throws Exception
     */
    public function delete($id): string
    {
        try {
            if (!$id) {
                throw new Exception('É necessário informar o ID do cliente para exclusão.');
            }

            $cliente = DB::table('cliente')->where('id', $id)->first();

            if (!$cliente) {
                throw new Exception('Cliente não encontrado.');
            }

            DB::beginTransaction();

            DB::table('cliente')->where('id', $id)->delete();

            DB::commit();

            return "Cliente {$cliente->nome} removido com sucesso!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao excluir cliente: ' . $e->getMessage());
        }
    }

}
