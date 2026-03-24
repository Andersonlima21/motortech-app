# RFC-002: Autenticação via CPF com AWS Lambda

**Status**: Aprovado
**Autor**: Equipe MotorTech
**Data**: 2026-03-24
**Revisores**: Arquitetura SOAT

## Contexto

O sistema MotorTech precisa permitir que clientes da oficina se autentiquem usando seu CPF para consultar o status de suas ordens de serviço. A Fase 3 exige uma solução serverless para autenticação.

## Problema

- Autenticação atual é por email/senha (voltada para operadores internos)
- Clientes da oficina não possuem conta de usuário no sistema
- Necessidade de validar CPF e verificar se o cliente está cadastrado
- Requisito de Function Serverless para autenticação

## Alternativas Consideradas

### 1. AWS Lambda + API Gateway (Escolhido)
- **Prós**: Serverless, pay-per-use, escala automática, cold start <1s com Node.js
- **Contras**: Cold start com VPC (~3-5s na primeira invocação), necessidade de shared secret
- **Runtime**: Node.js 20 (menor cold start que Python, `jsonwebtoken` battle-tested)

### 2. Endpoint no próprio Laravel
- **Prós**: Simples, mesmo codebase, sem infraestrutura adicional
- **Contras**: Não atende requisito de Function Serverless, sem isolamento

### 3. AWS Cognito
- **Prós**: Gerenciado, MFA, OAuth2 nativo, user pools
- **Contras**: Não usa CPF nativamente, complexidade adicional, custo por MAU, over-engineering

### 4. Auth0
- **Prós**: Fácil integração, social login, MFA
- **Contras**: Serviço externo pago, não é serverless próprio, vendor lock-in

## Decisão

**AWS Lambda (Node.js 20)** com **API Gateway HTTP API** porque:

1. **Atende o requisito**: Function Serverless para autenticação
2. **Isolamento**: A lógica de auth do cliente é separada da aplicação principal
3. **Custo**: ~$0/mês com Free Tier (1M invocações gratuitas)
4. **Compatibilidade JWT**: Usa `jsonwebtoken` com HS256, mesmo algoritmo do `tymon/jwt-auth` no Laravel
5. **CPF existente**: Reutiliza o algoritmo de validação já implementado em `app/Utils/Utils.php`

## Design Técnico

### Fluxo de Autenticação

```
POST /api/auth/cpf { "cpf": "529.982.247-25" }
  → API Gateway roteia para Lambda
  → Lambda:
    1. Remove formatação do CPF
    2. Valida formato (algoritmo mod 11)
    3. Consulta RDS: SELECT FROM cliente WHERE cpf_cnpj = ?
    4. Gera JWT com claims compatíveis com tymon/jwt-auth
  → Retorna: { access_token, token_type, expires_in, cliente }
```

### Compartilhamento do JWT_SECRET

Lambda e Laravel compartilham o mesmo `JWT_SECRET` via AWS Secrets Manager. Isso garante que:
- Tokens gerados pela Lambda são validados pelo middleware do Laravel
- Não há necessidade de comunicação entre Lambda e Laravel para validar tokens
- O algoritmo HS256 é simétrico (mesma chave para assinar e verificar)

### Claims JWT (compatibilidade tymon/jwt-auth)

```json
{
  "iss": "motortech-lambda",
  "sub": "1",
  "iat": 1711234567,
  "exp": 1711238167,
  "nbf": 1711234567,
  "jti": "uuid-v4",
  "auth_type": "cpf",
  "cliente_nome": "João Silva"
}
```

### Guard Dual no Laravel

O Laravel foi configurado com dois guards JWT:
- `api` — Para operadores (login por email/senha, model User)
- `api-cliente` — Para clientes (login por CPF via Lambda, model Cliente)

O middleware `Authenticate` verifica ambos os guards.

## Segurança

- CPF não é um segredo (é um identificador público), mas a consulta ao banco valida que o CPF está cadastrado como cliente
- Lambda roda dentro da VPC (acesso ao RDS via subnet privada)
- Tokens expiram em 60 minutos (JWT_TTL)
- Rate limiting no API Gateway para prevenir brute force

## Impacto

- Clientes podem consultar status de OS sem conta de usuário
- Operadores continuam usando login por email/senha
- Nenhuma mudança nos endpoints existentes
- Novo guard `api-cliente` não afeta rotas existentes (que usam `auth:api`)
