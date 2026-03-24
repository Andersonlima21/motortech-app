# RFC-003: Estratégia de Observabilidade com New Relic

**Status**: Aprovado
**Autor**: Equipe MotorTech
**Data**: 2026-03-24
**Revisores**: Arquitetura SOAT

## Contexto

Com a migração para AWS e a operação em múltiplas unidades, a direção da oficina precisa de visibilidade total sobre o funcionamento do sistema: latência, erros, consumo de recursos e volume de operações.

## Problema

- Zero visibilidade sobre performance das APIs
- Sem alertas para falhas no processamento de OS
- Logs não estruturados (texto puro em arquivo)
- Sem métricas de infraestrutura K8s
- Sem correlação entre requisições (tracing)

## Alternativas Consideradas

### 1. New Relic (Escolhido)
- **Prós**: Free tier permanente (100GB/mês), APM + Infra + Logs integrados, dashboards prontos, PHP agent nativo, alertas NRQL
- **Contras**: Vendor lock-in para queries (NRQL), dados fora da infraestrutura
- **Custo**: $0 (Free tier cobre o volume do projeto)

### 2. Datadog
- **Prós**: UI excelente, dashboards K8s prontos, APM + traces
- **Contras**: Free tier limitado (14 dias), custo alto após trial (~$15/host/mês), não há PHP agent nativo (usa OpenTelemetry)
- **Custo**: ~$45/mês (3 hosts)

### 3. Prometheus + Grafana
- **Prós**: Open source, roda no K8s, sem custo de licença, padrão de mercado
- **Contras**: Sem APM (precisa de Jaeger/Zipkin adicional), mais trabalho de setup e manutenção, sem alertas out-of-the-box, consome recursos do cluster
- **Custo**: $0 (mas consome CPU/memória do cluster)

### 4. AWS CloudWatch
- **Prós**: Nativo AWS, integração automática com EKS/RDS/Lambda
- **Contras**: UI limitada, dashboards menos flexíveis, APM básico (X-Ray separado), custo por métrica customizada
- **Custo**: ~$30/mês

## Decisão

**New Relic** foi escolhido porque:

1. **Free tier permanente**: 100GB/mês de ingestão gratuita, suficiente para o projeto
2. **APM nativo para PHP**: Agent que instrumenta automaticamente Laravel (transactions, DB queries, errors)
3. **Tudo-em-um**: APM + Infrastructure + Logs + Alertas em uma única plataforma
4. **Distributed Tracing**: Correlação entre API Gateway → Lambda → Laravel → RDS
5. **NRQL**: Linguagem de query poderosa para dashboards customizados
6. **Setup simples**: PHP agent via Dockerfile, infra agent via DaemonSet, logs via Fluent Bit

## Implementação

### 1. APM (Application Performance Monitoring)

**PHP Agent** instalado no Dockerfile.prod:
- Auto-instrumenta todas as transações Laravel
- Captura queries SQL (tempo, query text)
- Rastreia chamadas externas (HTTP, mail)
- Detecta erros e exceptions automaticamente
- Distributed tracing habilitado

**Métricas capturadas**:
- Response time (p50, p95, p99) por endpoint
- Throughput (req/min) por endpoint
- Error rate (%) global e por endpoint
- Database query time e N+1 detection
- External call duration

### 2. Infrastructure Monitoring

**New Relic Infrastructure DaemonSet** no EKS:
- CPU, memória, disco por node
- CPU, memória por pod/container
- Network I/O por node
- Kubernetes events (pod restarts, OOM kills)

### 3. Log Aggregation

**Fluent Bit DaemonSet** → New Relic Logs API:
- Coleta logs de containers no EKS
- Parser JSON (logs estruturados do Laravel)
- Enriquecimento com metadata K8s (pod, namespace, node)
- Filtro para apenas containers `motortech-*`

**Formato do log estruturado (Laravel)**:
```json
{
  "timestamp": "2026-03-24T10:30:00.000Z",
  "level": "INFO",
  "message": "OS criada com sucesso",
  "channel": "json-stderr",
  "correlation_id": "a1b2c3d4-e5f6-7890",
  "request_method": "POST",
  "request_path": "api/os/createOs",
  "user_id": 1,
  "context": { "os_id": 42 }
}
```

### 4. Lambda Monitoring

**New Relic Lambda Layer** para Node.js 20:
- Invocações, duração, erros
- Cold start frequency
- Timeout tracking

### 5. Dashboards

| Dashboard | Métricas | NRQL Exemplo |
|-----------|----------|--------------|
| API Latency | p50/p95/p99 por endpoint | `SELECT percentile(duration, 50, 95, 99) FROM Transaction FACET name` |
| K8s Resources | CPU/Mem por pod | `SELECT average(cpuUsedCores) FROM K8sContainerSample FACET containerName` |
| Daily OS Volume | OS criadas por dia | `SELECT count(*) FROM Transaction WHERE name LIKE '%createOs%' FACET dateOf(timestamp)` |
| Avg Time per Status | Tempo médio por status | `SELECT average(duration) FROM Transaction WHERE name LIKE '%os/%' FACET name` |
| Error Dashboard | 4xx/5xx breakdown | `SELECT count(*) FROM TransactionError FACET error.class` |

### 6. Alertas

| Alerta | Condição | Severidade |
|--------|----------|------------|
| High Latency | Response time > 2s por 5 min | Warning |
| Critical Latency | Response time > 5s por 5 min | Critical |
| High Error Rate | Error rate > 5% por 5 min | Critical |
| CPU High | CPU > 80% por 10 min | Warning |
| Memory High | Memory > 85% por 10 min | Warning |
| Healthcheck Fail | Synthetic check fails 3x | Critical |
| OS Processing Error | Logs com "Erro" + "OS" > 5/min | Warning |

## Custo

| Componente | Ingestão Estimada | Custo |
|------------|------------------|-------|
| APM (transactions) | ~5GB/mês | $0 (Free tier) |
| Infrastructure | ~3GB/mês | $0 (Free tier) |
| Logs | ~10GB/mês | $0 (Free tier) |
| **Total** | **~18GB/mês** | **$0** |

O Free tier do New Relic permite 100GB/mês, muito acima do volume estimado do projeto.
