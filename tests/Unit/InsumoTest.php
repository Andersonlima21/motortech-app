<?php

namespace Tests\Unit;

use App\DTOs\PecaDTO;
use App\DTOs\PecaQuantidadeDTO;
use App\Models\Pecas;
use App\Services\InsumoService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InsumoTest extends TestCase
{
    use DatabaseTransactions;

    private InsumoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InsumoService(new Pecas());
    }

    #[Test]
    public function deve_criar_um_insumo_com_sucesso(): void
    {
        $dto = PecaDTO::fromArray([
            'nome' => 'Filtro de Óleo',
            'preco_unitario' => 45.90,
            'quantidade_estoque' => 10,
        ]);

        $resposta = $this->service->create($dto);

        $this->assertSame('Insumo cadastrado com sucesso!', $resposta);
        $this->assertDatabaseHas('peca', ['nome' => 'Filtro de Óleo']);
    }

    #[Test]
    public function deve_rejeitar_cadastro_de_insumo_duplicado(): void
    {
        $dto = PecaDTO::fromArray([
            'nome' => 'Pneu Aro 17',
            'preco_unitario' => 599.00,
            'quantidade_estoque' => 5,
        ]);

        $this->service->create($dto);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insumo já consta em sistema!');

        $this->service->create($dto);
    }

    #[Test]
    public function deve_listar_todos_os_insumos(): void
    {
        DB::table('peca')->insert([
            ['nome' => 'Pastilha de Freio', 'preco_unitario' => 200, 'quantidade_estoque' => 5, 'criado_em' => now()],
            ['nome' => 'Amortecedor', 'preco_unitario' => 450, 'quantidade_estoque' => 3, 'criado_em' => now()],
        ]);

        $dados = $this->service->read();

        $this->assertCount(2, $dados);
    }

    #[Test]
    public function deve_atualizar_um_insumo_com_sucesso(): void
    {
        DB::table('peca')->insert([
            'id' => 1,
            'nome' => 'Correia Dentada',
            'preco_unitario' => 120,
            'quantidade_estoque' => 5,
            'criado_em' => now(),
        ]);

        $dto = PecaDTO::fromArray([
            'id' => 1,
            'nome' => 'Correia Dentada Premium',
            'preco_unitario' => 180,
            'quantidade_estoque' => 8,
        ]);

        $resposta = $this->service->update($dto);

        $this->assertSame('Insumo atualizado com sucesso!', $resposta);
        $this->assertDatabaseHas('peca', ['id' => 1, 'nome' => 'Correia Dentada Premium']);
    }

    #[Test]
    public function deve_excluir_um_insumo_com_sucesso(): void
    {
        DB::table('peca')->insert([
            'id' => 1,
            'nome' => 'Filtro de Ar',
            'preco_unitario' => 35.00,
            'quantidade_estoque' => 10,
            'criado_em' => now(),
        ]);

        $resposta = $this->service->delete(1);

        $this->assertSame('Insumo removido com sucesso!', $resposta);
        $this->assertDatabaseMissing('peca', ['id' => 1]);
    }

    #[Test]
    public function deve_adicionar_e_remover_estoque(): void
    {
        DB::table('peca')->insert([
            'id' => 1,
            'nome' => 'Óleo 5W30',
            'preco_unitario' => 60,
            'quantidade_estoque' => 5,
            'criado_em' => now(),
        ]);

        // Adiciona estoque
        $dtoAdd = PecaQuantidadeDTO::fromArray([
            'id' => 1,
            'acao' => 'adicionar',
            'qtd' => 3,
        ]);
        $msg1 = $this->service->estoque($dtoAdd);

        // Remove estoque
        $dtoRem = PecaQuantidadeDTO::fromArray([
            'id' => 1,
            'acao' => 'remover',
            'qtd' => 2,
        ]);
        $msg2 = $this->service->estoque($dtoRem);

        $this->assertSame('Estoque atualizado com sucesso!', $msg1);
        $this->assertSame('Estoque atualizado com sucesso!', $msg2);

        $this->assertDatabaseHas('peca', ['id' => 1, 'quantidade_estoque' => 6]);
    }
}
