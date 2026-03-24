<?php

namespace App\Services;

use App\Constants\OrcamentoConstants;
use App\Constants\OsConstants;
use App\DTOs\OrdemServicoDetalhesDTO;
use App\DTOs\OrdemServicoDTO;
use App\Models\HistoricoStatus;
use App\Models\Orcamento;
use App\Models\OrdemServicos;
use App\Models\OsPeca;
use App\Models\OsServico;
use App\Models\Pecas;
use App\Mail\OsEntregaConfirmacaoMail;
use App\Services\Contracts\ClienteServiceInterface;
use App\Services\Contracts\InsumoServiceInterface;
use App\Services\Contracts\OsServiceInteface;
use App\Services\Contracts\ServicoServiceInterface;
use App\Services\Contracts\VeiculoServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OsService implements OsServiceInteface
{
    private $veiculoService;
    private $clienteService;
    private $ordemServicos;
    private $servicos;
    private $insumos;
    private $orcamento;
    private $osServico;
    private $osInsumo;
    private $estoque;
    private $historico;
    public function __construct(VeiculoServiceInterface $veiculoService, ClienteServiceInterface $clienteService,
                                OrdemServicos $ordemServicos, ServicoServiceInterface $servicos,
                                InsumoServiceInterface $insumos, Orcamento $orcamento, OsServico $osServico,
                                OsPeca $osInsumo, Pecas $estoque, HistoricoStatus $historico)
    {
        $this->veiculoService = $veiculoService;
        $this->clienteService = $clienteService;
        $this->ordemServicos = $ordemServicos;
        $this->servicos = $servicos;
        $this->insumos = $insumos;
        $this->orcamento = $orcamento;
        $this->osServico = $osServico;
        $this->osInsumo = $osInsumo;
        $this->estoque = $estoque;
        $this->historico = $historico;
    }

    /**
     * @param OrdemServicoDTO $data
     * @return array{os_id:int,message:string}
     * @throws Exception
     */
    public function create(OrdemServicoDTO $data){

        try {

            DB::beginTransaction();
            $const = new OsConstants();

            $this->clienteService->read($data->cliente_id);
            $this->veiculoService->read($data->veiculo_id);
            $id = $this->ordemServicos->insertGetId($data->toArray());
            $this->historico->insert(['ordem_servico_id' => $id,'status_anterior' => $const::RECEBIDA, 'status_novo' => $const::RECEBIDA,'alterado_em' => DB::raw('NOW()')]);

            DB::commit();

            return [
                'os_id' => (int) $id,
                'message' => 'OS cadastrada com sucesso!'
            ];

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
    public function aprovar($id)
    {
        try {

            if(!$this->ordemServicos->find($id)) throw new Exception('Os não localizada!');
            $const = new OsConstants();

            DB::beginTransaction();
            $this->ordemServicos->where('id', $id)->update(['status' => $const::EM_DIAGNOSTICO]);
            $this->historico->insert(['ordem_servico_id' => $id,'status_anterior' => $const::RECEBIDA, 'status_novo' => $const::EM_DIAGNOSTICO,'alterado_em' => DB::raw('NOW()')]);
            DB::commit();

            return 'Os aprovada com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @param OrdemServicoDetalhesDTO $data
     * @return string
     * @throws Exception
     */
    public function diagnosticar(OrdemServicoDetalhesDTO $data)
    {

        try {

            $const = new OsConstants();
            $constOrcamento = new OrcamentoConstants();
            $os = $this->ordemServicos->find($data->id);
            if(empty($os)) throw new Exception('Os não localizada!');
            $os = $os->toArray();

            if($os['status'] <> $const::EM_DIAGNOSTICO)throw new Exception('Os não liberada para Diagnostico!');

            $insertServicoOs = [];
            $insertInsumoOs = [];
            $precoTotal = 0;

            foreach ($data->servico as $servico) {

                $servicoReal = $this->servicos->read($servico['servico_id']);
                if(empty($servicoReal)) throw new Exception('Serviço: '.$servico['servico_id'].' não encontrado!');
                $servicoReal = $servicoReal->toArray();

                $precoServico = ($servicoReal['preco_base'] *  $servico['servico_qtd']);

                $insertServicoOs[] = [
                    'ordem_servico_id' => $data->id,
                    'servico_id' => $servicoReal['id'],
                    'quantidade' => $servico['servico_qtd'],
                    'preco_aplicado' => $precoServico
                ];

                $precoTotal = $precoTotal + $precoServico;

                foreach ($servico['insumos'] as $insumo) {

                    $insumoReal = $this->insumos->read($insumo['peca_id']);
                    if(empty($insumoReal)) throw new Exception('Insumo: '.$insumo['peca_id'].' não encontrado!');
                    $insumoReal = $insumoReal->toArray();

                    if($insumoReal['quantidade_estoque'] < $insumo['peca_qtd']) throw new Exception('Quantidade estoque insuficiente para o insumo: '.$insumoReal['nome'].'!');

                    $precoInsumo = ($insumoReal['preco_unitario'] *  $insumo['peca_qtd']);

                    $insertInsumoOs[] = [
                        'ordem_servico_id' => $data->id,
                        'peca_id' => $insumo['peca_id'],
                        'quantidade' => $insumo['peca_qtd'],
                        'preco_unitario' => $precoInsumo
                    ];

                    $precoTotal = $precoTotal + $precoInsumo;

                }
            }

            DB::beginTransaction();

            if($precoTotal <> 0) $this->orcamento->insert(['ordem_servico_id' => $data->id, 'valor_total' => $precoTotal,
                'status' => $constOrcamento::AGUARDANDO_APROVACAO,'data_envio' => DB::raw('NOW()')]);

            $this->osServico->insert($insertServicoOs);
            $this->osInsumo->insert($insertInsumoOs);

            $this->ordemServicos->where('id', $data->id)->update(['status' => $const::AGUARDANDO_APROVACAO]);
            $this->historico->insert(['ordem_servico_id' => $data->id,'status_anterior' => $const::EM_DIAGNOSTICO, 'status_novo' => $const::AGUARDANDO_APROVACAO,'alterado_em' => DB::raw('NOW()')]);

            DB::commit();

            return 'Os liberada para pagamento!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @param $id
     * @param $status
     * @return string
     * @throws Exception
     */
    public function orcamento($id,$status)
    {
        try {
            $constOrcamento = new OrcamentoConstants();
            $const = new OsConstants();
            $orcamento = $this->orcamento->find($id);
            if(empty($orcamento)) throw new Exception('Orçamento não encontrado!');
            $orcamento = $orcamento->toArray();

            if($orcamento['status'] <> $constOrcamento::AGUARDANDO_APROVACAO) throw new Exception('Orçamento não está em status de aprovação');

            DB::beginTransaction();

            switch ($status) {

                case 'APROVADO':

                    $this->orcamento->where('id',$id)->update(['status' => $constOrcamento::APROVADO,'data_aprovacao' => DB::raw('NOW()')]);
                    $this->ordemServicos->where('id',$orcamento['ordem_servico_id'])->update(['status' => $const::EM_EXECUCAO]);
                    $this->historico->insert(['ordem_servico_id' => $orcamento['ordem_servico_id'],'status_anterior' => $const::AGUARDANDO_APROVACAO, 'status_novo' => $const::EM_EXECUCAO,'alterado_em' => DB::raw('NOW()')]);

                    $insumos = $this->osInsumo->where('ordem_servico_id',$orcamento['ordem_servico_id'])->get()->toArray();

                    foreach ($insumos as $insumo) {
                        $insumoReal = $this->insumos->read($insumo['peca_id'])->toArray();
                        $this->estoque->where('id',$insumo['peca_id'])->update(['quantidade_estoque' => ($insumoReal['quantidade_estoque'] - $insumo['quantidade'])]);
                    }

                    break;

                case 'REPROVADO':

                    $this->orcamento->where('id',$id)->update(['status' => $constOrcamento::REPROVADO]);
                    $this->ordemServicos->where('id',$orcamento['ordem_servico_id'])->update(['status' => $const::FINALIZADA,'data_fechamento' => DB::raw('NOW()')]);
                    $this->historico->insert(['ordem_servico_id' => $orcamento['ordem_servico_id'],'status_anterior' => $const::AGUARDANDO_APROVACAO, 'status_novo' => $const::FINALIZADA,'alterado_em' => DB::raw('NOW()')]);

                    break;
                default: throw new Exception('Status de orçamento invaliado!');
            }

            DB::commit();

            return 'Orçamento aprovado, Os em execução!';

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
    public function finalizar($id)
    {
        try {

            $const = new OsConstants();
            $os = $this->ordemServicos->find($id);
            if(empty($os)) throw new Exception('Os não encontrada!');
            $os = $os->toArray();

            if($os['status'] <> $const::EM_EXECUCAO) throw new Exception('Essa Os não pode ser finalizada!');

            $orcamento = $this->orcamento->where('ordem_servico_id',$id)->get()->toArray()[0] ?? [];

            DB::beginTransaction();

            $this->ordemServicos->where('id',$id)->update(['status' => $const::FINALIZADA,'data_fechamento' => DB::raw('NOW()'),'valor_total' => $orcamento['valor_total']]);
            $this->historico->insert(['ordem_servico_id' => $id,'status_anterior' => $const::EM_EXECUCAO, 'status_novo' => $const::FINALIZADA,'alterado_em' => DB::raw('NOW()')]);

            DB::commit();

            return 'Os finalizada com sucesso!';

        }catch (Exception $e){
            DB::rollBack();
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        return match (empty($id)){
            false => $this->ordemServicos->find($id),
            true => $this->ordemServicos->get()
        };
    }

    /**
     * Retorna o status atual e o histórico de uma OS.
     *
     * @param int $osId
     * @return array{os_id:int,status:string,historico:array}
     * @throws Exception
     */
    public function status(int $osId): array
    {
        $os = $this->ordemServicos->find($osId);
        if (!$os) {
            throw new Exception('OS não localizada!');
        }

        $historico = $this->historico
            ->where('ordem_servico_id', $osId)
            ->orderBy('alterado_em', 'asc')
            ->get()
            ->toArray();

        return [
            'os_id' => (int) $os->id,
            'status' => (string) $os->status,
            'historico' => $historico,
        ];
    }

    /**
     * Listagem de OS conforme regra do enunciado:
     * - Ordenação por status: EM_EXECUCAO > AGUARDANDO_APROVACAO > EM_DIAGNOSTICO > RECEBIDA
     * - Mais antigas primeiro
     * - Exclui FINALIZADA e ENTREGUE
     *
     * @return array
     */
    public function listagemFase2(): array
    {
        // MySQL: FIELD(). SQLite (tests) não tem FIELD, então usamos CASE.
        $driver = DB::getDriverName();
        $query = $this->ordemServicos
            ->whereNotIn('status', [OsConstants::FINALIZADA, OsConstants::ENTREGUE]);

        if ($driver === 'mysql') {
            $query->orderByRaw("FIELD(status, 'EM_EXECUCAO','AGUARDANDO_APROVACAO','EM_DIAGNOSTICO','RECEBIDA')");
        } else {
            $query->orderByRaw("CASE status
                WHEN 'EM_EXECUCAO' THEN 1
                WHEN 'AGUARDANDO_APROVACAO' THEN 2
                WHEN 'EM_DIAGNOSTICO' THEN 3
                WHEN 'RECEBIDA' THEN 4
                ELSE 99 END");
        }

        return $query
            ->orderBy('data_abertura', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Processa webhook externo de aprovação/recusa de orçamento.
     * Aceita:
     *  - orcamento_id + status (APROVADO|REPROVADO)
     *  - ou os_id + status
     *
     * @param array $payload
     * @return array{message:string}
     * @throws Exception
     */
    public function processarWebhookOrcamento(array $payload): array
    {
        $status = strtoupper((string)($payload['status'] ?? ''));
        if (!in_array($status, ['APROVADO', 'REPROVADO'], true)) {
            throw new Exception('Status inválido. Use APROVADO ou REPROVADO.');
        }

        if (!empty($payload['orcamento_id'])) {
            $this->orcamento((int)$payload['orcamento_id'], $status);
            return ['message' => 'Webhook processado com sucesso.'];
        }

        if (!empty($payload['os_id'])) {
            $orcamento = $this->orcamento
                ->where('ordem_servico_id', (int)$payload['os_id'])
                ->first();

            if (!$orcamento) {
                throw new Exception('Orçamento não encontrado para a OS informada.');
            }

            $this->orcamento((int)$orcamento->id, $status);
            return ['message' => 'Webhook processado com sucesso.'];
        }

        throw new Exception('Informe orcamento_id ou os_id.');
    }

    /**
     * Envia email com link assinado para confirmar entrega da OS.
     * Regra: só envia se a OS estiver FINALIZADA.
     *
     * @param int $osId
     * @param string|null $emailOverride
     * @return array{message:string,confirm_url:string}
     * @throws Exception
     */
    public function enviarEmailEntrega(int $osId, ?string $emailOverride = null): array
    {
        $const = new OsConstants();
        $os = $this->ordemServicos->find($osId);
        if (!$os) {
            throw new Exception('OS não localizada!');
        }

        if ($os->status !== $const::FINALIZADA) {
            throw new Exception('A OS precisa estar FINALIZADA para enviar o email de entrega.');
        }

        $email = $emailOverride;
        if (empty($email)) {
            // Busca email do cliente (tabela legacy "cliente")
            $email = DB::table('cliente')->where('id', (int)$os->cliente_id)->value('email');
        }

        if (empty($email)) {
            throw new Exception('Email do cliente não encontrado. Informe no body: {"email":"..."}');
        }

        $confirmUrl = URL::temporarySignedRoute(
            'public.os.confirm-entrega',
            now()->addHours(48),
            ['id' => $osId]
        );

        Mail::to($email)->send(new OsEntregaConfirmacaoMail($osId, $confirmUrl));

        return [
            'message' => 'Email de confirmação de entrega enviado com sucesso!',
            'confirm_url' => $confirmUrl,
        ];
    }

    /**
     * Marca a OS como ENTREGUE (fluxo via email).
     * @param int $osId
     * @return array{message:string}
     * @throws Exception
     */
    public function marcarEntregue(int $osId): array
    {
        $const = new OsConstants();
        $os = $this->ordemServicos->find($osId);
        if (!$os) {
            throw new Exception('OS não localizada!');
        }

        if ($os->status !== $const::FINALIZADA) {
            throw new Exception('A OS só pode ser marcada como ENTREGUE após FINALIZADA.');
        }

        DB::beginTransaction();
        $this->ordemServicos->where('id', $osId)->update(['status' => $const::ENTREGUE]);
        $this->historico->insert([
            'ordem_servico_id' => $osId,
            'status_anterior' => $const::FINALIZADA,
            'status_novo' => $const::ENTREGUE,
            'alterado_em' => DB::raw('NOW()')
        ]);
        DB::commit();

        return ['message' => 'OS marcada como ENTREGUE com sucesso!'];
    }

}
