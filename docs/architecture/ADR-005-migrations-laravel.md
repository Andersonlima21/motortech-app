# ADR-005: Migrations Laravel em vez de init.sql

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

O schema do banco de dados era definido em `docker/mysql/init.sql`, executado como init script do container MySQL. Com a migração para RDS gerenciado, precisamos de uma forma diferente de gerenciar o schema.

## Decisão

Convertemos o `init.sql` em uma **Laravel migration** (`2026_03_25_000001_create_domain_tables.php`) que cria todas as 10 tabelas de domínio via `Schema::create()`.

## Justificativa

- **Versionamento**: Migrations são versionadas no git e executadas em ordem
- **Rollback**: Método `down()` permite reverter o schema
- **Compatibilidade**: `php artisan migrate` funciona tanto com MySQL quanto SQLite (testes)
- **CI/CD**: Migrations rodam como K8s Job (`09-migrate-job.yaml`) antes do deploy da app
- **Padrão Laravel**: Segue as convenções do framework

## Alternativas

| Opção | Prós | Contras |
|-------|------|---------|
| Laravel Migrations | Versionado, rollback, CI/CD | Necessário converter init.sql |
| init.sql no RDS | Sem mudança | Não versionado, sem rollback, não funciona com SQLite |
| AWS DMS | Migra dados também | Over-engineering, custo, complexidade |
| Flyway | Padrão de mercado para SQL | Ferramenta adicional, não integrado com Laravel |

## Consequências

### Positivas
- Schema versionado e rastreável
- Testes unitários continuam funcionando com SQLite
- Deploy automatizado via K8s Job
- Possibilidade de migrations incrementais futuras

### Negativas
- Conversão manual de SQL para Schema Builder (feito uma vez)
- View `v_resumo_ordens` criada via `DB::statement()` (não tem Schema Builder)
