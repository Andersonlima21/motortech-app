# Diagrama de Sequência — Autenticação via CPF

## Fluxo Completo

```mermaid
sequenceDiagram
    actor C as Cliente
    participant AG as API Gateway
    participant L as Lambda (Node.js 20)
    participant DB as RDS MySQL
    participant SM as Secrets Manager

    Note over C,SM: Fluxo de Autenticação por CPF

    C->>AG: POST /api/auth/cpf<br/>{ "cpf": "529.982.247-25" }
    AG->>AG: Validar CORS e Rate Limit
    AG->>L: Invoke Lambda (event)

    Note over L: Processamento na Lambda

    L->>L: Extrair CPF do body
    L->>L: Remover formatação (só dígitos)
    L->>L: Validar formato CPF<br/>(algoritmo mod 11)

    alt CPF inválido
        L-->>AG: 400 { "message": "CPF inválido" }
        AG-->>C: 400 Bad Request
    end

    L->>SM: Obter DB credentials + JWT_SECRET
    SM-->>L: Credenciais

    L->>DB: SELECT id, nome, cpf_cnpj<br/>FROM cliente<br/>WHERE cpf_cnpj = '52998224725'

    alt Cliente não encontrado
        DB-->>L: Empty result
        L-->>AG: 404 { "message": "Cliente não encontrado" }
        AG-->>C: 404 Not Found
    end

    DB-->>L: { id: 1, nome: "João Silva", cpf_cnpj: "529.982.247-25" }

    L->>L: Gerar JWT Token (HS256)<br/>Claims: sub, iss, iat, exp, nbf, jti<br/>Custom: auth_type="cpf"

    L-->>AG: 200 {<br/>  "access_token": "eyJ...",<br/>  "token_type": "bearer",<br/>  "expires_in": 3600,<br/>  "cliente": { "id": 1, "nome": "João Silva" }<br/>}

    AG-->>C: 200 OK + JWT Token

    Note over C,SM: Token válido por 60 minutos (JWT_TTL)
```

## Detalhes Técnicos

### Claims do JWT gerado pela Lambda

| Claim | Descrição | Exemplo |
|-------|-----------|---------|
| `iss` | Emissor do token | `motortech-lambda` |
| `sub` | ID do cliente | `"1"` |
| `iat` | Timestamp de emissão | `1711234567` |
| `exp` | Timestamp de expiração | `1711238167` (iat + 3600s) |
| `nbf` | Válido a partir de | `1711234567` (= iat) |
| `jti` | ID único do token | `"a1b2c3d4-..."` (UUID v4) |
| `auth_type` | Tipo de autenticação | `"cpf"` |
| `cliente_nome` | Nome do cliente | `"João Silva"` |

### Validação do CPF (Algoritmo)

1. Remove caracteres não numéricos
2. Verifica se tem exatamente 11 dígitos
3. Rejeita CPFs com todos os dígitos iguais (ex: 111.111.111-11)
4. Calcula primeiro dígito verificador (módulo 11, pesos 10→2)
5. Calcula segundo dígito verificador (módulo 11, pesos 11→2)
6. Compara os dígitos calculados com os informados

### Compatibilidade com Laravel (tymon/jwt-auth)

O token gerado pela Lambda é validado pelo middleware `auth:api-cliente` do Laravel. A compatibilidade é garantida por:

- **Algoritmo**: HS256 (mesmo configurado em `config/jwt.php`)
- **Secret**: Mesmo `JWT_SECRET` compartilhado via Secrets Manager
- **Claims obrigatórios**: `iss`, `iat`, `exp`, `nbf`, `sub`, `jti` (conforme `required_claims` do jwt.php)
- **Guard**: `api-cliente` com provider `clientes` (model `App\Models\Cliente`)
