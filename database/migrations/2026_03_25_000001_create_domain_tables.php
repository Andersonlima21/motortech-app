<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cria todas as tabelas de domínio do sistema MotorTech.
     * Convertido de docker/mysql/init.sql para Laravel migrations.
     */
    public function up(): void
    {
        // 1. Usuário (admin)
        Schema::create('usuario', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 100);
            $table->string('email', 100)->unique();
            $table->string('senha_hash', 255);
            $table->enum('perfil', ['ADMIN', 'GERENTE', 'MECANICO'])->default('ADMIN');
            $table->timestamp('criado_em')->useCurrent();
        });

        // 2. Cliente
        Schema::create('cliente', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 120);
            $table->string('cpf_cnpj', 18)->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });

        // 3. Veículo
        Schema::create('veiculo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id');
            $table->string('placa', 8)->unique();
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->smallInteger('ano');
            $table->timestamp('criado_em')->useCurrent();
            $table->foreign('cliente_id')->references('id')->on('cliente')->onDelete('cascade');
        });

        // 4. Serviço
        Schema::create('servico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->decimal('preco_base', 10, 2);
            $table->integer('tempo_estimado_minutos')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });

        // 5. Peça / Insumo
        Schema::create('peca', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->decimal('preco_unitario', 10, 2);
            $table->integer('quantidade_estoque')->default(0);
            $table->timestamp('criado_em')->useCurrent();
        });

        // 6. Ordem de Serviço
        Schema::create('ordem_servico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('veiculo_id');
            $table->enum('status', [
                'RECEBIDA', 'EM_DIAGNOSTICO', 'AGUARDANDO_APROVACAO',
                'EM_EXECUCAO', 'FINALIZADA', 'ENTREGUE'
            ])->default('RECEBIDA');
            $table->timestamp('data_abertura')->useCurrent();
            $table->timestamp('data_fechamento')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->foreign('cliente_id')->references('id')->on('cliente')->onDelete('cascade');
            $table->foreign('veiculo_id')->references('id')->on('veiculo')->onDelete('cascade');
        });

        // 7. Ordem x Serviço
        Schema::create('ordem_servico_servico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ordem_servico_id');
            $table->unsignedBigInteger('servico_id');
            $table->decimal('preco_aplicado', 10, 2);
            $table->integer('quantidade')->default(1);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servico')->onDelete('cascade');
            $table->foreign('servico_id')->references('id')->on('servico');
            $table->index('ordem_servico_id', 'idx_os_servico');
        });

        // 8. Ordem x Peça
        Schema::create('ordem_servico_peca', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ordem_servico_id');
            $table->unsignedBigInteger('peca_id');
            $table->integer('quantidade');
            $table->decimal('preco_unitario', 10, 2);
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servico')->onDelete('cascade');
            $table->foreign('peca_id')->references('id')->on('peca');
            $table->index('ordem_servico_id', 'idx_os_peca');
        });

        // 9. Orçamento
        Schema::create('orcamento', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ordem_servico_id')->unique();
            $table->decimal('valor_total', 10, 2);
            $table->enum('status', ['AGUARDANDO_APROVACAO', 'APROVADO', 'REPROVADO'])->default('AGUARDANDO_APROVACAO');
            $table->timestamp('data_envio')->useCurrent();
            $table->timestamp('data_aprovacao')->nullable();
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servico')->onDelete('cascade');
        });

        // 10. Histórico de Status
        Schema::create('historico_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ordem_servico_id');
            $table->enum('status_anterior', [
                'RECEBIDA', 'EM_DIAGNOSTICO', 'AGUARDANDO_APROVACAO',
                'EM_EXECUCAO', 'FINALIZADA', 'ENTREGUE'
            ])->nullable();
            $table->enum('status_novo', [
                'RECEBIDA', 'EM_DIAGNOSTICO', 'AGUARDANDO_APROVACAO',
                'EM_EXECUCAO', 'FINALIZADA', 'ENTREGUE'
            ]);
            $table->timestamp('alterado_em')->useCurrent();
            $table->foreign('ordem_servico_id')->references('id')->on('ordem_servico')->onDelete('cascade');
            $table->index('ordem_servico_id', 'idx_historico_os');
        });

        // View: Resumo de Ordens
        DB::statement("
            CREATE OR REPLACE VIEW v_resumo_ordens AS
            SELECT
                os.id AS id_os,
                c.nome AS cliente,
                v.placa,
                os.status,
                os.valor_total,
                os.data_abertura,
                os.data_fechamento
            FROM ordem_servico os
            JOIN cliente c ON os.cliente_id = c.id
            JOIN veiculo v ON os.veiculo_id = v.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_resumo_ordens');
        Schema::dropIfExists('historico_status');
        Schema::dropIfExists('orcamento');
        Schema::dropIfExists('ordem_servico_peca');
        Schema::dropIfExists('ordem_servico_servico');
        Schema::dropIfExists('ordem_servico');
        Schema::dropIfExists('peca');
        Schema::dropIfExists('servico');
        Schema::dropIfExists('veiculo');
        Schema::dropIfExists('cliente');
        Schema::dropIfExists('usuario');
    }
};
