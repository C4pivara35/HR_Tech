# 🏢 HRTech Core — Plataforma Modular de Gestão de RH & Linha de Produção de Software (LPS)

> **Pontifícia Universidade Católica do Paraná (PUCPR)**  
> **Curso:** Bacharelado em Engenharia de Software  
> **Disciplina:** Medição e Análise de Software (2026)  
> **Trabalho em Equipe:** Implementação de Padrões de Projeto (Item 01), Banco de Dados Relacional SQLite com 10 CRUDs, Linha de Produção de Software (LPS) e Métricas de Software (UCP & APF).

---

## 👥 Equipe de Engenharia (5 Integrantes)

| Integrante | Atribuição no Projeto & Módulos CRUD |
|---|---|
| **Fernando Lopes Duarte** | **Líder de Arquitetura & Banco de Dados**<br>• **CRUD 1:** Gestão de Tenants / Empresas contratantes (`Tenant`)<br>• **CRUD 2:** Gestão de Usuários e Autenticação Bcrypt (`User`) |
| **Andryus** | **Engenharia de Estrutura Organizacional**<br>• **CRUD 3:** Cadastro de Colaboradores e Matrículas (`Employee`)<br>• **CRUD 4:** Gestão de Departamentos, Centros de Custo e Cargos (`Department` e `Role`) |
| **Felipe** | **Engenharia de Frequência e Portaria 671 MTE**<br>• **CRUD 5:** Registro de Ponto Eletrônico com Geolocalização e Encadeamento SHA-256 (`TimeLog`)<br>• **CRUD 6:** Workflow de Solicitações e Ajustes de Marcações (`TimeAdjustmentRequest`) |
| **Valentin** | **Engenharia de Benefícios e Ausências Legais**<br>• **CRUD 7:** Gestão e Agendamento de Férias CLT (`VacationRequest`)<br>• **CRUD 8:** Catálogo e Concessão de Benefícios Corporativos (`Benefit`) |
| **Nicholas** | **Engenharia de Segurança do Trabalho e FinCorp Seguros**<br>• **CRUD 9:** Gestão de EPIs (NR-6) e Exames Clínicos ASO (NR-7) (`EquipmentASO`)<br>• **CRUD 10:** Gestão de Apólices Corporativas FinCorp Seguros (`InsurancePolicy`) |

---

## 🛠️ Stack Tecnológica & Arquitetura

O sistema adota os princípios de **Clean Architecture**, **PSR-4 / PSR-12** e estilo corporativo moderno em **PHP 8.3**:

- **Linguagem Principal:** PHP 8.3.6 (com tipagem estrita `declare(strict_types=1)`, enums nativos, readonly properties e first-class callables).
- **Banco de Dados Relacional:** SQLite 3 via driver nativo PDO (`PRAGMA foreign_keys = ON;`, `PRAGMA journal_mode = WAL;`).
- **Padrões de Projeto GoF:** 2 Singletons, 3 Template Methods (com 9 especializações) e 3 Strategies (com 9 algoritmos dinâmicos).
- **Linha de Produção de Software (LPS / SPL):** Motor de variabilidade dinâmica (`LpsVariabilityEngine`) atendendo aos segmentos **Tech**, **Indústria** e **Financeiro**.
- **Frontend & Protótipo Interativo:** HTML5 semântico, CSS3 Moderno (design system dark mode) e JavaScript ES6+ modular com simulação em tempo real dos 3 tenants.
- **Métricas de Software:** Contagem consolidada de Casos de Uso Não Ajustados (UUCP = 178, AUCP = 148,501) e Análise de Pontos de Função IFPUG/NESMA (132 PFs não ajustados distribuídos entre os 5 alunos).

---

## 🧭 Guia "Onde Encontrar Cada Coisa" (Mapeamento Completo de Requisitos)

Para facilitar a navegação e a avaliação acadêmica, todos os requisitos dos slides da disciplina estão estritamente mapeados aos arquivos do repositório:

### 1. Item 01 do Slide — Padrões de Projeto GoF e 12 Classes de Domínio

#### 📦 12 Classes de Entidades de Domínio, Value Objects e Enums
- **Diretório:** `hrtech_backend_patterns/src/Domain/Entities/`
  1. `Tenant.php`: Entidade de organização/empresa cliente (multi-tenant isolado).
  2. `User.php`: Usuário autenticável do sistema com hash seguro de senha (Bcrypt) e controle RBAC.
  3. `Employee.php`: Colaborador vinculado ao tenant com matrícula, dados contratuais e saldo de férias/banco.
  4. `Department.php`: Estrutura departamental e centros de custo.
  5. `Role.php`: Cargo, CBO e faixas salariais.
  6. `TimeLog.php`: Batida de ponto eletrônico com NSR sequencial, IP, geolocalização e hash Portaria 671.
  7. `TimeAdjustmentRequest.php`: Pedido de retificação de batida com workflow de aprovação por gestor.
  8. `VacationRequest.php`: Solicitação de férias com controle de períodos aquisitivo/concessivo e abono pecuniário.
  9. `Benefit.php`: Benefício corporativo parametrizável (transporte, refeição, saúde, odontológico).
  10. `EquipmentASO.php`: Ficha unificada de EPIs (com CA do Ministério do Trabalho) e Atestados ASO (aptidão clínica).
  11. `InsurancePolicy.php`: Apólice de seguro corporativo emitida pelo Portal do Corretor FinCorp.
  12. `AuditLog.php`: Registro de trilha imutável de auditoria em conformidade com a LGPD.
- **Value Objects:** `hrtech_backend_patterns/src/Domain/ValueObjects/` (`Cpf.php`, `Cnpj.php`, `GeoLocation.php`, `Money.php`).
- **Enums Nativos:** `hrtech_backend_patterns/src/Domain/Enums/` (`UserRole.php`, `EmploymentType.php`, `TimeLogType.php`, `AdjustmentStatus.php`, `VacationStatus.php`, `BenefitType.php`, `ExamType.php`, `PolicyStatus.php`).

#### 🔒 2 Singletons
- **Diretório:** `hrtech_backend_patterns/src/Patterns/Singleton/`
  1. `TenantContextManager.php`: Gerenciador do escopo e contexto do tenant ativo durante a requisição, garantindo isolamento total de dados entre clientes.
  2. `AuditLogger.php`: Mecanismo centralizado de trilha de auditoria contínua, implementando encadeamento criptográfico inviolável (hash SHA-256 encadeado).
  *(Nota técnica: `DatabaseManager.php` em `src/Database/` também implementa o padrão Singleton para controle de conexão PDO).*

#### 📐 3 Template Methods (e 9 Implementações Concretas)
- **Diretório:** `hrtech_backend_patterns/src/Patterns/TemplateMethod/`
  1. **Cálculo de Folha de Pagamento:** `Payroll/PayrollCalculatorTemplate.php`
     - Estrutura invariante: `calculateBaseSalary()` $\rightarrow$ `calculateEarnings()` $\rightarrow$ `calculateTaxes()` $\rightarrow$ `deductBenefits()` $\rightarrow$ `generatePayslip()`.
     - Variação CLT: `Payroll/CltPayroll.php` (INSS, IRRF progressivo, FGTS).
     - Variação PJ: `Payroll/PjPayroll.php` (retenção na fonte, faturamento por nota).
     - Variação Estagiário: `Payroll/InternPayroll.php` (bolsa-auxílio, seguro de vida, isenção previdenciária).
  2. **Importação de Batidas de Ponto:** `Importer/TimeLogImporterTemplate.php`
     - Estrutura invariante: `openSource()` $\rightarrow$ `parseRecords()` $\rightarrow$ `validateRecords()` $\rightarrow$ `enrichLocation()` $\rightarrow$ `persistLogs()` $\rightarrow$ `auditImport()`.
     - Variação CSV: `Importer/CsvImporter.php`.
     - Variação JSON: `Importer/JsonImporter.php`.
     - Variação API: `Importer/ApiImporter.php` (integração direta com relógios de ponto REP homologados).
  3. **Geração de Relatórios Gerenciais:** `Report/ReportGeneratorTemplate.php`
     - Estrutura invariante: `fetchData()` $\rightarrow$ `filterByTenant()` $\rightarrow$ `aggregateMetrics()` $\rightarrow$ `formatOutput()` $\rightarrow$ `export()`.
     - Variação PDF: `Report/PdfReportGenerator.php`.
     - Variação Excel: `Report/ExcelReportGenerator.php`.
     - Variação JSON: `Report/JsonReportGenerator.php`.

#### 🎯 3 Strategies (e 9 Algoritmos Polimórficos)
- **Diretório:** `hrtech_backend_patterns/src/Patterns/Strategy/`
  1. **Horas Extras & Compensação:** `Overtime/`
     - `Standard50Strategy.php`: Acréscimo legal de 50% para dias úteis (CLT art. 59).
     - `Sunday100Strategy.php`: Adicional integral de 100% para domingos e feriados nacionais.
     - `BankHoursStrategy.php`: Sem desembolso financeiro imediato; conversão direta em minutos no Banco de Horas.
  2. **Deduções de Benefícios:** `BenefitDiscount/`
     - `TransportationVoucherStrategy.php`: Desconto com teto legal de 6% sobre o salário-base.
     - `HealthPlanStrategy.php`: Desconto coparticipativo com teto de coparticipação.
     - `MealVoucherStrategy.php`: Desconto percentual simbólico seguindo as diretrizes do PAT.
  3. **Avaliação de Desempenho e Bônus:** `Performance/`
     - `OkrStrategy.php`: Metas trimestrais de produto e tecnologia (máximo de 120% com sobrecumprimento).
     - `Evaluation360Strategy.php`: Média ponderada entre autoavaliação, avaliação da liderança e avaliação de pares.
     - `KpiStrategy.php`: Indicadores operacionais quantitativos com corte eliminatório de elegibilidade.

---

### 2. Item 02 do Slide — Banco de Dados SQLite, 10 CRUDs e Motor LPS

#### 🗄️ Banco de Dados Relacional SQLite
- **Arquivo Central:** `hrtech_backend_patterns/src/Database/DatabaseManager.php`
- **Características de Engenharia:**
  - **12 Tabelas Normalizadas:** `tenants`, `users`, `departments`, `roles`, `employees`, `time_logs`, `time_adjustment_requests`, `vacation_requests`, `benefits`, `equipment_aso`, `insurance_policies`, `audit_logs`.
  - **11 Índices de Alta Eficiência:** Índices cobrindo `tenant_id`, CPFs, matrículas, NSRs e chaves de busca frequente.
  - **Integridade e Transacionalidade:** `PRAGMA foreign_keys = ON;`, `BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK` atômico em falhas operacionais.
  - **Modo WAL (Write-Ahead Logging):** Concorrência limpa para leituras e gravações.

#### 📋 10 CRUDs Distribuídos entre os 5 Membros da Equipe

| Integrante | CRUD | Entidade | Repositório | Serviço de Negócio | Métodos Principais |
|---|---|---|---|---|---|
| **Fernando** | **CRUD 1: Tenants** | `Tenant` | `TenantRepository.php` | `TenantService.php` | `createTenant`, `updateTenantNames`, `listActiveTenants`, `switchTenant` |
| **Fernando** | **CRUD 2: Usuários** | `User` | `UserRepository.php` | `UserService.php` | `createUser`, `authenticate`, `updatePassword`, `deactivateUser` |
| **Andryus** | **CRUD 3: Colaboradores** | `Employee` | `EmployeeRepository.php` | `EmployeeService.php` | `hireEmployee`, `terminateEmployee`, `updateSalary`, `adjustBankHours` |
| **Andryus** | **CRUD 4: Deptos e Cargos** | `Department`, `Role` | `DepartmentRoleRepository.php` | `DepartmentRoleService.php` | `createDepartment`, `createRole`, `assignRole`, `getHierarchy` |
| **Felipe** | **CRUD 5: Ponto Eletrônico** | `TimeLog` | `TimeLogRepository.php` | `TimeLogService.php` | `recordPunch` (SHA-256 imutável), `getPunchesByPeriod`, `verifyChain` |
| **Felipe** | **CRUD 6: Ajustes de Ponto** | `TimeAdjustmentRequest` | `TimeAdjustmentRepository.php` | `TimeAdjustmentService.php` | `requestAdjustment`, `approveAdjustment`, `rejectAdjustment`, `listPending` |
| **Valentin** | **CRUD 7: Solicitações de Férias** | `VacationRequest` | `VacationRepository.php` | `VacationService.php` | `requestVacation`, `approveVacation`, `cancelVacation`, `getVacationBalance` |
| **Valentin** | **CRUD 8: Benefícios** | `Benefit` | `BenefitRepository.php` | `BenefitService.php` | `createBenefit`, `enrollEmployee`, `cancelBenefit`, `listActiveBenefits` |
| **Nicholas** | **CRUD 9: EPIs e Exames ASO** | `EquipmentASO` | `EquipmentASORepository.php` | `EquipmentASOService.php` | `deliverEquipment`, `recordMedicalExam`, `checkMedicalFitness`, `blockIfExpired` |
| **Nicholas** | **CRUD 10: Apólices Seguros** | `InsurancePolicy` | `InsurancePolicyRepository.php` | `InsurancePolicyService.php` | `issuePolicy`, `renewPolicy`, `cancelPolicy`, `listActivePolicies` |

#### 🏭 Motor de Variabilidade LPS (Software Product Line)
- **Diretório:** `hrtech_backend_patterns/src/Lps/`
  - `LpsVariabilityEngine.php`: Motor central de despacho e resolução de variabilidade dinâmica.
  - `FeatureToggleManager.php`: Gerenciador de flags de funcionalidade por segmento com suporte a overrides de tenant.
  - `Enums/TenantSegment.php`: Enum tipado com os 3 perfis de clientes (`TECH`, `INDUSTRIA`, `FINANCEIRO`).
- **Resumo dos Perfis LPS:**
  - **Perfil Tecnologia (Tech):** Banco de Horas ativo (`BankHoursStrategy`), horas extras em pecúnia desativadas, EPIs dispensados, pacote de benefícios flexíveis (Caju/Flash), avaliação por OKRs trimestrais (`OkrStrategy`), seguro D&O executivo.
  - **Perfil Manufatura / Fábrica (Indústria):** Horas extras em folha ativas com 50% e 100% (`Standard50Strategy` e `Sunday100Strategy`), banco de horas desativado, bloqueio estrito de alocação no chão de fábrica por falta de ASO apto (NR-7) ou CA de EPI expirado (NR-6), transporte fretado, avaliação 360° (`Evaluation360Strategy`).
  - **Perfil Bancário / Corporativo (Financeiro):** Ponto biométrico rigoroso com geolocalização e hash obrigatórios (Portaria 671), plano de saúde executivo, bônus agressivo por KPIs quantitativos (`KpiStrategy`), auditoria estrita LGPD/BACEN e seguro de vida corporativo FinCorp.

---

### 3. Entregas de Métricas da Disciplina (UCP & APF)

#### 📊 Análise de Pontos de Caso de Uso (UCP & AUCP)
- `HRTech_Core_UCP_Completo_3_Abas.xlsx`: Planilha mestra consolidada com 3 abas contendo o cálculo de UUCP (170 UUCW + 8 UAW = 178 UUCP), os 13 Fatores Técnicos TCF (1,105) e os 8 Fatores Ambientais ECF (0,755).
- `Calculo_Final_UCP_AUCP_HRTech_Core.xlsx`: Memória de cálculo detalhada resultando no total de **148,501 AUCP** e esforço estimado de **2.970 horas**.
- `HRTech_Core_Entrega_Completa_UCP.docx` & `entrega_completa_ucp_hrtech.md`: Relatórios técnicos formais com a matriz de rastreabilidade completa dos 17 Casos de Uso (UC01 a UC17).
- `Avaliacao_TCF_UCP_HRTech_Core.xlsx`, `.pdf` e `avaliacao_tcf_ucp.html`: Avaliações dos fatores técnicos.

#### 🧮 Análise de Pontos de Função (APF — 132 PFs para 5 Alunos)
- `Estimativa_APF_5_Membros_ALIs_AIEs_HRTech.docx` & `estimativa_apf_5_membros_hrtech.md`:
  - Contagem oficial conforme a regra da atividade (2 ALIs + 2 AIEs por integrante = **10 ALIs + 10 AIEs = 20 arquivos de dados**).
  - Total de **132 Pontos de Função Não Ajustados** perfeitamente distribuídos:
    * **Fernando (Membro 1):** 17 PF (ALIs) + 12 PF (AIEs) = **29 PF**
    * **Andryus (Membro 2):** 14 PF (ALIs) + 10 PF (AIEs) = **24 PF**
    * **Felipe (Membro 3):** 14 PF (ALIs) + 12 PF (AIEs) = **26 PF**
    * **Valentin (Membro 4):** 14 PF (ALIs) + 10 PF (AIEs) = **24 PF**
    * **Nicholas (Membro 5):** 17 PF (ALIs) + 12 PF (AIEs) = **29 PF**
    * **Total:** **76 PF (10 ALIs) + 56 PF (10 AIEs) = 132 PF**.

---

### 4. Relatórios de Entrega Acadêmica & Artefatos Visuais

- `entrega_projeto_equipe_padroes_lps_hrtech.md`: Relatório de entrega da equipe em estilo universitário limpo e direto, documentando a implementação dos padrões e dos 10 CRUDs.
- `Entrega_Projeto_Equipe_Padroes_LPS_HRTech.docx`: Versão formatada em Word (.docx) com tipografia limpa, tabelas e caixas de destaque para entrega ao professor.
- `relatorio_abnt.html`: Relatório técnico completo formatado segundo as normas ABNT.
- `HRTech_Core_Modelo_Logico.drawio.svg`: Diagrama vetorial completo do modelo relacional de dados.
- `HRTech_Core_Documento_Projeto-1.docx`, `.docx.md` e `.docx.pdf`: Especificação de requisitos original do projeto.

---

### 5. Protótipo Web Interativo (Frontend SPA)

- **Localização:** Raiz do projeto (`index.html`, `styles.css`, `app.js`, `assets/`, `screenshots/`).
- **Recursos:**
  - 17 telas funcionais navegáveis em modo escuro (dark theme).
  - Seletor de Tenant em tempo real (Tech vs. Indústria vs. FinCorp Seguros) alternando dinamicamente visibilidade de telas, campos adaptativos e validações.
  - Demonstração dos artefatos de Design Thinking (6 Personas detalhadas, Mapa de Empatia e Storyboard em 14 etapas).

---

## 🚀 Como Executar o Projeto Passo a Passo

### Pré-requisitos de Ambiente
- **PHP:** Versão >= 8.3 com extensão `pdo_sqlite` habilitada (`php -v`, `php -m | grep pdo_sqlite`).
- **Python:** Versão >= 3.8 (utilizado para subir o servidor web local do protótipo e gerar relatórios).
- **Navegador Web Moderno:** Chrome, Firefox, Edge ou Safari.

---

### 1. Executar a Demonstração Completa no Terminal (CLI Runner)

O script interativo em linha de comando executa o autoloader, reseta o banco SQLite, roda as migrações e demonstra em sequência todos os 8 padrões GoF, os 10 CRUDs dos 5 alunos e os cenários de variabilidade LPS:

```bash
php hrtech_backend_patterns/run_demo.php
```

---

### 2. Executar as Suítes de Testes Automatizados

O repositório inclui verificações formais unitárias, integradas e testes de desafio adversarial (com mais de 1.000 asserções de integridade):

```bash
# Execução das suítes de verificação dos Milestones (100% de aprovação)
php hrtech_backend_patterns/tests/m1_verify.php
php hrtech_backend_patterns/tests/m2_verify.php
php hrtech_backend_patterns/tests/m3_verify.php
php hrtech_backend_patterns/tests/m4_verify.php

# Execução das suítes adversariais e de estresse
php hrtech_backend_patterns/tests/m4_adversarial_cruds.php
php hrtech_backend_patterns/tests/m4_adversarial_lps.php
php hrtech_backend_patterns/tests/m1_adversarial_challenge.php
php hrtech_backend_patterns/tests/m1_stress_challenge.php
```

---

### 3. Acessar o Protótipo Web Interativo (Frontend)

Para explorar as 17 telas interativas e testar o switcher de clientes LPS:

```bash
# Inicie o servidor local na raiz do projeto:
python3 -m http.server 8085
```

Abra no navegador:  
👉 **[http://localhost:8085](http://localhost:8085)**

---

## 📄 Licença & Propriedade Acadêmica

Trabalho desenvolvido estritamente para fins acadêmicos e pedagógicos no curso de Engenharia de Software da **PUCPR (2026)**.  
Todos os direitos reservados à equipe de desenvolvimento.
