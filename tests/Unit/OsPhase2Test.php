<?php

namespace Tests\Unit;

use App\Constants\OsConstants;
use App\Models\HistoricoStatus;
use App\Models\Orcamento;
use App\Models\OrdemServicos;
use App\Models\OsPeca;
use App\Models\OsServico;
use App\Models\Pecas;
use App\Services\OsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OsPhase2Test extends TestCase
{
    use DatabaseTransactions;

    private OsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Schema mínimo (SQLite em memória nos testes)
        DB::statement('CREATE TABLE IF NOT EXISTS cliente (id INTEGER PRIMARY KEY AUTOINCREMENT, nome VARCHAR(255), cpf_cnpj VARCHAR(18), email VARCHAR(255))');
        DB::statement('CREATE TABLE IF NOT EXISTS veiculo (id INTEGER PRIMARY KEY AUTOINCREMENT, cliente_id INT, placa VARCHAR(8), marca VARCHAR(50), modelo VARCHAR(50), ano SMALLINT)');
        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico (id INTEGER PRIMARY KEY AUTOINCREMENT, cliente_id INT, veiculo_id INT, status VARCHAR(50), data_abertura TIMESTAMP NULL, data_fechamento TIMESTAMP NULL, valor_total DECIMAL(10,2) DEFAULT 0, observacoes TEXT)');
        DB::statement('CREATE TABLE IF NOT EXISTS historico_status (id INTEGER PRIMARY KEY AUTOINCREMENT, ordem_servico_id INT, status_anterior VARCHAR(50), status_novo VARCHAR(50), alterado_em TIMESTAMP NULL)');
        DB::statement('CREATE TABLE IF NOT EXISTS orcamento (id INTEGER PRIMARY KEY AUTOINCREMENT, ordem_servico_id INT, valor_total DECIMAL(10,2), status VARCHAR(50), data_envio TIMESTAMP NULL, data_aprovacao TIMESTAMP NULL)');
        DB::statement('CREATE TABLE IF NOT EXISTS servico (id INTEGER PRIMARY KEY AUTOINCREMENT, nome VARCHAR(100), preco_base DECIMAL(10,2))');
        DB::statement('CREATE TABLE IF NOT EXISTS peca (id INTEGER PRIMARY KEY AUTOINCREMENT, nome VARCHAR(100), preco_unitario DECIMAL(10,2), quantidade_estoque INT DEFAULT 0)');
        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico_servico (id INTEGER PRIMARY KEY AUTOINCREMENT, ordem_servico_id INT, servico_id INT, preco_aplicado DECIMAL(10,2), quantidade INT)');
        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico_peca (id INTEGER PRIMARY KEY AUTOINCREMENT, ordem_servico_id INT, peca_id INT, quantidade INT, preco_unitario DECIMAL(10,2))');

        $this->service = new OsService(
            new \App\Services\VeiculoService(),
            new \App\Services\ClienteService(new \App\Utils\Utils()),
            new OrdemServicos(),
            new \App\Services\ServicoService(new \App\Models\Servicos()),
            new \App\Services\InsumoService(new Pecas()),
            new Orcamento(),
            new OsServico(),
            new OsPeca(),
            new Pecas(),
            new HistoricoStatus()
        );
    }

    #[Test]
    public function listagem_fase2_deve_ordenar_por_status_e_antiguidade_e_excluir_finalizada_entregue(): void
    {
        $clienteId = DB::table('cliente')->insertGetId(['nome' => 'Fulano', 'cpf_cnpj' => '12345678901', 'email' => 'fulano@example.com']);
        $veiculoId = DB::table('veiculo')->insertGetId(['cliente_id' => $clienteId, 'placa' => 'ABC1234', 'marca' => 'X', 'modelo' => 'Y', 'ano' => 2020]);

        // Inserir OS em ordens diferentes (datas distintas)
        $idRecebida = DB::table('ordem_servico')->insertGetId(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::RECEBIDA, 'data_abertura' => '2025-01-01 10:00:00']);
        $idDiag = DB::table('ordem_servico')->insertGetId(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::EM_DIAGNOSTICO, 'data_abertura' => '2025-01-01 09:00:00']);
        $idAguard = DB::table('ordem_servico')->insertGetId(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::AGUARDANDO_APROVACAO, 'data_abertura' => '2025-01-01 08:00:00']);
        $idExec = DB::table('ordem_servico')->insertGetId(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::EM_EXECUCAO, 'data_abertura' => '2025-01-01 07:00:00']);

        // Esses não podem aparecer
        DB::table('ordem_servico')->insert(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::FINALIZADA, 'data_abertura' => '2025-01-01 06:00:00']);
        DB::table('ordem_servico')->insert(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::ENTREGUE, 'data_abertura' => '2025-01-01 05:00:00']);

        $lista = $this->service->listagemFase2();

        $this->assertCount(4, $lista);
        // Ordem por status (prioridade) e mais antigas primeiro dentro do mesmo status
        $this->assertEquals($idExec, $lista[0]['id']);
        $this->assertEquals($idAguard, $lista[1]['id']);
        $this->assertEquals($idDiag, $lista[2]['id']);
        $this->assertEquals($idRecebida, $lista[3]['id']);
    }

    #[Test]
    public function status_deve_retornar_status_atual_e_historico(): void
    {
        $clienteId = DB::table('cliente')->insertGetId(['nome' => 'Fulano', 'cpf_cnpj' => '12345678901', 'email' => 'fulano@example.com']);
        $veiculoId = DB::table('veiculo')->insertGetId(['cliente_id' => $clienteId, 'placa' => 'ABD1234', 'marca' => 'X', 'modelo' => 'Y', 'ano' => 2020]);

        $osId = DB::table('ordem_servico')->insertGetId(['cliente_id' => $clienteId, 'veiculo_id' => $veiculoId, 'status' => OsConstants::RECEBIDA, 'data_abertura' => '2025-01-01 10:00:00']);
        DB::table('historico_status')->insert(['ordem_servico_id' => $osId, 'status_anterior' => OsConstants::RECEBIDA, 'status_novo' => OsConstants::RECEBIDA, 'alterado_em' => '2025-01-01 10:00:00']);

        $res = $this->service->status($osId);

        $this->assertEquals($osId, $res['os_id']);
        $this->assertEquals(OsConstants::RECEBIDA, $res['status']);
        $this->assertIsArray($res['historico']);
        $this->assertCount(1, $res['historico']);
    }
}
