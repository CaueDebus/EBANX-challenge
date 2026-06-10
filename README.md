# EBANX Challenge — Desafio Técnico

API REST de operações financeiras básicas (depósito, saque e transferência) com estado em memória.

## Requisitos

- PHP 8.2+
- Composer

## Como rodar

```bash
composer install
php artisan serve
```

A API estará disponível em `http://localhost:8000`.

> **Atenção:** o estado é mantido em memória via processo único. Use obrigatoriamente `php artisan serve` — servidores com PHP-FPM (nginx, Herd) não compartilham estado entre workers e não funcionarão corretamente.

## Endpoints

| Método | Rota      | Descrição                        |
|--------|-----------|----------------------------------|
| POST   | /reset    | Reseta o estado da aplicação     |
| GET    | /balance  | Consulta saldo (`?account_id=X`) |
| POST   | /event    | Executa um evento financeiro     |

### Tipos de evento (`POST /event`)

| type       | Campos obrigatórios                        |
|------------|--------------------------------------------|
| `deposit`  | `destination`, `amount`                    |
| `withdraw` | `origin`, `amount`                         |
| `transfer` | `origin`, `destination`, `amount`          |

### Exemplos

```bash
# Reset
POST /reset
→ 200 OK

# Saldo de conta inexistente
GET /balance?account_id=1234
→ 404 0

# Depósito (cria conta automaticamente)
POST /event  {"type":"deposit","destination":"100","amount":10}
→ 201 {"destination":{"id":"100","balance":10}}

# Saque
POST /event  {"type":"withdraw","origin":"100","amount":5}
→ 201 {"origin":{"id":"100","balance":5}}

# Transferência
POST /event  {"type":"transfer","origin":"100","amount":5,"destination":"200"}
→ 201 {"origin":{"id":"100","balance":0},"destination":{"id":"200","balance":5}}

# Erros (conta inexistente ou saldo insuficiente)
→ 404 0
```

## Testes

```bash
php artisan test
```

A suíte cobre:
- Regras de negócio isoladas (`tests/Unit/AccountServiceTest.php`)
- Fluxo completo via HTTP com estado real (`tests/Feature/AccountIntegrationTest.php`)

## Regras de negócio

- Depósito cria a conta de destino automaticamente se ela não existir.
- Saque é rejeitado se a conta não existir ou se o saldo for insuficiente — o saldo nunca fica negativo.
- Transferência é rejeitada nas mesmas condições do saque, e é atômica: nenhuma das contas é alterada em caso de falha.
- Transferência para a própria conta de origem é rejeitada.
- `GET /balance` é estritamente somente leitura — não altera nenhum estado.

## Decisões técnicas

- **Estado em memória** (`static array`): conforme especificação, sem persistência em banco de dados.
- **`POST /reset`** zera o array estático, garantindo isolamento entre sessões de teste.
- **Sem camada de repositório**: o `AccountService` acessa o estado diretamente — adicionar abstração aqui seria over-engineering para o escopo do desafio.
- **Dois níveis de teste**: unitários (lógica do service com estado real) e integração (fluxo HTTP completo com service real, sem mocks).
