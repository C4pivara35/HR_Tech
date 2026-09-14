# RELATÓRIO DE ESTIMATIVA DE PONTOS DE FUNÇÃO (IFPUG)
## ATIVIDADE EM GRUPO: CONTAGEM DE ALIs E AIEs (EQUIPE DE 5 MEMBROS)

**Disciplina:** Medição e Análise de Software — PUCPR  
**Sistema:** HRTech Core — Plataforma Modular de Gestão de RH com LPS  
**Regra:** 2 ALIs + 2 AIEs por membro da equipe × 5 membros = **10 ALIs + 10 AIEs (Total: 20 Arquivos de Dados)**  
**Data:** 14 de Setembro de 2026  

---

## 1. Resumo da Distribuição da Equipe (5 Membros)

| Membro da Equipe | Módulo / Responsabilidade | 2 Arquivos Lógicos Internos (ALIs) | 2 Arquivos de Interface Externa (AIEs) |
| :--- | :--- | :--- | :--- |
| **Membro 1** | Gestão de Colaboradores & Organograma | **ALI 1:** `ALI_COLABORADORES`<br>**ALI 2:** `ALI_ESTRUTURA_ORGANIZACIONAL` | **AIE 1:** `AIE_RECEITA_FEDERAL_CPF`<br>**AIE 2:** `AIE_GOV_ESOCIAL` |
| **Membro 2** | Controle de Frequência & Banco de Horas | **ALI 3:** `ALI_REGISTROS_PONTO`<br>**ALI 4:** `ALI_BANCO_HORAS_ACUMULADO` | **AIE 3:** `AIE_RELOGIO_PONTO_REP`<br>**AIE 4:** `AIE_API_NTP_HORA_CERTA` |
| **Membro 3** | Gestão de Férias & Benefícios Corporativos | **ALI 5:** `ALI_SOLICITACOES_FERIAS`<br>**ALI 6:** `ALI_CARTOES_BENEFICIOS` | **AIE 5:** `AIE_OPERADORA_VR_VA`<br>**AIE 6:** `AIE_OPERADORA_SAUDE` |
| **Membro 4** | Segurança do Trabalho, EPIs e ASO | **ALI 7:** `ALI_EPIS_EQUIPAMENTOS`<br>**ALI 8:** `ALI_EXAMES_ASO` | **AIE 7:** `AIE_MINISTERIO_TRABALHO_CA`<br>**AIE 8:** `AIE_CLINICA_MEDICA_OCUPACIONAL` |
| **Membro 5** | Compliance, Auditoria & Portal do Corretor | **ALI 9:** `ALI_AUDITORIA_LOGS`<br>**ALI 10:** `ALI_APOLICES_CORRETOR` | **AIE 9:** `AIE_SEGURADORA_PORTAL`<br>**AIE 10:** `AIE_SISTEMA_BANCARIO_PIX` |

---

## 2. Detalhamento Técnico das 10 ALIs e 10 AIEs (RLR, DER e Complexidade)

### 👤 Membro 1: Gestão de Colaboradores & Organograma

#### **ALI 1: `ALI_COLABORADORES`**
* **Escopo:** Interno (ALI) | **Complexidade:** Média (10 PFs)
* **Descrição:** Base cadastral dos funcionários da empresa, contratos trabalhistas e dados bancários.
* **RLRs (Registros Lógicos Referenciados - 4):** Colaborador, Endereço, Contato, DadosBancarios.
* **DERs (Itens de Dados Referenciados - 24):** `id`, `tenant_id`, `matricula`, `nome_completo`, `cpf`, `rg`, `data_nasc`, `data_admissao`, `salario_base`, `email`, `telefone`, `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`, `banco`, `agencia`, `conta`, `tipo_chave_pix`, `chave_pix`, `status`, `data_criacao`.

#### **ALI 2: `ALI_ESTRUTURA_ORGANIZACIONAL`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Estrutura hierárquica da empresa, departamentos, cargos e níveis operacionais.
* **RLRs (3):** Departamento, Cargo, NivelHierarquico.
* **DERs (14):** `id`, `tenant_id`, `nome_departamento`, `centro_custo`, `gestor_id`, `titulo_cargo`, `nivel_hierarquico`, `cbo_codigo`, `descricao_atividades`, `faixa_salarial_inicio`, `faixa_salarial_fim`, `criado_em`, `atualizado_em`, `ativo`.

#### **AIE 1: `AIE_RECEITA_FEDERAL_CPF`**
* **Escopo:** Externo (AIE - Receita Federal) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** Interface externa de consulta via API para validação cadastral de CPF/CNPJ.
* **RLRs (2):** CadastroReceita, SituacaoCadastral.
* **DERs (12):** `cpf_cnpj`, `nome_receita`, `data_nascimento`, `situacao_cadastral`, `data_inscricao`, `codigo_situacao`, `motivo_situacao`, `data_consulta`, `protocolo_consulta`, `digito_verificador`, `uf_fiscal`, `hash_validacao`.

#### **AIE 2: `AIE_GOV_ESOCIAL`**
* **Escopo:** Externo (AIE - Governo Federal) | **Complexidade:** Média (7 PFs)
* **Descrição:** Comunicação com o ambiente nacional do eSocial para evento S-2200 (Admissão).
* **RLRs (2):** EventoAdmissao, TrabalhadorEsocial.
* **DERs (35):** `cpf_trabalhador`, `nis_pis`, `matricula_esocial`, `data_admissao`, `tipo_admissao`, `codigo_categoria`, `grau_instrucao`, `pais_nascimento`, `raca_cor`, `estado_civil`, `remunera_mensal`, `carga_horaria`, `cod_cbo`, `ambiente_envio`, `recibo_entrega`, `status_lote`, etc.

---

### ⏰ Membro 2: Controle de Frequência & Banco de Horas

#### **ALI 3: `ALI_REGISTROS_PONTO`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Marcações de ponto eletrônico registradas conforme Portaria MTP 671/2021.
* **RLRs (3):** RegistroPonto, Geolocalizacao, DispositivoOrigem.
* **DERs (18):** `id`, `tenant_id`, `colaborador_id`, `data_hora_registro`, `tipo_registro`, `ip_origem`, `latitude`, `longitude`, `precisao_m`, `dispositivo_id`, `navegador`, `modelo_aparelho`, `hash_sha256`, `assinado_digitalmente`, `pendente_aprovacao`, `motivo_ajuste`, `fuso_horario`, `criado_em`.

#### **ALI 4: `ALI_BANCO_HORAS_ACUMULADO`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Saldos acumulados de horas extras, horas negativas e compensações.
* **RLRs (3):** SaldoBancoHoras, HorasExtrasApuro, ExtratoCompensacao.
* **DERs (15):** `id`, `tenant_id`, `colaborador_id`, `mes_referencia`, `ano_referencia`, `saldo_anterior_min`, `horas_credito_min`, `horas_debito_min`, `saldo_atual_min`, `limite_acumulo_min`, `data_expiracao`, `status_fechamento`, `valor_pago_moeda`, `aprovador_id`, `atualizado_em`.

#### **AIE 3: `AIE_RELOGIO_PONTO_REP`**
* **Escopo:** Externo (AIE - Fabricante REP) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** Importação de arquivo AFD (Arquivo de Fonte de Dados) de relógios físicos.
* **RLRs (2):** BilheteAFD, CabecalhoAFD.
* **DERs (10):** `numero_fabricacao_rep`, `cnpj_empregador`, `nsr_sequencial`, `tipo_registro_afd`, `data_marcacao`, `hora_marcacao`, `pis_colaborador`, `crc16_checksum`, `data_importacao`, `status_processamento`.

#### **AIE 4: `AIE_API_NTP_HORA_CERTA`**
* **Escopo:** Externo (AIE - Observatório Nacional / NTP.br) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** Servidor de hora oficial brasileira para carimbo de tempo inviolável.
* **RLRs (1):** RespostaNTP.
* **DERs (6):** `timestamp_ntp`, `stratum_server`, `precision_ms`, `root_delay`, `root_dispersion`, `server_ip`.

---

### 🌴 Membro 3: Gestão de Férias & Benefícios Corporativos

#### **ALI 5: `ALI_SOLICITACOES_FERIAS`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Períodos aquisitivos, concessivos e solicitações de férias dos funcionários.
* **RLRs (3):** PeriodoAquisitivo, SolicitacaoFerias, AbonoPecuniario.
* **DERs (16):** `id`, `tenant_id`, `colaborador_id`, `inicio_periodo_aquisitivo`, `fim_periodo_aquisitivo`, `data_inicio_ferias`, `dias_gozo`, `dias_abono_pecuniario`, `adiantamento_13`, `valor_bruto_ferias`, `valor_13_terco`, `status_aprovacao`, `gestor_aprovador_id`, `data_solicitacao`, `data_resposta`, `observacoes`.

#### **ALI 6: `ALI_CARTOES_BENEFICIOS`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Gestão de cartões VR/VA, vale-transporte e planos de saúde corporativos.
* **RLRs (3):** CartaoBeneficio, SolicitacaoCarga, DependentePlano.
* **DERs (18):** `id`, `tenant_id`, `colaborador_id`, `tipo_beneficio`, `operadora_nome`, `numero_cartao_mascarado`, `valor_subsidio_empresa`, `valor_desconto_colaborador`, `dia_vencimento_carga`, `status_cartao`, `data_emissao`, `data_cancelamento`, `plano_nome`, `acomodacao_tipo`, `coparticipacao_flag`, `dependente_id`, `criado_em`, `atualizado_em`.

#### **AIE 5: `AIE_OPERADORA_VR_VA`**
* **Escopo:** Externo (AIE - Sodexo/Ticket/Alelo) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** API da gestora de cartões de benefício para execução de cargas de saldo.
* **RLRs (2):** PedidoCarga, ExtratoOperadora.
* **DERs (14):** `codigo_cliente_empresa`, `numero_contrato`, `cpf_favorecido`, `valor_credito`, `data_agendamento_carga`, `codigo_pedido_operadora`, `status_pedido`, `mensagem_retorno`, `taxa_servico`, `valor_total_fatura`, `comprovante_aut`, `protocolo_lote`, `data_confirmacao`, `canal_envio`.

#### **AIE 6: `AIE_OPERADORA_SAUDE`**
* **Escopo:** Externo (AIE - Unimed/Bradesco Saúde) | **Complexidade:** Média (7 PFs)
* **Descrição:** Interface de integração para inclusão e movimentação de vidas no plano médico.
* **RLRs (2):** ApoliceGrupo, BeneficiarioPlano.
* **DERs (20):** `numero_estipulante`, `codigo_subfatura`, `cpf_titular`, `nome_titular`, `data_admissao_plano`, `codigo_plano`, `valor_mensalidade_titular`, `valor_mensalidade_dependente`, `grau_parentesco_dep`, `cpf_dependente`, `data_nasc_dependente`, `status_inclusao`, `data_vigencia`, `codigo_carteirinha`, etc.

---

### 🛡️ Membro 4: Segurança do Trabalho, EPIs e ASO (Indústria)

#### **ALI 7: `ALI_EPIS_EQUIPAMENTOS`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Controle de estoque, entrega e substituição de Equipamentos de Proteção (EPI).
* **RLRs (3):** ItemEPI, FichaEntregaEPI, CertificadoAprovacao.
* **DERs (17):** `id`, `tenant_id`, `colaborador_id`, `nome_equipamento`, `categoria_epi`, `numero_ca_mte`, `data_fabricacao`, `data_validade_ca`, `quantidade_entregue`, `data_entrega`, `data_devolucao`, `motivo_substituicao`, `assinatura_eletronica_url`, `responsavel_entrega_id`, `status_termo`, `observacoes`, `criado_em`.

#### **ALI 8: `ALI_EXAMES_ASO`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Histórico de Atestados de Saúde Ocupacional (Admissional, Periódico, Demissional).
* **RLRs (3):** ExameASO, MedicoExaminador, RiscosOcupacionais.
* **DERs (19):** `id`, `tenant_id`, `colaborador_id`, `tipo_aso`, `data_realizacao_exame`, `data_validade_aso`, `resultado_aptidao`, `crm_medico`, `uf_crm_medico`, `nome_medico_coordenador`, `cnpj_clinica_examinadora`, `risco_fisico`, `risco_quimico`, `risco_biologico`, `risco_ergonomico`, `parecer_conclusivo`, `anexo_laudo_url`, `aprovado_por_id`, `criado_em`.

#### **AIE 7: `AIE_MINISTERIO_TRABALHO_CA`**
* **Escopo:** Externo (AIE - Ministério do Trabalho) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** Base do MTE consultada para validar a autenticidade do C.A. do EPI.
* **RLRs (1):** ConsultaCA.
* **DERs (8):** `numero_ca`, `nome_fabricante`, `cnpj_fabricante`, `descricao_equipamento`, `data_validade_ca`, `situacao_ca`, `laudo_tecnico_num`, `laboratorio_ensaio`.

#### **AIE 8: `AIE_CLINICA_MEDICA_OCUPACIONAL`**
* **Escopo:** Externo (AIE - Clínica Ocupacional) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** API externa para agendamento de exames e recepção de laudos médicos ASO.
* **RLRs (2):** LaudoExame, MedicoEmitente.
* **DERs (15):** `codigo_agendamento`, `cpf_paciente`, `data_hora_exame`, `codigo_procedimento_tuss`, `resultado_exame_status`, `laudo_pdf_base64`, `med_assinatura_digital`, `crm_emitente`, `laboratorio_parceiro`, `observacoes_medicas`, `data_emissao_laudo`, `protocolo_transmissao`, `hash_arquivo`, `status_sincronizacao`, `criado_em`.

---

### 🔒 Membro 5: Compliance, Auditoria & Portal do Corretor FinCorp

#### **ALI 9: `ALI_AUDITORIA_LOGS`**
* **Escopo:** Interno (ALI) | **Complexidade:** Baixa (7 PFs)
* **Descrição:** Trilha de auditoria imutável com logs criptografados SHA-256 e conformidade LGPD.
* **RLRs (3):** EventoAuditoria, HashIntegridade, LogAcesso.
* **DERs (14):** `id`, `tenant_id`, `usuario_id`, `modulo_afetado`, `acao_executada`, `tabela_alvo`, `registro_id_alvo`, `dados_anteriores_json`, `dados_novos_json`, `ip_origem`, `user_agent`, `hash_sha256_corrente`, `hash_sha256_anterior`, `timestamp_evento`.

#### **ALI 10: `ALI_APOLICES_CORRETOR`**
* **Escopo:** Interno (ALI) | **Complexidade:** Média (10 PFs)
* **Descrição:** Gestão de apólices de seguros corporativos FinCorp comercializadas via portal.
* **RLRs (3):** ApoliceSeguro, CoberturaContratada, SinistroRegistrado.
* **DERs (22):** `id`, `tenant_id`, `colaborador_id`, `corretor_usuario_id`, `numero_apolice`, `premio_mensal_bruto`, `valor_cobertura_morte`, `valor_cobertura_invalidez`, `valor_cobertura_hospitalar`, `data_inicio_vigencia`, `data_fim_vigencia`, `status_apolice`, `beneficiarios_json`, `numero_sinistro_aberto`, `data_sinistro`, `valor_indenizacao`, `status_sinistro`, `arquivo_apolice_url`, `criado_em`, `atualizado_em`, `parcelamento_tipo`, `codigo_seguradora`.

#### **AIE 9: `AIE_SEGURADORA_PORTAL`**
* **Escopo:** Externo (AIE - Seguradora Parceira) | **Complexidade:** Média (7 PFs)
* **Descrição:** API REST da seguradora para cotação em tempo real e emissão de bilhetes.
* **RLRs (2):** CotacaoApolice, EmissaoBilhete.
* **DERs (25):** `codigo_corretor_susep`, `proposta_numero`, `cpf_segurado`, `idade_segurado`, `valor_cobertura_solicitada`, `premio_calculado`, `classe_risco`, `status_proposta`, `bilhete_numero`, `pdf_bilhete_url`, `retorno_codigo`, `mensagem_erro`, `vigencia_inicio_emitida`, `vigencia_fim_emitida`, `token_autorizacao`, etc.

#### **AIE 10: `AIE_SISTEMA_BANCARIO_PIX`**
* **Escopo:** Externo (AIE - Gateway Banco Central) | **Complexidade:** Baixa (5 PFs)
* **Descrição:** Gateway financeiro para pagamento de reembolsos e indenizações via PIX.
* **RLRs (2):** TransacaoPix, ComprovanteBancario.
* **DERs (16):** `end_to_end_id`, `txid_pix`, `chave_pix_destino`, `tipo_chave`, `cpf_cnpj_recebedor`, `nome_recebedor`, `valor_transferencia`, `data_hora_pagamento`, `status_transacao`, `codigo_autenticacao_bancaria`, `qr_code_copia_cola`, `url_pix`, `retorno_banco_codigo`, `mensagem_motivo`, `tenant_id`, `data_conciliacao`.

---

## 3. Consolidação Geral dos Pontos de Função (Contagem Não Ajustada)

```
+-----------------------------------------------------------------------------------------+
|                  RESUMO DE PONTOS DE FUNÇÃO (10 ALIs + 10 AIEs - 5 MEMBROS)             |
+-----------------------------------------------------------------------------------------+
|  Membro da Equipe     | ALIs Ponderadas (PF)  | AIEs Ponderadas (PF)  | Subtotal Membro |
+-----------------------+-----------------------+-----------------------+-----------------+
|  Membro 1 (Cadastro)  | 10 PF + 7 PF = 17 PF  | 5 PF + 7 PF = 12 PF   | 29 PFs          |
|  Membro 2 (Ponto)     | 7 PF + 7 PF  = 14 PF  | 5 PF + 5 PF = 10 PF   | 24 PFs          |
|  Membro 3 (Férias)    | 7 PF + 7 PF  = 14 PF  | 5 PF + 7 PF = 12 PF   | 26 PFs          |
|  Membro 4 (EPIs/ASO)  | 7 PF + 7 PF  = 14 PF  | 5 PF + 5 PF = 10 PF   | 24 PFs          |
|  Membro 5 (Compliance)| 7 PF + 10 PF = 17 PF  | 7 PF + 5 PF = 12 PF   | 29 PFs          |
+-----------------------+-----------------------+-----------------------+-----------------+
|  TOTAL DA EQUIPE      | 76 PFs (10 ALIs)      | 56 PFs (10 AIEs)      | 132 PFs GERAIS  |
+-----------------------------------------------------------------------------------------+
```
