<?php

namespace App\Services;

use App\DTOs\PecaDTO;
use App\DTOs\PecaQuantidadeDTO;
use App\Models\Pecas;
use App\Services\Contracts\InsumoServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class InsumoService implements InsumoServiceInterface
{
    private $pecas;
    public function __construct(Pecas $pecas)
    {
        $this->pecas = $pecas;
    }

    /**
     * @param PecaDTO $data
     * @return string
     * @throws Exception
     */
    public function create(PecaDTO $data)
    {
        try {

            $peca = $this->pecas->where('nome',$data->nome)->where('preco_unitario',$data->preco_unitario)->get()->toArray()[0] ?? [];
            if(count($peca) <> 0)throw new Exception('Insumo já consta em sistema!');

            DB::beginTransaction();

            $data->criado_em = date('Y-m-d H:i:s');

            $this->pecas->insert($data->toArray());

            DB::commit();

            return 'Insumo cadastrado com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function read($id = null)
    {
        return match (empty($id)){
            false => $this->pecas->find($id),
            true => $this->pecas->get()
        };
    }

    /**
     * @param PecaDTO $data
     * @return string
     * @throws Exception
     */
    public function update(PecaDTO $data)
    {
        try {

            $peca = $this->pecas->where('id',$data->id)->get()->toArray()[0] ?? [];
            if(count($peca) == 0)throw new Exception('Nenhum insumo encontrado!');

            if($peca['nome'] == $data->nome && $peca['preco_unitario'] == $data->preco_unitario)throw new Exception('Insumo já consta em sistema!');

            DB::beginTransaction();

            $data->criado_em = $peca['criado_em'];
            $this->pecas->where('id',$data->id)->update($data->toArray());

            DB::commit();

            return 'Insumo atualizado com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $id
     * @return string
     * @throws Exception
     */
    public function delete($id)
    {
        try {

            if(!$this->pecas->find($id)) throw new Exception('Nenhum Insumo encontrado!');

            DB::beginTransaction();
            $this->pecas->where('id',$id)->delete();
            DB::commit();

            return 'Insumo removido com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param PecaQuantidadeDTO $dto
     * @return string
     * @throws Exception
     */
    public function estoque(PecaQuantidadeDTO $dto)
    {
        try {

            $peca = $this->pecas->where('id',$dto->id)->get()->toArray()[0] ?? [];
            if(count($peca) == 0)throw new Exception('Nenhum insumo encontrado!');

            $total = match ($dto->acao){
              'adicionar' => $peca['quantidade_estoque'] + $dto->qtd,
              'remover' => $peca['quantidade_estoque'] - $dto->qtd,
            };

            DB::beginTransaction();
            $this->pecas->where('id',$dto->id)->update(['quantidade_estoque' => ($total < 0)? 0: $total]);
            DB::commit();

            return 'Estoque atualizado com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
