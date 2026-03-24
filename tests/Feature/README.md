# 🧪 Relatório de Testes Automatizados — MotorTech

> Este relatório documenta os resultados obtidos na execução dos testes automatizados com **PHPUnit**, realizados dentro do container Docker do projeto **MotorTech**.

---

## ⚙️ Ambiente de Testes

**Container:** `api_motortech`
**Framework:** Laravel 12
**Linguagem:** PHP 8.2
**Banco de Dados:** MySQL (em container isolado)
**Comando executado:**

```bash
docker exec -it api_motortech php artisan test
```

---

## 🧩 Testes Executados por Unidade

### 🔧 InsumoTest

```bash
php artisan test --filter=InsumoTest
```

**Resumo:**

* ✅ 6 testes executados
* ✅ 12 assertions
* ⏱️ Duração: 0.57s
* 💬 Todos os testes passaram com sucesso.

**Cenários testados:**

1. Criação de insumo com sucesso
2. Rejeição de cadastro duplicado
3. Listagem de insumos
4. Atualização de insumo
5. Exclusão de insumo
6. Adição e remoção de estoque

---

### 👤 ClienteTest

```bash
php artisan test --filter=ClienteTest
```

**Resumo:**

* ✅ 5 testes executados
* ✅ 10 assertions
* ⏱️ Duração: 0.30s
* 💬 Todos os testes passaram com sucesso.

**Cenários testados:**

1. Criação de cliente válido
2. Rejeição de CPF inválido
3. Retorno de cliente por ID
4. Atualização de cliente
5. Exclusão de cliente

---

### 🚗 VeiculoTest

```bash
php artisan test --filter=VeiculoTest
```

**Resumo:**

* ✅ 6 testes executados
* ✅ 12 assertions
* ⏱️ Duração: 0.39s
* 💬 Todos os testes passaram com sucesso.

**Cenários testados:**

1. Criação de veículo
2. Rejeição de cliente inexistente
3. Rejeição de placa duplicada
4. Consulta de veículo por ID
5. Atualização de veículo
6. Exclusão de veículo

---

### 🧰 ServicoTest

```bash
php artisan test --filter=ServicoTest
```

**Resumo:**

* ✅ 5 testes executados
* ✅ 9 assertions
* ⏱️ Duração: 0.19s
* 💬 Todos os testes passaram com sucesso.

**Cenários testados:**

1. Criação de serviço
2. Rejeição de duplicidade
3. Listagem geral
4. Atualização
5. Exclusão

---

---

### 🧰 osService

```bash
php artisan test --filter=OsServiceTest
```

**Resumo:**

* ✅ 5 testes executados
* ✅ 11 assertions
* ⏱️ Duração: 1.30s
* 💬 Todos os testes passaram com sucesso.

**Cenários testados:**

1. Criação de OS
2. Aprovar OS com sucesso
3. Diagnostico OS e geração de orçamento
4. aprovar orçamento e inciar execução
5. finalizar OS

---

## 📊 Resumo Geral

| Teste           | Total | Assertions | Duração | Status   |
| --------------- | ----- | ---------- | ------- | -------- |
| **InsumoTest**  | 6     | 12         | 0.57s   | ✅ Passed |
| **ClienteTest** | 5     | 10         | 0.30s   | ✅ Passed |
| **VeiculoTest** | 6     | 12         | 0.39s   | ✅ Passed |
| **ServicoTest** | 5     | 9          | 0.19s   | ✅ Passed |
| **OsServiceTest** | 5     | 11          | 1.30s   | ✅ Passed |

🟢 **Total:** 27 testes executados / 54 assertions
🕒 **Tempo total aproximado:** 2.75 segundos
✅ **100% de sucesso nos testes**

---

## 🔍 Observações Técnicas

* Todos os testes utilizam **banco em memória (SQLite)** ou **transações rollback** (`DatabaseTransactions`) garantindo isolamento.
* Os cenários cobrem as principais operações **CRUD** e validações de regras de negócio.
* Nenhum erro, exceção não tratada ou falha de integridade foi detectado.
* O fluxo de **estoque** (em `InsumoService`) foi validado para adição e subtração com controle de quantidade mínima (`0`).

---

## 🧾 Conclusão

Os testes demonstram que os módulos de **Cliente**, **Veículo**, **Serviço** e **Insumo** estão implementados de forma consistente e estável.
Todas as operações essenciais de CRUD e manipulação de dados foram validadas com sucesso, indicando **baixa probabilidade de falhas em produção**.

📌 O projeto cumpre os critérios de qualidade exigidos pelo **Tech Challenge**, apresentando testes automatizados robustos, integrais e de fácil manutenção.

---

📸 **Locais reservados para imagens dos testes:**

![Resultado InsumoTest](https://gitlab.com/andersonlimadev21-group/motortech/-/raw/main/api_motortech/public/assets/images/InsumoTest.png)
![Resultado ClienteTest](https://gitlab.com/andersonlimadev21-group/motortech/-/raw/main/api_motortech/public/assets/images/ClienteTest.png)
![Resultado VeiculoTest](https://gitlab.com/andersonlimadev21-group/motortech/-/raw/main/api_motortech/public/assets/images/VeiculoTest.png)
![Resultado ServicoTest](https://gitlab.com/andersonlimadev21-group/motortech/-/raw/main/api_motortech/public/assets/images/ServicoTest.png)
![Resultado OsServiceTest](https://gitlab.com/andersonlimadev21-group/motortech/-/raw/main/api_motortech/public/assets/images/OsServiceTest.png)

