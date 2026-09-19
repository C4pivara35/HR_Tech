# Relatório de Entrega: Padrões de Projeto, Persistência Relacional e Linha de Produção de Software (LPS)
## Projeto HRTech Core — Plataforma Modular de Gestão de Recursos Humanos

**Instituição:** Pontifícia Universidade Católica do Paraná (PUCPR)  
**Escola:** Escola Politécnica — Bacharelado em Engenharia de Software / Ciência da Computação  
**Disciplina:** Medição e Análise de Software (2026-2)  
**Professores Orientadores:** Equipe Docente da Disciplina de Medição e Análise de Software  
**Data da Entrega:** Setembro de 2026  
**Versão do Documento:** 1.0 (Consolidada Final da Equipe)  

---

### Equipe de Engenharia (5 Alunos)

| Integrante | Matrícula / Papel Principal no Projeto | Módulos e CRUDs Sob Responsabilidade |
| :--- | :--- | :--- |
| **Fernando Lopes Duarte** | Arquiteto Core, Database Lead & LPS Engineer | **CRUD 1:** Gestão de Tenants (Empresas Clientes)<br>**CRUD 2:** Gestão de Usuários e Controle de Acesso (RBAC/MFA) |
| **Andryus** | Engenheiro de Domínio & Estrutura Organizacional | **CRUD 3:** Cadastro e Manutenção de Colaboradores<br>**CRUD 4:** Gestão de Cargos, Níveis e Departamentos |
| **Felipe** | Engenheiro de Compliance & Ponto Eletrônico | **CRUD 5:** Registro de Ponto Eletrônico (Portaria 671 MTE & Hash)<br>**CRUD 6:** Gestão e Workflow de Ajustes de Ponto |
| **Valentin** | Engenheiro de Benefícios & Direitos Trabalhistas | **CRUD 7:** Gestão e Agendamento de Férias (CLT Art. 129-145)<br>**CRUD 8:** Catálogo e Manutenção de Benefícios Corporativos |
| **Nicholas** | Engenheiro de SST & Integração de Seguros | **CRUD 9:** Controle de EPIs (NR-6) e Exames Ocupacionais ASO (NR-7)<br>**CRUD 10:** Gestão de Apólices do Portal do Corretor FinCorp |

---

## 1. Apresentação e Contexto da Entrega

Professor,

A nossa equipe de cinco estudantes desenvolveu a arquitetura backend da plataforma **HRTech Core**, atendendo integralmente às especificações do slide da disciplina:
1. **Item 01 do Slide — Padrões de Projeto (Opção 01):** Modelagem e implementação de **12 Entidades de Domínio** enriquecidas com Value Objects e Enums com tipagem estrita no **PHP 8.3.6**, além de **8 Design Patterns** consolidados (2 Singletons, 3 Template Methods e 3 Strategies).
2. **Item 02 do Slide — Banco de Dados Relacional, 10 CRUDs e Variabilidade LPS:** Persistência relacional em **SQLite** com 12 tabelas, 11 índices de performance, chaves estrangeiras cascateadas e controle transacional ACID; **10 módulos CRUD** desenvolvidos e atribuídos de forma equitativa entre os 5 alunos (2 CRUDs por integrante); e o motor de **Linha de Produção de Software (LPS)** (`LpsVariabilityEngine`), demonstrando regras de negócio altamente variáveis e bloqueios de segurança do trabalho entre os perfis de **Tecnologia**, **Indústria** e **Financeiro**.

Para validar a integridade da plataforma, construímos uma suíte de testes com **868 asserções unitárias e de regressão (100% aprovadas)**, além de suítes adversariais contra injeção SQL, quebra de encadeamento criptográfico e estresse da LPS. Disponibilizamos também o executável CLI unificado `run_demo.php` e o protótipo web navegável com 17 telas.

---

## 2. Item 01 do Slide: 12 Classes de Domínio & 8 Padrões de Projeto (Opção 01)

### 2.1 As 12 Entidades de Domínio e Componentes de Apoio

A camada de domínio (`hrtech_backend_patterns/src/Domain/Entities/`) foi estruturada segundo os princípios do *Domain-Driven Design* (DDD) e Clean Architecture, garantindo que as regras de integridade dos dados residam no próprio núcleo do sistema.

```
Domain/
├── Entities/
│   ├── Tenant.php                  # Empresa cliente multi-tenant
│   ├── User.php                    # Credencial de acesso e autenticação
│   ├── Employee.php                # Dados do colaborador e vínculos contratuais
│   ├── Department.php              # Centro de custo e departamento
│   ├── Role.php                    # Cargo, alçada hierárquica e permissões
│   ├── TimeLog.php                 # Marcação de ponto eletrônico (Portaria 671)
│   ├── TimeAdjustmentRequest.php   # Solicitação de retificação/abono de ponto
│   ├── VacationRequest.php         # Solicitação e programação de férias CLT
│   ├── Benefit.php                 # Pacote de benefício e coparticipação
│   ├── EquipmentASO.php            # Ficha de EPI (NR-6) e Atestado ASO (NR-7)
│   ├── InsurancePolicy.php         # Apólice de seguros FinCorp Broker Portal
│   └── AuditLog.php                # Registro imutável de auditoria e trilha LGPD
├── ValueObjects/
│   ├── Cnpj.php                    # Validação módulo 11, formatação e filial
│   ├── Cpf.php                     # Validação algorítmica e máscara LGPD
│   ├── Money.php                   # Centavos inteiros, imutabilidade e Fowler Allocation
│   └── GeoLocation.php             # Coordenadas geográficas e cálculo Haversine
└── Enums/
    ├── TenantSegment.php           # tech, industria, financeiro
    ├── UserRole.php                # super_admin, company_admin, hr_manager, manager, employee, auditor, broker
    ├── EmploymentType.php          # CLT, PJ, INTERN
    ├── TimeLogType.php             # ENTRY, INTERVAL_START, INTERVAL_END, EXIT
    ├── AdjustmentStatus.php        # PENDING, APPROVED, REJECTED
    ├── VacationStatus.php          # REQUESTED, APPROVED, REJECTED, CANCELLED
    ├── BenefitType.php             # TRANSPORTATION, MEAL_VOUCHER, HEALTH_PLAN, etc.
    ├── ExamType.php                # ADMISSION, PERIODIC, RETURN_TO_WORK, ROLE_CHANGE, DISMISSAL
    └── PolicyStatus.php            # PROPOSED, ACTIVE, EXPIRED, CANCELLED, CLAIMED
```

#### Tabela de Especificação das 12 Entidades

| # | Entidade de Domínio | Descrição Funcional | Principais Atributos e Regras |
| :---: | :--- | :--- | :--- |
| **01** | `Tenant` | Representa a pessoa jurídica contratante da plataforma na modalidade multi-tenant. | ID único, CNPJ validado (`Cnpj`), Razão Social, Nome Fantasia, Segmento de Mercado (`TenantSegment`), Licenças ativas de módulos (JSON). |
| **02** | `User` | Usuário com credenciais para autenticação e alçadas de permissão. | Username único por tenant, email, hash bcrypt (`password_hash`), Perfil (`UserRole`), flag MFA, vínculo opcional a colaborador. |
| **03** | `Employee` | Cadastro do profissional com histórico trabalhista e contratual. | CPF validado (`Cpf`), nome completo, datas contratuais, Salário-base em centavos inteiros (`Money`), Tipo de vínculo (`EmploymentType`), saldos de férias e banco de horas. |
| **04** | `Department` | Unidade funcional interna da organização. | Código alfanumérico único por tenant, nome da divisão, código do centro de custo, ID do gestor responsável. |
| **05** | `Role` | Cargo ocupacional e definição de alçadas decisórias. | Nome do cargo, descrição, nível hierárquico numérico (1 a 100 para roteamento de aprovações), conjunto de permissões granulares em JSON. |
| **06** | `TimeLog` | Registro físico ou virtual de jornada sob a Portaria 671/2021 MTE. | Timestamp ISO 8601, Tipo de batida (`TimeLogType`), Coordenadas (`GeoLocation`), NSR sequencial, `previous_hash` e `signature_hash` SHA-256 imutáveis. |
| **07** | `TimeAdjustmentRequest` | Pedido de correção ou inclusão manual de ponto esquecido. | Data de referência, horário solicitado, justificativa legal, link para documento comprobatório, status (`AdjustmentStatus`), parecer do gestor. |
| **08** | `VacationRequest` | Solicitação de fruição de férias regulamentares (CLT Art. 129-145). | Período de gozo (mínimo 5 dias), abono pecuniário (venda legal de 1/3), adiantamento de 13º salário, status de aprovação (`VacationStatus`). |
| **09** | `Benefit` | Benefício concedido pela empresa ou convenção sindical. | Tipo do benefício (`BenefitType`), nome do plano, operadora conveniada, custo nominal em centavos, percentual de coparticipação do colaborador. |
| **10** | `EquipmentASO` | Ficha unificada de Saúde e Segurança do Trabalho (SST). | Nome do EPI, Número do CA e validade (NR-6), Tipo de exame ocupacional (`ExamType`), data e validade do ASO, CRM do médico, aptidão clínica (`isFit`). |
| **11** | `InsurancePolicy` | Apólice corporativa operada pelo Portal do Corretor FinCorp Seguros. | Número da apólice, código da corretora, seguradora emissora, colaborador segurado, capital segurado, prêmio mensal em centavos, status (`PolicyStatus`). |
| **12** | `AuditLog` | Trilha criptográfica de auditoria contábil, operacional e LGPD. | Identificador do ator (User), tipo de ação, entidade afetada, deltas de estado anterior e novo (JSON), endereço IP, User-Agent, carimbo temporal, encadeamento SHA-256. |

#### Value Objects Especializados
* **`Cnpj` & `Cpf`**: Implementam a rotina de validação dos dígitos verificadores (módulo 11), rejeitando sequências nulas repetidas (ex.: `000.000.000-00`). O `Cpf` implementa o método `getMasked()`, em conformidade com a Lei Geral de Proteção de Dados (LGPD).
* **`Money`**: Modela a moeda corrente nacional (BRL) armazenando valores monetários como **inteiros de centavos**. Essa abordagem elimina os conhecidos erros de arredondamento de ponto flutuante em operações contábeis. Inclui o algoritmo de partição justa de centavos residuais de Martin Fowler.
* **`GeoLocation`**: Encapsula latitude, longitude e raio de precisão, oferecendo o método `distanceTo()` baseado na fórmula trigonométrica de Haversine para determinar se a marcação de ponto ocorreu dentro do perímetro da sede ou da filial do cliente.

---

### 2.2 Os 8 Padrões de Projeto Implementados

A equipe implementou os 8 padrões de projeto da **Opção 01** exigidos pelo professor, divididos em **2 Singletons**, **3 Template Methods** e **3 Strategies**.

```
Patterns/
├── Singleton/
│   ├── TenantContextManager.php            # Isolamento multi-tenant da sessão
│   └── AuditLogger.php                     # Livro-razão criptográfico imutável
├── TemplateMethod/
│   ├── Payroll/
│   │   ├── PayrollCalculatorTemplate.php   # Algoritmo de folha de pagamento
│   │   ├── CltPayroll.php                  # Deduções progressivas CLT (INSS/IRRF)
│   │   ├── PjPayroll.php                   # Retenções empresariais na fonte
│   │   └── InternPayroll.php               # Bolsa-estágio conforme Lei 11.788
│   ├── Importer/
│   │   ├── TimeLogImporterTemplate.php     # Algoritmo de ingestão de batidas
│   │   ├── CsvImporter.php                 # Arquivos tabulares legados
│   │   ├── JsonImporter.php                # Webhooks de aplicativos móveis
│   │   └── ApiImporter.php                 # Relógios REP homologados (Bearer Token)
│   └── Report/
│       ├── ReportGeneratorTemplate.php     # Algoritmo de geração de relatórios
│       ├── PdfReportGenerator.php          # Layout paginado para impressão
│       ├── ExcelReportGenerator.php        # Exportação analítica com totalizadores
│       └── JsonReportGenerator.php         # Cargas estruturadas de auditoria
└── Strategy/
    ├── Overtime/
    │   ├── Standard50Strategy.php          # Horas extras úteis (50% CLT Art. 59 §1)
    │   ├── Sunday100Strategy.php           # Horas extras domingos/feriados (100% Súmula 146)
    │   └── BankHoursStrategy.php           # Compensação em banco de horas (Art. 59 §2)
    ├── BenefitDiscount/
    │   ├── TransportationVoucherStrategy.php # Teto legal de 6% do salário (Lei 7.418/85)
    │   ├── HealthPlanStrategy.php          # Coparticipação por faixas etárias da ANS
    │   └── MealVoucherStrategy.php         # Coparticipação limitada a 20% pelo PAT
    └── Performance/
        ├── OkrStrategy.php                 # Metas ágeis trimestrais (Foco Tech)
        ├── Evaluation360Strategy.php       # Avaliação equilibrada multissetorial (Indústria)
        └── KpiStrategy.php                 # Indicadores quantitativos de eficiência (Financeiro)
```

#### 1. Padrão Singleton (2 Instâncias)

* **`TenantContextManager`**:
  * **Problema:** Em uma arquitetura multi-tenant, o sistema deve garantir que todas as transações, consultas a banco e auditorias sejam estritamente isoladas por cliente, eliminando o risco de vazamento cruzado (*cross-tenant data leakage*).
  * **Solução:** Padrão Singleton que gerencia a instância do tenant ativo na requisição ou processo CLI. Possui o método `runInContext(Tenant $tenant, callable $task)`, que altera o contexto temporariamente para a execução da tarefa e garante a restauração atômica do contexto anterior em bloco `finally`.
  * **Snippet:**
    ```php
    $context = TenantContextManager::getInstance();
    $context->setTenant($empresaTech);
    // Toda consulta subsequente no repositório herda automaticamente o tenant ativo
    $colaboradores = $employeeRepo->findAll();
    ```

* **`AuditLogger`**:
  * **Problema:** As normas de auditoria e a LGPD exigem que alterações em dados sensíveis (salários, registros de ponto, demissões) sejam imutáveis e auditáveis contra adulterações no banco de dados.
  * **Solução:** Padrão Singleton que implementa uma trilha de auditoria encadeada com SHA-256. Cada novo log calcula o seu hash criptográfico com base nos seus dados e no `previous_hash` do evento imediatamente anterior. O método `verifyChainIntegrity()` percorre a lista e detecta imediatamente qualquer modificação não autorizada.

#### 2. Padrão Template Method (3 Algoritmos Macro)

* **`PayrollCalculatorTemplate`**:
  * **Problema:** O fluxo macro de cálculo da folha de pagamento (verificação de elegibilidade $\rightarrow$ totalização de proventos $\rightarrow$ desconto de tributos legais $\rightarrow$ desconto de benefícios $\rightarrow$ composição do holerite) é idêntico em toda a empresa, porém os cálculos tributários divergem completamente dependendo do regime contratual.
  * **Solução:** O método `calculatePayroll()` é declarado como `final` na classe abstrata, garantindo a sequência inviolável das etapas. As subclasses concretas sobrescrevem os passos específicos:
    * `CltPayroll`: Calcula faixas progressivas vigentes de INSS e IRRF, dedução de dependentes legais e respeita o teto estatutário de contribuição previdenciária.
    * `PjPayroll`: Aplica retenção corporativa na fonte (IRRF de 1,5% e CSRF de 4,65%), com deduções trabalhistas estritamente zeradas.
    * `InternPayroll`: Isenção de impostos trabalhistas e previdenciários conforme a Lei do Estágio (Lei 11.788/2008), repassando integralmente a bolsa-auxílio e o auxílio-transporte.

* **`TimeLogImporterTemplate`**:
  * **Problema:** A coleta de batidas de ponto eletrônico precisa receber dados de fontes heterogêneas (arquivos CSV exportados por relógios antigos, payloads JSON de celulares de colaboradores remotos ou chamadas diretas de relógios homologados REP via API).
  * **Solução:** O método `import()` orquestra a leitura bruta dos dados, validação de tipos, validação de conformidade (com emissão do NSR e hash de integridade) e persistência transacional.
    * `CsvImporter`: Realiza parsing de texto plano delimitado com tolerância a cabeçalhos sinônimos.
    * `JsonImporter`: Trata arrays associativos e formatos ISO 8601 enviados por endpoints web.
    * `ApiImporter`: Exige e valida cabeçalho de autenticação Bearer Token antes de realizar o processamento.

* **`ReportGeneratorTemplate`**:
  * **Problema:** Relatórios corporativos compartilham as fases de extração de dados, filtragem por alçada multi-tenant e formatação de cabeçalho e rodapé institucional, mas devem ser exportados em diferentes extensões e estruturas.
  * **Solução:** A classe abstrata gerencia o ciclo de vida da geração através do método `final generate()`.
    * `PdfReportGenerator`: Renderiza leiaute com quebras de linha e paginação textual para visualização humana.
    * `ExcelReportGenerator`: Constrói planilhas delimitadas com cálculo de soma aritmética na linha de rodapé.
    * `JsonReportGenerator`: Produz estrutura JSON pura com metadados analíticos para integração com BI.

#### 3. Padrão Strategy (3 Famílias de Regras de Negócio)

* **`OvertimeStrategy`**:
  * `Standard50Strategy`: Multiplica a taxa horária pelo fator legal de 1,5x para horas extras realizadas de segunda a sábado (CLT Art. 59 §1).
  * `Sunday100Strategy`: Aplica o fator em dobro (2,0x) sobre a hora normal para labor realizado em domingos e feriados (Súmula 146 do TST).
  * `BankHoursStrategy`: Zera a remuneração monetária imediata em dinheiro e converte as horas excedentes em minutos positivos creditados no banco de horas do colaborador (fator 1,5x ou 1,0x, Art. 59 §2 da CLT).

* **`BenefitDiscountStrategy`**:
  * `TransportationVoucherStrategy`: Aplica o desconto de Vale-Transporte respeitando estritamente o teto legal de 6% do salário-base contratual (Lei 7.418/1985), descontando o menor valor entre o teto e o custo real das passagens.
  * `HealthPlanStrategy`: Calcula a coparticipação em plano de saúde privado através de faixas etárias estipuladas pela Agência Nacional de Saúde Suplementar (ANS).
  * `MealVoucherStrategy`: Limita o desconto salarial de vale-refeição/alimentação ao teto máximo de 20% do valor do benefício previsto pelo Programa de Alimentação do Trabalhador (PAT - Lei 6.321/1976).

* **`PerformanceBonusStrategy`**:
  * `OkrStrategy`: Pontuação fundamentada em Objetivos e Resultados-Chave trimestrais (*Objectives and Key Results*), permitindo alavancagem por superação de metas (*overachievement*) de até 120%, padrão na indústria de Tecnologia.
  * `Evaluation360Strategy`: Combinação ponderada de autoavaliação (15%), avaliação do gestor direto (50%), avaliação de pares (20%) e subordinados (15%), gerando uma nota composta estável para ambientes fabris e operacionais.
  * `KpiStrategy`: Avaliação baseada em métricas numéricas com linha de corte mínima (*gate condition*), suporte a métricas normais e inversas ("menor é melhor", como taxa de turnover ou índice de acidentes), com modelo de bônus agressivo para executivos do setor financeiro.

---

## 3. Item 02 do Slide: Banco de Dados SQLite Relacional, 10 CRUDs e Variabilidade LPS

### 3.1 Arquitetura de Persistência Relacional SQLite

A camada de persistência foi concebida para fornecer robustez equivalente a bancos corporativos, utilizando o driver PDO do PHP conectado ao arquivo local `database.sqlite` (ou `:memory:` nos testes rápidos).

* **Gerenciador de Conexão (`DatabaseManager`)**:
  * Configurado com `PRAGMA foreign_keys = ON;`, impedindo registros órfãos.
  * Modo Write-Ahead Logging (`PRAGMA journal_mode = WAL;`) para alto desempenho em concorrência de leitura e escrita.
  * Suporte a transações atômicas seguras via método `transaction(callable $callback)`, que efetua `commit` automático em caso de sucesso e `rollBack` imediato caso qualquer exceção seja disparada.
* **12 Tabelas Relacionais Estruturadas**:
  1. `tenants`: Cadastro central de empresas clientes com segmentação e licenças.
  2. `departments`: Departamentos corporativos com vínculo relacional ao tenant e centro de custo.
  3. `roles`: Cargos com hierarquia de 1 a 100 e matriz de permissões.
  4. `employees`: Tabela de colaboradores com CPF, dados de admissão e saldos de banco e férias.
  5. `users`: Contas de autenticação, senhas criptografadas em bcrypt e status MFA.
  6. `time_logs`: Registro oficial de ponto eletrônico com NSR, coordenadas e encadeamento SHA-256.
  7. `time_adjustment_requests`: Solicitações de retificação de batidas e justificativas.
  8. `vacation_requests`: Gestão de solicitações de férias, abono pecuniário e status de aprovação.
  9. `benefits`: Tabela de benefícios cadastrados, provedores e regras de coparticipação.
  10. `equipment_aso`: Controle de EPIs entregues, número do CA e laudos médicos de ASO.
  11. `insurance_policies`: Apólices emitidas pela FinCorp Broker Portal com capital e prêmios.
  12. `audit_logs`: Ledger de auditoria imutável com hashes anterior e atual.
* **11 Índices de Alta Eficiência Criados**:
  * `idx_users_tenant`: Otimização de login multi-tenant `(tenant_id, username)`.
  * `idx_employees_tenant_cpf`: Busca rápida e garantia de unicidade `(tenant_id, cpf)`.
  * `idx_time_logs_tenant_emp`: Filtro de espelho de ponto `(tenant_id, employee_id, timestamp)`.
  * `idx_audit_logs_tenant`: Trilha cronológica de auditoria `(tenant_id, timestamp)`.
  * `idx_vacation_requests_emp`: Gestão de férias pendentes `(tenant_id, employee_id, status)`.
  * `idx_equipment_aso_emp`: Controle de segurança por colaborador `(tenant_id, employee_id)`.
  * `idx_insurance_policies_emp`: Localização de apólices ativas `(tenant_id, employee_id)`.
  * `idx_departments_tenant`: Busca de departamentos por código no tenant `(tenant_id, code)`.
  * `idx_roles_tenant`: Ordenação hierárquica de alçadas `(tenant_id, hierarchy_level)`.
  * `idx_time_adjustment_requests_tenant_emp`: Fila de aprovação do gestor `(tenant_id, employee_id, status)`.
  * `idx_benefits_tenant`: Catálogo de benefícios por categoria `(tenant_id, type)`.

---

### 3.2 Os 10 CRUDs Implementados e Distribuídos Entre os 5 Integrantes

A distribuição das operações de persistência e regras de negócio foi dividida formalmente entre os cinco estudantes do grupo, mantendo o padrão arquitetural em camadas: **Interface de Contrato** $\rightarrow$ **Repositório Concreto PDO** $\rightarrow$ **Serviço de Domínio**.

#### Matriz Oficial de Mapeamento dos 10 CRUDs

| Integrante | CRUD | Entidade / Módulo | Repositório & Serviço | Regras de Negócio e Validações Implementadas |
| :--- | :--- | :--- | :--- | :--- |
| **Fernando Lopes Duarte** | **CRUD 1** | Gestão de Tenants (Empresas Clientes) | `TenantRepository`<br>`TenantService` | Validação formal de CNPJ com dígito verificador via Value Object; garantia de unicidade de CNPJ em toda a plataforma; ativação, inativação e atualização de pacotes de licenças. |
| **Fernando Lopes Duarte** | **CRUD 2** | Gestão de Usuários e Controle de Acesso | `UserRepository`<br>`UserService` | Hash seguro de senhas via `password_hash(PASSWORD_BCRYPT)`; proteção contra senhas fracas; controle de tentativas de login e MFA; isolamento multi-tenant (username único por tenant). |
| **Andryus** | **CRUD 3** | Cadastro e Manutenção de Colaboradores | `EmployeeRepository`<br>`EmployeeService` | Validação de CPF com rejeição de repetidos; armazenamento financeiro estrito em centavos inteiros; controle inicial de 30 dias de saldo de férias; movimentação de saldo de banco de horas (em minutos). |
| **Andryus** | **CRUD 4** | Gestão de Cargos e Departamentos | `DepartmentRoleRepository`<br>`DepartmentRoleService` | Unicidade de código de departamento por tenant; vinculação de centro de custo; estruturação de níveis hierárquicos numéricos de 1 a 100 para governança de aprovação de alçadas. |
| **Felipe** | **CRUD 5** | Registro de Ponto Eletrônico (Portaria 671) | `TimeLogRepository`<br>`TimeLogService` | Sequenciamento automático de NSR; captura de coordenadas com tolerância de geofencing; encadeamento de assinatura digital SHA-256. **Imutabilidade**: comandos de `UPDATE` e `DELETE` são bloqueados com `InvalidOperationException`. |
| **Felipe** | **CRUD 6** | Ajustes e Retificações de Ponto | `TimeAdjustmentRepository`<br>`TimeAdjustmentService` | Submissão de justificativa e comprovante de ausência; workflow de aprovação pelo gestor com registro de carimbo temporal e ID do aprovador; transição de estados `PENDING` $\rightarrow$ `APPROVED` / `REJECTED`. |
| **Valentin** | **CRUD 7** | Gestão e Agendamento de Férias | `VacationRepository`<br>`VacationService` | Validação das regras da CLT (Art. 129-145); período mínimo de 5 dias corridos e fracionamento permitido; cálculo de abono pecuniário (venda de 10 dias); débito atômico do saldo de férias do colaborador na aprovação. |
| **Valentin** | **CRUD 8** | Catálogo e Gestão de Benefícios | `BenefitRepository`<br>`BenefitService` | Cadastro de benefícios obrigatórios e flexíveis (VT, VR, VA, Saúde); parametrização de coparticipação percentual do empregado; cálculo de deduções máximas admitidas pela legislação trabalhista. |
| **Nicholas** | **CRUD 9** | Controle de EPIs e Exames Ocupacionais (ASO) | `EquipmentASORepository`<br>`EquipmentASOService` | Controle de entrega e devolução de EPI com número do Certificado de Aprovação (CA sob NR-6); registro de exames médicos ASO (admissional, periódico e demissional sob NR-7) com CRM e parecer Apto/Inapto. |
| **Nicholas** | **CRUD 10** | Apólices do Portal do Corretor FinCorp | `InsurancePolicyRepository`<br>`InsurancePolicyService` | Emissão de apólices de seguro de vida coletivo e seguro D&O; vinculação com código da corretora parceira; cálculo de prêmio mensal e capital total segurado; processo formal de renovação e cancelamento motivado. |

---

### 3.3 Motor de Variabilidade da Linha de Produção de Software (LPS)

O mecanismo de variabilidade da LPS foi implementado na classe central `HrTech\Lps\LpsVariabilityEngine`, apoiada pelo enum `TenantSegment` e pelo gerenciador de sinalizadores `FeatureToggleManager`.

A plataforma atende a três perfis de mercado (*tenants*) com requisitos e obrigações legais totalmente distintos:
1. **Tecnologia & Startups (`tech`)**: Trabalho flexível, foco em banco de horas, benefícios flexíveis, bônus baseado em OKRs e isenção de equipamentos de proteção individual.
2. **Indústria & Manufatura (`industria`)**: Rigorosa observância às Normas Regulamentadoras NR-6 e NR-7, horas extras pagas em folha, transporte fretado e avaliação de desempenho 360º.
3. **Setor Financeiro & Mercado de Capitais (`financeiro`)**: Ponto biométrico estritamente obrigatório (Portaria 671), auditoria avançada para SOX/LGPD, bônus agressivo por KPIs quantitativos e apólices de seguro de vida exclusivas do Portal do Corretor FinCorp.

#### Matriz de Configuração das 11 Feature Flags da LPS

| Feature Flag | Descrição do Recurso / Regra de Negócio | Tech | Indústria | Financeiro |
| :--- | :--- | :---: | :---: | :---: |
| `bank_of_hours` | Horas extras compensadas em descanso | **Ativo** | Inativo | Inativo |
| `overtime_payout` | Horas extras pagas em dinheiro no holerite | Inativo | **Ativo** | **Ativo** |
| `risk_ppe_required` | Obrigatoriedade de EPIs (CA) e Atestado ASO | Dispensado | **Obrigatório** | Dispensado |
| `flexible_benefits` | Cartão multibenefícios com saldo flexível | **Ativo** | Inativo | Inativo |
| `d_and_o_insurance` | Seguro de responsabilidade civil para diretores | **Ativo** | Inativo | Inativo |
| `biometric_punch_mandatory` | Ponto eletrônico com reconhecimento biométrico facial/digital | Inativo | Inativo | **Obrigatório** |
| `chartered_transport` | Fornecimento de transporte fretado próprio | Inativo | **Ativo** | Inativo |
| `executive_health_plan` | Plano médico com cobertura hospitalar executiva | Inativo | Inativo | **Ativo** |
| `aggressive_bonus` | Multiplicador de bonificação por metas agressivas | Inativo | Inativo | **Ativo** |
| `strict_lgpd_audit` | Auditoria completa com retenção estendida de logs | Inativo | Inativo | **Ativo** |
| `fincorp_life_policy` | Seguro de vida empresarial FinCorp Broker | Inativo | Inativo | **Ativo** |

#### Exemplos de Código do Motor de Variabilidade

##### 1. Despacho Polimórfico de Estratégias
O motor instancia dinamicamente a família de classes Strategy adequada às configurações ativas da empresa:

```php
// No arquivo hrtech_backend_patterns/src/Lps/LpsVariabilityEngine.php:
public function resolveOvertimeStrategy(Tenant|TenantSegment|string $tenant, bool $isSundayOrHoliday = false): OvertimeStrategyInterface
{
    // Se o tenant opera com Banco de Horas (ex.: Tech), compensa em minutos
    if ($this->isBankOfHoursActive($tenant)) {
        return new BankHoursStrategy();
    }

    // Se o labor ocorreu em domingo ou feriado (Indústria ou Financeiro), paga 100%
    if ($isSundayOrHoliday) {
        return new Sunday100Strategy();
    }

    // Caso padrão em dias úteis: paga 50%
    return new Standard50Strategy();
}
```

##### 2. Bloqueio de Segurança Ocupacional no Chão de Fábrica (NR-6 e NR-7)
Na Indústria, o motor de variabilidade atua como barreira impeditiva (*hard gate*). Se um colaborador não possuir exame ASO válido ou se o Certificado de Aprovação do seu EPI estiver expirado, ele é sumariamente **bloqueado de assumir o posto de trabalho**:

```php
// Validação de elegibilidade para início de jornada:
public function validateWorkEligibility(Employee $employee, ?EquipmentASO $aso, Tenant|TenantSegment|string $tenant): array
{
    // Em Tecnologia ou Financeiro, a exigência de EPI de risco é dispensada
    if (!$this->isPpeMandatory($tenant)) {
        return ['allowed' => true, 'reason' => 'EPI dispensado para este perfil.', 'code' => 'PPE_WAIVED'];
    }

    // Na Indústria, a ausência de registro médico bloqueia o início do turno
    if ($aso === null) {
        return ['allowed' => false, 'reason' => 'Trabalho bloqueado: Colaborador fabril sem ASO cadastrado.', 'code' => 'ASO_MISSING'];
    }

    // Colaborador com parecer clínico 'Inapto'
    if (!$aso->isFit) {
        return ['allowed' => false, 'reason' => 'Trabalho bloqueado: Colaborador considerado INAPTO pelo médico.', 'code' => 'ASO_UNFIT'];
    }

    // Atestado de Saúde Ocupacional vencido (infração à NR-7)
    if ($aso->isExamExpired()) {
        return ['allowed' => false, 'reason' => 'Trabalho bloqueado: Atestado ASO vencido.', 'code' => 'ASO_EXPIRED'];
    }

    // EPI com Certificado de Aprovação (CA) vencido (infração à NR-6)
    if ($aso->isCaExpired()) {
        return ['allowed' => false, 'reason' => 'Trabalho bloqueado: EPI com CA expirado.', 'code' => 'PPE_CA_EXPIRED'];
    }

    return ['allowed' => true, 'reason' => 'Colaborador apto e com EPIs regulares.', 'code' => 'COMPLIANT'];
}
```

##### 3. Validação de Ponto Biométrico no Setor Financeiro
No segmento Financeiro, a batida de ponto via web ou celular que não apresente autenticação biométrica criptográfica é sumariamente recusada:

```php
public function validateTimePunch(TimeLog $punch, Tenant|TenantSegment|string $tenant, bool $biometricVerified = false): array
{
    if ($this->isBiometricPunchMandatory($tenant) && !$biometricVerified) {
        return [
            'allowed' => false,
            'reason' => 'Batida rejeitada: Perfil financeiro exige validação biométrica sob a Portaria 671.'
        ];
    }

    return ['allowed' => true, 'reason' => 'Marcação validada com sucesso.'];
}
```

---

## 4. Guia Passo a Passo de Execução

Professor, para facilitar a avaliação prática do nosso projeto, estruturamos três maneiras de execução: via terminal com demonstração completa, execução das baterias de testes automatizados e acesso ao protótipo web interativo.

### 4.1 Execução do Script Demonstrativo CLI (`run_demo.php`)

O script `run_demo.php` é o ponto de entrada único que inicializa a plataforma, reconstrói o banco de dados e executa uma demonstração visual de todas as camadas.

```bash
# 1. Navegue até a pasta do backend a partir da raiz do projeto:
cd hrtech_backend_patterns

# 2. Execute o runner PHP:
php run_demo.php
```

#### O que o `run_demo.php` executa em tempo real:
1. **Bootstrap do Autoloader PSR-4:** Carrega dinamicamente todas as classes do namespace `HrTech\`.
2. **Inicialização do SQLite:** Cria ou recria o arquivo `database.sqlite` executando as migrações de todas as 12 tabelas e 11 índices.
3. **Demonstração dos 8 Design Patterns:**
   * Alterna contextos multi-tenant via `TenantContextManager`.
   * Registra eventos imutáveis no `AuditLogger` e valida a cadeia SHA-256.
   * Executa os 3 Template Methods (Folha CLT, PJ e Estagiário; Importador CSV, JSON e API; Relatórios PDF, Excel e JSON).
   * Executa as 3 Strategies (Horas Extras 50%, 100% e Banco de Horas; Descontos de VT, Saúde e VR; Avaliações OKR, 360º e KPI).
4. **Demonstração dos 10 CRUDs:** Executa operações reais de inserção, consulta, atualização e exclusão atribuídas individualmente aos 5 integrantes do grupo.
5. **Demonstração Prática da LPS:**
   * Simula a jornada em uma empresa **Tech** com saldo em banco de horas e OKRs.
   * Simula o bloqueio preventivo de um operário da **Indústria** por laudo de ASO vencido (NR-7) e subsequente liberação após regularização.
   * Simula a recusa de uma marcação de ponto sem biometria em uma instituição do setor **Financeiro** e aceitação após confirmação biométrica.

---

### 4.2 Execução das Suítes de Testes Automatizados

A equipe desenvolveu testes automatizados rigorosos, garantindo cobertura total dos requisitos de arquitetura, integridade e segurança.

#### 1. Testes Regressivos Oficiais (868 Asserções — 100% de Aprovação)

```bash
# Executar a partir da pasta 'hrtech_backend_patterns':

# Milestone 1: Value Objects, Enums e Contratos (132 asserções)
php tests/m1_verify.php

# Milestone 2: 12 Entidades de Domínio e Isolamento Multi-Tenant (376 asserções)
php tests/m2_verify.php

# Milestone 3: 2 Singletons, 3 Template Methods e 3 Strategies (200 asserções)
php tests/m3_verify.php

# Milestone 4: Banco SQLite, 10 CRUDs dos 5 Alunos e Motor LPS (160 asserções)
php tests/m4_verify.php
```

#### 2. Testes de Estresse e Adversariais (676 Asserções Adicionais)

```bash
# Teste de Estresse dos 10 CRUDs: SQL Injection, Foreign Keys e Concorrência (416 asserções)
php tests/m4_adversarial_cruds.php

# Teste Adversarial da LPS: Fuzzing de 10.000 iterações e reversão de regras (260 asserções)
php tests/m4_adversarial_lps.php
```

---

### 4.3 Acesso ao Protótipo Web Interativo (Porta 8085)

O protótipo visual da interface gráfica da plataforma HRTech Core foi implementado em HTML5, CSS3 e JavaScript moderno, contendo 17 telas funcionais e responsivas.

```bash
# 1. A partir da raiz do repositório, inicie o servidor embutido do Python:
python3 -m http.server 8085

# 2. Abra o navegador de sua preferência no endereço:
# http://localhost:8085
```

#### Navegação e Simulação de Cenários no Navegador:
* **Telas do Sistema:** Utilize a barra lateral para navegar entre o Dashboard do Colaborador, Painel do RH, Cadastro de Funcionários, Organograma Dinâmico, Espelho de Ponto Eletrônico, Agendamento de Férias, Gestão de EPIs/ASO e Portal FinCorp Seguros.
* **Alternância Interativa de Tenants:** No topo da página ou abrindo o Console de Desenvolvedor (F12), utilize as funções de alternância para inspecionar as variações da LPS em tempo real:
  * `switchTenant('tech')` $\rightarrow$ Perfil Startups e Tecnologia.
  * `switchTenant('industry')` $\rightarrow$ Perfil Indústria e Manufatura.
  * `switchTenant('financial')` $\rightarrow$ Perfil Financeiro e Bancário.

---

## 5. Conclusão e Considerações Finais da Equipe

A realização deste projeto consolidou de forma prática os conceitos de **Medição e Análise de Software**, **Engenharia de Linhas de Produção de Software (LPS)** e **Padrões de Arquitetura de Software**.

Destacamos como principais entregas da equipe:
1. **Conformidade Legal Estrita:** Aderência direta às normas brasileiras CLT (Art. 59 e 129), Portaria 671/2021 do Ministério do Trabalho e Emprego, Normas Regulamentadoras NR-6 (EPIs) e NR-7 (PCMSO/ASO), normas da ANS para coparticipação em saúde e PAT para alimentação.
2. **Qualidade de Código e Tipagem:** Código desenvolvido para **PHP 8.3.6**, utilizando recursos modernos de orientação a objetos como *constructor property promotion*, tipos estritos (`declare(strict_types=1)`), Value Objects imutáveis e enums tipados com *match expressions*.
3. **Persistência Confiável:** Implementação em SQLite com integridade referencial ativa, transações ACID e 11 índices de isolamento multi-tenant.
4. **Trabalho em Equipe Real:** Divisão equilibrada e rastreável de 10 CRUDs entre os 5 alunos (Fernando, Andryus, Felipe, Valentin e Nicholas), refletida em código, testes e relatórios.

Agradecemos a orientação do professor e colocamo-nos à disposição para eventuais esclarecimentos na arguição técnica do projeto.

---
*Curitiba, Setembro de 2026.*  
**Equipe HRTech Core — PUCPR**
