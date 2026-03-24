<?php

namespace App\Services;

use App\DTOs\ServicoDTO;
use App\Models\Servicos;
use App\Services\Contracts\ServicoServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class ServicoService implements ServicoServiceInterface
{
    private $servico;
    public function __construct(Servicos $servico){
        $this->servico = $servico;
    }

    /**
     * @param ServicoDTO $data
     * @return string
     * @throws Exception
     */
    public function create(ServicoDTO $data)
    {
        try {

            $servico = $this->servico->where('nome', $data->nome)->first();
            if(!empty((array)$servico)) throw new Exception('Serviço já existe em sistema!');

            DB::beginTransaction();

            $data->criado_em = date('Y-m-d H:i:s');

            $this->servico->insert($data->toArray());

            DB::commit();

            return 'Serviço cadastrado com sucesso!';

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
            false => $this->servico->find($id),
            true => $this->servico->get()
        };
    }


    /**
     * @param ServicoDTO $data
     * @return string
     * @throws Exception
     */
    public function update(ServicoDTO $data)
    {
        try {
            $servico = $this->servico->where('id',$data->id)->get()->toArray()[0] ?? [];
            if(count($servico) == 0)throw new Exception('Nenhum Serviço encontrado!');

            DB::beginTransaction();

            $data->criado_em = $servico['criado_em'];
            $this->servico->where('id',$data->id)->update($data->toArray());

            DB::commit();

            return 'Serviço atualizado com sucesso!';

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

            if(!$this->servico->find($id)) throw new Exception('Nenhum Serviço encontrado!');

            DB::beginTransaction();
            $this->servico->where('id',$id)->delete();
            DB::commit();

            return 'Serviço removido com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}
