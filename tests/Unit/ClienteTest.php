<?php

namespace Tests\Unit;

use App\Services\ClienteService;
use App\Utils\Utils;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use DatabaseTransactions;

    private ClienteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClienteService(new Utils());
    }

    #[Test]
    public function deve_criar_um_cliente_com_sucesso(): void
    {
        $mensagem = $this->service->create([
            'nome' => 'João da Silva',
            'cpf_cnpj' => '123.456.789-09',
            'telefone' => '11999999999',
            'email' => 'joao@example.com',
            'endereco' => 'Rua A, 123',
        ]);

        $this->assertStringContainsString('realizado com sucesso', $mensagem);
        $this->assertDatabaseHas('cliente', ['nome' => 'João da Silva']);
    }

    #[Test]
    public function deve_rejeitar_cadastro_com_cpf_invalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CPF inválido.');

        $this->service->create([
            'nome' => 'Maria Souza',
            'cpf_cnpj' => '11111111111',
        ]);
    }

    #[Test]
    public function deve_retornar_cliente_por_id(): void
    {
        $id = DB::table('cliente')->insertGetId([
            'nome' => 'Carlos Mendes',
            'cpf_cnpj' => '12345678909',
            'telefone' => '11988881111',
            'email' => 'carlos@example.com',
            'endereco' => 'Av. Paulista, 500',
            'criado_em' => now(),
        ]);

        $cliente = $this->service->read($id);

        $this->assertEquals('Carlos Mendes', $cliente->nome);
        $this->assertEquals('carlos@example.com', $cliente->email);
    }

    #[Test]
    public function deve_atualizar_cliente_com_sucesso(): void
    {
        $id = DB::table('cliente')->insertGetId([
            'nome' => 'Antigo Nome',
            'cpf_cnpj' => '12345678909',
            'telefone' => '1111111111',
            'email' => 'antigo@example.com',
            'endereco' => 'Rua Antiga',
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->update([
            'nome' => 'Novo Nome',
        ], $id);

        $this->assertStringContainsString('atualizado com sucesso', $mensagem);
        $this->assertDatabaseHas('cliente', ['id' => $id, 'nome' => 'Novo Nome']);
    }

    #[Test]
    public function deve_deletar_cliente_com_sucesso(): void
    {
        $id = DB::table('cliente')->insertGetId([
            'nome' => 'Pedro Costa',
            'cpf_cnpj' => '98765432100',
            'telefone' => '11977776666',
            'email' => 'pedro@example.com',
            'endereco' => 'Rua Central, 200',
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->delete($id);

        $this->assertStringContainsString('removido com sucesso', $mensagem);
        $this->assertDatabaseMissing('cliente', ['id' => $id]);
    }
}
