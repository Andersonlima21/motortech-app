# ADR-002: JWT HS256 com Shared Secret

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

A Lambda de autenticação gera tokens JWT que devem ser validados pelo middleware do Laravel. Precisamos definir o algoritmo de assinatura e a estratégia de distribuição de chaves.

## Decisão

Utilizamos **HS256 (HMAC-SHA256)** com um **shared secret** armazenado no AWS Secrets Manager, acessível tanto pela Lambda quanto pelo Laravel.

## Justificativa

- **Compatibilidade**: O Laravel já usa HS256 via `tymon/jwt-auth` (configuração existente em `config/jwt.php`)
- **Simplicidade**: HS256 requer apenas uma chave simétrica, sem necessidade de par de chaves RSA
- **Segurança adequada**: Para comunicação interna (Lambda → Laravel dentro da mesma VPC), HS256 é suficiente
- **Sem JWKS endpoint**: RS256 exigiria um JWKS endpoint público, adicionando complexidade

## Alternativa considerada: RS256

RS256 (RSA-SHA256) seria mais seguro em cenários multi-tenant ou com verificação pública:
- Chave privada na Lambda (assinar)
- Chave pública no Laravel (verificar)
- API Gateway poderia validar tokens nativamente via JWKS

Descartado por adicionar complexidade sem benefício significativo no cenário atual (comunicação interna).

## Consequências

### Positivas
- Zero mudanças no `config/jwt.php` do Laravel (já usa HS256)
- Uma única chave para gerenciar
- Implementação simples na Lambda (`jsonwebtoken` com secret)

### Negativas
- API Gateway HTTP API não consegue validar tokens HS256 nativamente (só RS256/ES256)
- Se o secret vazar, tanto geração quanto verificação ficam comprometidos
- Mitigação: Secret rotacionado via Secrets Manager, acesso restrito por IAM
