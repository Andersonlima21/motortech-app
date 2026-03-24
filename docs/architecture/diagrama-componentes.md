# Diagrama de Componentes — MotorTech (AWS)

## Visão Geral da Arquitetura Cloud

```mermaid
graph TB
    subgraph Internet
        CLIENT[Cliente / Frontend]
    end

    subgraph AWS Cloud
        subgraph "API Gateway"
            APIGW[AWS API Gateway HTTP API]
        end

        subgraph "Serverless"
            LAMBDA[AWS Lambda<br/>Node.js 20<br/>Auth CPF → JWT]
        end

        subgraph "VPC 10.0.0.0/16"
            subgraph "Public Subnets"
                ALB[Application Load Balancer]
                NAT[NAT Gateway]
                IGW[Internet Gateway]
            end

            subgraph "Private Subnets"
                subgraph "EKS Cluster v1.29"
                    subgraph "Pod: motortech-app"
                        NGINX[Nginx<br/>Reverse Proxy]
                        PHP[PHP-FPM 8.2<br/>Laravel 12]
                    end
                    subgraph "Monitoring Pods"
                        NRAGENT[New Relic<br/>Infrastructure<br/>DaemonSet]
                        FLUENTBIT[Fluent Bit<br/>Log Forwarder<br/>DaemonSet]
                    end
                    HPA[HPA<br/>2-5 replicas]
                end
                RDS[(RDS MySQL 8.0<br/>Multi-AZ)]
            end
        end

        ECR[Amazon ECR<br/>Container Registry]
        SM[AWS Secrets Manager<br/>DB Creds / JWT Secret / APP_KEY]
        S3[S3 Bucket<br/>Terraform State]
    end

    subgraph "Observabilidade"
        NR[New Relic<br/>APM + Infra + Logs]
    end

    subgraph "CI/CD"
        GHA[GitHub Actions<br/>Build → Push → Deploy]
    end

    CLIENT -->|HTTPS| APIGW
    APIGW -->|POST /api/auth/cpf| LAMBDA
    APIGW -->|ANY /api/*| ALB
    LAMBDA -->|Query cliente| RDS
    LAMBDA -->|JWT Token| APIGW
    ALB --> NGINX
    NGINX --> PHP
    PHP -->|Queries| RDS
    PHP -.->|APM traces| NR
    FLUENTBIT -.->|JSON logs| NR
    NRAGENT -.->|Metrics| NR
    SM -.->|Secrets| PHP
    SM -.->|Secrets| LAMBDA
    GHA -->|Push image| ECR
    GHA -->|kubectl deploy| PHP
    HPA -.->|Scale| PHP
    IGW --- NAT
```

## Componentes e Responsabilidades

| Componente | Serviço AWS | Responsabilidade |
|------------|-------------|-----------------|
| API Gateway | API Gateway HTTP API | Ponto de entrada público, roteamento, rate limiting, CORS |
| Lambda Auth | Lambda (Node.js 20) | Validação de CPF, consulta cliente no RDS, geração de JWT |
| Load Balancer | ALB (via AWS LB Controller) | Distribuição de tráfego para pods EKS |
| App Server | EKS (PHP-FPM + Nginx) | API REST Laravel, lógica de negócio, validações |
| Banco de Dados | RDS MySQL 8.0 | Persistência de dados, Multi-AZ (produção) |
| Container Registry | ECR | Armazenamento de imagens Docker da aplicação |
| Secrets | Secrets Manager | Credenciais DB, JWT_SECRET, APP_KEY, webhook token |
| Terraform State | S3 + DynamoDB | Estado remoto do Terraform com locking |
| APM | New Relic PHP Agent | Monitoramento de transações, queries, erros |
| Infra Monitoring | New Relic Infrastructure | CPU, memória, disco por node/pod |
| Logs | Fluent Bit → New Relic Logs | Agregação de logs JSON estruturados |
| CI/CD | GitHub Actions | Build, test, push ECR, deploy EKS automático |

## Fluxo de Rede

1. **Requisições externas** entram pelo API Gateway (público)
2. **Autenticação CPF** é roteada para a Lambda (serverless, dentro da VPC)
3. **Requisições autenticadas** passam pelo ALB (subnet pública) para os pods EKS (subnet privada)
4. **Pods EKS** acessam o RDS (subnet privada) via security groups
5. **Lambda** acessa o RDS (subnet privada) via ENI na VPC
6. **Saída internet** dos pods privados passa pelo NAT Gateway

## Segurança

- **API Gateway**: CORS configurado, rate limiting
- **Lambda**: IAM Role com permissões mínimas, VPC-attached
- **EKS**: Nodes em subnets privadas, IRSA para service accounts
- **RDS**: Subnet privada, SG permite apenas EKS e Lambda na porta 3306
- **Secrets Manager**: Rotação automática de credenciais
- **ALB**: Interno (não exposto diretamente à internet)
- **JWT**: HS256 com secret compartilhado via Secrets Manager
