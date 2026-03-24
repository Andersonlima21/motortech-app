# ADR-004: Fluent Bit para Log Forwarding

**Status**: Aceito
**Data**: 2026-03-24

## Contexto

Precisamos coletar logs dos containers no EKS e enviá-los para o New Relic para análise e alertas. Existem várias opções de log forwarders para Kubernetes.

## Decisão

Utilizamos **Fluent Bit** como DaemonSet no EKS para coletar e enviar logs ao New Relic.

## Justificativa

- **Leve**: Fluent Bit consome ~50MB de RAM vs ~500MB do Fluentd
- **Performance**: Escrito em C, throughput superior ao Fluentd (Ruby)
- **Plugin nativo**: Plugin `nrlogs` envia direto para New Relic Logs API
- **K8s integration**: Enriquecimento automático com metadata K8s (pod, namespace, node)
- **CNCF**: Projeto graduado da Cloud Native Computing Foundation

## Alternativas

| Opção | RAM | Linguagem | Decisão |
|-------|-----|-----------|---------|
| Fluent Bit | ~50MB | C | Escolhido |
| Fluentd | ~500MB | Ruby | Descartado (pesado) |
| Vector | ~100MB | Rust | Descartado (menos maduro no K8s) |
| New Relic Logs agent | ~200MB | Go | Descartado (vendor-specific) |

## Consequências

### Positivas
- Baixo consumo de recursos no cluster (50m CPU, 64Mi RAM por node)
- Parser JSON nativo (logs estruturados do Laravel)
- Configuração via ConfigMap (fácil atualização)

### Negativas
- Configuração mais verbosa que Fluentd (sem plugins Ruby)
- Mitigação: configuração versionada no repo infra-k8s
