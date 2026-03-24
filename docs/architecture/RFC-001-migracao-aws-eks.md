# RFC-001: Migração de Kind Local para AWS EKS

**Status**: Aprovado
**Autor**: Equipe MotorTech
**Data**: 2026-03-24
**Revisores**: Arquitetura SOAT

## Contexto

O projeto MotorTech operava em um cluster Kubernetes local (Kind) para demonstração. Com a expansão para múltiplas unidades da oficina, tornou-se necessário migrar para um ambiente cloud com alta disponibilidade, escalabilidade e segurança.

## Problema

- Kind é efêmero (dados perdidos ao reiniciar)
- Sem alta disponibilidade (single node)
- MySQL rodando como pod sem persistência (emptyDir)
- Sem escalabilidade automática real
- Sem monitoramento ou observabilidade
- Deploy manual via Terraform local

## Alternativas Consideradas

### 1. AWS EKS (Escolhido)
- **Prós**: Kubernetes gerenciado, integração nativa com ALB/ECR/RDS, IRSA, auto-scaling, Multi-AZ
- **Contras**: Custo do control plane (~$72/mês), curva de aprendizado AWS
- **Custo estimado**: ~$180/mês (EKS + 2x t3.medium + RDS t3.micro + NAT)

### 2. AWS ECS (Fargate)
- **Prós**: Serverless containers, sem gerenciar nodes, pay-per-use
- **Contras**: Não é Kubernetes (desvio do requisito), menos portável, menos controle
- **Custo estimado**: ~$100/mês

### 3. AWS EC2 Direto (sem orquestrador)
- **Prós**: Simples, barato, controle total
- **Contras**: Sem orquestração, sem auto-scaling nativo, sem self-healing, não atende requisito de K8s
- **Custo estimado**: ~$60/mês

### 4. Azure AKS
- **Prós**: Control plane gratuito, bom suporte K8s
- **Contras**: Menor adoção no mercado brasileiro, time sem experiência Azure
- **Custo estimado**: ~$120/mês

## Decisão

**AWS EKS** foi escolhido porque:

1. **Requisito acadêmico**: A Fase 3 exige Kubernetes com escalabilidade
2. **Ecossistema AWS**: Integração nativa com RDS, ECR, ALB, Secrets Manager, Lambda
3. **Mercado**: AWS lidera o mercado cloud no Brasil (~40% market share)
4. **HPA funcional**: Metrics Server como add-on gerenciado, scaling real baseado em CPU/memória
5. **Segurança**: IRSA (IAM Roles for Service Accounts), VPC isolation, security groups

## Arquitetura Definida

### Rede
- VPC: 10.0.0.0/16
- 2 subnets públicas (ALB, NAT Gateway)
- 2 subnets privadas (EKS nodes, RDS, Lambda)
- NAT Gateway para saída internet de subnets privadas

### Cluster EKS
- Versão: 1.29
- Nodes: t3.medium (2-5, auto-scaling)
- Add-ons: vpc-cni, coredns, kube-proxy
- AWS Load Balancer Controller para ALB Ingress

### Ambientes
- **Homologation**: SPOT instances (1-3 nodes), custo otimizado
- **Production**: ON_DEMAND instances (2-5 nodes), alta disponibilidade

## Impacto

- Deploy automatizado via GitHub Actions
- Alta disponibilidade com Multi-AZ
- Escalabilidade horizontal automática (HPA)
- Isolamento de rede via VPC + security groups
- Custo mensal estimado: ~$180/mês (hml + prod)

## Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| Custo acima do esperado | Média | Alto | Usar SPOT em hml, alertas de billing |
| Cold start do NAT Gateway | Baixa | Baixo | NAT é sempre ativo, sem cold start |
| Falha no EKS control plane | Muito baixa | Alto | AWS SLA 99.95%, Multi-AZ automático |
| Complexidade operacional | Alta | Médio | IaC via Terraform, CI/CD automatizado |
