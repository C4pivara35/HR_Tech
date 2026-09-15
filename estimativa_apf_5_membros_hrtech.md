# Trabalho de Medição e Análise de Software — PUCPR
## Atividade em Grupo: Contagem de Pontos de Função (IFPUG) — ALIs e AIEs

**Disciplina:** Medição e Análise de Software  
**Sistema:** HRTech Core — Plataforma de Gestão de RH  
**Integrantes do Grupo:** Fernando Lopes Duarte + 4 integrantes da equipe (5 alunos)  
**Regra da Atividade:** 2 ALIs + 2 AIEs por integrante = **10 ALIs + 10 AIEs (Total: 20 arquivos)**  

---

Professor, a nossa equipe é formada por 5 integrantes. Conforme a orientação da aula (2 ALIs e 2 AIEs por aluno), organizamos a contagem dos 20 arquivos de dados do nosso sistema HRTech Core divididos entre os membros do grupo. 

Abaixo apresentamos a divisão do trabalho, a contagem detalhada de cada arquivo (RLR, DER, complexidade e PFs) e a tabela consolidada com o total de Pontos de Função da equipe.

---

## 1. Divisão do Trabalho no Grupo (5 Integrantes)

| Integrante | Módulo do Sistema | 2 ALIs (Arquivos Internos) | 2 AIEs (Interfaces Externas) |
| :--- | :--- | :--- | :--- |
| **Membro 1** | Cadastro & Organograma | **ALI 1:** `ALI_COLABORADORES`<br>**ALI 2:** `ALI_ESTRUTURA_ORGANIZACIONAL` | **AIE 1:** `AIE_RECEITA_FEDERAL_CPF`<br>**AIE 2:** `AIE_GOV_ESOCIAL` |
| **Membro 2** | Ponto & Banco de Horas | **ALI 3:** `ALI_REGISTROS_PONTO`<br>**ALI 4:** `ALI_BANCO_HORAS_ACUMULADO` | **AIE 3:** `AIE_RELOGIO_PONTO_REP`<br>**AIE 4:** `AIE_API_NTP_HORA_CERTA` |
| **Membro 3** | Férias & Benefícios | **ALI 5:** `ALI_SOLICITACOES_FERIAS`<br>**ALI 6:** `ALI_CARTOES_BENEFICIOS` | **AIE 5:** `AIE_OPERADORA_VR_VA`<br>**AIE 6:** `AIE_OPERADORA_SAUDE` |
| **Membro 4** | EPIs & Exames ASO | **ALI 7:** `ALI_EPIS_EQUIPAMENTOS`<br>**ALI 8:** `ALI_EXAMES_ASO` | **AIE 7:** `AIE_MINISTERIO_TRABALHO_CA`<br>**AIE 8:** `AIE_CLINICA_MEDICA_OCUPACIONAL` |
| **Membro 5** | Auditoria & Portal Corretor | **ALI 9:** `ALI_AUDITORIA_LOGS`<br>**ALI 10:** `ALI_APOLICES_CORRETOR` | **AIE 9:** `AIE_SEGURADORA_PORTAL`<br>**AIE 10:** `AIE_SISTEMA_BANCARIO_PIX` |

---

## 2. Detalhamento dos Arquivos (ALIs e AIEs por Aluno)

### 👤 Membro 1 — Cadastro & Organograma

* **ALI 1: `ALI_COLABORADORES`** (Complexidade Média $\rightarrow$ **10 PFs**)
  * *Descrição:* Tabela principal dos colaboradores da empresa com dados pessoais e contratuais.
  * *RLRs (4):* Colaborador, Endereço, Contato, DadosBancarios.
  * *DERs (24):* `id`, `matricula`, `nome`, `cpf`, `rg`, `data_nasc`, `salario`, `email`, `telefone`, `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `uf`, `banco`, `agencia`, `conta`, `pix`, etc.

* **ALI 2: `ALI_ESTRUTURA_ORGANIZACIONAL`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Estrutura dos departamentos, cargos e níveis da empresa.
  * *RLRs (3):* Departamento, Cargo, NivelHierarquico.
  * *DERs (14):* `id`, `nome_depto`, `centro_custo`, `gestor_id`, `titulo_cargo`, `nivel`, `cbo`, `descricao`, `faixa_sal_inicio`, `faixa_sal_fim`, etc.

* **AIE 1: `AIE_RECEITA_FEDERAL_CPF`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Consulta externa via API na Receita Federal para validar CPF/CNPJ.
  * *RLRs (2):* CadastroReceita, SituacaoCadastral.
  * *DERs (12):* `cpf_cnpj`, `nome`, `situacao`, `data_nasc`, `data_consulta`, `protocolo`, `digito_verificador`, `uf`, `hash`, etc.

* **AIE 2: `AIE_GOV_ESOCIAL`** (Complexidade Média $\rightarrow$ **7 PFs**)
  * *Descrição:* Integração externa com governo federal para envio do evento eSocial S-2200 (Admissão).
  * *RLRs (2):* EventoAdmissao, TrabalhadorEsocial.
  * *DERs (35):* `cpf`, `nis`, `matricula_esocial`, `data_admissao`, `cbo`, `remuneracao`, `carga_horaria`, `recibo_entrega`, etc.

---

### ⏰ Membro 2 — Ponto & Banco de Horas

* **ALI 3: `ALI_REGISTROS_PONTO`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Marcações de ponto eletrônico com geolocalização e carimbo de hora.
  * *RLRs (3):* RegistroPonto, Geolocalizacao, Dispositivo.
  * *DERs (18):* `id`, `colaborador_id`, `data_hora`, `tipo`, `ip`, `latitude`, `longitude`, `precisao`, `dispositivo`, `hash_sha256`, etc.

* **ALI 4: `ALI_BANCO_HORAS_ACUMULADO`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Saldos acumulados de horas extras e compensações de ponto.
  * *RLRs (3):* SaldoBancoHoras, HorasExtras, ExtratoCompensacao.
  * *DERs (15):* `id`, `colaborador_id`, `mes`, `ano`, `saldo_anterior`, `horas_credito`, `horas_debito`, `saldo_atual`, `status`, etc.

* **AIE 3: `AIE_RELOGIO_PONTO_REP`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Importação de arquivo AFD dos relógios de ponto físicos da empresa.
  * *RLRs (2):* BilheteAFD, CabecalhoAFD.
  * *DERs (10):* `numero_rep`, `cnpj_empresa`, `nsr`, `data_marcacao`, `hora_marcacao`, `pis`, `checksum`, `status`, etc.

* **AIE 4: `AIE_API_NTP_HORA_CERTA`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Servidor externo de hora oficial NTP.br para validar o horário do ponto.
  * *RLRs (1):* RespostaNTP.
  * *DERs (6):* `timestamp_ntp`, `stratum`, `precision_ms`, `delay`, `dispersion`, `server_ip`.

---

### 🌴 Membro 3 — Férias & Benefícios Corporativos

* **ALI 5: `ALI_SOLICITACOES_FERIAS`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Solicitações de férias dos funcionários e cálculo de 1/3 constitucional.
  * *RLRs (3):* PeriodoAquisitivo, SolicitacaoFerias, AbonoPecuniario.
  * *DERs (16):* `id`, `colaborador_id`, `inicio_aquisitivo`, `fim_aquisitivo`, `data_inicio`, `dias_gozo`, `dias_abono`, `adiantamento13`, `status`, etc.

* **ALI 6: `ALI_CARTOES_BENEFICIOS`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Gestão dos cartões de VR/VA, transporte e plano de saúde.
  * *RLRs (3):* CartaoBeneficio, SolicitacaoCarga, DependentePlano.
  * *DERs (18):* `id`, `colaborador_id`, `tipo_beneficio`, `operadora`, `numero_cartao`, `valor_empresa`, `valor_desconto`, `status`, etc.

* **AIE 5: `AIE_OPERADORA_VR_VA`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* API da operadora de benefícios (Ticket/Sodexo/Alelo) para pedir recarga de cartão.
  * *RLRs (2):* PedidoCarga, ExtratoOperadora.
  * *DERs (14):* `cod_cliente`, `contrato`, `cpf_favorecido`, `valor_credito`, `data_carga`, `cod_pedido`, `status`, `protocolo`, etc.

* **AIE 6: `AIE_OPERADORA_SAUDE`** (Complexidade Média $\rightarrow$ **7 PFs**)
  * *Descrição:* Interface externa com a operadora de plano de saúde para inclusão de dependentes e coparticipação.
  * *RLRs (3):* FaturaOperadora, MovimentacaoVidas, SinistroCoparticipacao.
  * *DERs (22):* `cnpj_operadora`, `numero_apolice`, `cpf_titular`, `cpf_dependente`, `tipo_movimentacao`, `valor_mensalidade`, etc.

---

### 👷 Membro 4 — Segurança do Trabalho (EPIs & Exames ASO)

* **ALI 7: `ALI_EPIS_EQUIPAMENTOS`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Ficha de controle de entregas de EPIs aos funcionários.
  * *RLRs (3):* FichaEPI, ItemEquipamento, TermoResponsabilidade.
  * *DERs (15):* `id`, `colaborador_id`, `nome_epi`, `ca_numero`, `data_entrega`, `quantidade`, `data_validade_epi`, `assinatura`, etc.

* **ALI 8: `ALI_EXAMES_ASO`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Prontuário de ASO (Atestado de Saúde Ocupacional) e exames periódicos.
  * *RLRs (3):* ProntuarioASO, ExameRealizado, MedicoExaminador.
  * *DERs (16):* `id`, `colaborador_id`, `tipo_aso`, `data_exame`, `resultado`, `nome_medico`, `crm`, `uf_crm`, `validade_aso`, etc.

* **AIE 7: `AIE_MINISTERIO_TRABALHO_CA`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Consulta ao banco público do MTE para verificar a validade do Certificado de Aprovação (CA) do EPI.
  * *RLRs (1):* ConsultaCA.
  * *DERs (8):* `numero_ca`, `nome_equipamento`, `fabricante`, `data_validade_ca`, `situacao`, `laudo_tecnico`, etc.

* **AIE 8: `AIE_CLINICA_MEDICA_OCUPACIONAL`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Integração via API com a clínica parceira para agendar exames admissionais e receber laudos.
  * *RLRs (2):* AgendamentoExame, LaudoDigital.
  * *DERs (12):* `cnpj_clinica`, `codigo_agendamento`, `cpf_funcionario`, `tipo_exame`, `data_agendada`, `status`, `laudo_pdf_url`, etc.

---

### 🛡️ Membro 5 — Compliance, Auditoria & Portal do Corretor

* **ALI 9: `ALI_AUDITORIA_LOGS`** (Complexidade Baixa $\rightarrow$ **7 PFs**)
  * *Descrição:* Logs de auditoria imutáveis com hash para rastrear quem alterou dados no sistema.
  * *RLRs (3):* LogOperacao, TrilhaLGPD, EventoSeguranca.
  * *DERs (14):* `id`, `tenant_id`, `usuario_id`, `acao`, `entidade`, `dados_anteriores`, `dados_novos`, `ip`, `data_hora`, `hash_sha256`, etc.

* **ALI 10: `ALI_APOLICES_CORRETOR`** (Complexidade Média $\rightarrow$ **10 PFs**)
  * *Descrição:* Gestão das apólices exclusivas de seguro do Portal do Corretor (FinCorp).
  * *RLRs (4):* ApoliceSeguro, CorretorParceiro, CoberturaContratada, SinistroRegistrado.
  * *DERs (26):* `id`, `numero_apolice`, `corretor_id`, `premio_total`, `comissao_porcentagem`, `inicio_vigencia`, `fim_vigencia`, `status`, etc.

* **AIE 9: `AIE_SEGURADORA_PORTAL`** (Complexidade Média $\rightarrow$ **7 PFs**)
  * *Descrição:* Comunicação via API com a seguradora para emitir apólices e validar propostas.
  * *RLRs (3):* PropostaSeguradora, EndossoApolice, ComissaoCorretor.
  * *DERs (20):* `codigo_proposta`, `cnpj_seguradora`, `dados_estipulante`, `valor_premio_net`, `imposto_iof`, `status_emissao`, etc.

* **AIE 10: `AIE_SISTEMA_BANCARIO_PIX`** (Complexidade Baixa $\rightarrow$ **5 PFs**)
  * *Descrição:* Integração com gateway bancário para pagamento automático via PIX.
  * *RLRs (2):* TransacaoPix, ComprovanteBancario.
  * *DERs (16):* `end_to_end_id`, `txid`, `chave_pix`, `valor`, `data_hora`, `status`, `codigo_autenticacao`, `qr_code`, etc.

---

## 3. Resumo da Contagem Final de Pontos de Função

Somando a pontuação de todos os 20 arquivos contados pelos 5 integrantes do grupo, chegamos ao total de **132 Pontos de Função Não Ajustados**:

| Integrante do Grupo | Pontos ALIs (Internos) | Pontos AIEs (Externos) | Total por Aluno |
| :--- | :---: | :---: | :---: |
| **Membro 1 (Cadastro & Organograma)** | 10 PF + 7 PF = **17 PF** | 5 PF + 7 PF = **12 PF** | **29 PF** |
| **Membro 2 (Ponto & Banco de Horas)** | 7 PF + 7 PF = **14 PF** | 5 PF + 5 PF = **10 PF** | **24 PF** |
| **Membro 3 (Férias & Benefícios)** | 7 PF + 7 PF = **14 PF** | 5 PF + 7 PF = **12 PF** | **26 PF** |
| **Membro 4 (EPIs & Exames ASO)** | 7 PF + 7 PF = **14 PF** | 5 PF + 5 PF = **10 PF** | **24 PF** |
| **Membro 5 (Auditoria & Corretor)** | 7 PF + 10 PF = **17 PF** | 7 PF + 5 PF = **12 PF** | **29 PF** |
| **TOTAL DA EQUIPE** | **76 PF (10 ALIs)** | **56 PF (10 AIEs)** | **132 PF** |
