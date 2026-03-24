<?php

namespace Tests\Unit;

use App\Services\VeiculoService;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VeiculoTest extends TestCase
{
    use DatabaseTransactions;

    private VeiculoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VeiculoService::class);
    }

    #[Test]
    public function deve_criar_um_veiculo_com_sucesso(): void
    {
        $clienteId = DB::table('cliente')->insertGetId([
            'nome' => 'João da Silva',
            'cpf_cnpj' => '12345678909',
            'telefone' => '11999999999',
            'email' => 'joao@example.com',
            'endereco' => 'Rua A, 123',
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->create([
            'cliente_id' => $clienteId,
            'placa' => 'ABC1234',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'ano' => 2020,
        ]);

        $this->assertStringContainsString('cadastrado com sucesso', $mensagem);
        $this->assertDatabaseHas('veiculo', ['placa' => 'ABC1234']);
    }

    #[Test]
    public function deve_rejeitar_veiculo_com_cliente_inexistente(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cliente associado não encontrado.');

        $this->service->create([
            'cliente_id' => 999,
            'placa' => 'XYZ9999',
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2018,
        ]);
    }

    #[Test]
    public function deve_rejeitar_placa_duplicada(): void
    {
        $clienteId = DB::table('cliente')->insertGetId([
            'nome' => 'Maria Souza',
            'cpf_cnpj' => '98765432100',
            'criado_em' => now(),
        ]);

        DB::table('veiculo')->insert([
            'cliente_id' => $clienteId,
            'placa' => 'ZZZ0001',
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'ano' => 2019,
            'criado_em' => now(),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Já existe um veículo cadastrado');

        $this->service->create([
            'cliente_id' => $clienteId,
            'placa' => 'ZZZ0001',
            'marca' => 'Honda',
            'modelo' => 'City',
            'ano' => 2021,
        ]);
    }

    #[Test]
    public function deve_retornar_veiculo_por_id(): void
    {
        $clienteId = DB::table('cliente')->insertGetId([
            'nome' => 'Carlos Mendes',
            'cpf_cnpj' => '12345678909',
            'criado_em' => now(),
        ]);

        $veiculoId = DB::table('veiculo')->insertGetId([
            'cliente_id' => $clienteId,
            'placa' => 'AAA1111',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'ano' => 2017,
            'criado_em' => now(),
        ]);

        $resultado = $this->service->read($veiculoId);

        $this->assertEquals('Focus', $resultado->modelo);
        $this->assertEquals('Carlos Mendes', $resultado->cliente_nome);
    }

    #[Test]
    public function deve_atualizar_veiculo_com_sucesso(): void
    {
        $clienteId = DB::table('cliente')->insertGetId([
            'nome' => 'Paula Lima',
            'cpf_cnpj' => '32165498700',
            'criado_em' => now(),
        ]);

        $veiculoId = DB::table('veiculo')->insertGetId([
            'cliente_id' => $clienteId,
            'placa' => 'QWE4321',
            'marca' => 'VW',
            'modelo' => 'Gol',
            'ano' => 2015,
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->update([
            'modelo' => 'Gol G6',
            'ano' => 2016,
        ], $veiculoId);

        $this->assertStringContainsString('atualizado com sucesso', $mensagem);
        $this->assertDatabaseHas('veiculo', [
            'id' => $veiculoId,
            'modelo' => 'Gol G6',
            'ano' => 2016,
        ]);
    }

    #[Test]
    public function deve_deletar_veiculo_com_sucesso(): void
    {
        $clienteId = DB::table('cliente')->insertGetId([
            'nome' => 'Rafael Alves',
            'cpf_cnpj' => '65498732100',
            'criado_em' => now(),
        ]);

        $veiculoId = DB::table('veiculo')->insertGetId([
            'cliente_id' => $clienteId,
            'placa' => 'JKL2222',
            'marca' => 'Chevrolet',
            'modelo' => 'Onix',
            'ano' => 2022,
            'criado_em' => now(),
        ]);

        $mensagem = $this->service->delete($veiculoId);

        $this->assertStringContainsString('removido com sucesso', $mensagem);
        $this->assertDatabaseMissing('veiculo', ['id' => $veiculoId]);
    }
}
