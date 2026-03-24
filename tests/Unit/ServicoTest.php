<?php


namespace Tests\Unit;

use App\DTOs\ServicoDTO;
use App\Services\ServicoService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServicoTest extends TestCase
{
    use DatabaseTransactions;

    private ServicoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ServicoService::class);
    }

    #[Test]
    public function deve_criar_um_servico_com_sucesso(): void
    {
        $dto = ServicoDTO::fromArray([
            'nome' => 'Troca de Óleo',
            'descricao' => 'Troca de óleo sintético e filtro.',
            'preco_base' => 120.00,
            'tempo_estimado_minutos' => 60,
        ]);

        $mensagem = $this->service->create($dto);

        $this->assertSame('Serviço cadastrado com sucesso!', $mensagem);
        $this->assertDatabaseHas('servico', ['nome' => 'Troca de Óleo']);
    }

    #[Test]
    public function deve_rejeitar_cadastro_de_servico_duplicado(): void
    {
        $dto = ServicoDTO::fromArray([
            'nome' => 'Alinhamento',
            'descricao' => 'Serviço de alinhamento de rodas.',
            'preco_base' => 80.00,
            'tempo_estimado_minutos' => 45,
        ]);

        $this->service->create($dto);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Serviço já existe em sistema!');

        $this->service->create($dto);
    }

    #[Test]
    public function deve_listar_todos_os_servicos(): void
    {
        DB::table('servico')->insert([
            ['nome' => 'Balanceamento', 'descricao' => 'Serviço de balanceamento de rodas', 'preco_base' => 50.00, 'tempo_estimado_minutos' => 30, 'criado_em' => now()],
            ['nome' => 'Troca de Pastilhas', 'descricao' => 'Troca completa das pastilhas de freio', 'preco_base' => 150.00, 'tempo_estimado_minutos' => 90, 'criado_em' => now()],
        ]);

        $dados = $this->service->read();

        $this->assertCount(2, $dados);
    }

    #[Test]
    public function deve_atualizar_servico_com_sucesso(): void
    {
        $id = DB::table('servico')->insertGetId([
            'nome' => 'Revisão Simples',
            'descricao' => 'Revisão básica do veículo.',
            'preco_base' => 100.00,
            'tempo_estimado_minutos' => 60,
            'criado_em' => now(),
        ]);

        $dto = ServicoDTO::fromArray([
            'id' => $id,
            'nome' => 'Revisão Completa',
            'descricao' => 'Revisão completa com troca de filtros e fluídos.',
            'preco_base' => 250.00,
            'tempo_estimado_minutos' => 180,
        ]);

        $mensagem = $this->service->update($dto);

        $this->assertSame('Serviço atualizado com sucesso!', $mensagem);
        $this->assertDatabaseHas('servico', ['id' => $id, 'nome' => 'Revisão Completa']);
    }

    #[Test]
    public function deve_deletar_servico_com_sucesso(): void
    {
        $id = DB::table('servico')->insertGetId([
            'nome' => 'Polimento',
            'descricao' => 'Serviço de polimento automotivo completo.',
            'preco_base' => 200.00,
            'tempo_estimado_minutos' => 120,
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->delete($id);

        $this->assertStringContainsString('removido com sucesso', $mensagem);
        $this->assertDatabaseMissing('servico', ['id' => $id]);
    }
}
