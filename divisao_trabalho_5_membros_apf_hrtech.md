# 🏢 HRTech Core — Divisão de Trabalho e Contribuição Individual da Equipe (APF)
## Trabalho em Grupo: Análise de Pontos de Função (IFPUG CPM 4.3.1 / NESMA)
### Mapeamento Completo de ALIs, AIEs, EEs, SEs (Dados Derivados), CEs (Entrada/Saída), Cálculos, Esforço, Prazo e Custo

> **Instituição:** Pontifícia Universidade Católica do Paraná (PUCPR)  
> **Curso:** Bacharelado em Engenharia de Software  
> **Disciplina:** Medição e Análise de Software (2026-2)  
> **Sistema:** HRTech Core — Plataforma Modular de Gestão de RH com Linha de Produção de Software (LPS)  
> **Data:** Outubro de 2026  
> **Equipe de Engenharia (5 Integrantes):**
> 1. **Andryus Zolet** — Engenheiro de Domínio & Estrutura Organizacional
> 2. **Felipe** — Engenheiro de Compliance & Ponto Eletrônico
> 3. **Valentin** — Engenheiro de Benefícios & Direitos Trabalhistas
> 4. **Nicolas** — Engenheiro de SST & Ambientes Industriais
> 5. **Fernando Lopes Duarte** — Arquiteto Core, Database Lead & FinCorp Specialist

---

## 📌 1. Quadro Resumo de Divisão do Trabalho (5 Integrantes)

Cada integrante ficou responsável por exatamente **10 funções elementares** do sistema:
* **Funções de Dados:** 2 ALIs (Internos) + 2 AIEs (Externos)
* **Funções de Transação:** 2 EEs (Entradas) + 2 SEs (Saídas com Dados Derivados) + 2 CEs (Consultas com Entrada e Saída)

| Integrante | Módulo / Escopo | 2 ALIs | 2 AIEs | 2 EEs | 2 SEs (Dados Derivados) | 2 CEs (Entrada / Saída) | PFB Individual | % Equipe |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :---: | :---: |
| **Andryus Zolet** | Cadastro & Organograma | `ALI_COLABORADORES`<br>`ALI_ESTRUTURA_ORG` | `AIE_RECEITA_CPF`<br>`AIE_GOV_ESOCIAL` | `EE_ADMITIR_COLAB`<br>`EE_CRIAR_DEPTO_CARGO` | `SE_REL_TURNO_CONTRATO`<br>`SE_XML_ESOCIAL_S2200` | `CE_BUSCA_COLABORADOR`<br>`CE_ORGANOGRAMA_GRAFICO` | **53 PF** | **21,0%** |
| **Felipe** | Ponto & Banco de Horas | `ALI_REGISTROS_PONTO`<br>`ALI_BANCO_HORAS` | `AIE_RELOGIO_REP`<br>`AIE_API_NTP_HORA` | `EE_BATER_PONTO_671`<br>`EE_AJUSTE_ABONO_PONTO` | `SE_ESPELHO_PONTO_CALC`<br>`SE_APURACAO_BANCO_HORAS` | `CE_BATIDAS_DO_DIA`<br>`CE_EXTRATO_BANCO_HORAS` | **48 PF** | **19,0%** |
| **Valentin** | Férias & Benefícios | `ALI_SOLICITACOES_FERIAS`<br>`ALI_CARTOES_BENEF` | `AIE_OPERADORA_VR_VA`<br>`AIE_OPERADORA_SAUDE` | `EE_SOLICITAR_FERIAS`<br>`EE_VINCULAR_BENEFICIO` | `SE_RECIBO_FERIAS_1_3`<br>`SE_FATURA_COPARTICIPACAO` | `CE_SALDO_FERIAS_AQUISIT`<br>`CE_CATALOGO_BENEFICIOS` | **50 PF** | **19,8%** |
| **Nicolas** | Segurança do Trabalho (SST) | `ALI_EPIS_EQUIP`<br>`ALI_EXAMES_ASO` | `AIE_MTE_CA`<br>`AIE_CLINICA_MEDICA` | `EE_ENTREGA_EPI_NR6`<br>`EE_REGISTRAR_ASO_NR7` | `SE_BLOQUEIO_CHAO_FABRICA`<br>`SE_CRONOGRAMA_ASO_PER` | `CE_FICHA_EPIS_COLAB`<br>`CE_HISTORICO_ASO_CA` | **48 PF** | **19,0%** |
| **Fernando Lopes Duarte** | Auditoria, Tenants & FinCorp | `ALI_AUDITORIA_LOGS`<br>`ALI_APOLICES_CORRET` | `AIE_SEGURADORA_PORTAL`<br>`AIE_SISTEMA_PIX` | `EE_CRIAR_TENANT_LIC`<br>`EE_EMITIR_APOLICE_VIDA` | `SE_FOLHA_PAGAMENTO_CALC`<br>`SE_LEDGER_AUDITORIA_SHA` | `CE_LOGS_AUDITORIA_LGPD`<br>`CE_APOLICES_ATIVAS` | **53 PF** | **21,0%** |
| **TOTAL GERAL DA EQUIPE** | **Plataforma HRTech Core** | **10 ALIs (76 PF)** | **10 AIEs (56 PF)** | **10 EEs (32 PF)** | **10 SEs (58 PF)** | **10 CEs (30 PF)** | **252 PF** | **100,0%** |

---

## 👤 2. Detalhamento Individual por Integrante

---

### 🟢 2.1 ANDRYUS ZOLET
* **Papel:** Engenheiro de Domínio & Estrutura Organizacional
* **Módulo:** Cadastro de Empregados, Matrículas, Departamentos, Cargos e Organograma
* **Subtotal Funcional:** **53 PFB** (29 PF Dados + 24 PF Transações)

#### A. Funções de Dados (ALIs e AIEs — 29 PF)
1. **`ALI_COLABORADORES` (Complexidade Média — 10 PF):**
   * *Tabela física:* `employees` (SQLite `hrtech_db.sqlite`).
   * *RLRs (4):* DadosPessoais, VinculoContratual, RemuneracaoBancaria, SaldosAcumulados.
   * *DERs (24):* `id`, `tenant_id`, `cpf`, `matricula`, `full_name`, `email`, `phone`, `birth_date`, `admission_date`, `termination_date`, `department_id`, `role_id`, `base_salary_cents`, `employment_type`, `is_active`, `vacation_days_balance`, `bank_hours_balance`, `cost_center`, `manager_id`, `emergency_contact`, `bank_account`, `hire_status`, `created_at`, `updated_at`.
   * *Prototipação:* Tela 04 (`screenshots/tela_04_lista_colaboradores.png`) e Tela 05 (`screenshots/tela_05_cadastro_colaborador.png`).
2. **`ALI_ESTRUTURA_ORG` (Complexidade Baixa — 7 PF):**
   * *Tabelas físicas:* `departments` e `roles` (SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* Departamento, CargoFuncao, AlcadaAprovacao.
   * *DERs (14):* `dept_id`, `dept_code`, `dept_name`, `cost_center`, `manager_id`, `parent_department_id`, `role_id`, `role_name`, `hierarchy_level`, `permissions_json`, `is_active`, `tenant_id`, `created_at`, `updated_at`.
   * *Prototipação:* Tela 07 (`screenshots/tela_07_organograma.png`).
3. **`AIE_RECEITA_CPF` (Complexidade Baixa — 5 PF):**
   * *Origem:* Web Service Receita Federal (validação cadastral de CPF e regularidade fiscal).
   * *RLRs (2):* CadastroReceita, SituacaoCadastral.
   * *DERs (12):* `cpf_cnpj`, `nome_completo`, `situacao`, `data_nascimento`, `digito_verificador`, `protocolo_consulta`, `data_consulta`, `codigo_retorno`, `uf`, `nome_mae`, `titulo_eleitor`, `hash_autenticidade`.
   * *Prototipação:* Tela 05 (validador online no cadastro).
4. **`AIE_GOV_ESOCIAL` (Complexidade Média — 7 PF):**
   * *Origem:* Gateway Federal eSocial (Eventos S-2200 de Admissão e S-2206 de Alteração Contratual).
   * *RLRs (2):* EventoAdmissao, TrabalhadorEsocial.
   * *DERs (22):* `cpf`, `nis_pis`, `matricula_esocial`, `data_admissao`, `cbo`, `remuneracao`, `recibo_entrega`, `protocolo_envio`, `tipo_admissao`, `natureza_atividade`, `grau_instrucao`, `raca_cor`, `estado_civil`, `pais_nasc`, `endereco_esocial`, `deficiencia_flag`, `regime_jornada`, `cargo_esocial`, `alvara_judicial`, `hash_xml_retorno`, `created_at`, `status_processamento`.
   * *Prototipação:* Tela 06 (`screenshots/tela_06_detalhes_colaborador.png`).

#### B. Funções de Transação (EEs, SEs e CEs — 24 PF)
1. **`EE_ADMITIR_COLAB` (Complexidade Média — 4 PF):**
   * *Serviço Backend:* `HrTech\Services\EmployeeService::hireEmployee()`
   * *Mantém:* `ALI_COLABORADORES` | *Lê:* `ALI_ESTRUTURA_ORG`, `AIE_RECEITA_CPF`
   * *Operação:* Inclusão de novo empregado com validação de CPF e alocação de cargo.
   * *Prototipação:* Tela 05 (`screenshots/tela_05_cadastro_colaborador.png`).
2. **`EE_CRIAR_DEPTO_CARGO` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\DepartmentRoleService::createDepartment()`
   * *Mantém:* `ALI_ESTRUTURA_ORG`
   * *Operação:* Inclusão de unidades organizacionais, níveis hierárquicos e centros de custo.
   * *Prototipação:* Tela 07 (`screenshots/tela_07_organograma.png`).
3. **`SE_REL_TURNO_CONTRATO` (Complexidade Baixa — 4 PF):**
   * *Serviço Backend:* `HrTech\Patterns\TemplateMethod\Report\ReportGeneratorTemplate`
   * *Lê:* `ALI_COLABORADORES`, `ALI_ESTRUTURA_ORG`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** O relatório não exibe dados brutos: ele calcula a taxa de rotatividade (*Turnover Rate*) pela fórmula clássica:  
     $$\text{Turnover} = \frac{\frac{\text{Admissões} + \text{Desligamentos}}{2}}{\text{Efetivo Médio do Período}} \times 100$$  
     além de totalizar headcount ativo por regime CLT/PJ/Estágio e calcular a média salarial ponderada por departamento.
   * *Prototipação:* Tela 03 (`screenshots/tela_03_dashboard_rh.png`).
4. **`SE_XML_ESOCIAL_S2200` (Complexidade Alta — 7 PF):**
   * *Serviço Backend:* Integrador eSocial XML Gateway
   * *Lê:* `ALI_COLABORADORES`, `ALI_ESTRUTURA_ORG` | *Atualiza:* `AIE_GOV_ESOCIAL`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** O processo constrói dinamicamente um arquivo estruturado XML segundo o leiaute S-2200 da Receita/MTE, calculando a digestão canônica dos dados com transformação em base64 e assinatura digital XML-DSig padrão ICP-Brasil com hash SHA-256.
   * *Prototipação:* Tela 06 (`screenshots/tela_06_detalhes_colaborador.png`).
5. **`CE_BUSCA_COLABORADOR` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_COLABORADORES`
   * *Entrada (Filtros):* Matrícula, CPF, Nome, Departamento ID ou Status ('ativo'/'inativo').
   * *Saída (Grid):* Nome, CPF mascarado, Cargo, Departamento, Salário e Status.
   * *Prototipação:* Tela 04 (`screenshots/tela_04_lista_colaboradores.png`).
6. **`CE_ORGANOGRAMA_GRAFICO` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_ESTRUTURA_ORG`, `ALI_COLABORADORES`
   * *Entrada (Filtros):* Tenant ID e Departamento Raiz (ou filtro por Gestor).
   * *Saída (Grid/Árvore):* Nós hierárquicos interativos, quantidade de subordinados diretos e headcount.
   * *Prototipação:* Tela 07 (`screenshots/tela_07_organograma.png`).

---

### ⏰ 2.2 FELIPE
* **Papel:** Engenheiro de Compliance & Ponto Eletrônico
* **Módulo:** Controle de Frequência, Portaria 671 MTE, Horas Extras e Banco de Horas
* **Subtotal Funcional:** **48 PFB** (24 PF Dados + 24 PF Transações)

#### A. Funções de Dados (ALIs e AIEs — 24 PF)
1. **`ALI_REGISTROS_PONTO` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `time_logs` (SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* MarcacaoPonto, PosicionamentoGeo, SelagemCriptografica.
   * *DERs (18):* `id`, `tenant_id`, `employee_id`, `timestamp`, `type`, `latitude`, `longitude`, `accuracy`, `nsr`, `previous_hash`, `signature_hash`, `created_at`, `device_id`, `ip_address`, `cpf_snapshot`, `timezone_offset`, `punch_source`, `biometric_flag`.
   * *Prototipação:* Tela 08 (`screenshots/tela_08_espelho_ponto.png`).
2. **`ALI_BANCO_HORAS` (Complexidade Baixa — 7 PF):**
   * *Tabelas físicas:* `employees.bank_hours_balance` e `overtime_entries`.
   * *RLRs (3):* SaldoAcumulado, LancamentoCreditoDebito, AcordoColetivoRegra.
   * *DERs (15):* `id`, `tenant_id`, `employee_id`, `date`, `hours`, `type`, `hourly_rate`, `amount`, `strategy_used`, `description`, `bank_hours_balance_min`, `created_at`, `expiration_date`, `status_fechamento`, `approver_id`.
   * *Prototipação:* Tela 02 (`screenshots/tela_02_dashboard_colaborador.png`).
3. **`AIE_RELOGIO_REP` (Complexidade Baixa — 5 PF):**
   * *Origem:* Relógios de ponto REP homologados (Arquivo Fonte de Dados — AFD).
   * *RLRs (2):* BilheteAFD, CabecalhoREP.
   * *DERs (10):* `numero_rep`, `cnpj_empregador`, `nsr_rep`, `data_hora_gravada`, `pis`, `tipo_marcacao`, `crc16`, `versao_afd`, `ip_rep`, `status_leitura`.
   * *Prototipação:* Tela 08.
4. **`AIE_API_NTP_HORA` (Complexidade Baixa — 5 PF):**
   * *Origem:* Servidores oficiais do Observatório Nacional / NTP.br (Hora Legal Brasileira).
   * *RLRs (1):* RespostaNTP.
   * *DERs (6):* `timestamp_ntp`, `stratum`, `precision_ms`, `root_delay`, `dispersion`, `server_ip`.
   * *Prototipação:* Tela 08 (indicador de hora certa).

#### B. Funções de Transação (EEs, SEs e CEs — 24 PF)
1. **`EE_BATER_PONTO_671` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\TimeLogService::registerPunch()`
   * *Mantém:* `ALI_REGISTROS_PONTO` | *Lê:* `AIE_API_NTP_HORA`
   * *Operação:* Inclusão de batida com carimbo NTP, geolocalização e hash SHA-256 encadeado.
   * *Prototipação:* Tela 08 (`screenshots/tela_08_espelho_ponto.png`).
2. **`EE_AJUSTE_ABONO_PONTO` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\TimeAdjustmentService::approveRequest()`
   * *Mantém:* `ALI_REGISTROS_PONTO`, `ALI_BANCO_HORAS`
   * *Operação:* Retificação de batida esquecida com parecer gerencial e recálculo de saldo.
   * *Prototipação:* Tela 09 (`screenshots/tela_09_ajuste_ponto.png`) e Tela 11 (`screenshots/tela_11_aprovacoes_gestor.png`).
3. **`SE_ESPELHO_PONTO_CALC` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `HrTech\Patterns\TemplateMethod\Importer\TimeLogImporterTemplate`
   * *Lê:* `ALI_REGISTROS_PONTO`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** O espelho mensal computa a apuração da jornada aplicando regras da CLT: converte horário noturno (hora fita de 52m30s entre 22h e 05h), calcula adicional noturno de 20%, apura tolerância legal de 10 minutos diários (Art. 58 § 1º CLT) e sintetiza o saldo de horas extras e atrasos.
   * *Prototipação:* Tela 08 (`screenshots/tela_08_espelho_ponto.png`).
4. **`SE_APURACAO_BANCO_HORAS` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `HrTech\Patterns\Strategy\Overtime\BankHoursStrategy`
   * *Lê:* `ALI_REGISTROS_PONTO`, `ALI_BANCO_HORAS`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Executa a fórmula de acúmulo de banco de horas com multiplicadores dinâmicos da Linha de Produção de Software: horas extras de dias úteis são convertidas a 1,5x (fator 50%) e domingos/feriados a 2,0x (fator 100%), apurando o prazo prescricional de compensação semestral.
   * *Prototipação:* Tela 02 (`screenshots/tela_02_dashboard_colaborador.png`).
5. **`CE_BATIDAS_DO_DIA` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_REGISTROS_PONTO`
   * *Entrada (Filtros):* Colaborador ID e Data de Referência.
   * *Saída (Grid):* Marcações do turno (Entrada 1, Saída 1, Entrada 2, Saída 2), NSR e geolocalização.
   * *Prototipação:* Tela 08 (`screenshots/tela_08_espelho_ponto.png`).
6. **`CE_EXTRATO_BANCO_HORAS` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_BANCO_HORAS`
   * *Entrada (Filtros):* Colaborador ID, Mês e Ano.
   * *Saída (Grid):* Extrato analítico com lançamentos de créditos, débitos, compensações e saldo líquido.
   * *Prototipação:* Tela 02 (`screenshots/tela_02_dashboard_colaborador.png`).

---

### 🌴 2.3 VALENTIN
* **Papel:** Engenheiro de Benefícios & Direitos Trabalhistas
* **Módulo:** Concessão e Agendamento de Férias CLT e Gestão de Benefícios Flexíveis
* **Subtotal Funcional:** **50 PFB** (26 PF Dados + 24 PF Transações)

#### A. Funções de Dados (ALIs e AIEs — 26 PF)
1. **`ALI_SOLICITACOES_FERIAS` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `vacation_requests` (SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* PeriodoAquisitivo, FruicaoGozo, AbonoPecuniario143.
   * *DERs (16):* `id`, `tenant_id`, `employee_id`, `start_date`, `end_date`, `duration_days`, `abono_pecuniario`, `advance_thirteenth_salary`, `abono_days`, `status`, `approver_id`, `approved_at`, `rejection_reason`, `created_at`, `periodo_aquisitivo_inicio`, `periodo_aquisitivo_fim`.
   * *Prototipação:* Tela 10 (`screenshots/tela_10_gestao_ferias.png`).
2. **`ALI_CARTOES_BENEF` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `benefits` (SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* PacoteBeneficio, OperadoraConveniada, CoparticipacaoFolha.
   * *DERs (18):* `id`, `tenant_id`, `type`, `name`, `provider`, `value_cents`, `employee_cost_share_percentage`, `is_deductible`, `card_number_masked`, `activation_date`, `expiration_date`, `category`, `subsidy_amount_cents`, `status`, `created_at`, `updated_at`, `elegibilidade_cargo`, `copay_rules`.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).
3. **`AIE_OPERADORA_VR_VA` (Complexidade Baixa — 5 PF):**
   * *Origem:* Gateway Externo de Operadoras de Vale-Refeição/Alimentação (Ticket, Sodexo, Alelo).
   * *RLRs (2):* PedidoCarga, ExtratoOperadora.
   * *DERs (14):* `cod_cliente`, `contrato`, `cpf_favorecido`, `valor_credito`, `data_carga`, `cod_pedido`, `status_pedido`, `lote_processamento`, `tipo_cartao`, `saldo_residual`, `taxa_emissao`, `hash_autorizacao`, `nsu`, `created_at`.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).
4. **`AIE_OPERADORA_SAUDE` (Complexidade Média — 7 PF):**
   * *Origem:* API Externa da Seguradora de Saúde / Operadora Odontológica (Faixas ANS).
   * *RLRs (3):* FaturaOperadora, MovimentacaoVidas, SinistroCoparticipacao.
   * *DERs (22):* `cnpj_operadora`, `numero_contrato`, `cpf_titular`, `grau_parentesco`, `faixa_etaria_ans`, `mensalidade_centavos`, `coparticipacao_consultas`, `coparticipacao_exames`, `sinistralidade_grupo`, `status_vida`, `data_inclusao`, `data_exclusao`, `plano_tipo`, `rede_credenciada`, `acomodacao`, `carencia_dias`, `valor_reembolso`, `protocolo_ans`, `codigo_tuss`, `data_atendimento`, `guia_autorizacao`, `created_at`.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).

#### B. Funções de Transação (EEs, SEs e CEs — 24 PF)
1. **`EE_SOLICITAR_FERIAS` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\VacationService::requestVacation()`
   * *Mantém:* `ALI_SOLICITACOES_FERIAS` | *Lê:* `ALI_COLABORADORES`
   * *Operação:* Agendamento de férias com opção de venda de 10 dias (abono) e adiantamento do 13º salário.
   * *Prototipação:* Tela 10 (`screenshots/tela_10_gestao_ferias.png`).
2. **`EE_VINCULAR_BENEFICIO` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\BenefitService::createBenefit()`
   * *Mantém:* `ALI_CARTOES_BENEF`
   * *Operação:* Associação de plano de benefícios ao empregado com parametrização de coparticipação.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).
3. **`SE_RECIBO_FERIAS_1_3` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `HrTech\Services\VacationService::approveVacation()`
   * *Lê:* `ALI_SOLICITACOES_FERIAS`, `ALI_COLABORADORES`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Gera o recibo formal de férias calculando a remuneração base acrescida do terço constitucional (Art. 7º, XVII CF/88), a conversão monetária dos dias vendidos no abono pecuniário (Art. 143 CLT), retenção tributária de INSS e IRRF sobre férias e adiantamento de 50% do décimo terceiro.
   * *Prototipação:* Tela 10 (`screenshots/tela_10_gestao_ferias.png`).
4. **`SE_FATURA_COPARTICIPACAO` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `HrTech\Patterns\Strategy\BenefitDiscount\TransportationVoucherStrategy`
   * *Lê:* `ALI_CARTOES_BENEF`, `ALI_COLABORADORES`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** O relatório calcula os tetos legais de desconto: aplica a trava de até 6% do salário-base para vale-transporte (Lei 7.418/85), até 20% do custo da refeição pelo Programa de Alimentação do Trabalhador (PAT) e coparticipação de consultas médicas por faixa etária ANS.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).
5. **`CE_SALDO_FERIAS_AQUISIT` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_SOLICITACOES_FERIAS`, `ALI_COLABORADORES`
   * *Entrada (Filtros):* Colaborador ID e Ano de Exercício.
   * *Saída (Grid):* Início e fim do período aquisitivo, limite do período concessivo e dias de saldo remanescentes.
   * *Prototipação:* Tela 10 (`screenshots/tela_10_gestao_ferias.png`).
6. **`CE_CATALOGO_BENEFICIOS` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_CARTOES_BENEF`
   * *Entrada (Filtros):* Segmento do Tenant ('tech', 'industry', 'financial') e Categoria de Benefício.
   * *Saída (Grid):* Pacotes elegíveis, fornecedora, custo integral e percentual de subsídio patronal.
   * *Prototipação:* Tela 12 (`screenshots/tela_12_gestao_beneficios.png`).

---

### 👷 2.4 NICOLAS
* **Papel:** Engenheiro de Segurança do Trabalho (SST)
* **Módulo:** Controle de EPIs (NR-06), Exames Ocupacionais ASO (NR-07) e Barreira Industrial
* **Subtotal Funcional:** **48 PFB** (24 PF Dados + 24 PF Transações)

#### A. Funções de Dados (ALIs e AIEs — 24 PF)
1. **`ALI_EPIS_EQUIP` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `equipment_aso` (campos de EPI / SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* FichaCustodiaEPI, CertificadoAprovacaoNR6, TermoEntregaDevolucao.
   * *DERs (15):* `id`, `tenant_id`, `employee_id`, `equipment_name`, `ca_number`, `ca_expiration_date`, `delivery_date`, `return_date`, `created_at`, `is_fit`, `condition_status`, `serial_number`, `signature_receipt`, `replacement_reason`, `manufacturer_name`.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
2. **`ALI_EXAMES_ASO` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `equipment_aso` (campos de ASO / SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* ProntuarioOcupacional, ParecerAptidaoNR7, MedicoExaminadorCRM.
   * *DERs (16):* `id`, `tenant_id`, `employee_id`, `exam_type`, `exam_date`, `expiration_date`, `physician_name`, `physician_crm`, `is_fit`, `created_at`, `clinic_cnpj`, `crm_state`, `complementary_exams_json`, `occupational_risks`, `next_exam_schedule`, `medical_notes`.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
3. **`AIE_MTE_CA` (Complexidade Baixa — 5 PF):**
   * *Origem:* Base Pública de Certificados de Aprovação do Ministério do Trabalho e Emprego (CAEPI).
   * *RLRs (1):* BaseCertificadosCA.
   * *DERs (8):* `ca_numero`, `fabricante_cnpj`, `equipamento_tipo`, `validade_ca`, `situacao_cadastral`, `laudo_laboratorial`, `norma_atendida`, `data_consulta_ca`.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
4. **`AIE_CLINICA_MEDICA` (Complexidade Baixa — 5 PF):**
   * *Origem:* Sistema de Medicina Ocupacional da Clínica Parceira (Laudos Digitais PCMSO).
   * *RLRs (2):* AgendamentoClinico, LaudoDigitalASO.
   * *DERs (12):* `cnpj_clinica`, `codigo_agendamento`, `cpf_paciente`, `tipo_exame_aso`, `data_atendimento`, `url_laudo_assinado`, `medico_crm`, `audiometria_resultado`, `espirometria_resultado`, `acuidade_visual`, `raio_x_torax`, `created_at`.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).

#### B. Funções de Transação (EEs, SEs e CEs — 24 PF)
1. **`EE_ENTREGA_EPI_NR6` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\EquipmentASOService::deliverEquipment()`
   * *Mantém:* `ALI_EPIS_EQUIP` | *Lê:* `AIE_MTE_CA`
   * *Operação:* Emissão de termo de guarda de equipamento com validação automática de validade do CA.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
2. **`EE_REGISTRAR_ASO_NR7` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\EquipmentASOService::recordAsoExam()`
   * *Mantém:* `ALI_EXAMES_ASO` | *Lê:* `AIE_CLINICA_MEDICA`
   * *Operação:* Registro de prontuário com parecer médico de aptidão clínica.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
3. **`SE_BLOQUEIO_CHAO_FABRICA` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `HrTech\Lps\LpsVariabilityEngine::validateWorkEligibility()`
   * *Lê:* `ALI_EPIS_EQUIP`, `ALI_EXAMES_ASO`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Executa a lógica impeditiva da Linha de Produção de Software: cruza o vencimento dos exames ASO com a validade dos EPIs sob custódia e emite um veredito booleano impeditivo. Se o ASO estiver vencido ou qualquer EPI obrigatório estiver vencido, gera sinal de bloqueio imediato na catraca de chão de fábrica para prevenir passivos perante o Ministério Público do Trabalho.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
4. **`SE_CRONOGRAMA_ASO_PER` (Complexidade Média — 5 PF):**
   * *Serviço Backend:* `App\Controllers\SafetyController.php`
   * *Lê:* `ALI_EXAMES_ASO`, `ALI_COLABORADORES`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Processa todo o quadro funcional e calcula a projeção matemática de vencimentos em 30, 60 e 90 dias, calculando o risco financeiro de multas da NR-07 por empregado irregular e ordenando convocações clínicas por prioridade de risco.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
5. **`CE_FICHA_EPIS_COLAB` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_EPIS_EQUIP`
   * *Entrada (Filtros):* Colaborador ID ou Matrícula.
   * *Saída (Grid):* Lista de equipamentos entregues, número do CA, data de entrega e validade.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).
6. **`CE_HISTORICO_ASO_CA` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_EXAMES_ASO`
   * *Entrada (Filtros):* Colaborador ID e Período (Ano Inicial/Final).
   * *Saída (Grid):* Prontuário cronológico com tipo de exame, médico examinador, CRM e parecer de aptidão.
   * *Prototipação:* Tela 14 (`screenshots/tela_14_modulo_industrial.png`).

---

### 🛡️ 2.5 FERNANDO LOPES DUARTE
* **Papel:** Arquiteto Core, Database Lead & FinCorp Specialist
* **Módulo:** Auditoria Imutável (Ledger SHA-256), Multi-tenancy (LPS) e Portal FinCorp Seguros
* **Subtotal Funcional:** **53 PFB** (29 PF Dados + 24 PF Transações)

#### A. Funções de Dados (ALIs e AIEs — 29 PF)
1. **`ALI_AUDITORIA_LOGS` (Complexidade Baixa — 7 PF):**
   * *Tabela física:* `audit_logs` (SQLite `hrtech_db.sqlite`).
   * *RLRs (3):* RegistroTransacao, DeltaEstadoLGPD, EncadeamentoBlocos.
   * *DERs (14):* `id`, `tenant_id`, `actor_user_id`, `action`, `entity_type`, `entity_id`, `previous_state`, `new_state`, `ip_address`, `user_agent`, `timestamp`, `previous_hash`, `integrity_hash`, `retention_period`.
   * *Prototipação:* Tela 16 (`screenshots/tela_16_compliance_auditoria.png`).
2. **`ALI_APOLICES_CORRET` (Complexidade Média — 10 PF):**
   * *Tabela física:* `insurance_policies` (SQLite `hrtech_db.sqlite`).
   * *RLRs (4):* ContratoApolice, CorretorParceiro, CoberturaCapitalRisco, VigenciaSinistro.
   * *DERs (26):* `id`, `tenant_id`, `policy_number`, `broker_code`, `insurer_name`, `employee_id`, `insured_capital_cents`, `monthly_premium_cents`, `status`, `start_date`, `end_date`, `coverage_details`, `cancellation_reason`, `cancelled_at`, `commission_percentage`, `insured_name`, `beneficiaries_json`, `iof_cents`, `grace_period_days`, `policy_type`, `deductible_cents`, `claim_contact_phone`, `risk_class`, `endorsement_number`, `created_at`, `updated_at`.
   * *Prototipação:* Tela 17 (`screenshots/tela_17_portal_corretor.png`).
3. **`AIE_SEGURADORA_PORTAL` (Complexidade Média — 7 PF):**
   * *Origem:* Gateway Externo de Underwriting FinCorp Seguros (SUSEP).
   * *RLRs (3):* PropostaComercial, EmissaoApolice, EndossoRisco.
   * *DERs (20):* `numero_proposta`, `cnpj_estipulante`, `tabela_sinistro`, `capital_segurado`, `iof_retido`, `status_emissao`, `premio_puro`, `premio_comercial`, `data_emissao`, `limite_retencao`, `susep_processo`, `classe_ocupacional`, `carregamento_comercial`, `comissao_corretor`, `custo_apolice`, `taxa_risco_morte`, `taxa_risco_invalidez`, `franquia_dias`, `cobertura_conjugal`, `hash_seguradora`.
   * *Prototipação:* Tela 17 (`screenshots/tela_17_portal_corretor.png`).
4. **`AIE_SISTEMA_PIX` (Complexidade Baixa — 5 PF):**
   * *Origem:* Gateway Bancário de Liquidação Instantânea (BACEN / SPI / DICT).
   * *RLRs (2):* PayloadPix, ComprovanteLiquidacao.
   * *DERs (16):* `e2eid`, `txid`, `chave_pix`, `valor_centavos`, `timestamp_liquidacao`, `qr_code_copia_cola`, `status_pagamento`, `ispb_banco`, `nome_recebedor`, `cpf_cnpj_recebedor`, `end_to_end_id`, `mensagem_pix`, `autenticacao_bacen`, `tarifa_liquidacao`, `data_vencimento`, `created_at`.
   * *Prototipação:* Tela 17 (`screenshots/tela_17_portal_corretor.png`).

#### B. Funções de Transação (EEs, SEs e CEs — 24 PF)
1. **`EE_CRIAR_TENANT_LIC` (Complexidade Baixa — 3 PF):**
   * *Serviço Backend:* `HrTech\Services\TenantService::createTenant()`
   * *Mantém:* `tenants` (Multi-tenancy) | *Lê:* `AIE_RECEITA_CPF`
   * *Operação:* Provisionamento de nova empresa cliente com validação de CNPJ e ativação de módulos LPS.
   * *Prototipação:* Tela 15 (`screenshots/tela_15_configuracao_empresa.png`).
2. **`EE_EMITIR_APOLICE_VIDA` (Complexidade Média — 4 PF):**
   * *Serviço Backend:* `HrTech\Services\InsurancePolicyService::issuePolicy()`
   * *Mantém:* `ALI_APOLICES_CORRET` | *Lê:* `AIE_SEGURADORA_PORTAL`
   * *Operação:* Emissão de apólice de seguro de vida em grupo com tarifação e split de comissão.
   * *Prototipação:* Tela 17 (`screenshots/tela_17_portal_corretor.png`).
3. **`SE_FOLHA_PAGAMENTO_CALC` (Complexidade Alta — 7 PF):**
   * *Serviço Backend:* `HrTech\Patterns\TemplateMethod\Payroll\PayrollCalculatorTemplate`
   * *Lê:* `ALI_COLABORADORES`, `ALI_REGISTROS_PONTO`, `ALI_CARTOES_BENEF`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Executa pipeline matemático rigoroso em 5 etapas: calcula remuneração bruta total (salário-base + adicionais noturnos e de horas extras apurados), calcula o desconto do INSS progressivo pelas 4 alíquotas oficiais (7,5%, 9%, 12% e 14% com dedução de parcelas legais), deduz o IRRF conforme a base tributável com dedução de R$ 189,59 por dependente, aplica tetos de benefícios (VT e VR) e emite o demonstrativo de holerite líquido.
   * *Prototipação:* Tela 03 (`screenshots/tela_03_dashboard_rh.png`).
4. **`SE_LEDGER_AUDITORIA_SHA` (Complexidade Baixa — 4 PF):**
   * *Serviço Backend:* `HrTech\Patterns\Singleton\AuditLogger::verifyChainIntegrity()`
   * *Lê:* `ALI_AUDITORIA_LOGS`
   * *Justificativa de Dado Derivado:* **Dado estritamente derivado.** Percorre todos os blocos do ledger recalculando iterativamente o hash canônico SHA-256 de cada registro concatenado ao hash anterior. Gera um laudo pericial atestando se a cadeia permanece 100% íntegra ou se houve adulteração externa não autorizada em conformidade com as diretrizes da LGPD.
   * *Prototipação:* Tela 16 (`screenshots/tela_16_compliance_auditoria.png`).
5. **`CE_LOGS_AUDITORIA_LGPD` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_AUDITORIA_LOGS`
   * *Entrada (Filtros):* Período (Data Início / Fim), Usuário Ator ID e Entidade Afetada ('Employee', 'InsurancePolicy').
   * *Saída (Grid):* Carimbo de data/hora UTC, ator, verbo da ação, snapshot do estado modificado e hash SHA-256.
   * *Prototipação:* Tela 16 (`screenshots/tela_16_compliance_auditoria.png`).
6. **`CE_APOLICES_ATIVAS` (Complexidade Baixa — 3 PF):**
   * *Lê:* `ALI_APOLICES_CORRET`
   * *Entrada (Filtros):* Corretor SUSEP ID e Status da Apólice ('ativa', 'cancelada').
   * *Saída (Grid):* Número do contrato, nome do segurado, capital em risco, prêmio mensal e comissão a receber.
   * *Prototipação:* Tela 17 (`screenshots/tela_17_portal_corretor.png`).

---

## 📐 3. Memória de Cálculo: NI, PFB, FA e PFA

### 3.1 Nível de Influência (NI) — As 14 Características Gerais da Aplicação (GSC)

Avaliação formal de 0 a 5 das 14 características gerais segundo a norma IFPUG CPM 4.3.1:

| # | Característica Geral (GSC) | Nota (0 a 5) | Justificativa Arquitetural HRTech Core |
|---|:---|:---:|:---|
| **C1** | Comunicação de Dados | **4** | Arquitetura RESTful desacoplada entre Frontend SPA e Backend MVC com APIs integradas. |
| **C2** | Processamento Distribuído | **4** | Processamento distribuído entre cliente SPA, backend e serviços governamentais/bancários. |
| **C3** | Performance / Tempo de Resposta | **4** | Exigência de tempo de resposta em sub-segundo para marcações de ponto em turnos industriais. |
| **C4** | Configuração do Equipamento | **4** | Execução concorrente com concorrência otimizada via SQLite WAL (`PRAGMA journal_mode = WAL;`). |
| **C5** | Volume de Transações | **4** | Picos transacionais nos horários de entrada e saída fabril com selagem criptográfica. |
| **C6** | Entrada de Dados Online | **5** | 100% das transações e aprovações operam via interface online responsiva e modais interativos. |
| **C7** | Eficiência do Usuário Final | **5** | Interface ergonômica com dark mode, validações de CPF/CNPJ em tempo real e atalhos rápidos. |
| **C8** | Atualização Online | **5** | Atualização imediata do banco de horas, ledger de auditoria e recálculos no banco relacional. |
| **C9** | Processamento Complexo | **5** | Algoritmos tributários CLT (INSS/IRRF), conversão de hora noturna e bloqueio de catraca NR-06/07. |
| **C10**| Reusabilidade de Código | **5** | Linha de Produção de Software (LPS) com 3 perfis e 8 Design Patterns GoF implementados. |
| **C11**| Facilidade de Instalação | **4** | Instalação rápida baseada em PHP 8.3 e banco SQLite embutido sem dependências pesadas de SGBD. |
| **C12**| Facilidade de Operação | **4** | Automação de rotinas de folha, backups atômicos e reconciliação contínua da cadeia de logs. |
| **C13**| Múltiplos Locais (Multi-tenant) | **5** | Suporte nativo a múltiplos clientes corporativos (*multi-tenancy*) com isolamento estrito de dados. |
| **C14**| Facilidade de Mudança | **4** | Arquitetura modular extensível por Strategy e Feature Flags para novos segmentos de mercado. |
| **SOMA**| **Nível de Influência Total (NI)** | **58** | **$\sum_{i=1}^{14} C_i = 58$ pontos (Grau de influência técnica elevado: 58/70)** |

---

### 3.2 Fator de Ajuste (FA) e Pontos de Função Ajustados (PFA)

$$FA = 0{,}65 + (0{,}01 \times NI) = 0{,}65 + (0{,}01 \times 58) = \mathbf{1{,}2300}$$

O Fator de Ajuste de **1,23 (+23%)** reflete a alta complexidade técnica da plataforma (LPS, multi-tenancy e blockchain de auditoria).

#### Cálculo Consolidado da Equipe:
$$PFA_{\text{equipe}} = PFB_{\text{equipe}} \times FA = 252 \times 1{,}23 = \mathbf{309{,}96 \text{ PFA}} \approx \mathbf{310 \text{ PFA}}$$

#### Cálculo Proporcional dos Integrantes:
* **Andryus Zolet (53 PFB):** $53 \times 1{,}23 = \mathbf{65{,}19 \text{ PFA}}$
* **Felipe (48 PFB):** $48 \times 1{,}23 = \mathbf{59{,}04 \text{ PFA}}$
* **Valentin (50 PFB):** $50 \times 1{,}23 = \mathbf{61{,}50 \text{ PFA}}$
* **Nicolas (48 PFB):** $48 \times 1{,}23 = \mathbf{59{,}04 \text{ PFA}}$
* **Fernando Lopes Duarte (53 PFB):** $53 \times 1{,}23 = \mathbf{65{,}19 \text{ PFA}}$

---

## ⏱️ 4. Estimativas de Esforço, Prazo e Custo

### 4.1 Esforço em Homem-Horas (HH) e Convergência com UCP
Adotando a taxa de produtividade empírica de **9,58 HH/PFA** (calibrada pela validação cruzada com o relatório de Casos de Uso que totalizou 2.970 horas úteis para 148,5 AUCP):

$$\text{Esforço Total} = 309{,}96 \text{ PFA} \times 9{,}5818 \text{ HH/PFA} \approx \mathbf{2.970 \text{ Horas}}$$

* **Distribuição do Esforço por Integrante:**
  * Andryus: $65{,}19 \times 9{,}5818 = \mathbf{624{,}65 \text{ HH}}$
  * Felipe: $59{,}04 \times 9{,}5818 = \mathbf{565{,}71 \text{ HH}}$
  * Valentin: $61{,}50 \times 9{,}5818 = \mathbf{589{,}29 \text{ HH}}$
  * Nicolas: $59{,}04 \times 9{,}5818 = \mathbf{565{,}71 \text{ HH}}$
  * Fernando Lopes: $65{,}19 \times 9{,}5818 = \mathbf{624{,}65 \text{ HH}}$

### 4.2 Cronograma e Prazo de Entrega
Considerando jornada de **160 horas úteis por mês** por desenvolvedor:

$$\text{Prazo (Equipe de 5 Devs)} = \frac{2.970 \text{ HH}}{5 \times 160 \text{ h/mês}} = \frac{2.970}{800} = \mathbf{3{,}71 \text{ Meses}} \approx \mathbf{15 \text{ Semanas}}$$

*Cenários Comparativos:*
* **1 Desenvolvedor solo:** $2.970 / 160 = 18{,}56 \text{ meses}$ (~74 semanas)
* **2 Desenvolvedores:** $2.970 / 320 = 9{,}28 \text{ meses}$ (~37 semanas)
* **Equipe Plena (5 Devs):** **3,71 meses** (~15 semanas) $\rightarrow$ *Time-to-market otimizado!*

### 4.3 Orçamento e Custo Financeiro
Adotando a taxa de mercado de **R$ 50,00 / hora** para desenvolvimento pleno em engenharia de software:

$$\text{Custo Total da Plataforma} = 2.970 \text{ horas} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 148.501{,}00}$$

* **Rateio de Custos por Módulo / Integrante:**
  * **Andryus Zolet (Cadastro & Organograma):** $624{,}65\text{h} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 31.232,35}$
  * **Felipe (Ponto & Banco de Horas):** $565{,}71\text{h} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 28.285,90}$
  * **Valentin (Férias & Benefícios):** $589{,}29\text{h} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 29.464,48}$
  * **Nicolas (Segurança do Trabalho):** $565{,}71\text{h} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 28.285,90}$
  * **Fernando Lopes (Auditoria & FinCorp):** $624{,}65\text{h} \times \text{R\$} 50{,}00 = \mathbf{\text{R\$} 31.232,35}$
  * **TOTAL CONSOLIDADO:** **R$ 148.501,00**

---

## 📋 5. Termo de Responsabilidade e Aprovação da Equipe

Declaramos que a presente divisão de escopo funcional do projeto **HRTech Core** reflete fielmente o código-fonte, o banco de dados e as interfaces implementadas, mantendo a paridade de dedicação técnica e conformidade integral com os editais da Pontifícia Universidade Católica do Paraná (PUCPR).

*Curitiba, Outubro de 2026.*

* **Fernando Lopes Duarte**
* **Andryus Zolet**
* **Felipe**
* **Valentin**
* **Nicolas**
