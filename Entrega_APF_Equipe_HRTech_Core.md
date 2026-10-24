# PONTIFÍCIA UNIVERSIDADE CATÓLICA DO PARANÁ — PUCPR
## ESCOLA POLITÉCNICA — CURSO DE ENGENHARIA DE SOFTWARE
### DISCIPLINA: MEDIÇÃO E ANÁLISE DE SOFTWARE (2026-2)

---

# ATIVIDADE 06: ANÁLISE DE PONTOS DE FUNÇÃO (APF) — ENTREGA EM EQUIPE
### Medição Fiel ao Software Implementado (*As-Built* / Padrão IFPUG CPM 4.3.1)

**Sistema:** HRTech Core — Plataforma Modular de Gestão de RH com Linha de Produção de Software (LPS)  
**Equipe de Engenharia (5 Integrantes):**
1. **Fernando Lopes Duarte** — Módulo: Administração Master, Multi-Tenancy (CRUD 1), Trilha de Auditoria LGPD & Portal FinCorp (CRUD 10)
2. **Valentin** — Módulo: Gestão de Férias Trabalhistas CLT (CRUD 7) & Catálogo de Benefícios Corporativos (CRUD 8)
3. **Andryus Zolet** — Módulo: Cadastro de Colaboradores, Cargos & Organograma Institucional (CRUDs 2 e 3)
4. **Felipe** — Módulo: Ponto Eletrônico (Portaria 671 MTE), Ajustes e Banco de Horas (CRUDs 4 e 5)
5. **Nicolas** — Módulo: Segurança do Trabalho (EPIs NR-06 & ASOs NR-07 / CRUD 9) e Avaliação de Desempenho / OKRs

---

## 1. Visão Geral da Abordagem de Medição (*As-Built*) e Divisão do Projeto

Com base nas boas práticas de auditoria da metodologia IFPUG CPM 4.3.1, a equipe de engenharia do **HRTech Core** optou por realizar a medição **estritamente sobre o software construído, funcional e comprovado em código-fonte e banco de dados relacional (SQLite)**.

Em vez de incluir arquivos de interfaces externas (AIEs) conceituais ou telas não implementadas, a contagem consolidada da equipe reflete **100% das transações e tabelas reais da aplicação**:
* **Arquivos Lógicos Internos (ALIs):** Tabelas mantidas no banco de dados SQLite (`tenants`, `employees`, `time_logs`, `vacation_requests`, `benefits`, `equipment_aso`, `insurance_policies`, `audit_logs`).
* **Entradas Externas (EEs):** Formulários reais de cadastro, solicitação, aprovação e exclusão dos controllers da aplicação.
* **Saídas Externas (SEs):** Relatórios e indicadores que envolvem cálculos tributários/matemáticos ou processamento algorítmico derivado (como folha com descontos progressivos e verificação criptográfica SHA-256).
* **Consultas Externas (CEs):** Telas de consulta e grids de listagem com clara separação entre parâmetros de entrada e campos de saída.

### Tabela 1.1 — Resumo Consolidado do Escopo da Equipe (*As-Built*)

| Integrante | Módulo do Sistema | Funções de Dados (ALIs) | Funções de Transação (EE / SE / CE) | PFB Bruto | Status de Auditoria |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **Fernando Lopes Duarte** | Admin Multi-Tenant, Auditoria & Portal FinCorp | 3 ALIs (21 PF) | 5 EEs + 1 SE + 1 CE (15 PF) | **36 PF** | **100% Auditado com Código & Prints** |
| **Valentin** | Férias CLT & Catálogo de Benefícios | 2 ALIs (14 PF) | 5 EEs + 0 SE + 2 CEs (23 PF) | **37 PF** | **100% Auditado com Código & Prints** |
| **Andryus Zolet** | Cadastro de Colaboradores & Organograma | 2 ALIs (17 PF) | 3 EEs + 1 SE + 2 CEs (19 PF) | **36 PF** | Alinhado com o App Real |
| **Felipe** | Ponto Eletrônico & Banco de Horas | 2 ALIs (14 PF) | 4 EEs + 1 SE + 2 CEs (20 PF) | **34 PF** | Alinhado com o App Real |
| **Nicolas** | Segurança do Trabalho (SST) & OKRs | 2 ALIs (14 PF) | 3 EEs + 1 SE + 2 CEs (17 PF) | **31 PF** | Alinhado com o App Real |
| **TOTAL CONSOLIDADO** | **HRTech Core (5 Membros)** | **11 ALIs (80 PF)** | **30 Transações (94 PF)** | **174 PF** | **100% Implementado no Software** |

---

## 2. Detalhamento do Escopo de Fernando Lopes Duarte (Auditado & Completo)

> **Responsável:** Fernando Lopes Duarte  
> **Módulos:** Administração Master Multi-Tenancy (CRUD 1), Trilha de Auditoria & Compliance LGPD/Bacen e Portal FinCorp de Seguros Corporativos (CRUD 10)  
> **Pontuação Bruta:** **36 PFB** (21 PF de Dados + 15 PF de Transações)

### 2.1 Funções de Dados do Fernando (3 ALIs — 21 PF)

#### ALI 01: `ALI_EMPRESAS_TENANTS` (Empresas Contratantes)
* **Finalidade no Negócio:** Manter o registro das organizações contratantes da plataforma multi-tenant, permitindo segregação lógica de dados e configuração de licenças por segmento de mercado (Tecnologia, Indústria ou Financeiro).
* **Tabela no SQLite:** `tenants` (1 RLR, 9 DERs).
* **Complexidade:** 1 RLR e 9 DERs $\rightarrow$ **Baixa (7 PF)**.
* **Evidência Visual:** [Tela 15: Gestão e Configuração de Empresas](screenshots/tela_15_configuracao_empresa.png).

#### ALI 02: `ALI_AUDITORIA_LOGS` (Ledger Criptográfico de Auditoria)
* **Finalidade no Negócio:** Armazenar cronológica e imutavelmente a trilha de auditoria para fins de compliance regulatório (LGPD e BACEN), registrando ator, ação, estado anterior/posterior e hashes criptográficos SHA-256.
* **Tabela no SQLite:** `audit_logs` (1 RLR, 13 DERs).
* **Complexidade:** 1 RLR e 13 DERs $\rightarrow$ **Baixa (7 PF)**.
* **Evidência Visual:** [Tela 16: Trilha de Auditoria e Logs LGPD](screenshots/tela_16_compliance_auditoria.png).

#### ALI 03: `ALI_APOLICES_FINCORP` (Apólices de Seguros Corporativos)
* **Finalidade no Negócio:** Armazenar contratos de seguros corporativos (Seguro D&O, Vida em Grupo e Riscos Operacionais) comercializados pelo Portal do Corretor FinCorp.
* **Tabela no SQLite:** `insurance_policies` (1 RLR, 14 DERs).
* **Complexidade:** 1 RLR e 14 DERs $\rightarrow$ **Baixa (7 PF)**.
* **Evidência Visual:** [Tela 17: Painel de Apólices Corporativas FinCorp](screenshots/tela_17_portal_corretor.png).

---

### 2.2 Funções de Transação do Fernando (5 EEs, 1 SE e 1 CE — 15 PF)

1. **`EE_CRIAR_TENANT` (Baixa — 3 PF):** Processa o formulário de cadastro de novas empresas no ecossistema multi-tenant via `AdminController::createTenant()`. 1 FTR, 5 DERs. Evidência: Modal da Tela 15.
2. **`EE_EXCLUIR_TENANT` (Baixa — 3 PF):** Executa a exclusão de empresa através do método `AdminController::deleteTenant()`. 1 FTR, 2 DERs. Evidência: Botão Excluir na Tela 15.
3. **`EE_REGISTRAR_EVENTO_AUDIT` (Baixa — 3 PF):** Processa novo evento de auditoria manual e gera hash SHA-256 no ledger via `ComplianceController::recordLog()`. 1 FTR, 4 DERs. Evidência: Modal da Tela 16.
4. **`EE_EMITIR_APOLICE` (Média — 4 PF):** Processa contrato de seguro corporativo no Portal FinCorp via `InsuranceController::issue()`. 2 FTRs (`insurance_policies`, `employees`), 5 DERs. Evidência: Modal da Tela 17.
5. **`EE_EXCLUIR_APOLICE` (Baixa — 3 PF):** Cancela e remove o contrato de seguro via `InsuranceController::delete()`. 1 FTR, 2 DERs. Evidência: Botão Excluir na Tela 17.
6. **`SE_LAUDO_INTEGRIDADE_SHA` (Baixa — 4 PF):** **Saída Externa com Dado Derivado Algorítmico.** O sistema recalcula em tempo de execução todos os hashes SHA-256 encadeados (`AuditLogger::verifyChainIntegrity()`) para emitir o veredito pericial de integridade do ledger ("Cadeia Inviolável: 100% VÁLIDA"). 1 FTR, 4 DERs de saída. Evidência: Banner Superior da Tela 16.
7. **`CE_CONSULTAR_EMPRESAS` (Baixa — 3 PF):** Consulta e exibe em tabela interativa todas as empresas ativas no ecossistema (`AdminController::index()`). 1 FTR, 6 DERs de saída. Evidência: Tabela da Tela 15.

---

## 3. Detalhamento dos Módulos dos Demais Integrantes (*As-Built*)

### 🌴 3.1 VALENTIN — Férias Trabalhistas CLT & Catálogo de Benefícios Corporativos

> **Auditoria de Código Concluída:** Levantamento 100% fiel ao código executável dos controllers `VacationController.php` e `BenefitController.php`.

* **Subtotal Bruto:** **37 PFB** (14 PF Dados + 23 PF Transações)

#### Funções de Dados (2 ALIs — 14 PF):
1. **`ALI_SOLICITACOES_FERIAS` (Baixa — 7 PF):** Mantém as programações de férias na tabela `vacation_requests`. 1 RLR, 13 DERs.
2. **`ALI_CATALOGO_BENEFICIOS` (Baixa — 7 PF):** Mantém o catálogo corporativo de benefícios (VR, VT, Saúde, Descontos) na tabela `benefits`. 1 RLR, 7 DERs.

#### Funções de Transação (5 EEs e 2 CEs — 23 PF):
1. **`EE_SOLICITAR_FERIAS` (Média — 4 PF):** Cadastra nova solicitação de férias no modal da Tela 10 (`action=request`). Atualiza `vacation_requests` e valida com `employees`. 2 FTRs, 5 DERs.
2. **`EE_APROVAR_FERIAS` (Baixa — 3 PF):** Aprovação de férias pelo gestor (`action=approve`), com **débito atômico** dos dias concedidos no saldo do colaborador (`employees.vacation_days_balance`). 2 FTRs, 3 DERs.
3. **`EE_EXCLUIR_FERIAS` (Baixa — 3 PF):** Exclusão de solicitação de férias pelo botão de lixeira na tabela da Tela 10 (`action=delete`). 1 FTR, 3 DERs.
4. **`EE_CADASTRAR_BENEFICIO` (Baixa — 3 PF):** Cadastra novo benefício corporativo no catálogo via modal da Tela 12 (`action=store`). 1 FTR, 6 DERs.
5. **`EE_EXCLUIR_BENEFICIO` (Baixa — 3 PF):** Remove benefício do catálogo da empresa (`action=delete`). 1 FTR, 3 DERs.
6. **`CE_LISTAR_FERIAS_SALDO` (Média — 4 PF):** Listagem das solicitações de férias exibindo o saldo aquisitivo restante do colaborador. 2 FTRs, 6 DERs.
7. **`CE_CATALOGO_BENEFICIOS` (Baixa — 3 PF):** Consulta e tabela dos pacotes de benefícios ativos na empresa com percentuais de coparticipação. 1 FTR, 5 DERs.

#### Relação de Capturas de Tela de Valentin:
* `Print 01`: Modal de agendamento de férias CLT (Tela 10).
* `Print 02`: Lista de solicitações de férias em análise (Tela 10).
* `Print 03`: Confirmação de aprovação com débito do saldo de férias do colaborador (Tela 10).
* `Print 04`: Modal de cadastro de novo benefício corporativo (Tela 12).
* `Print 05`: Tabela do catálogo de benefícios ativos e regras de coparticipação (Tela 12).

---

### 👥 3.2 ANDRYUS ZOLET — Cadastro de Colaboradores & Organograma

* **Subtotal Bruto:** **36 PFB** (17 PF Dados + 19 PF Transações)

#### Funções de Dados (2 ALIs — 17 PF):
1. **`ALI_COLABORADORES` (Média — 10 PF):** Tabela `employees` (dados pessoais, contratuais, salariais e saldos). 1 RLR, 18 DERs.
2. **`ALI_ESTRUTURA_ORG` (Baixa — 7 PF):** Tabelas `departments` e `roles` (departamentos e cargos). 2 RLRs, 10 DERs.

#### Funções de Transação (3 EEs, 1 SE e 2 CEs — 19 PF):
1. **`EE_CADASTRAR_COLABORADOR` (Média — 4 PF):** Admissão de novo funcionário na Tela 05 via `EmployeeController::store()`. 2 FTRs, 8 DERs.
2. **`EE_EXCLUIR_COLABORADOR` (Baixa — 3 PF):** Desligamento/exclusão de empregado via `EmployeeController::delete()`. 1 FTR, 2 DERs.
3. **`EE_VINCULAR_DEPTO_CARGO` (Baixa — 3 PF):** Atribuição de centro de custo e cargo ao funcionário. 1 FTR, 4 DERs.
4. **`SE_INDICADOR_HEADCOUNT` (Baixa — 3 PF):** Cálculo derivado de efetivo total e distribuição percentual por departamento na Tela 03. 2 FTRs, 4 DERs.
5. **`CE_LISTAR_COLABORADORES` (Baixa — 3 PF):** Tabela interativa da Tela 04 com busca por nome e cargo. 1 FTR, 7 DERs.
6. **`CE_ORGANOGRAMA_HIERARQUICO` (Baixa — 3 PF):** Visualização em árvore hierárquica dos cargos na Tela 07. 1 FTR, 5 DERs.

---

### ⏰ 3.3 FELIPE — Ponto Eletrônico & Banco de Horas

* **Subtotal Bruto:** **34 PFB** (14 PF Dados + 20 PF Transações)

#### Funções de Dados (2 ALIs — 14 PF):
1. **`ALI_REGISTROS_PONTO` (Baixa — 7 PF):** Tabela `time_logs` (batidas, geolocalização e hash NSR). 1 RLR, 11 DERs.
2. **`ALI_AJUSTES_HORAS_EXTRAS` (Baixa — 7 PF):** Tabelas `time_adjustment_requests` e `overtime_entries`. 2 RLRs, 11 DERs.

#### Funções de Transação (4 EEs, 1 SE e 2 CEs — 20 PF):
1. **`EE_BATER_PONTO_WEB` (Baixa — 3 PF):** Registro de ponto com geolocalização na Tela 08 via `TimeLogController::punch()`. 1 FTR, 5 DERs.
2. **`EE_SOLICITAR_AJUSTE_PONTO` (Baixa — 3 PF):** Solicitação de inclusão de batida esquecida na Tela 09 via `TimeLogController::requestAdjustment()`. 1 FTR, 5 DERs.
3. **`EE_APROVAR_AJUSTE_GESTOR` (Baixa — 3 PF):** Aprovação de abono pelo gestor na Tela 11 via `TimeLogController::approveAdjustment()`. 2 FTRs, 3 DERs.
4. **`EE_LANCAR_HORAS_EXTRAS` (Média — 4 PF):** Lançamento de horas extras com aplicação de Strategy 50%, 100% ou Banco via `TimeLogController::recordOvertime()`. 2 FTRs, 6 DERs.
5. **`SE_ESPELHO_PONTO_MENSAL` (Baixa — 4 PF):** **Saída Externa com Dado Derivado.** Totalização da jornada, cálculo de saldo devedor/credor de horas no mês. 2 FTRs, 6 DERs de saída.
6. **`CE_HISTORICO_BATIDAS` (Baixa — 3 PF):** Consulta ao espelho de ponto na Tela 08. 1 FTR, 6 DERs.
7. **`CE_EXTRATO_BANCO_HORAS` (Baixa — 3 PF):** Consulta ao extrato de horas acumuladas na Tela 02. 1 FTR, 4 DERs.

---

### 👷 3.4 NICOLAS — Segurança do Trabalho (SST) & Avaliação OKRs

* **Subtotal Bruto:** **31 PFB** (14 PF Dados + 17 PF Transações)

#### Funções de Dados (2 ALIs — 14 PF):
1. **`ALI_EQUIPAMENTOS_ASO` (Baixa — 7 PF):** Tabela `equipment_aso` (fichas de entrega de EPIs NR-06 e exames médicos ASO NR-07). 1 RLR, 15 DERs.
2. **`ALI_METAS_OKRS` (Baixa — 7 PF):** Registros de metas e avaliações de desempenho no módulo de gestão estratégica. 1 RLR, 8 DERs.

#### Funções de Transação (3 EEs, 1 SE e 2 CEs — 17 PF):
1. **`EE_ENTREGA_EPI_NR6` (Baixa — 3 PF):** Registro de custódia de equipamento na Tela 14 via `SafetyController::deliverEquipment()`. 1 FTR, 5 DERs.
2. **`EE_REGISTRAR_EXAME_ASO` (Baixa — 3 PF):** Lançamento de atestado de saúde ocupacional na Tela 14 via `SafetyController::recordExam()`. 1 FTR, 6 DERs.
3. **`EE_EXCLUIR_ITEM_SST` (Baixa — 3 PF):** Exclusão de registro de EPI ou ASO via `SafetyController::delete()`. 1 FTR, 2 DERs.
4. **`SE_PAINEL_ALERTAS_VENCIMENTO` (Baixa — 3 PF):** **Saída Externa com Dado Derivado.** Cruzamento de datas de vencimento de ASOs com emissão de semáforo de risco ocupacional. 1 FTR, 4 DERs.
5. **`CE_CONSULTAR_FICHAS_SST` (Baixa — 3 PF):** Consulta das entregas e prontuários na Tela 14. 1 FTR, 7 DERs.
6. **`CE_PAINEL_METAS_OKRS` (Baixa — 3 PF):** Visualização de metas departamentais e atingimento na Tela 13. 1 FTR, 5 DERs.

---

## 4. Memória de Cálculo Consolidada da Equipe

### 4.1 Nível de Influência Geral do Sistema (NI = 58)

As 14 características técnicas gerais (GSC) foram avaliadas para toda a plataforma **HRTech Core**:
* **Fórmula do Fator de Ajuste:** $\text{FA} = 0,65 + (0,01 \times \text{NI}) = 0,65 + (0,01 \times 58) = \mathbf{1,23}$

---

### 4.2 Tabela Consolidada de Esforço, Prazo e Custo por Membro

Adotando a produtividade calibrada de **9,58 horas por PFA** e o custo de **R$ 50,00 por hora de desenvolvimento**:

| Integrante | PFB Bruto | PFA Ajustado (`PFB × 1,23`) | Esforço em Horas (`PFA × 9,58`) | Prazo Dedicado (`Horas / 160h`) | Prazo Cronograma ($2,5 \times E^{0,32}$) | Custo Estimado (`Horas × R$ 50`) |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Fernando Lopes Duarte** | **36 PF** | **44,28 PFA** | **424,20 h** | 2,65 meses | 2,70 meses (17,25 sem) | R$ 21.210,12 |
| **Valentin** | **37 PF** | **45,51 PFA** | **435,99 h** | 2,72 meses | 2,72 meses (17,40 sem) | R$ 21.799,29 |
| **Andryus Zolet** | **36 PF** | **44,28 PFA** | **424,20 h** | 2,65 meses | 2,70 meses (17,25 sem) | R$ 21.210,12 |
| **Felipe** | **34 PF** | **41,82 PFA** | **400,64 h** | 2,50 meses | 2,65 meses (16,92 sem) | R$ 20.031,78 |
| **Nicolas** | **31 PF** | **38,13 PFA** | **365,29 h** | 2,28 meses | 2,57 meses (16,40 sem) | R$ 18.264,27 |
| **TOTAL CONSOLIDADO** | **174 PF** | **214,02 PFA** | **2.050,32 h** | — | **2,85 meses (equipe paralela)** | **R$ 102.515,58** |

* **Prazo da Equipe Paralela (5 desenvolvedores simultâneos):**  
  $$\text{Prazo da Equipe} = \frac{2.050,32\text{ h}}{5 \times 160\text{ h/mês}} = \frac{2.050,32}{800} = \mathbf{2,56\text{ meses (aprox. 10,2 semanas)}}$$

---

## 5. Conclusão da Entrega em Grupo

A plataforma **HRTech Core** atinge com este relatório a conformidade com a medição **As-Built** do IFPUG:
1. **Autenticidade e Rastreabilidade Total:** 100% das 174 funções mapeadas existem no código-fonte PHP e no banco relacional SQLite, eliminando qualquer dependência de integrações fictícias.
2. **Equilíbrio entre os Membros:** Cada estudante responde por uma média entre **31 e 37 PFB**, assegurando paridade técnica e responsabilidade clara.
3. **Evidência por Prototipação Real:** Todas as telas referenciadas correspondem a rotas do `Router.php` e views da pasta `app/Views/`, prontas para validação perante a banca acadêmica.
