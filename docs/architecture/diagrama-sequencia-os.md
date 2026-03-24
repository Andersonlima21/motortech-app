# Diagrama de Sequência — Abertura de Ordem de Serviço

## Fluxo Completo

```mermaid
sequenceDiagram
    actor C as Cliente Autenticado
    participant AG as API Gateway
    participant ALB as ALB
    participant MW as Middleware Auth
    participant CTRL as OsController
    participant SVC as OsService
    participant DB as RDS MySQL
    participant NR as New Relic

    Note over C,NR: Pré-requisito: Cliente possui JWT válido

    C->>AG: POST /api/os/createOs<br/>Authorization: Bearer eyJ...<br/>{ "cliente_id": 1, "veiculo_id": 5, "observacoes": "Barulho no motor" }
    AG->>ALB: Forward request (route /api/*)
    ALB->>MW: HTTP Request → Nginx → PHP-FPM

    Note over MW: Validação JWT

    MW->>MW: Extrair token do header Authorization
    MW->>MW: Decodificar JWT (HS256 + JWT_SECRET)
    MW->>MW: Verificar: exp > now, nbf <= now
    MW->>MW: Identificar guard (api ou api-cliente)

    alt Token inválido/expirado
        MW-->>C: 401 { "message": "Unauthenticated" }
    end

    MW->>CTRL: Request autenticado
    CTRL->>CTRL: Validar request (OrdemServicoRequest)
    CTRL->>SVC: createOs(OrdemServicoDTO)

    Note over SVC,DB: Lógica de Negócio (Transaction)

    SVC->>DB: SELECT * FROM cliente WHERE id = 1
    alt Cliente não existe
        SVC-->>CTRL: Erro: "Cliente não encontrado"
        CTRL-->>C: 404
    end

    SVC->>DB: SELECT * FROM veiculo WHERE id = 5
    alt Veículo não existe
        SVC-->>CTRL: Erro: "Veículo não encontrado"
        CTRL-->>C: 404
    end

    SVC->>DB: BEGIN TRANSACTION
    SVC->>DB: INSERT INTO ordem_servico<br/>(cliente_id, veiculo_id, status, observacoes)<br/>VALUES (1, 5, 'RECEBIDA', 'Barulho no motor')
    DB-->>SVC: OS criada (id: 42)

    SVC->>DB: INSERT INTO historico_status<br/>(ordem_servico_id, status_anterior, status_novo)<br/>VALUES (42, NULL, 'RECEBIDA')
    SVC->>DB: COMMIT

    SVC-->>CTRL: OrdemServicoDTO { id: 42, status: 'RECEBIDA', ... }

    CTRL-->>ALB: 201 { "success": true, "data": { "id": 42, ... } }
    ALB-->>AG: Response
    AG-->>C: 201 Created

    Note over NR: Telemetria capturada automaticamente
    SVC-.->NR: Transaction trace + DB queries
```

## Máquina de Estados da Ordem de Serviço

```mermaid
stateDiagram-v2
    [*] --> RECEBIDA: createOs()
    RECEBIDA --> EM_DIAGNOSTICO: aprovar()
    EM_DIAGNOSTICO --> AGUARDANDO_APROVACAO: diagnosticar()
    AGUARDANDO_APROVACAO --> EM_EXECUCAO: orcamento(APROVADO)
    AGUARDANDO_APROVACAO --> FINALIZADA: orcamento(REPROVADO)
    EM_EXECUCAO --> FINALIZADA: finalizar()
    FINALIZADA --> ENTREGUE: marcarEntregue()
    ENTREGUE --> [*]
```

## Endpoints do Fluxo de OS

| Etapa | Método | Rota | Status Anterior | Status Novo |
|-------|--------|------|-----------------|-------------|
| Criar OS | POST | `/api/os/createOs` | — | RECEBIDA |
| Aprovar para diagnóstico | PUT | `/api/os/aprovar/{id}` | RECEBIDA | EM_DIAGNOSTICO |
| Diagnosticar | POST | `/api/os/diagnosticar/{id}` | EM_DIAGNOSTICO | AGUARDANDO_APROVACAO |
| Aprovar orçamento | PUT | `/api/os/orcamento/{id}/APROVADO` | AGUARDANDO_APROVACAO | EM_EXECUCAO |
| Reprovar orçamento | PUT | `/api/os/orcamento/{id}/REPROVADO` | AGUARDANDO_APROVACAO | FINALIZADA |
| Finalizar | PUT | `/api/os/finalizar/{id}` | EM_EXECUCAO | FINALIZADA |
| Confirmar entrega | GET | `/public/os/{id}/confirm-entrega` | FINALIZADA | ENTREGUE |

## Tabelas Envolvidas

Cada transição de status gera um registro em `historico_status`, garantindo rastreabilidade completa do ciclo de vida da OS.
