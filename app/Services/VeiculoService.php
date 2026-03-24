<?php

namespace App\Services;

use App\Services\Contracts\VeiculoServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class VeiculoService implements VeiculoServiceInterface
{
    /**
     * @throws Exception
     */
    public function create(array $data): string
    {
        try {
            if (empty($data['cliente_id'])) {
                throw new Exception('O campo cliente_id é obrigatório.');
            }

            if (empty($data['placa'])) {
                throw new Exception('A placa do veículo é obrigatória.');
            }

            // 🔍 Verifica se o cliente existe
            $cliente = DB::table('cliente')->where('id', $data['cliente_id'])->first();
            if (!$cliente) {
                throw new Exception('Cliente associado não encontrado.');
            }

            $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $data['placa']));

            // 🚫 Verifica duplicidade de placa
            $existe = DB::table('veiculo')->where('placa', $placa)->exists();
            if ($existe) {
                throw new Exception("Já existe um veículo cadastrado com a placa {$placa}.");
            }

            $insert = [
                'cliente_id' => $data['cliente_id'],
                'placa' => $placa,
                'marca' => $data['marca'] ?? null,
                'modelo' => $data['modelo'] ?? null,
                'ano' => $data['ano'] ?? null,
            ];

            DB::beginTransaction();
            DB::table('veiculo')->insert($insert);
            DB::commit();

            return "Veículo {$insert['modelo']} ({$placa}) cadastrado com sucesso para o cliente {$cliente->nome}!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao cadastrar veículo: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function read($id = null, $clienteId = null, $cpf = null, $cnpj = null, $placa = null): object|array
    {
        try {
            if (!$id && !$clienteId && !$cpf && !$cnpj && !$placa) {
                throw new Exception('É necessário informar pelo menos um parâmetro: id, cliente_id, cpf, cnpj ou placa.');
            }

            $query = DB::table('veiculo')
                ->join('cliente', 'veiculo.cliente_id', '=', 'cliente.id')
                ->select(
                    'veiculo.placa',
                    'veiculo.marca',
                    'veiculo.modelo',
                    'veiculo.ano',
                    'cliente.nome as cliente_nome',
                    'cliente.cpf_cnpj'
                );

            $resultado = null;

            if (!is_null($id)) {
                $resultado = $query->where('veiculo.id', $id)->first();
            } elseif (!is_null($clienteId)) {
                $resultado = $query->where('cliente.id', $clienteId)->get();
            } elseif (!is_null($cpf)) {
                $cpfLimpo = preg_replace('/\D/', '', $cpf);
                if (strlen($cpfLimpo) !== 11) {
                    throw new Exception('CPF inválido.');
                }
                $resultado = $query->where('cliente.cpf_cnpj', $cpfLimpo)->get();
            } elseif (!is_null($cnpj)) {
                $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
                if (strlen($cnpjLimpo) !== 14) {
                    throw new Exception('CNPJ inválido.');
                }
                $resultado = $query->where('cliente.cpf_cnpj', $cnpjLimpo)->get();
            } elseif (!is_null($placa)) {
                $placaFormatada = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa));
                $resultado = $query->where('veiculo.placa', $placaFormatada)->first();
            }

            $nenhumResultado = false;

            if (is_null($resultado)) {
                $nenhumResultado = true;
            } elseif ($resultado instanceof \Illuminate\Support\Collection && $resultado->isEmpty()) {
                $nenhumResultado = true;
            } elseif (is_object($resultado) && empty((array)$resultado)) {
                $nenhumResultado = true;
            }

            if ($nenhumResultado) {
                throw new Exception('Nenhum veículo encontrado com os parâmetros informados.');
            }

            $formatarDocumento = function ($doc) {
                $docLimpo = preg_replace('/\D/', '', $doc);
                if (strlen($docLimpo) === 11) {
                    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $docLimpo);
                } elseif (strlen($docLimpo) === 14) {
                    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $docLimpo);
                }
                return $doc;
            };

            if ($resultado instanceof \Illuminate\Support\Collection) {
                foreach ($resultado as $r) {
                    $r->cpf_cnpj = $formatarDocumento($r->cpf_cnpj);
                }
            } else {
                $resultado->cpf_cnpj = $formatarDocumento($resultado->cpf_cnpj);
            }

            return $resultado;

        } catch (Exception $e) {
            throw new Exception('Erro ao buscar veículo: ' . $e->getMessage());
        }
    }


    /**
     * @throws Exception
     */
    public function update(array $data, $id): string
    {
        try {
            $veiculo = DB::table('veiculo')->where('id', $id)->first();
            if (!$veiculo) {
                throw new Exception('Veículo não encontrado.');
            }

            if (!empty($data['placa'])) {
                $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $data['placa']));
                $existe = DB::table('veiculo')
                    ->where('placa', $placa)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($existe) {
                    throw new Exception("Já existe outro veículo cadastrado com a placa {$placa}.");
                }

                $data['placa'] = $placa;
            }

            if (!empty($data['cliente_id'])) {
                $cliente = DB::table('cliente')->where('id', $data['cliente_id'])->first();
                if (!$cliente) {
                    throw new Exception('Cliente informado para atualização não existe.');
                }
            }

            $update = array_filter([
                'cliente_id' => $data['cliente_id'] ?? $veiculo->cliente_id,
                'placa' => $data['placa'] ?? $veiculo->placa,
                'marca' => $data['marca'] ?? $veiculo->marca,
                'modelo' => $data['modelo'] ?? $veiculo->modelo,
                'ano' => $data['ano'] ?? $veiculo->ano,
            ], fn($v) => !is_null($v));

            DB::beginTransaction();
            DB::table('veiculo')->where('id', $id)->update($update);
            DB::commit();

            return "Veículo {$update['modelo']} ({$update['placa']}) atualizado com sucesso!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao atualizar veículo: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function delete($id): string
    {
        try {
            $veiculo = DB::table('veiculo')->where('id', $id)->first();
            if (!$veiculo) {
                throw new Exception('Veículo não encontrado.');
            }

            DB::beginTransaction();
            DB::table('veiculo')->where('id', $id)->delete();
            DB::commit();

            return "Veículo {$veiculo->modelo} ({$veiculo->placa}) removido com sucesso!";

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao excluir veículo: ' . $e->getMessage());
        }
    }
}
