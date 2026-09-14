import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(
        f'<w:tcMar {nsdecls("w")}>'
        f'<w:top w:w="{top}" w:type="dxa"/>'
        f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
        f'<w:left w:w="{left}" w:type="dxa"/>'
        f'<w:right w:w="{right}" w:type="dxa"/>'
        f'</w:tcMar>'
    )
    tcPr.append(tcMar)

def set_table_borders(table, color="CCCCCC", sz="4", val="single"):
    tblPr = table._tbl.tblPr
    borders = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>'
        f'<w:top w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:insideH w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)

def format_cell(cell, text, bold=False, italic=False, color=RGBColor(51, 51, 51), font_size=10, align=WD_ALIGN_PARAGRAPH.LEFT, bg_color=None):
    if bg_color:
        set_cell_background(cell, bg_color)
    set_cell_margins(cell)
    cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
    p = cell.paragraphs[0]
    p.alignment = align
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run(str(text))
    run.font.name = 'Arial'
    run.font.size = Pt(font_size)
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = color

def add_heading_1(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(18)
    p.paragraph_format.space_after = Pt(8)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(15)
    run.font.bold = True
    run.font.color.rgb = RGBColor(15, 23, 42)

def add_heading_2(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(12)
    run.font.bold = True
    run.font.color.rgb = RGBColor(30, 58, 138)

def add_body_p(doc, text, bold_prefix="", italic=False):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = 'Arial'
        r_pre.font.size = Pt(10.5)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(30, 41, 59)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(10.5)
    run.font.italic = italic
    run.font.color.rgb = RGBColor(51, 51, 51)
    return p

def main():
    doc = Document()
    
    for section in doc.sections:
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)

    # Title Block
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_before = Pt(12)
    p_title.paragraph_format.space_after = Pt(4)
    r = p_title.add_run("PUCPR — ESCOLA POLITÉCNICA\nDISCIPLINA: MEDIÇÃO E ANÁLISE DE SOFTWARE")
    r.font.name = 'Arial'
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = RGBColor(71, 85, 105)

    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sub.paragraph_format.space_before = Pt(18)
    p_sub.paragraph_format.space_after = Pt(8)
    r2 = p_sub.add_run("ATIVIDADE EM GRUPO — ESTIMATIVA DE PONTOS DE FUNÇÃO (IFPUG)\nCONTAGEM DE ALIs E AIEs PARA EQUIPE DE 5 MEMBROS")
    r2.font.name = 'Arial'
    r2.font.size = Pt(16)
    r2.font.bold = True
    r2.font.color.rgb = RGBColor(15, 23, 42)

    p_proj = doc.add_paragraph()
    p_proj.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_proj.paragraph_format.space_before = Pt(4)
    p_proj.paragraph_format.space_after = Pt(24)
    r3 = p_proj.add_run("Sistema: HRTech Core — Plataforma Modular de Gestão de RH com LPS\nRegra: 2 ALIs + 2 AIEs por Membro × 5 Membros = 10 ALIs + 10 AIEs (Total: 20 Arquivos de Dados)")
    r3.font.name = 'Arial'
    r3.font.size = Pt(11)
    r3.font.italic = True
    r3.font.color.rgb = RGBColor(30, 58, 138)

    # Overview
    add_heading_1(doc, "1. Resumo da Distribuição do Projeto (5 Membros)")
    add_body_p(doc, "Conforme a diretriz da atividade acadêmica de medição por Análise de Pontos de Função (APF - IFPUG), cada um dos 5 membros da equipe é responsável por indicar e especificar 2 Arquivos Lógicos Internos (ALIs) e 2 Arquivos de Interface Externa (AIEs), identificando expressamente os Registros Lógicos Referenciados (RLR) e os Itens de Dados Referenciados (DER).")

    # Table 5 Members Overview
    m_data = [
        ("Membro 1", "Gestão de Colaboradores & Organograma", "ALI 1: Colaboradores\nALI 2: Estrutura Organizacional", "AIE 1: Validação Receita Federal (CPF)\nAIE 2: eSocial Governo (Admissão)"),
        ("Membro 2", "Controle de Frequência & Banco de Horas", "ALI 3: Registros de Ponto (Portaria 671)\nALI 4: Saldo de Banco de Horas", "AIE 3: Relógio Ponto Físico (REP)\nAIE 4: Hora Certa NTP Oficial"),
        ("Membro 3", "Gestão de Férias & Benefícios Corporativos", "ALI 5: Solicitações de Férias\nALI 6: Cartões de Benefícios", "AIE 5: Operadora VR/VA (Ticket/Alelo)\nAIE 6: Operadora de Plano de Saúde"),
        ("Membro 4", "Segurança do Trabalho, EPIs e ASO (Indústria)", "ALI 7: Estoque & Entrega de EPIs\nALI 8: Atestados de Saúde (ASO)", "AIE 7: Ministério do Trabalho (Certificado CA)\nAIE 8: Clínica Médica Ocupacional Externa"),
        ("Membro 5", "Compliance, Auditoria & Portal do Corretor", "ALI 9: Trilhas de Auditoria (LGPD)\nALI 10: Apólices Corretor FinCorp", "AIE 9: API Seguradora Parceira\nAIE 10: Gateway de Pagamentos PIX")
    ]

    t_m = doc.add_table(rows=len(m_data) + 1, cols=4)
    t_m.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_m)

    headers = ["Membro da Equipe", "Módulo / Responsabilidade", "2 Arquivos Lógicos Internos (ALIs)", "2 Arquivos de Interface Externa (AIEs)"]
    widths = [Inches(1.2), Inches(2.1), Inches(2.3), Inches(2.4)]

    for j, h in enumerate(headers):
        cell = t_m.cell(0, j)
        cell.width = widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A")

    for i, row in enumerate(m_data):
        bg = "F8FAFC" if i % 2 == 1 else "FFFFFF"
        for j, val in enumerate(row):
            cell = t_m.cell(i + 1, j)
            cell.width = widths[j]
            format_cell(cell, val, bold=(j==0), bg_color=bg)

    doc.add_page_break()

    # Detailed specifications for 10 ALIs and 10 AIEs
    add_heading_1(doc, "2. Detalhamento Completo das ALIs e AIEs (RLR, DER e Complexidade IFPUG)")

    items_detail = [
        # Membro 1
        ("MEMBRO 1 — Gestão de Colaboradores & Organograma", [
            ("ALI 1", "ALI_COLABORADORES", "Interno", 
             "Contém a base cadastral dos funcionários da empresa, contratos trabalhistas e dados pessoais.",
             "4 RLRs (Colaborador, Endereço, Contato, DadosBancarios)",
             "24 DERs (id, tenant_id, matricula, nome_completo, cpf, rg, data_nasc, data_admissao, salario_base, email, telefone, cep, logradouro, numero, bairro, cidade, uf, banco, agencia, conta, tipo_chave_pix, chave_pix, status, data_criacao)",
             "Média", "10 PFs"),
            
            ("ALI 2", "ALI_ESTRUTURA_ORGANIZACIONAL", "Interno", 
             "Armazena a estrutura hierárquica da empresa, departamentos, cargos e níveis de reporte.",
             "3 RLRs (Departamento, Cargo, NivelHierarquico)",
             "14 DERs (id, tenant_id, nome_departamento, centro_custo, gestor_id, titulo_cargo, nivel_hierarquico, cbo_codigo, descricao_atividades, faixa_salarial_inicio, faixa_salarial_fim, criado_em, atualizado_em, ativo)",
             "Baixa", "7 PFs"),

            ("AIE 1", "AIE_RECEITA_FEDERAL_CPF", "Externo (Receita Federal)", 
             "Base externa consultada via API da Receita Federal para validação cadastral de CPF/CNPJ.",
             "2 RLRs (CadastroReceita, SituacaoCadastral)",
             "12 DERs (cpf_cnpj, nome_receita, data_nascimento, situacao_cadastral, data_inscricao, codigo_situacao, motivo_situacao, data_consulta, protocolo_consulta, digito_verificador, uf_fiscal, hash_validacao)",
             "Baixa", "5 PFs"),

            ("AIE 2", "AIE_GOV_ESOCIAL", "Externo (Governo Federal)", 
             "Interface com o ambiente nacional do eSocial para transmissão dos eventos S-2200 (Admissão).",
             "2 RLRs (EventoAdmissao, TrabalhadorEsocial)",
             "35 DERs (cpf_trabalhador, nis_pis, matricula_esocial, data_admissao, tipo_admissao, codigo_categoria, grau_instrucao, pais_nascimento, raca_cor, estado_civil, remunera_mensal, carga_horaria, cod_cbo, ambiente_envio, recibo_entrega, status_lote, etc.)",
             "Média", "7 PFs")
        ]),

        # Membro 2
        ("MEMBRO 2 — Controle de Frequência & Banco de Horas", [
            ("ALI 3", "ALI_REGISTROS_PONTO", "Interno", 
             "Armazena as marcações de ponto eletrônico registradas conforme a Portaria MTP 671/2021.",
             "3 RLRs (RegistroPonto, Geolocalizacao, DispositivoOrigem)",
             "18 DERs (id, tenant_id, colaborador_id, data_hora_registro, tipo_registro, ip_origem, latitude, longitude, precisao_m, dispositivo_id, navegador, modelo_aparelho, hash_sha256, assinado_digitalmente, pendente_aprovacao, motivo_ajuste, fuso_horario, criado_em)",
             "Baixa", "7 PFs"),

            ("ALI 4", "ALI_BANCO_HORAS_ACUMULADO", "Interno", 
             "Registra os saldos acumulados de horas extras, horas negativas e extrato de compensação.",
             "3 RLRs (SaldoBancoHoras, HorasExtrasApuro, ExtratoCompensacao)",
             "15 DERs (id, tenant_id, colaborador_id, mes_referencia, ano_referencia, saldo_anterior_min, horas_credito_min, horas_debito_min, saldo_atual_min, limite_acumulo_min, data_expiracao, status_fechamento, valor_pago_moeda, aprovador_id, atualizado_em)",
             "Baixa", "7 PFs"),

            ("AIE 3", "AIE_RELOGIO_PONTO_REP", "Externo (Fabricante REP)", 
             "Interface de importação de arquivo AFD (Arquivo de Fonte de Dados) de relógios de ponto físicos.",
             "2 RLRs (BilheteAFD, CabecalhoAFD)",
             "10 DERs (numero_fabricacao_rep, cnpj_empregador, nsr_sequencial, tipo_registro_afd, data_marcacao, hora_marcacao, pis_colaborador, crc16_checksum, data_importacao, status_processamento)",
             "Baixa", "5 PFs"),

            ("AIE 4", "AIE_API_NTP_HORA_CERTA", "Externo (Observatório Nacional / NTP.br)", 
             "Servidor NTP de estrato 1 de hora legal brasileira para carimbo de tempo inviolável.",
             "1 RLR (RespostaNTP)",
             "6 DERs (timestamp_ntp, stratum_server, precision_ms, root_delay, root_dispersion, server_ip)",
             "Baixa", "5 PFs")
        ]),

        # Membro 3
        ("MEMBRO 3 — Gestão de Férias & Benefícios Corporativos", [
            ("ALI 5", "ALI_SOLICITACOES_FERIAS", "Interno", 
             "Gerencia períodos aquisitivos, prazos concessivos e solicitações de férias dos colaboradores.",
             "3 RLRs (PeriodoAquisitivo, SolicitacaoFerias, AbonoPecuniario)",
             "16 DERs (id, tenant_id, colaborador_id, inicio_periodo_aquisitivo, fim_periodo_aquisitivo, data_inicio_ferias, dias_gozo, dias_abono_pecuniario, adiantamento_13, valor_bruto_ferias, valor_13_terco, status_aprovacao, gestor_aprovador_id, data_solicitacao, data_resposta, observacoes)",
             "Baixa", "7 PFs"),

            ("ALI 6", "ALI_CARTOES_BENEFICIOS", "Interno", 
             "Armazena os cartões de benefícios corporativos (VR, VA, VT, Saúde) e regras de subsídio.",
             "3 RLRs (CartaoBeneficio, SolicitacaoCarga, DependentePlano)",
             "18 DERs (id, tenant_id, colaborador_id, tipo_beneficio, operadora_nome, numero_cartao_mascarado, valor_subsidio_empresa, valor_desconto_colaborador, dia_vencimento_carga, status_cartao, data_emissao, data_cancelamento, plano_nome, acomodacao_tipo, coparticipacao_flag, dependente_id, criado_em, atualizado_em)",
             "Baixa", "7 PFs"),

            ("AIE 5", "AIE_OPERADORA_VR_VA", "Externo (Operadora Sodexo/Ticket/Alelo)", 
             "API da gestora de cartões de refeição e alimentação para execução de cargas de saldo.",
             "2 RLRs (PedidoCarga, ExtratoOperadora)",
             "14 DERs (codigo_cliente_empresa, numero_contrato, cpf_favorecido, valor_credito, data_agendamento_carga, codigo_pedido_operadora, status_pedido, mensagem_retorno, taxa_servico, valor_total_fatura, comprovante_aut, protocolo_lote, data_confirmacao, canal_envio)",
             "Baixa", "5 PFs"),

            ("AIE 6", "AIE_OPERADORA_SAUDE", "Externo (Seguradora Saúde Unimed/Bradesco)", 
             "Interface de integração com a operadora de saúde para inclusão e movimentação de vidas.",
             "2 RLRs (ApoliceGrupo, BeneficiarioPlano)",
             "20 DERs (numero_estipulante, codigo_subfatura, cpf_titular, nome_titular, data_admissao_plano, codigo_plano, valor_mensalidade_titular, valor_mensalidade_dependente, grau_parentesco_dep, cpf_dependente, data_nasc_dependente, status_inclusao, data_vigencia, codigo_carteirinha, etc.)",
             "Média", "7 PFs")
        ]),

        # Membro 4
        ("MEMBRO 4 — Segurança do Trabalho, EPIs e ASO (Indústria)", [
            ("ALI 7", "ALI_EPIS_EQUIPAMENTOS", "Interno", 
             "Gerencia o estoque de Equipamentos de Proteção Individual (EPI), entrega e controle de validade.",
             "3 RLRs (ItemEPI, FichaEntregaEPI, CertificadoAprovacao)",
             "17 DERs (id, tenant_id, colaborador_id, nome_equipamento, categoria_epi, numero_ca_mte, data_fabricacao, data_validade_ca, quantidade_entregue, data_entrega, data_devolucao, motivo_substituicao, assinatura_eletronica_url, responsavel_entrega_id, status_termo, observacoes, criado_em)",
             "Baixa", "7 PFs"),

            ("ALI 8", "ALI_EXAMES_ASO", "Interno", 
             "Armazena o histórico de Atestados de Saúde Ocupacional (Admissional, Periódico, Demissional).",
             "3 RLRs (ExameASO, MedicoExaminador, RiscosOcupacionais)",
             "19 DERs (id, tenant_id, colaborador_id, tipo_aso, data_realizacao_exame, data_validade_aso, resultado_aptidao, crm_medico, uf_crm_medico, nome_medico_coordenador, cnpj_clinica_examinadora, risco_fisico, risco_quimico, risco_biologico, risco_ergonomico, parecer_conclusivo, anexo_laudo_url, aprovado_por_id, criado_em)",
             "Baixa", "7 PFs"),

            ("AIE 7", "AIE_MINISTERIO_TRABALHO_CA", "Externo (Ministério do Trabalho - MTE)", 
             "Base governamental consultada para validação da autenticidade e validade do número do C.A. do EPI.",
             "1 RLR (ConsultaCA)",
             "8 DERs (numero_ca, nome_fabricante, cnpj_fabricante, descricao_equipamento, data_validade_ca, situacao_ca, laudo_tecnico_num, laboratorio_ensaio)",
             "Baixa", "5 PFs"),

            ("AIE 8", "AIE_CLINICA_MEDICA_OCUPACIONAL", "Externo (Clínica Ocupacional Parceira)", 
             "API de integração para agendamento automático de exames e recepção de laudos médicos.",
             "2 RLRs (LaudoExame, MedicoEmitente)",
             "15 DERs (codigo_agendamento, cpf_paciente, data_hora_exame, codigo_procedimento_tuss, resultado_exame_status, laudo_pdf_base64, med_assinatura_digital, crm_emitente, laboratorio_parceiro, observacoes_medicas, data_emissao_laudo, protocolo_transmissao, hash_arquivo, status_sincronizacao, criado_em)",
             "Baixa", "5 PFs")
        ]),

        # Membro 5
        ("MEMBRO 5 — Compliance, Auditoria & Portal do Corretor FinCorp", [
            ("ALI 9", "ALI_AUDITORIA_LOGS", "Interno", 
             "Registra a trilha de auditoria imutável do sistema com carimbo de tempo, IP e conformidade LGPD.",
             "3 RLRs (EventoAuditoria, HashIntegridade, LogAcesso)",
             "14 DERs (id, tenant_id, usuario_id, modulo_afetado, acao_executada, tabela_alvo, registro_id_alvo, dados_anteriores_json, dados_novos_json, ip_origem, user_agent, hash_sha256_corrente, hash_sha256_anterior, timestamp_evento)",
             "Baixa", "7 PFs"),

            ("ALI 10", "ALI_APOLICES_CORRETOR", "Interno", 
             "Gerencia as apólices de seguros corporativos (Vida/Saúde) comercializadas via Portal do Corretor.",
             "3 RLRs (ApoliceSeguro, CoberturaContratada, SinistroRegistrado)",
             "22 DERs (id, tenant_id, colaborador_id, corretor_usuario_id, numero_apolice, premio_mensal_bruto, valor_cobertura_morte, valor_cobertura_invalidez, valor_cobertura_hospitalar, data_inicio_vigencia, data_fim_vigencia, status_apolice, beneficiarios_json, numero_sinistro_aberto, data_sinistro, valor_indenizacao, status_sinistro, arquivo_apolice_url, criado_em, atualizado_em, parcelamento_tipo, codigo_seguradora)",
             "Média", "10 PFs"),

            ("AIE 9", "AIE_SEGURADORA_PORTAL", "Externo (Seguradora Tokio Marine/SulAmérica)", 
             "API REST da seguradora para cotação em tempo real, emissão de bilhetes e acompanhamento de sinistros.",
             "2 RLRs (CotacaoApolice, EmissaoBilhete)",
             "25 DERs (codigo_corretor_susep, proposta_numero, cpf_segurado, idade_segurado, valor_cobertura_solicitada, premio_calculado, classe_risco, status_proposta, bilhete_numero, pdf_bilhete_url, retorno_codigo, mensagem_erro, vigencia_inicio_emitida, vigencia_fim_emitida, token_autorizacao, etc.)",
             "Média", "7 PFs"),

            ("AIE 10", "AIE_SISTEMA_BANCARIO_PIX", "Externo (Banco Central / Gateway PIX)", 
             "Gateway financeiro para pagamento automatizado de reembolsos de benefícios e prêmios de seguro via PIX.",
             "2 RLRs (TransacaoPix, ComprovanteBancario)",
             "16 DERs (end_to_end_id, txid_pix, chave_pix_destino, tipo_chave, cpf_cnpj_recebedor, nome_recebedor, valor_transferencia, data_hora_pagamento, status_transacao, codigo_autenticacao_bancaria, qr_code_copia_cola, url_pix, retorno_banco_codigo, mensagem_motivo, tenant_id, data_conciliacao)",
             "Baixa", "5 PFs")
        ])
    ]

    for title, group in items_detail:
        add_heading_2(doc, title)
        
        t_g = doc.add_table(rows=len(group) + 1, cols=8)
        t_g.alignment = WD_TABLE_ALIGNMENT.CENTER
        set_table_borders(t_g)

        g_headers = ["Tipo", "Nome do Arquivo", "Escopo", "Descrição", "RLRs (RET)", "DERs (DET)", "Complexidade", "Pontos de Função"]
        g_widths = [Inches(0.6), Inches(1.5), Inches(0.9), Inches(1.8), Inches(1.2), Inches(1.3), Inches(0.8), Inches(0.8)]

        for j, h in enumerate(g_headers):
            cell = t_g.cell(0, j)
            cell.width = g_widths[j]
            format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A")

        for i, item in enumerate(group):
            bg = "F8FAFC" if i % 2 == 1 else "FFFFFF"
            # item = (Tipo, Nome, Escopo, Descricao, RLR, DER, Compl, PF)
            row_vals = [item[0], item[1], item[2], item[3], item[4], item[5], item[6], item[7]]
            for j, val in enumerate(row_vals):
                cell = t_g.cell(i + 1, j)
                cell.width = g_widths[j]
                format_cell(cell, val, bold=(j in [0, 1, 7]), color=RGBColor(30,58,138) if j==7 else RGBColor(51,51,51), bg_color=bg)

    doc.add_page_break()

    # Section 3: Final Consolidation Table
    add_heading_1(doc, "3. Tabela Consolidada de Pontos de Função (Contagem Não Ajustada - IFPUG)")
    add_body_p(doc, "Sumário geral dos Pontos de Função de Dados (ALIs + AIEs) calculados para todo o sistema HRTech Core considerando a equipe de 5 membros:")

    summary_rows = [
        ("Membro 1 — Gestão de Colaboradores", "2 ALIs (Colaboradores, Estrutura) + 2 AIEs (Receita, eSocial)", "17 PFs (ALIs)", "12 PFs (AIEs)", "29 PFs"),
        ("Membro 2 — Controle de Frequência", "2 ALIs (Ponto, Banco Horas) + 2 AIEs (REP Ponto, Hora NTP)", "14 PFs (ALIs)", "10 PFs (AIEs)", "24 PFs"),
        ("Membro 3 — Férias e Benefícios", "2 ALIs (Férias, Benefícios) + 2 AIEs (VR/VA, Saúde)", "14 PFs (ALIs)", "12 PFs (AIEs)", "26 PFs"),
        ("Membro 4 — Segurança / EPIs / ASO", "2 ALIs (EPIs, Exames ASO) + 2 AIEs (MTE CA, Clínica)", "14 PFs (ALIs)", "10 PFs (AIEs)", "24 PFs"),
        ("Membro 5 — Compliance & Corretor", "2 ALIs (Auditoria, Apólices) + 2 AIEs (Seguradora, PIX)", "17 PFs (ALIs)", "12 PFs (AIEs)", "29 PFs"),
        ("TOTAL GERAL DA EQUIPE (5 MEMBROS)", "10 ALIs + 10 AIEs (20 Arquivos de Dados)", "76 PFs (ALIs)", "56 PFs (AIEs)", "132 PFs GERAIS")
    ]

    t_s = doc.add_table(rows=len(summary_rows) + 1, cols=5)
    t_s.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_s)

    s_headers = ["Membro / Módulo", "Distribuição dos Arquivos", "Total ALIs (PF)", "Total AIEs (PF)", "Subtotal Geral (PF)"]
    s_widths = [Inches(2.0), Inches(2.5), Inches(1.2), Inches(1.2), Inches(1.3)]

    for j, h in enumerate(s_headers):
        cell = t_s.cell(0, j)
        cell.width = s_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A")

    for i, row in enumerate(summary_rows):
        is_tot = (i == len(summary_rows)-1)
        bg = "EFF6FF" if is_tot else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_s.cell(i + 1, j)
            cell.width = s_widths[j]
            format_cell(cell, val, bold=is_tot or (j==0 or j==4), color=RGBColor(30,58,138) if is_tot else RGBColor(51,51,51), bg_color=bg)

    output_file = "/home/fernando/Documentos/Faculdade/Projeto de medição e analise/Estimativa_APF_5_Membros_ALIs_AIEs_HRTech.docx"
    doc.save(output_file)
    print(f"Documento salvo com sucesso em: {output_file}")

if __name__ == "__main__":
    main()
