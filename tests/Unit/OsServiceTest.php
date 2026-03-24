<?php


namespace Tests\Unit;

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
use App\Services\OsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Str;

class OsServiceTest extends TestCase
{
    use DatabaseTransactions;

    private OsService $service;
    private int $clienteId;
    private int $veiculoId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE IF NOT EXISTS cliente (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255),
            cpf_cnpj VARCHAR(18)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS veiculo (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            cliente_id BIGINT,
            placa VARCHAR(8),
            marca VARCHAR(50),
            modelo VARCHAR(50),
            ano SMALLINT
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS servico (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100),
            preco_base DECIMAL(10,2)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS peca (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100),
            preco_unitario DECIMAL(10,2),
            quantidade_estoque INT DEFAULT 0
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            cliente_id BIGINT,
            veiculo_id BIGINT,
            status VARCHAR(50),
            data_abertura TIMESTAMP NULL,
            data_fechamento TIMESTAMP NULL,
            valor_total DECIMAL(10,2) DEFAULT 0,
            observacoes TEXT
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico_servico (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id BIGINT,
            servico_id BIGINT,
            preco_aplicado DECIMAL(10,2),
            quantidade INT
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS ordem_servico_peca (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id BIGINT,
            peca_id BIGINT,
            quantidade INT,
            preco_unitario DECIMAL(10,2)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS historico_status (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id BIGINT,
            status_anterior VARCHAR(50),
            status_novo VARCHAR(50),
            alterado_em TIMESTAMP NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS orcamento (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id BIGINT,
            valor_total DECIMAL(10,2),
            status VARCHAR(50),
            data_envio TIMESTAMP NULL,
            data_aprovacao TIMESTAMP NULL
        )');

        // Instanciar o serviço real
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

    $placa = strtoupper(Str::random(3)) . rand(1000, 9999);

    $this->clienteId = DB::table('cliente')->insertGetId([
        'nome' => 'João Mecânico',
        'cpf_cnpj' => (string) rand(10000000000, 99999999999)
    ]);

    $this->veiculoId = DB::table('veiculo')->insertGetId([
        'cliente_id' => $this->clienteId,
        'placa' => $placa,
        'marca' => 'Fiat',
        'modelo' => 'Uno',
        'ano' => rand(2010, 2024)
    ]);
    }

    #[Test]
    public function deve_criar_os_com_sucesso(): void
    {
        $dto = OrdemServicoDTO::fromArray([
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'status' => OsConstants::RECEBIDA
        ]);

        $resposta = $this->service->create($dto);

        $this->assertIsArray($resposta);
        $this->assertArrayHasKey('os_id', $resposta);
        $this->assertEquals('OS cadastrada com sucesso!', $resposta['message']);
        $this->assertDatabaseHas('ordem_servico', ['cliente_id' => $this->clienteId]);
    }

    #[Test]
    public function deve_aprovar_os_com_sucesso(): void
    {
        $id = DB::table('ordem_servico')->insertGetId([
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'status' => OsConstants::RECEBIDA
        ]);

        $msg = $this->service->aprovar($id);

        $this->assertEquals('Os aprovada com sucesso!', $msg);
        $this->assertDatabaseHas('ordem_servico', ['id' => $id, 'status' => OsConstants::EM_DIAGNOSTICO]);
    }

    #[Test]
    public function deve_diagnosticar_os_e_gerar_orcamento(): void
    {
        // Criar insumo e serviço base
        $pecaId = DB::table('peca')->insertGetId([
            'nome' => 'Filtro de Óleo',
            'preco_unitario' => 50.00,
            'quantidade_estoque' => 10
        ]);

        $servicoId = DB::table('servico')->insertGetId([
            'nome' => 'Troca de Óleo',
            'preco_base' => 100.00
        ]);

        $osId = DB::table('ordem_servico')->insertGetId([
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'status' => OsConstants::EM_DIAGNOSTICO
        ]);

        $dto = OrdemServicoDetalhesDTO::fromArray([
            'servico' => [
                [
                    'servico_id' => $servicoId,
                    'servico_qtd' => 1,
                    'insumos' => [
                        ['peca_id' => $pecaId, 'peca_qtd' => 2]
                    ]
                ]
            ]
        ]);
        $dto->id = $osId;

        $msg = $this->service->diagnosticar($dto);

        $this->assertEquals('Os liberada para pagamento!', $msg);
        $this->assertDatabaseHas('orcamento', ['ordem_servico_id' => $osId]);
        $this->assertDatabaseHas('ordem_servico', ['id' => $osId, 'status' => OsConstants::AGUARDANDO_APROVACAO]);
    }

    #[Test]
    public function deve_aprovar_orcamento_e_iniciar_execucao(): void
    {
        $osId = DB::table('ordem_servico')->insertGetId([
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'status' => OsConstants::AGUARDANDO_APROVACAO
        ]);

        $orcamentoId = DB::table('orcamento')->insertGetId([
            'ordem_servico_id' => $osId,
            'valor_total' => 300.00,
            'status' => OrcamentoConstants::AGUARDANDO_APROVACAO
        ]);

        $msg = $this->service->orcamento($orcamentoId, 'APROVADO');

        $this->assertEquals('Orçamento aprovado, Os em execução!', $msg);
        $this->assertDatabaseHas('ordem_servico', ['id' => $osId, 'status' => OsConstants::EM_EXECUCAO]);
    }

    #[Test]
    public function deve_finalizar_os_com_sucesso(): void
    {
        $osId = DB::table('ordem_servico')->insertGetId([
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'status' => OsConstants::EM_EXECUCAO
        ]);

        DB::table('orcamento')->insert([
            'ordem_servico_id' => $osId,
            'valor_total' => 400.00,
            'status' => OrcamentoConstants::APROVADO
        ]);

        $msg = $this->service->finalizar($osId);

        $this->assertEquals('Os finalizada com sucesso!', $msg);
        $this->assertDatabaseHas('ordem_servico', ['id' => $osId, 'status' => OsConstants::FINALIZADA]);
    }
}
