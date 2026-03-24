# ADR-006: GitHub Actions para CI/CD

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

O projeto utilizava GitLab CI (`.gitlab-ci.yml`) para build e deploy. Com a migração para 4 repositórios no GitHub, precisamos de uma solução de CI/CD integrada.

## Decisão

Migramos de **GitLab CI** para **GitHub Actions** como plataforma de CI/CD.

## Justificativa

- **Integração nativa**: Repos estão no GitHub, Actions é nativo
- **Custo**: Gratuito para repositórios públicos (ilimitado)
- **Marketplace**: Actions oficiais da AWS (`aws-actions/configure-aws-credentials`, `amazon-ecr-login`, etc.)
- **OIDC**: Suporte nativo a OIDC para autenticação com AWS (sem access keys estáticas)
- **Matrix builds**: Terraform plan para múltiplos ambientes em paralelo
- **PR integration**: Comentários automáticos de terraform plan no PR

## Workflows Implementados

### motortech-app
| Workflow | Trigger | Ação |
|----------|---------|------|
| `ci.yml` | PR → main | PHP tests + code style (Pint) |
| `deploy-hml.yml` | push → homologation | Build Docker → Push ECR → Deploy EKS |
| `deploy-prod.yml` | push → production | Build Docker → Push ECR → Deploy EKS |

### motortech-lambda
| Workflow | Trigger | Ação |
|----------|---------|------|
| `ci.yml` | PR → main | Jest tests + ESLint |
| `deploy-hml.yml` | push → homologation | Package ZIP → Update Lambda |
| `deploy-prod.yml` | push → production | Package ZIP → Update Lambda |

### motortech-infra-k8s / motortech-infra-db
| Workflow | Trigger | Ação |
|----------|---------|------|
| `plan.yml` | PR → main | Terraform plan (post comment no PR) |
| `apply-hml.yml` | push → homologation | Terraform apply (hml) |
| `apply-prod.yml` | push → production | Terraform apply (prod) |

## Fluxo de Deploy

```
feature branch → PR → main (requer approval)
  ↓ merge
main → PR → homologation (auto-deploy)
  ↓ merge
homologation → PR → production (auto-deploy)
```

## Consequências

### Positivas
- CI/CD integrado com o repositório
- OIDC elimina necessidade de AWS access keys estáticas
- Terraform plan como comentário no PR facilita revisão
- Gratuito para repos públicos

### Negativas
- Perda do pipeline GitLab existente (`.gitlab-ci.yml` mantido como referência)
- GitHub Actions runners podem ter fila em horários de pico (mitigado: repos públicos têm prioridade)
