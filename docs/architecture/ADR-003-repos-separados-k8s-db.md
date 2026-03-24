# ADR-003: Repositórios Separados para K8s e Banco de Dados

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

A Fase 3 exige 4 repositórios separados. A decisão de como dividir infraestrutura entre os repos afeta o blast radius de operações destrutivas e o ciclo de vida de cada componente.

## Decisão

Separamos a infraestrutura em dois repositórios:
- **motortech-infra-k8s**: VPC, EKS, ECR, API Gateway, Lambda
- **motortech-infra-db**: RDS, Secrets Manager

## Justificativa

- **Blast radius**: Um `terraform destroy` acidental no repo de K8s NÃO afeta o banco de dados
- **Ciclo de vida diferente**: O banco de dados muda raramente; o cluster K8s pode ser recriado sem perda de dados
- **Permissões**: Times diferentes podem ter acesso a repos diferentes (infra vs. DBA)
- **Recovery**: Cluster EKS pode ser destruído e recriado em ~15 min; reconstruir RDS com dados pode levar horas

## Consequências

### Positivas
- Proteção dos dados: banco isolado de operações no cluster
- Deploy independente de infra e banco
- Terraform state menor e mais rápido em cada repo

### Negativas
- Cross-repo dependencies: infra-db precisa do VPC ID do infra-k8s
- Mitigação: uso de `data sources` Terraform para buscar recursos por tags
- Ordem de apply: infra-k8s deve ser aplicado ANTES de infra-db
