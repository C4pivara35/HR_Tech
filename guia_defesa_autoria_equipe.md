# 🎓 Guia Estratégico de Preparação para a Defesa de Autoria do Código
## Projeto HRTech Core — Padrões de Reuso, 10 CRUDs e Linha de Produção de Software (LPS)

> **Instituição:** Pontifícia Universidade Católica do Paraná (PUCPR)  
> **Disciplina:** Medição e Análise de Software / Engenharia de Software  
> **Data da Prova de Autoria:** 15/10 e 16/10 (conforme sorteio das equipes)  
> **Aviso Crítico:** A avaliação é individual por sorteio/arguição. Se um membro não souber responder sobre a sua parte ou sobre a arquitetura do projeto, a equipe inteira perde pontos.

---

## 🧭 1. O que TODOS os 5 Integrantes Devem Saber (Visão Compartilhada)

O professor pode abrir a arguição fazendo perguntas conceituais para qualquer aluno. Todos devem ter na ponta da língua:

### 1.1 A Arquitetura do Sistema em 3 Camadas
* **Camada 1: Domínio (`src/Domain/`):** Entidades puras (`Employee`, `Tenant`, `TimeLog`, etc.), Value Objects imutáveis (`Cpf`, `Cnpj`, `Money`, `GeoLocation`) e Enums tipados. Não depende de banco de dados.
* **Camada 2: Repositório (`src/Repositories/`):** Interfaces em `src/Repositories/Contracts/` e implementações concretas usando **PDO SQLite**. Executa `INSERT`, `SELECT`, `UPDATE`, `DELETE` com `PRAGMA foreign_keys = ON;`.
* **Camada 3: Serviço (`src/Services/`):** Onde reside a lógica de negócio, validações, transações ACID e orquestração dos repositórios.

### 1.2 Por que usamos Padrões focados em **REUSO** (Item 01 do Trabalho)?
* **Singleton (2 exemplos):**
  1. `TenantContextManager`: Reutiliza a mesma instância de contexto em toda a requisição, garantindo isolamento multi-tenant sem criar múltiplos objetos.
  2. `AuditLogger`: Reutiliza um ledger centralizado com encadeamento SHA-256 para gravar a trilha imutável.
  *(Bônus técnico: `DatabaseManager` também é Singleton para reaproveitar a conexão PDO).*
* **Template Method (3 exemplos — 9 subclasses):**
  1. `PayrollCalculatorTemplate`: Reutiliza o esqueleto fixo do cálculo da folha (`calculateBaseSalary` $\rightarrow$ `calculateEarnings` $\rightarrow$ `calculateTaxes` $\rightarrow$ `deductBenefits` $\rightarrow$ `generatePayslip`), variando os passos tributários em `CltPayroll`, `PjPayroll` e `InternPayroll`.
  2. `TimeLogImporterTemplate`: Reutiliza o fluxo de ingestão e validação de batidas, variando a extração em `CsvImporter`, `JsonImporter` e `ApiImporter`.
  3. `ReportGeneratorTemplate`: Reutiliza a extração e agrupamento de dados, variando a exportação em `PdfReportGenerator`, `ExcelReportGenerator` e `JsonReportGenerator`.
* **Strategy (3 famílias — 9 estratégias):**
  1. Horas Extras: `Standard50Strategy` (50% CLT), `Sunday100Strategy` (100% Domingo) e `BankHoursStrategy` (Banco de Horas).
  2. Desconto de Benefícios: `TransportationVoucherStrategy` (teto 6%), `HealthPlanStrategy` (faixas ANS) e `MealVoucherStrategy` (teto 20% PAT).
  3. Desempenho e Bônus: `OkrStrategy` (Tech), `Evaluation360Strategy` (Indústria) e `KpiStrategy` (Financeiro).
  * **Vantagem de Reuso:** Elimina `if/switch` repetitivos no código. O algoritmo é injetado polimorficamente em tempo de execução.

### 1.3 O que é a Linha de Produção de Software (LPS) do Projeto?
* **Arquivo:** `src/Lps/LpsVariabilityEngine.php` e `src/Lps/FeatureToggleManager.php`.
* **Como funciona:** O sistema adapta seu comportamento dinamicamente dependendo do segmento da empresa cliente:
  * **Tech:** Usa Banco de Horas (`BankHoursStrategy`), bônus por OKRs, benefícios flexíveis e dispensa EPIs.
  * **Indústria:** Paga horas extras em dinheiro (`Standard50Strategy`/`Sunday100Strategy`), transporte fretado, avaliação 360º e **bloqueia operários sem ASO ou com EPI vencido** (NR-6/NR-7).
  * **Financeiro:** Exige batida de ponto com biometria facial/digital (Portaria 671), auditoria estrita LGPD e bônus agressivo por KPIs.

### 1.4 Como rodar o projeto se o professor pedir ao vivo?
* **Terminal (Demonstração completa CLI):**
  ```bash
  php hrtech_backend_patterns/run_demo.php
  ```
* **Testes Automatizados (Zero erros):**
  ```bash
  php hrtech_backend_patterns/tests/m4_verify.php
  ```
* **Interface Web (Protótipo interativo):**
  ```bash
  python -m http.server 8085
  # Abrir http://localhost:8085 no navegador
  ```

---

## 👤 2. Fichas de Estudo Individuais dos 5 Integrantes

---

### 🟢 Integrante 1: Fernando Lopes Duarte
**Papel Principal:** Líder de Arquitetura, Persistência Relacional & Isolamento Multi-Tenant

#### 📁 Arquivos que Você Deve Abrir e Conhecer:
1. `src/Domain/Entities/Tenant.php` e `src/Domain/Entities/User.php`
2. `src/Domain/ValueObjects/Cnpj.php`
3. `src/Database/DatabaseManager.php`
4. `src/Patterns/Singleton/TenantContextManager.php` e `src/Patterns/Singleton/AuditLogger.php`
5. `src/Repositories/TenantRepository.php` e `src/Repositories/UserRepository.php`
6. `src/Services/TenantService.php` e `src/Services/UserService.php`

#### 🧠 O que Você Deve Saber Explicar:
* **CRUD 1 (Tenants):** Cadastro de empresas clientes, validação matemática do CNPJ (algoritmo Módulo 11 em `Cnpj.php`), unicidade do CNPJ em todo o sistema e licenciamento de módulos.
* **CRUD 2 (Usuários):** Criação de credenciais, controle RBAC (`UserRole`), autenticação segura com `password_hash($senha, PASSWORD_BCRYPT)` e proteção contra credenciais fracas ou duplicadas.
* **Padrão Singleton:** Explique por que o construtor é `private`, a instância é estática (`getInstance()`) e como o `TenantContextManager::runInContext()` garante que consultas nunca vazem dados entre empresas diferentes (*cross-tenant leakage*).
* **Banco de Dados:** Como o `DatabaseManager` configura `PRAGMA foreign_keys = ON;`, `WAL mode` e executa transações atômicas com `transaction(callable $callback)`.

#### ❓ Prováveis Perguntas do Professor & Como Responder:
> **P1: "Fernando, como você garante que uma empresa não acesse os usuários de outra empresa no mesmo banco?"**  
> **R:** *"Professor, nós implementamos o padrão Singleton `TenantContextManager`. Todas as consultas nos repositórios recebem o `tenant_id` ativo da sessão. Além disso, criamos o índice composto `idx_users_tenant` no SQLite cobrindo `(tenant_id, username)`, garantindo tanto a rapidez de busca quanto a restrição de unicidade por empresa."*

> **P2: "Por que você usou um Value Object para o CNPJ em vez de uma string comum?"**  
> **R:** *"Para garantir o princípio de 'Always-Valid Domain Model' do DDD. A classe `Cnpj` é imutável (`readonly`). No próprio construtor ela executa a validação dos dois dígitos verificadores por Módulo 11 e rejeita sequências inválidas. Se a instância existe, o CNPJ é comprovadamente válido."*

---

### 🟢 Integrante 2: Andryus
**Papel Principal:** Engenharia de Estrutura Organizacional, Cargos & Colaboradores

#### 📁 Arquivos que Você Deve Abrir e Conhecer:
1. `src/Domain/Entities/Employee.php`, `src/Domain/Entities/Department.php` e `src/Domain/Entities/Role.php`
2. `src/Domain/ValueObjects/Cpf.php` e `src/Domain/ValueObjects/Money.php`
3. `src/Repositories/EmployeeRepository.php` e `src/Repositories/DepartmentRoleRepository.php`
4. `src/Services/EmployeeService.php` e `src/Services/DepartmentRoleService.php`

#### 🧠 O que Você Deve Saber Explicar:
* **CRUD 3 (Colaboradores):** Admissão (`hireEmployee`), demissão (`terminateEmployee`), alteração salarial e controle de saldos iniciais (30 dias de férias e 0 minutos de banco de horas).
* **CRUD 4 (Departamentos e Cargos):** Criação de departamentos com centro de custo (`CostCenter`), estruturação de cargos com alçada hierárquica numérica de 1 a 100 (`Role::$hierarchyLevel`) para governança de aprovação de despesas e férias.
* **Value Object Money:** Explique por que valores monetários são guardados como **inteiros de centavos** (`cents`), evitando erros de arredondamento de ponto flutuante (`float`), e como o algoritmo de Martin Fowler reparte centavos residuais.
* **Value Object Cpf:** Validação dos dígitos do CPF e conformidade com LGPD através do método `getMasked()` (ex.: `***.456.789-**`).

#### ❓ Prováveis Perguntas do Professor & Como Responder:
> **P1: "Andryus, por que você usou inteiros para representar dinheiro na entidade Employee?"**  
> **R:** *"Professor, tipos `float` em computação causam imprecisão em operações financeiras e acumulam dízimas periódicas. Seguindo o padrão de Martin Fowler, o nosso Value Object `Money` armazena o valor em centavos inteiros (ex: R$ 1.500,50 vira 150050). Todos os cálculos de folha e benefícios são exatos."*

> **P2: "Como funciona a governança de aprovações entre cargos no CRUD 4?"**  
> **R:** *"Cada cargo possui um `hierarchy_level` de 1 a 100. Um cargo de nível 80 (Tech Lead ou Gerente) tem alçada para aprovar férias e ajustes de cargos de nível inferior, o que é validado pelo método `hasAuthorityOver()` da entidade."*

---

### 🟢 Integrante 3: Felipe
**Papel Principal:** Engenharia de Ponto Eletrônico, Conformidade Portaria 671 MTE & Imutabilidade

#### 📁 Arquivos que Você Deve Abrir e Conhecer:
1. `src/Domain/Entities/TimeLog.php` e `src/Domain/Entities/TimeAdjustmentRequest.php`
2. `src/Domain/ValueObjects/GeoLocation.php`
3. `src/Patterns/TemplateMethod/Importer/TimeLogImporterTemplate.php` (e `CsvImporter.php`, `JsonImporter.php`, `ApiImporter.php`)
4. `src/Repositories/TimeLogRepository.php` e `src/Repositories/TimeAdjustmentRepository.php`
5. `src/Services/TimeLogService.php` e `src/Services/TimeAdjustmentService.php`

#### 🧠 O que Você Deve Saber Explicar:
* **CRUD 5 (Ponto Eletrônico):** Registro de batida com NSR sequencial por empresa, geolocalização e assinatura SHA-256 encadeada (`previous_hash` do ponto anterior).
* **Imutabilidade Legal:** A Portaria 671/2021 do MTE proíbe expressamente alteração ou exclusão de batidas. No repositório, `update()` e `delete()` disparam `InvalidOperationException`.
* **CRUD 6 (Ajustes de Ponto):** Como um funcionário solicita ajuste caso tenha esquecido de bater o ponto (anexa justificativa, entra em status `PENDING` e o gestor aprova via `approveAdjustment()`).
* **Template Method de Importação:** A classe abstrata `TimeLogImporterTemplate` define as etapas (`openSource` $\rightarrow$ `parseRecords` $\rightarrow$ `validateRecords` $\rightarrow$ `enrichLocation` $\rightarrow$ `persistLogs` $\rightarrow$ `auditImport`), reaproveitando o código comum e especializando apenas a leitura em CSV, JSON ou API Bearer Token.

#### ❓ Prováveis Perguntas do Professor & Como Responder:
> **P1: "Felipe, e se um funcionário quiser apagar uma batida de ponto errada no banco de dados?"**  
> **R:** *"Professor, a Portaria 671/2021 do Ministério do Trabalho proíbe a alteração ou exclusão de registros de ponto. No `TimeLogRepository.php`, os métodos `update` e `delete` disparam uma `InvalidOperationException`. Se houver erro, o colaborador deve abrir uma solicitação de ajuste pelo CRUD 6 (`TimeAdjustmentRequest`), que passará por aprovação do gestor sem apagar o histórico original."*

> **P2: "Onde está o Template Method no seu módulo e qual o reuso dele?"**  
> **R:** *"Está no `TimeLogImporterTemplate.php`. Ele define o algoritmo invariante de importação de batidas. As subclasses `CsvImporter`, `JsonImporter` e `ApiImporter` apenas implementam a leitura do formato específico. Validação de NSR, geofencing e persistência são 100% reaproveitados da classe base."*

---

### 🟢 Integrante 4: Valentin
**Papel Principal:** Engenharia de Férias CLT, Gestão de Benefícios & Motor de Folha

#### 📁 Arquivos que Você Deve Abrir e Conhecer:
1. `src/Domain/Entities/VacationRequest.php` e `src/Domain/Entities/Benefit.php`
2. `src/Patterns/TemplateMethod/Payroll/PayrollCalculatorTemplate.php` (e `CltPayroll.php`, `PjPayroll.php`, `InternPayroll.php`)
3. `src/Patterns/Strategy/BenefitDiscount/` (`TransportationVoucherStrategy.php`, `HealthPlanStrategy.php`, `MealVoucherStrategy.php`)
4. `src/Repositories/VacationRepository.php` e `src/Repositories/BenefitRepository.php`
5. `src/Services/VacationService.php` e `src/Services/BenefitService.php`

#### 🧠 O que Você Deve Saber Explicar:
* **CRUD 7 (Férias CLT):** Validação dos Artigos 129 a 145 da CLT: período mínimo de 5 dias, fracionamento permitido em até 3 períodos (sendo um deles maior que 14 dias), abono pecuniário (venda legal de 10 dias) e **débito atômico** no saldo do colaborador após aprovação do gestor.
* **CRUD 8 (Benefícios):** Cadastro no catálogo (VT, VR, Plano de Saúde), parametrização de coparticipação percentual e cálculo de desconto salarial.
* **Template Method de Folha (`PayrollCalculatorTemplate`):** O método `final calculatePayroll()` executa os passos na ordem legal. `CltPayroll` calcula INSS e IRRF progressivos; `PjPayroll` aplica retenções na fonte (IRRF 1,5% e CSRF 4,65%); `InternPayroll` aplica isenção fiscal sob a Lei 11.788/2008.
* **Strategy de Benefícios:**
  * `TransportationVoucherStrategy`: Limita o desconto ao teto legal de 6% do salário (desconta o menor entre o custo real e os 6%).
  * `HealthPlanStrategy`: Aplica coparticipação com base nas faixas etárias da ANS.
  * `MealVoucherStrategy`: Limita o desconto ao teto de 20% previsto no PAT.

#### ❓ Prováveis Perguntas do Professor & Como Responder:
> **P1: "Valentin, como o sistema garante que o desconto de Vale-Transporte não seja abusivo?"**  
> **R:** *"Professor, nós encapsulamos essa regra no padrão Strategy `TransportationVoucherStrategy.php`, atendendo à Lei 7.418/1985. O algoritmo calcula 6% do salário-base. Se o custo real das passagens for menor que 6%, desconta o custo real; se for maior, o desconto é travado estritamente no teto de 6%."*

> **P2: "O que acontece com o saldo de férias do colaborador quando as férias são aprovadas?"**  
> **R:** *"No `VacationService.php`, a aprovação ocorre dentro de uma transação do SQLite. O status da solicitação muda para `APPROVED` e o método `debitVacationDays()` do `Employee` é chamado imediatamente, subtraindo os dias gozados do saldo disponível de forma atômica."*

---

### 🟢 Integrante 5: Nicholas
**Papel Principal:** Engenharia de SST (NR-6 / NR-7), Portal FinCorp Seguros & Variabilidade LPS

#### 📁 Arquivos que Você Deve Abrir e Conhecer:
1. `src/Domain/Entities/EquipmentASO.php` e `src/Domain/Entities/InsurancePolicy.php`
2. `src/Lps/LpsVariabilityEngine.php`, `src/Lps/FeatureToggleManager.php` e `src/Lps/Enums/TenantSegment.php`
3. `src/Patterns/Strategy/Performance/` (`OkrStrategy.php`, `Evaluation360Strategy.php`, `KpiStrategy.php`)
4. `src/Repositories/EquipmentASORepository.php` e `src/Repositories/InsurancePolicyRepository.php`
5. `src/Services/EquipmentASOService.php` e `src/Services/InsurancePolicyService.php`

#### 🧠 O que Você Deve Saber Explicar:
* **CRUD 9 (EPIs e Exames ASO):** Ficha unificada de Saúde e Segurança do Trabalho. Rastreia o Certificado de Aprovação (CA sob NR-6) do EPI e o laudo de Atestado de Saúde Ocupacional (admissional/periódico/demissional sob NR-7 com CRM do médico e aptidão `isFit`).
* **CRUD 10 (FinCorp Seguros):** Emissão de apólices de vida e responsabilidade civil (D&O), cálculo de prêmio mensal em centavos, capital total segurado e workflow de renovação e cancelamento.
* **Motor de Variabilidade LPS (`LpsVariabilityEngine`):**
  * Na **Indústria**, o método `validateWorkEligibility()` atua como barreira de segurança (*hard gate*): se o operário estiver sem ASO, com ASO inapto/vencido ou EPI com CA expirado, ele é **bloqueado de bater ponto ou entrar na fábrica**.
  * No perfil **Tech**, a exigência de EPI é dispensada (`PPE_WAIVED`).
  * No perfil **Financeiro**, a batida de ponto sem biometria é rejeitada.
* **Strategy de Performance:** `OkrStrategy` para Tech (com sobrecumprimento de até 120%), `Evaluation360Strategy` para Indústria (média ponderada autoavaliação + pares + liderança) e `KpiStrategy` para o setor Financeiro.

#### ❓ Prováveis Perguntas do Professor & Como Responder:
> **P1: "Nicholas, como a Linha de Produção de Software (LPS) altera o comportamento do sistema na prática?"**  
> **R:** *"Professor, o `LpsVariabilityEngine.php` consulta as feature flags da empresa. Se a empresa for do segmento `INDUSTRIA`, o motor obriga a checagem do ASO (NR-7) e CA do EPI (NR-6) antes de permitir a jornada. Se for do segmento `TECH`, o motor dispensa o EPI e resolve a estratégia de horas extras para `BankHoursStrategy` (banco de horas) e bônus para `OkrStrategy`."*

> **P2: "Como é controlado o vencimento de um EPI no CRUD 9?"**  
> **R:** *"A entidade `EquipmentASO` armazena a data de validade do CA do Ministério do Trabalho. O método `isCaExpired()` compara com a data atual. Se expirado, o repositório alerta o gestor e o motor da LPS bloqueia a alocação do trabalhador em áreas de risco."*

---

## 🎯 3. Cronograma e Plano de Ação para a Equipe

```
┌─────────────────┬────────────────────────────────────────────────────────────────────────┐
│ Etapa           │ O que a equipe deve fazer                                              │
├─────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 1. Leitura      │ Cada integrante lê com atenção a sua seção deste guia e abre seus      │
│                 │ arquivos de código correspondentes no VS Code.                         │
├─────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 2. Simulação    │ Reúnam-se no Discord/Meet e façam uma rodada de "Professor Falso": um  │
│                 │ colega faz as perguntas deste guia e o responsável responde sem olhar. │
├─────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 3. Execução     │ Todos devem praticar abrir o terminal e rodar:                         │
│                 │ `php hrtech_backend_patterns/run_demo.php`                             │
│                 │ `php hrtech_backend_patterns/tests/m4_verify.php`                      │
├─────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 4. Item 03      │ Gravar o vídeo curto (3 a 5 min) no formato não listado no YouTube     │
│                 │ demonstrando a compactação de código focada em reuso.                  │
└─────────────────┴────────────────────────────────────────────────────────────────────────┘
```
