# ADR-001: Uso de API Gateway HTTP API (não REST API)

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

A AWS oferece dois tipos de API Gateway: REST API (v1) e HTTP API (v2). Precisamos de um gateway para rotear requisições entre a Lambda de autenticação e o ALB do EKS.

## Decisão

Utilizamos **API Gateway HTTP API (v2)** em vez de REST API (v1).

## Justificativa

- **Custo**: HTTP API custa ~70% menos que REST API ($1.00/M vs $3.50/M requests)
- **Latência**: HTTP API tem latência ~60% menor (single-digit ms)
- **Simplicidade**: Configuração mais simples, auto-deploy de stages
- **Suficiente**: Não precisamos de features exclusivas do REST API (usage plans, API keys, request transformation, caching)
- **Lambda integration**: Suporte nativo ao payload format 2.0

## Consequências

### Positivas
- Menor custo operacional
- Menor latência no roteamento
- Configuração Terraform mais simples

### Negativas
- Sem suporte a WAF (Web Application Firewall) no HTTP API — mitigado pelo rate limiting nativo
- Sem API keys/usage plans — não necessário para o escopo atual
- Sem request/response transformation — aplicação lida com isso internamente
