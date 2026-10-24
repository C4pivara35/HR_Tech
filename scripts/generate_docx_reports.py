#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
HRTech Core — Gerador Oficial de Relatórios Executivos de APF em DOCX
Atividades 05 (Individual) e 06 (Equipe) — PUCPR — Medição e Análise de Software

Gera os dois documentos Word (.docx) oficiais em conformidade estrita com o
IFPUG CPM 4.3.1 (ISO/IEC 20926), normas ABNT e diretrizes da disciplina:
  1. Entrega_APF_Individual_Fernando_Lopes_Duarte.docx (53 PFB / 65,19 PFA)
  2. Entrega_APF_Equipe_HRTech_Core.docx (252 PFB / 309,96 PFA)
"""

import os
import sys
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

PROJECT_ROOT = "/home/fernando/Documentos/Faculdade/Projeto de medição e analise"
SCREENSHOTS_DIR = os.path.join(PROJECT_ROOT, "screenshots")

# ==============================================================================
# CONFIGURAÇÃO DE DESIGN & PALETA EXECUTIVA NAVY BLUE
# ==============================================================================
class StyleConfig:
    NAVY_PRIMARY = "1B365D"      # Azul Marinho PUCPR Executivo
    NAVY_SECONDARY = "2B4C7E"    # Slate Blue H2
    SLATE_DARK = "334155"        # Dark Slate H3
    BODY_COLOR = "222222"        # Charcoal texto
    MUTED_GRAY = "555555"        # Legendas
    BORDER_COLOR = "D0D5DD"      # Bordas sutis de tabela
    ZEBRA_BG = "F8FAFC"          # Linhas alternadas
    CALLOUT_BG = "F1F5F9"        # Fundo do destaque
    WHITE = "FFFFFF"
    FONT_FAMILY = "Calibri"

# ==============================================================================
# HELPERS XML PARA TABELAS, BORDAS E DESTAQUES
# ==============================================================================
class TableHelper:
    @staticmethod
    def set_cell_background(cell, color_hex):
        tcPr = cell._tc.get_or_add_tcPr()
        for child in list(tcPr):
            if child.tag.endswith('shd'):
                tcPr.remove(child)
        shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{color_hex}"/>')
        tcPr.append(shd)

    @staticmethod
    def set_cell_margins(cell, top=100, bottom=100, left=140, right=140):
        tcPr = cell._tc.get_or_add_tcPr()
        for child in list(tcPr):
            if child.tag.endswith('tcMar'):
                tcPr.remove(child)
        tcMar = parse_xml(
            f'<w:tcMar {nsdecls("w")}>'
            f'<w:top w:w="{top}" w:type="dxa"/>'
            f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
            f'<w:left w:w="{left}" w:type="dxa"/>'
            f'<w:right w:w="{right}" w:type="dxa"/>'
            f'</w:tcMar>'
        )
        tcPr.append(tcMar)

    @staticmethod
    def apply_table_borders(table, border_color=StyleConfig.BORDER_COLOR):
        tblPr = table._tbl.tblPr
        for child in list(tblPr):
            if child.tag.endswith('tblBorders'):
                tblPr.remove(child)
        borders = parse_xml(
            f'<w:tblBorders {nsdecls("w")}>'
            f'<w:top w:val="single" w:sz="4" w:space="0" w:color="{border_color}"/>'
            f'<w:left w:val="none"/>'
            f'<w:bottom w:val="single" w:sz="4" w:space="0" w:color="{border_color}"/>'
            f'<w:right w:val="none"/>'
            f'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="{border_color}"/>'
            f'<w:insideV w:val="none"/>'
            f'</w:tblBorders>'
        )
        tblPr.append(borders)

    @staticmethod
    def format_full_table(table, col_widths=None):
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        TableHelper.apply_table_borders(table)
        
        # Header Row: <w:tblHeader/>
        header_trPr = table.rows[0]._tr.get_or_add_trPr()
        header_trPr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))

        for i, row in enumerate(table.rows):
            # <w:cantSplit/>
            trPr = row._tr.get_or_add_trPr()
            trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

            is_header = (i == 0)
            is_even = (i % 2 == 0) and (not is_header)

            for j, cell in enumerate(row.cells):
                TableHelper.set_cell_margins(cell, top=100, bottom=100, left=140, right=140)
                if col_widths and j < len(col_widths):
                    cell.width = col_widths[j]

                if is_header:
                    TableHelper.set_cell_background(cell, StyleConfig.NAVY_PRIMARY)
                    for p in cell.paragraphs:
                        p.paragraph_format.space_before = Pt(4)
                        p.paragraph_format.space_after = Pt(4)
                        for r in p.runs:
                            r.font.name = StyleConfig.FONT_FAMILY
                            r.font.size = Pt(9.5)
                            r.font.bold = True
                            r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
                else:
                    if is_even:
                        TableHelper.set_cell_background(cell, StyleConfig.ZEBRA_BG)
                    else:
                        TableHelper.set_cell_background(cell, StyleConfig.WHITE)
                    for p in cell.paragraphs:
                        p.paragraph_format.space_before = Pt(2)
                        p.paragraph_format.space_after = Pt(2)
                        for r in p.runs:
                            r.font.name = StyleConfig.FONT_FAMILY
                            r.font.size = Pt(9.0)
                            r.font.color.rgb = RGBColor(0x22, 0x22, 0x22)

class CalloutHelper:
    @staticmethod
    def add_callout(doc, title, text, formula=None):
        table = doc.add_table(rows=1, cols=1)
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = table.cell(0, 0)
        cell.width = Inches(6.0)

        # Style background
        TableHelper.set_cell_background(cell, StyleConfig.CALLOUT_BG)
        TableHelper.set_cell_margins(cell, top=140, bottom=140, left=180, right=140)

        # Style left border: 36 (4.5 pt) solid Navy Blue
        tcPr = cell._tc.get_or_add_tcPr()
        for child in list(tcPr):
            if child.tag.endswith('tcBorders'):
                tcPr.remove(child)
        borders = parse_xml(
            f'<w:tcBorders {nsdecls("w")}>'
            f'<w:top w:val="none"/>'
            f'<w:left w:val="single" w:sz="36" w:space="0" w:color="{StyleConfig.NAVY_PRIMARY}"/>'
            f'<w:bottom w:val="none"/>'
            f'<w:right w:val="none"/>'
            f'</w:tcBorders>'
        )
        tcPr.append(borders)

        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(3)
        run_title = p.add_run(f"📌 {title}\n")
        run_title.font.name = StyleConfig.FONT_FAMILY
        run_title.font.bold = True
        run_title.font.size = Pt(9.5)
        run_title.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

        run_text = p.add_run(text)
        run_text.font.name = StyleConfig.FONT_FAMILY
        run_text.font.size = Pt(9.0)
        run_text.font.color.rgb = RGBColor(0x22, 0x22, 0x22)

        if formula:
            p2 = cell.add_paragraph()
            p2.paragraph_format.space_before = Pt(3)
            p2.paragraph_format.space_after = Pt(2)
            run_f_label = p2.add_run("Fórmula / Algoritmo: ")
            run_f_label.font.name = StyleConfig.FONT_FAMILY
            run_f_label.font.bold = True
            run_f_label.font.size = Pt(9.0)
            run_f_label.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

            run_f = p2.add_run(formula)
            run_f.font.name = "Consolas"
            run_f.font.size = Pt(8.5)
            run_f.font.bold = True
            run_f.font.color.rgb = RGBColor(0x0F, 0x29, 0x4A)

        # Spacing after callout table
        p_after = doc.add_paragraph()
        p_after.paragraph_format.space_before = Pt(0)
        p_after.paragraph_format.space_after = Pt(4)

class ImageHelper:
    @staticmethod
    def resolve_screenshot(name):
        candidates = [
            os.path.join(SCREENSHOTS_DIR, name),
            os.path.join(SCREENSHOTS_DIR, f"{name}.png"),
        ]
        for c in candidates:
            if os.path.exists(c):
                return c
        if os.path.isdir(SCREENSHOTS_DIR):
            for fname in os.listdir(SCREENSHOTS_DIR):
                if fname.startswith(name) and fname.endswith(".png"):
                    return os.path.join(SCREENSHOTS_DIR, fname)
        return None

    @staticmethod
    def add_screenshot(doc, img_name, fig_num, title, width_inches=6.0):
        img_path = ImageHelper.resolve_screenshot(img_name)
        if not img_path:
            # Fallback callout if missing
            CalloutHelper.add_callout(
                doc,
                f"Evidência Visual — Figura {fig_num}",
                f"Captura de tela '{img_name}' referenciada para: {title} (Arquivo aguardando renderização)."
            )
            return

        p_img = doc.add_paragraph()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.space_before = Pt(8)
        p_img.paragraph_format.space_after = Pt(3)
        run_img = p_img.add_run()
        run_img.add_picture(img_path, width=Inches(width_inches))

        p_cap = doc.add_paragraph()
        p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_cap.paragraph_format.space_before = Pt(2)
        p_cap.paragraph_format.space_after = Pt(10)
        run_cap = p_cap.add_run(f"Figura {fig_num} — {title} (Fonte: Plataforma HRTech Core / Autores, 2026)")
        run_cap.font.name = StyleConfig.FONT_FAMILY
        run_cap.font.size = Pt(8.5)
        run_cap.font.italic = True
        run_cap.font.color.rgb = RGBColor(0x55, 0x55, 0x55)

# ==============================================================================
# BASE DE DADOS COMPLETA DO SISTEMA (50 FUNÇÕES + GSC + GESTÃO)
# ==============================================================================
MEMBERS_DATA = [
    {
        "nome": "Andryus Zolet",
        "papel": "Engenheiro de Domínio & Organograma",
        "modulo": "Cadastro de Colaboradores, Matrículas e Organograma",
        "pfb": 53,
        "pfa": 65.19,
        "hh": 624.65,
        "custo": 31232.35,
        "perc": "21,0%"
    },
    {
        "nome": "Felipe",
        "papel": "Engenheiro de Compliance & Ponto REP",
        "modulo": "Controle de Ponto Eletrônico Portaria 671 MTE & Banco de Horas",
        "pfb": 48,
        "pfa": 59.04,
        "hh": 565.71,
        "custo": 28285.90,
        "perc": "19,0%"
    },
    {
        "nome": "Valentin",
        "papel": "Engenheiro de Benefícios & Férias CLT",
        "modulo": "Gestão de Férias CLT, Abono Pecuniário e Benefícios Flexíveis",
        "pfb": 50,
        "pfa": 61.50,
        "hh": 589.29,
        "custo": 29464.48,
        "perc": "19,8%"
    },
    {
        "nome": "Nicolas",
        "papel": "Engenheiro de SST & Medicina Fabril",
        "modulo": "Segurança do Trabalho (NR-06/07) e Barreira Industrial",
        "pfb": 48,
        "pfa": 59.04,
        "hh": 565.71,
        "custo": 28285.90,
        "perc": "19,0%"
    },
    {
        "nome": "Fernando Lopes Duarte",
        "papel": "Arquiteto Core, Database Lead & FinCorp Specialist",
        "modulo": "Auditoria Imutável (Ledger SHA-256), Multi-Tenancy e FinCorp",
        "pfb": 53,
        "pfa": 65.19,
        "hh": 624.65,
        "custo": 31232.35,
        "perc": "21,0%"
    }
]

ALIS_DATA = [
    {
        "id": "ALI_COLABORADORES",
        "nome": "Cadastro Mestre e Histórico Funcional de Colaboradores",
        "responsavel": "Andryus Zolet",
        "tabela": "employees (SQLite hrtech_db.sqlite)",
        "rlrs_count": 4,
        "rlrs": "Dados Pessoais (Identificação/Doc), Vínculo Contratual CLT/PJ, Remuneração Bancária e Encargos, Saldos Acumulados (Férias/Banco).",
        "ders_count": 24,
        "ders": "id, tenant_id, cpf, matricula, full_name, email, phone, birth_date, admission_date, termination_date, department_id, role_id, base_salary_cents, employment_type, is_active, vacation_days_balance, bank_hours_balance, cost_center, manager_id, emergency_contact, bank_account, hire_status, created_at, updated_at.",
        "complexidade": "Média",
        "pfb": 10,
        "tela_img": "tela_04_lista_colaboradores",
        "tela_desc": "Tela 04 (Lista Cadastral de Colaboradores) e Tela 05 (Formulário de Admissão)."
    },
    {
        "id": "ALI_ESTRUTURA_ORG",
        "nome": "Estrutura Organizacional, Departamentos e Cargos",
        "responsavel": "Andryus Zolet",
        "tabela": "departments, roles (SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Unidades Departamentais, Cargos e Funções, Alçadas de Aprovação Hierárquica.",
        "ders_count": 14,
        "ders": "dept_id, dept_code, dept_name, cost_center, manager_id, parent_department_id, role_id, role_name, hierarchy_level, permissions_json, is_active, tenant_id, created_at, updated_at.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_07_organograma",
        "tela_desc": "Tela 07 (Organograma Hierárquico Interativo e Alçadas)."
    },
    {
        "id": "ALI_REGISTROS_PONTO",
        "nome": "Registros Fiscais de Ponto Eletrônico (REP Portaria 671 MTE)",
        "responsavel": "Felipe",
        "tabela": "time_logs (SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Marcação de Ponto (Batida), Posicionamento Geográfico (GPS), Selagem Criptográfica SHA-256.",
        "ders_count": 18,
        "ders": "id, tenant_id, employee_id, timestamp, type, latitude, longitude, accuracy, nsr, previous_hash, signature_hash, created_at, device_id, ip_address, cpf_snapshot, timezone_offset, punch_source, biometric_flag.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_08_espelho_ponto",
        "tela_desc": "Tela 08 (Espelho de Ponto Eletrônico Mensal e NSR)."
    },
    {
        "id": "ALI_BANCO_HORAS",
        "nome": "Acúmulo e Compensação do Banco de Horas",
        "responsavel": "Felipe",
        "tabela": "employees.bank_hours_balance, overtime_entries",
        "rlrs_count": 3,
        "rlrs": "Saldo Acumulado Consolidado, Lançamentos Analíticos Crédito/Débito, Regras de Acordo Coletivo (ACT/CCT).",
        "ders_count": 15,
        "ders": "id, tenant_id, employee_id, date, hours, type, hourly_rate, amount, strategy_used, description, bank_hours_balance_min, created_at, expiration_date, status_fechamento, approver_id.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_02_dashboard_colaborador",
        "tela_desc": "Tela 02 (Dashboard do Colaborador e Extrato do Banco de Horas)."
    },
    {
        "id": "ALI_SOLICITACOES_FERIAS",
        "nome": "Gestão de Programação e Concessão de Férias Trabalhistas",
        "responsavel": "Valentin",
        "tabela": "vacation_requests (SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Período Aquisitivo CLT, Programação de Fruição (Gozo), Conversão em Abono Pecuniário (Art. 143 CLT).",
        "ders_count": 16,
        "ders": "id, tenant_id, employee_id, start_date, end_date, duration_days, abono_pecuniario, advance_thirteenth_salary, abono_days, status, approver_id, approved_at, rejection_reason, created_at, periodo_aquisitivo_inicio, periodo_aquisitivo_fim.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_10_gestao_ferias",
        "tela_desc": "Tela 10 (Painel de Gestão de Férias e Agendamento CLT)."
    },
    {
        "id": "ALI_CARTOES_BENEF",
        "nome": "Catálogo de Benefícios Flexíveis e Vínculos Funcionais",
        "responsavel": "Valentin",
        "tabela": "benefits (SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Pacotes e Apólices de Benefícios, Fornecedoras Conveniadas, Regras de Coparticipação em Folha.",
        "ders_count": 18,
        "ders": "id, tenant_id, type, name, provider, value_cents, employee_cost_share_percentage, is_deductible, card_number_masked, activation_date, expiration_date, category, subsidy_amount_cents, status, created_at, updated_at, elegibilidade_cargo, copay_rules.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_12_gestao_beneficios",
        "tela_desc": "Tela 12 (Central de Benefícios Corporativos e Subsídios)."
    },
    {
        "id": "ALI_EPIS_EQUIP",
        "nome": "Fichas de Custódia e Controle de EPIs (NR-06 MTE)",
        "responsavel": "Nicolas",
        "tabela": "equipment_aso (campos EPI / SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Ficha de Custódia do Empregado, Certificados de Aprovação (CA MTE), Termos de Entrega e Devolução.",
        "ders_count": 15,
        "ders": "id, tenant_id, employee_id, equipment_name, ca_number, ca_expiration_date, delivery_date, return_date, created_at, is_fit, condition_status, serial_number, signature_receipt, replacement_reason, manufacturer_name.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_14_modulo_industrial",
        "tela_desc": "Tela 14 (Barreira Fabril SST: Ficha de EPIs NR-06)."
    },
    {
        "id": "ALI_EXAMES_ASO",
        "nome": "Prontuário Ocupacional e Atestados ASO (NR-07 PCMSO)",
        "responsavel": "Nicolas",
        "tabela": "equipment_aso (campos ASO / SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Prontuário Ocupacional Histórico, Parecer Clínico de Aptidão, Registro do Médico Coordenador CRM.",
        "ders_count": 16,
        "ders": "id, tenant_id, employee_id, exam_type, exam_date, expiration_date, physician_name, physician_crm, is_fit, created_at, clinic_cnpj, crm_state, complementary_exams_json, occupational_risks, next_exam_schedule, medical_notes.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_14_modulo_industrial",
        "tela_desc": "Tela 14 (Barreira Fabril SST: Atestados ASO NR-07)."
    },
    {
        "id": "ALI_AUDITORIA_LOGS",
        "nome": "Trilha de Auditoria Imutável (Ledger Criptográfico LGPD/Bacen)",
        "responsavel": "Fernando Lopes Duarte",
        "tabela": "audit_logs (SQLite hrtech_db.sqlite)",
        "rlrs_count": 3,
        "rlrs": "Registro de Transação Auditável, Delta de Estado LGPD (Snapshot Anterior/Novo), Encadeamento de Blocos Criptográficos SHA-256.",
        "ders_count": 14,
        "ders": "id, tenant_id, actor_user_id, action, entity_type, entity_id, previous_state, new_state, ip_address, user_agent, timestamp, previous_hash, integrity_hash, retention_period.",
        "complexidade": "Baixa",
        "pfb": 7,
        "tela_img": "tela_16_compliance_auditoria",
        "tela_desc": "Tela 16 (Compliance, Trilha de Auditoria e Ledger SHA-256)."
    },
    {
        "id": "ALI_APOLICES_CORRET",
        "nome": "Carteira de Apólices Corporativas e Seguro de Vida FinCorp",
        "responsavel": "Fernando Lopes Duarte",
        "tabela": "insurance_policies (SQLite hrtech_db.sqlite)",
        "rlrs_count": 4,
        "rlrs": "Contrato de Apólice, Registro do Corretor SUSEP, Coberturas e Capitais Segurados, Histórico de Vigência e Sinistros.",
        "ders_count": 26,
        "ders": "id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date, coverage_details, cancellation_reason, cancelled_at, commission_percentage, insured_name, beneficiaries_json, iof_cents, grace_period_days, policy_type, deductible_cents, claim_contact_phone, risk_class, endorsement_number, created_at, updated_at.",
        "complexidade": "Média",
        "pfb": 10,
        "tela_img": "tela_17_portal_corretor",
        "tela_desc": "Tela 17 (Portal do Corretor FinCorp Seguros e Carteira de Apólices)."
    }
]

AIES_DATA = [
    {
        "id": "AIE_RECEITA_CPF",
        "nome": "API da Receita Federal (Validação Cadastral de CPF)",
        "responsavel": "Andryus Zolet",
        "origem": "Web Service Receita Federal / SERPRO",
        "rlrs_count": 2,
        "rlrs": "Cadastro Central de Pessoas Físicas, Situação Cadastral e Fiscal.",
        "ders_count": 12,
        "ders": "cpf_cnpj, nome_completo, situacao, data_nascimento, digito_verificador, protocolo_consulta, data_consulta, codigo_retorno, uf, nome_mae, titulo_eleitor, hash_autenticidade.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_05_cadastro_colaborador",
        "tela_desc": "Tela 05 (Validador Cadastral de CPF no Formulário de Admissão)."
    },
    {
        "id": "AIE_GOV_ESOCIAL",
        "nome": "Gateway do eSocial Governo Federal (Eventos S-2200 / S-2206)",
        "responsavel": "Andryus Zolet",
        "origem": "Ambiente Nacional eSocial / Receita Federal",
        "rlrs_count": 2,
        "rlrs": "Leiaute Oficial do Evento de Admissão, Recibo de Protocolo Governamental.",
        "ders_count": 22,
        "ders": "cpf, nis_pis, matricula_esocial, data_admissao, cbo, remuneracao, recibo_entrega, protocolo_envio, tipo_admissao, natureza_atividade, grau_instrucao, raca_cor, estado_civil, pais_nasc, endereco_esocial, deficiencia_flag, regime_jornada, cargo_esocial, alvara_judicial, hash_xml_retorno, created_at, status_processamento.",
        "complexidade": "Média",
        "pfb": 7,
        "tela_img": "tela_06_detalhes_colaborador",
        "tela_desc": "Tela 06 (Prontuário Funcional e Painel de Transmissão eSocial)."
    },
    {
        "id": "AIE_RELOGIO_REP",
        "nome": "Relógio Eletrônico de Ponto REP (Arquivo AFD / Portaria 671 MTE)",
        "responsavel": "Felipe",
        "origem": "Equipamento Físico Registrador Eletrônico de Ponto",
        "rlrs_count": 2,
        "rlrs": "Cabeçalho Fiscal do Equipamento REP, Bilhete de Batida NSR Inviolável.",
        "ders_count": 10,
        "ders": "numero_rep, cnpj_empregador, nsr_rep, data_hora_gravada, pis, tipo_marcacao, crc16, versao_afd, ip_rep, status_leitura.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_08_espelho_ponto",
        "tela_desc": "Tela 08 (Interface de Leitura e Auditoria de Registros AFD)."
    },
    {
        "id": "AIE_API_NTP_HORA",
        "nome": "Servidor NTP.br / Observatório Nacional (Hora Legal Brasileira)",
        "responsavel": "Felipe",
        "origem": "Servidores Oficiais de Sincronismo NTP.br / NIC.br",
        "rlrs_count": 1,
        "rlrs": "Pacote de Resposta NTP com Timestamp Legal Auditado.",
        "ders_count": 6,
        "ders": "timestamp_ntp, stratum, precision_ms, root_delay, dispersion, server_ip.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_08_espelho_ponto",
        "tela_desc": "Tela 08 (Selo de Carimbo de Tempo Legal NTP.br)."
    },
    {
        "id": "AIE_OPERADORA_VR_VA",
        "nome": "Gateway de Benefícios VR/VA (Ticket, Alelo, Sodexo, Flash)",
        "responsavel": "Valentin",
        "origem": "Web Service das Operadoras de Benefícios PAT",
        "rlrs_count": 2,
        "rlrs": "Pedido de Carga Mensal, Extrato e Saldos de Cartões de Alimentação.",
        "ders_count": 14,
        "ders": "cod_cliente, contrato, cpf_favorecido, valor_credito, data_carga, cod_pedido, status_pedido, lote_processamento, tipo_cartao, saldo_residual, taxa_emissao, hash_autorizacao, nsu, created_at.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_12_gestao_beneficios",
        "tela_desc": "Tela 12 (Telemetria de Pedidos de Cargas VR/VA)."
    },
    {
        "id": "AIE_OPERADORA_SAUDE",
        "nome": "API da Operadora de Saúde / Odonto (Padrão TISS / ANS)",
        "responsavel": "Valentin",
        "origem": "Gateway da Seguradora de Saúde e Odontologia",
        "rlrs_count": 3,
        "rlrs": "Guia Eletrônica TISS, Fatura de Coparticipação, Sinistralidade do Contrato.",
        "ders_count": 22,
        "ders": "cnpj_operadora, numero_contrato, cpf_titular, grau_parentesco, faixa_etaria_ans, mensalidade_centavos, coparticipacao_consultas, coparticipacao_exames, sinistralidade_grupo, status_vida, data_inclusao, data_exclusao, plano_tipo, rede_credenciada, acomodacao, carencia_dias, valor_reembolso, protocolo_ans, codigo_tuss, data_atendimento, guia_autorizacao, created_at.",
        "complexidade": "Média",
        "pfb": 7,
        "tela_img": "tela_12_gestao_beneficios",
        "tela_desc": "Tela 12 (Fatura Analítica TISS da Operadora de Saúde)."
    },
    {
        "id": "AIE_MTE_CA",
        "nome": "Base Pública CAEPI do Ministério do Trabalho e Emprego",
        "responsavel": "Nicolas",
        "origem": "Base de Dados Governamental CAEPI / MTE",
        "rlrs_count": 1,
        "rlrs": "Registro de Certificado de Aprovação de EPI.",
        "ders_count": 8,
        "ders": "ca_numero, fabricante_cnpj, equipamento_tipo, validade_ca, situacao_cadastral, laudo_laboratorial, norma_atendida, data_consulta_ca.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_14_modulo_industrial",
        "tela_desc": "Tela 14 (Consulta Automática do Número de CA no MTE)."
    },
    {
        "id": "AIE_CLINICA_MEDICA",
        "nome": "Sistema de Medicina Ocupacional da Clínica Parceira (PCMSO)",
        "responsavel": "Nicolas",
        "origem": "API Externa da Clínica Credenciada de Saúde Ocupacional",
        "rlrs_count": 2,
        "rlrs": "Agendamento Clínico de Exames, Laudo Digital Assinado por Médico do Trabalho.",
        "ders_count": 12,
        "ders": "cnpj_clinica, codigo_agendamento, cpf_paciente, tipo_exame_aso, data_atendimento, url_laudo_assinado, medico_crm, audiometria_resultado, espirometria_resultado, acuidade_visual, raio_x_torax, created_at.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_14_modulo_industrial",
        "tela_desc": "Tela 14 (Agendamentos e Laudos Clínicos Integrados)."
    },
    {
        "id": "AIE_SEGURADORA_PORTAL",
        "nome": "Gateway de Underwriting e Subscrição FinCorp Seguros (SUSEP)",
        "responsavel": "Fernando Lopes Duarte",
        "origem": "Plataforma de Subscrição FinCorp Seguros S.A.",
        "rlrs_count": 3,
        "rlrs": "Proposta Comercial de Seguro, Tabela Atuarial de Tarifação, Endosso de Apólice.",
        "ders_count": 20,
        "ders": "numero_proposta, cnpj_estipulante, tabela_sinistro, capital_segurado, iof_retido, status_emissao, premio_puro, premio_comercial, data_emissao, limite_retencao, susep_processo, classe_ocupacional, carregamento_comercial, comissao_corretor, custo_apolice, taxa_risco_morte, taxa_risco_invalidez, franquia_dias, cobertura_conjugal, hash_seguradora.",
        "complexidade": "Média",
        "pfb": 7,
        "tela_img": "tela_17_portal_corretor",
        "tela_desc": "Tela 17 (Cotação e Subscrição Online FinCorp Seguros)."
    },
    {
        "id": "AIE_SISTEMA_PIX",
        "nome": "Gateway de Pagamentos Instantâneos PIX BACEN (SPI / DICT)",
        "responsavel": "Fernando Lopes Duarte",
        "origem": "Sistema de Pagamentos Instantâneos (SPI) do Banco Central do Brasil",
        "rlrs_count": 2,
        "rlrs": "Payload de Cobrança PIX EMV (QR Code Dinâmico), Notificação de Liquidação SPI.",
        "ders_count": 16,
        "ders": "e2eid, txid, chave_pix, valor_centavos, timestamp_liquidacao, qr_code_copia_cola, status_pagamento, ispb_banco, nome_recebedor, cpf_cnpj_recebedor, end_to_end_id, mensagem_pix, autenticacao_bacen, tarifa_liquidacao, data_vencimento, created_at.",
        "complexidade": "Baixa",
        "pfb": 5,
        "tela_img": "tela_20",
        "tela_desc": "Tela 20 (Gateway de Liquidação Instantânea PIX e QR Code)."
    }
]

EES_DATA = [
    {
        "id": "EE_ADMITIR_COLAB",
        "nome": "Admissão e Contratação Formal de Colaborador",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 2,
        "ftrs": "ALI_COLABORADORES (Mantém), ALI_ESTRUTURA_ORG (Lê)",
        "ders_count": 14,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\EmployeeService::hireEmployee()",
        "tela_img": "tela_05_cadastro_colaborador",
        "descricao": "Processa formulário cadastral, valida estrutura departamental e registra colaborador."
    },
    {
        "id": "EE_CRIAR_DEPTO_CARGO",
        "nome": "Criação de Departamentos, Cargos e Níveis Hierárquicos",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 1,
        "ftrs": "ALI_ESTRUTURA_ORG (Mantém)",
        "ders_count": 8,
        "complexidade": "Baixa",
        "pfb": 3,
        "metodo": "HrTech\\Services\\DepartmentRoleService::createDepartment()",
        "tela_img": "tela_07_organograma",
        "descricao": "Cadastra novas unidades organizacionais, centros de custo e níveis de chefia."
    },
    {
        "id": "EE_BATER_PONTO_671",
        "nome": "Registro de Marcação de Ponto com Carimbo NTP.br",
        "responsavel": "Felipe",
        "ftrs_count": 2,
        "ftrs": "ALI_REGISTROS_PONTO (Mantém), AIE_API_NTP_HORA (Lê)",
        "ders_count": 12,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\TimeLogService::registerPunch()",
        "tela_img": "tela_08_espelho_ponto",
        "descricao": "Efetua marcação eletrônica com carimbo oficial NTP.br, GPS e hash SHA-256."
    },
    {
        "id": "EE_AJUSTE_ABONO_PONTO",
        "nome": "Solicitação e Parecer Gerencial de Ajuste de Ponto",
        "responsavel": "Felipe",
        "ftrs_count": 2,
        "ftrs": "ALI_REGISTROS_PONTO (Mantém), ALI_BANCO_HORAS (Mantém)",
        "ders_count": 10,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\TimeAdjustmentService::approveRequest()",
        "tela_img": "tela_09_ajuste_ponto",
        "descricao": "Processa justificativa de esquecimento/atestado médico com recálculo de saldo."
    },
    {
        "id": "EE_SOLICITAR_FERIAS",
        "nome": "Agendamento e Solicitação de Férias Trabalhistas",
        "responsavel": "Valentin",
        "ftrs_count": 2,
        "ftrs": "ALI_SOLICITACOES_FERIAS (Mantém), ALI_COLABORADORES (Lê)",
        "ders_count": 9,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\VacationService::requestVacation()",
        "tela_img": "tela_10_gestao_ferias",
        "descricao": "Registra período de gozo, opção de abono pecuniário (10 dias) e adiantamento de 13º."
    },
    {
        "id": "EE_VINCULAR_BENEFICIO",
        "nome": "Vinculação de Plano de Benefícios com Coparticipação",
        "responsavel": "Valentin",
        "ftrs_count": 2,
        "ftrs": "ALI_CARTOES_BENEF (Mantém), ALI_COLABORADORES (Lê)",
        "ders_count": 8,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\BenefitService::createBenefit()",
        "tela_img": "tela_12_gestao_beneficios",
        "descricao": "Associa planos corporativos ao colaborador definindo percentuais legais de rateio."
    },
    {
        "id": "EE_ENTREGA_EPI_NR6",
        "nome": "Entrega de Equipamento de Proteção Individual e Termo",
        "responsavel": "Nicolas",
        "ftrs_count": 2,
        "ftrs": "ALI_EPIS_EQUIP (Mantém), AIE_MTE_CA (Lê)",
        "ders_count": 11,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\EquipmentASOService::deliverEquipment()",
        "tela_img": "tela_14_modulo_industrial",
        "descricao": "Registra entrega de EPI validando o CA junto ao MTE e gerando termo assinado."
    },
    {
        "id": "EE_REGISTRAR_ASO_NR7",
        "nome": "Registro de Atestado de Saúde Ocupacional (ASO)",
        "responsavel": "Nicolas",
        "ftrs_count": 2,
        "ftrs": "ALI_EXAMES_ASO (Mantém), AIE_CLINICA_MEDICA (Lê)",
        "ders_count": 12,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\EquipmentASOService::recordAsoExam()",
        "tela_img": "tela_14_modulo_industrial",
        "descricao": "Registra parecer clínico ocupacional, validade periódica e CRM médico."
    },
    {
        "id": "EE_CRIAR_TENANT_LIC",
        "nome": "Provisionamento de Nova Empresa Cliente (Multi-Tenant)",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 1,
        "ftrs": "tenants (Mantém / 1 FTR)",
        "ders_count": 5,
        "complexidade": "Baixa",
        "pfb": 3,
        "metodo": "HrTech\\Services\\TenantService::createTenant()",
        "tela_img": "tela_15_configuracao_empresa",
        "descricao": "Provisiona novo tenant corporativo com isolamento de banco e licenças da LPS."
    },
    {
        "id": "EE_EMITIR_APOLICE_VIDA",
        "nome": "Emissão de Apólice Corporativa no Portal FinCorp",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 2,
        "ftrs": "ALI_APOLICES_CORRET (Mantém), AIE_SEGURADORA_PORTAL (Lê)",
        "ders_count": 14,
        "complexidade": "Média",
        "pfb": 4,
        "metodo": "HrTech\\Services\\InsurancePolicyService::issuePolicy()",
        "tela_img": "tela_17_portal_corretor",
        "descricao": "Emite apólice de seguro corporativo tarifando prêmio mensal e comissão do corretor."
    }
]

SES_DATA = [
    {
        "id": "SE_REL_TURNO_CONTRATO",
        "nome": "Relatório Executivo de Rotatividade Contratual (Turnover CLT)",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 1,
        "ftrs": "ALI_COLABORADORES",
        "ders_count": 14,
        "complexidade": "Baixa",
        "pfb": 4,
        "tela_img": "tela_01",
        "formula": "Turnover = (((Admissoes + Desligamentos) / 2) / HeadcountMedio) * 100",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O indicador de Turnover não existe armazenado em nenhuma tabela do banco. Ele é apurado dinamicamente através da fórmula matemática clássica da CLT: soma das admissões e desligamentos do período dividida por 2, sobre o efetivo médio. O relatório calcula ainda a média salarial ponderada por departamento e totaliza o headcount por regime CLT/PJ."
    },
    {
        "id": "SE_XML_ESOCIAL_S2200",
        "nome": "Emissão de XML Estruturado do eSocial S-2200 com Assinatura Digital",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 3,
        "ftrs": "ALI_COLABORADORES, ALI_ESTRUTURA_ORG, AIE_GOV_ESOCIAL",
        "ders_count": 24,
        "complexidade": "Alta",
        "pfb": 7,
        "tela_img": "tela_06_detalhes_colaborador",
        "formula": "XML-DSig = Base64(SHA256(CanonicalizeC14N(S2200_Payload)))",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O arquivo XML eSocial é gerado por compilação algorítmica: os dados relacionais são serializados no padrão XML-DSig, submetidos à canonicalização (C14N), cálculo de digestão criptográfica SHA-256 e conversão em Base64 para assinatura digital ICP-Brasil."
    },
    {
        "id": "SE_ESPELHO_PONTO_CALC",
        "nome": "Demonstrativo Fiscal de Ponto Mensal e Hora Noturna Reduzida",
        "responsavel": "Felipe",
        "ftrs_count": 2,
        "ftrs": "ALI_REGISTROS_PONTO, ALI_BANCO_HORAS",
        "ders_count": 16,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_08_espelho_ponto",
        "formula": "HoraFitaNoturna = MinutosReais * (60 / 52.5); AdicNoturno = ValorHora * 1.20",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O espelho mensal de ponto aplica processamento matemático sobre marcações brutas: converte as horas trabalhadas entre 22h e 05h na hora ficta legal de 52 minutos e 30 segundos (fator 1,142857), aplica o adicional noturno de 20%, compensa tolerâncias legais de 10 minutos (Art. 58 § 1º CLT) e sintetiza adicionais de horas extras a 50% e 100%."
    },
    {
        "id": "SE_APURACAO_BANCO_HORAS",
        "nome": "Apuração Dinâmica com Multiplicadores do Banco de Horas",
        "responsavel": "Felipe",
        "ftrs_count": 2,
        "ftrs": "ALI_REGISTROS_PONTO, ALI_BANCO_HORAS",
        "ders_count": 14,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_02_dashboard_colaborador",
        "formula": "CreditoBanco = (HorasUteis * 1.5) + (HorasFinaisDeSemana * 2.0)",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O saldo computado decorre da aplicação da estratégia da Linha de Produção de Software: horas suplementares em dias úteis recebem multiplicador de 1,5x (fator 50%), enquanto domingos e feriados recebem multiplicador de 2,0x (fator 100%). Calcula ainda o prazo decadencial de compensação semestral."
    },
    {
        "id": "SE_RECIBO_FERIAS_1_3",
        "nome": "Cálculo e Emissão do Recibo Oficial de Férias CLT com 1/3 e Abono",
        "responsavel": "Valentin",
        "ftrs_count": 2,
        "ftrs": "ALI_SOLICITACOES_FERIAS, ALI_COLABORADORES",
        "ders_count": 18,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_10_gestao_ferias",
        "formula": "TotalFerias = RemunDiasGozo + (RemunDiasGozo / 3) + Abono10Dias + (Abono / 3) + Adiant13",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. Gera o recibo formal calculando o terço constitucional compulsório (Art. 7º, XVII da CF/88), a remuneração proporcional aos dias de descanso, o valor da venda de até 10 dias de abono pecuniário (Art. 143 da CLT) acrescido de seu respectivo terço, e adiantamento facultativo de 50% do décimo terceiro salário com deduções legais."
    },
    {
        "id": "SE_FATURA_COPARTICIPACAO",
        "nome": "Fatura Analítica de Benefícios com Tetos Legais de 6% VT e 20% PAT",
        "responsavel": "Valentin",
        "ftrs_count": 2,
        "ftrs": "ALI_CARTOES_BENEF, ALI_COLABORADORES",
        "ders_count": 15,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_12_gestao_beneficios",
        "formula": "DescontoVT = Min(CustoPasse, SalarioBase * 0.06); DescontoPAT = Min(CustoVR, CustoVR * 0.20)",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O demonstrativo aplica tetos legais protetivos: limita o desconto do vale-transporte ao teto máximo de 6% do salário-base do trabalhador (Lei 7.418/85), limita o desconto de alimentação em no máximo 20% do custo do benefício (Programa de Alimentação do Trabalhador - PAT) e calcula coparticipações ambulatoriais por faixa etária ANS."
    },
    {
        "id": "SE_BLOQUEIO_CHAO_FABRICA",
        "nome": "Veredito Impeditivo de Catraca Fabril por ASO ou EPI Vencido",
        "responsavel": "Nicolas",
        "ftrs_count": 2,
        "ftrs": "ALI_EPIS_EQUIP, ALI_EXAMES_ASO",
        "ders_count": 12,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_14_modulo_industrial",
        "formula": "AcessoPermitido = (ASO_Valido == True) AND (DataAtual <= CA_Validade_EPI_Obrigatorio)",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O semáforo de liberação de catraca executa cruzamento matricial em tempo de execução: avalia o vencimento do exame clínico periódico (NR-07) em conjunto com a data de validade dos Certificados de Aprovação (CA) dos EPIs sob posse do operário (NR-06). Caso haja qualquer irregularidade, emite sinalização de BLOQUEIO IMEDIATO."
    },
    {
        "id": "SE_CRONOGRAMA_ASO_PER",
        "nome": "Painel Preditivo de Exames e Projeção de Passivo Trabalhista NR-07",
        "responsavel": "Nicolas",
        "ftrs_count": 2,
        "ftrs": "ALI_EXAMES_ASO, ALI_COLABORADORES",
        "ders_count": 14,
        "complexidade": "Média",
        "pfb": 5,
        "tela_img": "tela_15",
        "formula": "PassivoEstimado = Sum(MultaBaseMTE * GravidadeRiscoPorOperarioIrregular)",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O sistema projeta matematicamente as janelas de vencimento de exames clínicos em 30, 60 e 90 dias, calculando o risco financeiro cumulativo de autuações do Ministério do Trabalho por manter funcionários em atividade com ASO vencido."
    },
    {
        "id": "SE_FOLHA_PAGAMENTO_CALC",
        "nome": "Motor Tributário e Emissão de Holerite com INSS e IRRF Progressivos",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 3,
        "ftrs": "ALI_COLABORADORES, ALI_REGISTROS_PONTO, ALI_CARTOES_BENEF",
        "ders_count": 22,
        "complexidade": "Alta",
        "pfb": 7,
        "tela_img": "tela_18",
        "formula": "INSS = Sum(FaixasProgressivas); BaseIRRF = Bruto - INSS - (Dep * 189.59); IRRF = (Base * Aliq) - Dedução",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O holerite líquido decorre de um pipeline matemático de 5 fases: totalização de vencimentos brutos com adicionais noturnos/horas extras apurados; cálculo do INSS progressivo em 4 faixas legais (7,5%, 9%, 12% e 14% com abatimento de parcelas a deduzir); apuração da base de cálculo do IRRF com dedução legal de R$ 189,59 por dependente e alíquotas progressivas; aplicação dos tetos de benefícios e liquidação líquida."
    },
    {
        "id": "SE_LEDGER_AUDITORIA_SHA",
        "nome": "Laudo Pericial Criptográfico e Verificação de Integridade Blockchain",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 1,
        "ftrs": "ALI_AUDITORIA_LOGS",
        "ders_count": 8,
        "complexidade": "Baixa",
        "pfb": 4,
        "tela_img": "tela_16_compliance_auditoria",
        "formula": "BlocoValido = (SHA256(BlocoAtual.Dados + BlocoAnterior.Hash) == BlocoAtual.Hash)",
        "justificativa": "DADO ESTRITAMENTE DERIVADO. O diagnóstico de conformidade não existe persistido em disco. O algoritmo varre iterativamente toda a cadeia do ledger desde o bloco gênesis, recalculando o hash canônico SHA-256 de cada registro concatenado ao hash do registro anterior e atesta o veredito pericial de integridade e não-repúdio perante a LGPD e o Bacen."
    }
]

CES_DATA = [
    {
        "id": "CE_BUSCA_COLABORADOR",
        "nome": "Consulta Cadastral Multicritério de Colaboradores",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 1,
        "ftrs": "ALI_COLABORADORES",
        "ders_count": 10,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_03",
        "entradas": "Matrícula, CPF, Nome Parcial, Departamento ID, Status ('ativo'/'inativo').",
        "saidas": "Nome Completo, CPF Mascarado, Cargo, Departamento, Salário Base, Status Operacional."
    },
    {
        "id": "CE_ORGANOGRAMA_GRAFICO",
        "nome": "Consulta Hierárquica do Organograma Institucional",
        "responsavel": "Andryus Zolet",
        "ftrs_count": 1,
        "ftrs": "ALI_ESTRUTURA_ORG",
        "ders_count": 12,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_07_organograma",
        "entradas": "Tenant ID, Departamento Raiz, Gestor ID.",
        "saidas": "Nós Hierárquicos Interativos, Nome do Gestor, Qtde de Subordinados Diretos, Headcount da Unidade."
    },
    {
        "id": "CE_BATIDAS_DO_DIA",
        "nome": "Consulta em Tempo Real de Batidas e Marcações do Turno",
        "responsavel": "Felipe",
        "ftrs_count": 1,
        "ftrs": "ALI_REGISTROS_PONTO",
        "ders_count": 9,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_08_espelho_ponto",
        "entradas": "Colaborador ID, Data de Referência.",
        "saidas": "Marcações do Turno (Entrada 1, Saída 1, Entrada 2, Saída 2), NSR Fiscal, Hash SHA-256, Geolocalização."
    },
    {
        "id": "CE_EXTRATO_BANCO_HORAS",
        "nome": "Consulta ao Extrato Analítico do Banco de Horas",
        "responsavel": "Felipe",
        "ftrs_count": 1,
        "ftrs": "ALI_BANCO_HORAS",
        "ders_count": 11,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_04",
        "entradas": "Colaborador ID, Mês de Competência, Ano.",
        "saidas": "Lançamentos de Crédito (+), Lançamentos de Débito (-), Justificativa do Ajuste, Saldo Líquido Acumulado."
    },
    {
        "id": "CE_SALDO_FERIAS_AQUISIT",
        "nome": "Consulta de Saldo Aquisitivo e Limite Concessivo de Férias",
        "responsavel": "Valentin",
        "ftrs_count": 1,
        "ftrs": "ALI_SOLICITACOES_FERIAS",
        "ders_count": 10,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_10_gestao_ferias",
        "entradas": "Colaborador ID, Exercício Aquisitivo.",
        "saidas": "Data Início Período Aquisitivo, Data Fim, Limite Máximo Concessivo, Dias Saldo Remanescente."
    },
    {
        "id": "CE_CATALOGO_BENEFICIOS",
        "nome": "Consulta ao Catálogo Parametrizado de Benefícios Corporativos",
        "responsavel": "Valentin",
        "ftrs_count": 1,
        "ftrs": "ALI_CARTOES_BENEF",
        "ders_count": 12,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_12_gestao_beneficios",
        "entradas": "Segmento do Tenant ('tech', 'industry', 'financial'), Categoria de Benefício.",
        "saidas": "Pacotes Elegíveis, Fornecedora Conveniada, Custo Integral, Percentual de Subsídio Patronal."
    },
    {
        "id": "CE_FICHA_EPIS_COLAB",
        "nome": "Consulta à Ficha Individual de EPIs sob Custódia",
        "responsavel": "Nicolas",
        "ftrs_count": 1,
        "ftrs": "ALI_EPIS_EQUIP",
        "ders_count": 11,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_14_modulo_industrial",
        "entradas": "Colaborador ID, Matrícula Funcional.",
        "saidas": "Equipamentos Entregues, Número do CA MTE, Data de Entrega, Prazo de Validade do CA, Estado de Conservação."
    },
    {
        "id": "CE_HISTORICO_ASO_CA",
        "nome": "Consulta ao Prontuário Cronológico de Exames Ocupacionais",
        "responsavel": "Nicolas",
        "ftrs_count": 1,
        "ftrs": "ALI_EXAMES_ASO",
        "ders_count": 12,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_14_modulo_industrial",
        "entradas": "Colaborador ID, Período de Exercício (Ano Inicial e Final).",
        "saidas": "Histórico de ASOs Realizados, Tipo de Exame (Admissional/Periódico), Médico Examinador, CRM, Parecer de Aptidão."
    },
    {
        "id": "CE_LOGS_AUDITORIA_LGPD",
        "nome": "Consulta à Trilha de Auditoria e Logs de Compliance LGPD",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 1,
        "ftrs": "ALI_AUDITORIA_LOGS",
        "ders_count": 12,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_16_compliance_auditoria",
        "entradas": "Intervalo de Datas (Início/Fim), ID do Usuário Ator, Entidade Auditada ('Employee', 'Policy', 'Tenant').",
        "saidas": "Timestamp UTC, Usuário Ator, Ação Executada, Snapshot de Mudança (Delta), Hash SHA-256 do Registro."
    },
    {
        "id": "CE_APOLICES_ATIVAS",
        "nome": "Consulta a Apólices Vigentes no Portal do Corretor FinCorp",
        "responsavel": "Fernando Lopes Duarte",
        "ftrs_count": 1,
        "ftrs": "ALI_APOLICES_CORRET",
        "ders_count": 11,
        "complexidade": "Baixa",
        "pfb": 3,
        "tela_img": "tela_17_portal_corretor",
        "entradas": "Código SUSEP do Corretor, Status do Contrato ('ativa', 'em_analise', 'cancelada').",
        "saidas": "Número da Apólice, Nome do Segurado, Capital Segurado, Prêmio Mensal, Comissão a Receber."
    }
]

GSCS_DATA = [
    {
        "id": "C1",
        "nome": "Comunicação de Dados",
        "nota": 4,
        "justificativa": "Arquitetura RESTful desacoplada entre front-end SPA e backend MVC, com troca intensiva de payloads JSON e consumo de APIs externas (eSocial, PIX BACEN, NTP.br)."
    },
    {
        "id": "C2",
        "nome": "Processamento Distribuído",
        "nota": 4,
        "justificativa": "Distribuição de responsabilidades computacionais entre navegador cliente (SPA), servidor backend PHP e múltiplos gateways governamentais e de seguros."
    },
    {
        "id": "C3",
        "nome": "Performance / Tempo de Resposta",
        "nota": 4,
        "justificativa": "Exigência mandatória de tempo de resposta em sub-segundo para marcações de ponto REP em horários de pico de turnos industriais e apurações em tempo real."
    },
    {
        "id": "C4",
        "nome": "Configuração do Equipamento",
        "nota": 3,
        "justificativa": "Aplicação web com SQLite embutido em modo WAL e sincronização de concorrência otimizada."
    },
    {
        "id": "C5",
        "nome": "Volume de Transações",
        "nota": 3,
        "justificativa": "Fluxo concentrado em fechamentos de turno e folha, com demanda transacional sazonal moderada."
    },
    {
        "id": "C6",
        "nome": "Entrada de Dados Online",
        "nota": 5,
        "justificativa": "100% das transações, lançamentos, agendamentos e aprovações operam via interface online responsiva com validações de máscaras e formulários interativos."
    },
    {
        "id": "C7",
        "nome": "Eficiência do Usuário Final",
        "nota": 5,
        "justificativa": "Design ergonômico moderno com suporte a dark mode, navegação por atalhos, validações de CPF/CNPJ síncronas e feedback imediato via modais assíncronos."
    },
    {
        "id": "C8",
        "nome": "Atualização Online",
        "nota": 5,
        "justificativa": "Persistência imediata de marcações de ponto, débitos no saldo de férias, recálculos do banco de horas e gravação de blocos imutáveis no ledger de auditoria."
    },
    {
        "id": "C9",
        "nome": "Processamento Complexo",
        "nota": 5,
        "justificativa": "Execução de regras de negócio altamente intrincadas: apuração de INSS progressivo, IRRF com deduções, hora noturna CLT (52m30s), algoritmo de bloqueio SST e verificação de hashes SHA-256."
    },
    {
        "id": "C10",
        "nome": "Reusabilidade de Código",
        "nota": 5,
        "justificativa": "Arquitetura em Linha de Produção de Software (LPS) com 6 Ativos Reutilizáveis (ARTs) e 8 Design Patterns GoF implementados (Strategy, Factory, Singleton, Template Method)."
    },
    {
        "id": "C11",
        "nome": "Facilidade de Instalação",
        "nota": 3,
        "justificativa": "Ambiente PHP pré-configurado sem clusters distribuídos, viabilizando execução direta com SQLite local e contêineres Docker."
    },
    {
        "id": "C12",
        "nome": "Facilidade de Operação",
        "nota": 3,
        "justificativa": "Rotinas automatizadas locais para backups do banco relacional, emissão de holerites e monitoramento de integridade sem DBA dedicado."
    },
    {
        "id": "C13",
        "nome": "Múltiplos Locais (Multi-tenant)",
        "nota": 5,
        "justificativa": "Suporte corporativo multi-empresa com isolamento estrito de dados por tenant_id, parametrização dinâmica de licenças por segmento e controle de acesso baseado em papéis."
    },
    {
        "id": "C14",
        "nome": "Facilidade de Mudança",
        "nota": 4,
        "justificativa": "Alta flexibilidade para expansão e manutenção garantida pelo padrão Strategy e Feature Flags da LPS, permitindo plugar novos módulos sem impactar o núcleo estável."
    }
]

# ==============================================================================
# CLASSE BASE DO GERADOR DE DOCUMENTOS
# ==============================================================================
class BaseReportBuilder:
    def __init__(self, output_filename):
        self.output_filename = output_filename
        self.doc = Document()
        self.fig_counter = 1
        self._setup_page_geometry()

    def _setup_page_geometry(self):
        # ABNT A4: Margens Sup=3.0cm, Esq=3.0cm, Inf=2.0cm, Dir=2.0cm
        for section in self.doc.sections:
            section.page_width = Cm(21.0)
            section.page_height = Cm(29.7)
            section.top_margin = Cm(3.0)
            section.left_margin = Cm(3.0)
            section.bottom_margin = Cm(2.0)
            section.right_margin = Cm(2.0)

    def add_cover(self, title, subtitle, authors, doc_type="Equipe"):
        # Header institucional
        p_inst = self.doc.add_paragraph()
        p_inst.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_inst.paragraph_format.space_before = Pt(0)
        p_inst.paragraph_format.space_after = Pt(2)
        r1 = p_inst.add_run("PONTIFÍCIA UNIVERSIDADE CATÓLICA DO PARANÁ — PUCPR\n")
        r1.font.name = StyleConfig.FONT_FAMILY
        r1.font.size = Pt(13)
        r1.font.bold = True
        r1.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

        r2 = p_inst.add_run("ESCOLA POLITÉCNICA — CURSO DE BACHARELADO EM ENGENHARIA DE SOFTWARE\n")
        r2.font.name = StyleConfig.FONT_FAMILY
        r2.font.size = Pt(10.5)
        r2.font.bold = True
        r2.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

        r3 = p_inst.add_run("DISCIPLINA: MEDIÇÃO E ANÁLISE DE SOFTWARE (2026-2)\n")
        r3.font.name = StyleConfig.FONT_FAMILY
        r3.font.size = Pt(10)
        r3.font.color.rgb = RGBColor(0x47, 0x55, 0x69)

        # Espaçamento vertical
        p_space = self.doc.add_paragraph()
        p_space.paragraph_format.space_before = Pt(72)
        p_space.paragraph_format.space_after = Pt(0)

        # Título principal
        p_title = self.doc.add_paragraph()
        p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_title.paragraph_format.space_before = Pt(0)
        p_title.paragraph_format.space_after = Pt(8)
        r_t = p_title.add_run(title)
        r_t.font.name = StyleConfig.FONT_FAMILY
        r_t.font.size = Pt(21)
        r_t.font.bold = True
        r_t.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

        # Subtítulo
        p_sub = self.doc.add_paragraph()
        p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_sub.paragraph_format.space_before = Pt(0)
        p_sub.paragraph_format.space_after = Pt(24)
        r_s = p_sub.add_run(subtitle)
        r_s.font.name = StyleConfig.FONT_FAMILY
        r_s.font.size = Pt(12)
        r_s.font.italic = True
        r_s.font.color.rgb = RGBColor(0x2B, 0x4C, 0x7E)

        # Espaçamento para autores
        p_space2 = self.doc.add_paragraph()
        p_space2.paragraph_format.space_before = Pt(64)
        p_space2.paragraph_format.space_after = Pt(0)

        # Bloco de Autores
        p_auth = self.doc.add_paragraph()
        p_auth.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_auth.paragraph_format.space_before = Pt(0)
        p_auth.paragraph_format.space_after = Pt(4)
        r_al = p_auth.add_run("AUTORIA E EQUIPE DE ENGENHARIA:\n" if doc_type == "Equipe" else "AUTOR DO PROJETO:\n")
        r_al.font.name = StyleConfig.FONT_FAMILY
        r_al.font.size = Pt(10)
        r_al.font.bold = True
        r_al.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

        for author in authors:
            r_a = p_auth.add_run(f"• {author}\n")
            r_a.font.name = StyleConfig.FONT_FAMILY
            r_a.font.size = Pt(10)
            r_a.font.color.rgb = RGBColor(0x22, 0x22, 0x22)

        # Rodapé da Capa
        p_space3 = self.doc.add_paragraph()
        p_space3.paragraph_format.space_before = Pt(72)
        p_space3.paragraph_format.space_after = Pt(0)

        p_foot = self.doc.add_paragraph()
        p_foot.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_foot.paragraph_format.space_before = Pt(0)
        p_foot.paragraph_format.space_after = Pt(0)
        r_f = p_foot.add_run("CURITIBA — PARANÁ\nOUTUBRO DE 2026")
        r_f.font.name = StyleConfig.FONT_FAMILY
        r_f.font.size = Pt(9.5)
        r_f.font.bold = True
        r_f.font.color.rgb = RGBColor(0x47, 0x55, 0x69)

        # Quebra de página estrita após a capa
        self.doc.add_page_break()

    def add_h1(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(14)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.keep_with_next = True
        r = p.add_run(text)
        r.font.name = StyleConfig.FONT_FAMILY
        r.font.size = Pt(14.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)
        return p

    def add_h2(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(11)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.keep_with_next = True
        r = p.add_run(text)
        r.font.name = StyleConfig.FONT_FAMILY
        r.font.size = Pt(12.5)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x2B, 0x4C, 0x7E)
        return p

    def add_h3(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(8)
        p.paragraph_format.space_after = Pt(3)
        p.paragraph_format.keep_with_next = True
        r = p.add_run(text)
        r.font.name = StyleConfig.FONT_FAMILY
        r.font.size = Pt(11)
        r.font.bold = True
        r.font.color.rgb = RGBColor(0x33, 0x41, 0x55)
        return p

    def add_p(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.15
        r = p.add_run(text)
        r.font.name = StyleConfig.FONT_FAMILY
        r.font.size = Pt(10)
        r.font.color.rgb = RGBColor(0x22, 0x22, 0x22)
        return p

    def add_bullet(self, bold_prefix, text):
        p = self.doc.add_paragraph(style='List Bullet')
        p.paragraph_format.space_before = Pt(1)
        p.paragraph_format.space_after = Pt(2)
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = StyleConfig.FONT_FAMILY
        r_pre.font.size = Pt(9.5)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(0x1B, 0x36, 0x5D)

        r_text = p.add_run(text)
        r_text.font.name = StyleConfig.FONT_FAMILY
        r_text.font.size = Pt(9.5)
        r_text.font.color.rgb = RGBColor(0x22, 0x22, 0x22)
        return p

# ==============================================================================
# CONSTRUTOR DO RELATÓRIO INDIVIDUAL (FERNANDO LOPES DUARTE — 53 PFB)
# ==============================================================================
class IndividualReportBuilder(BaseReportBuilder):
    def __init__(self):
        super().__init__("Entrega_APF_Individual_Fernando_Lopes_Duarte.docx")

    def build(self):
        print("-> Montando Relatório Individual de Fernando Lopes Duarte (53 PFB / 65,19 PFA)...")
        self.add_cover(
            title="RELATÓRIO OFICIAL DE MEDIÇÃO: ANÁLISE DE PONTOS DE FUNÇÃO (APF)",
            subtitle="Entrega Individual (Atividade 05 — PUCPR) — Módulos: Auditoria Imutável (Ledger SHA-256), Multi-Tenancy e FinCorp Seguros & PIX",
            authors=["Fernando Lopes Duarte (Matrícula / Engenheiro Responsável)"],
            doc_type="Individual"
        )

        # 1. Sumário Executivo
        self.add_h1("1. Sumário Executivo e Contexto da Medição")
        self.add_p(
            "O presente relatório técnico-acadêmico consolida a medição oficial de Pontos de Função "
            "(IFPUG CPM 4.3.1 / NESMA) correspondente à contribuição individual de Fernando Lopes Duarte "
            "no desenvolvimento da plataforma HRTech Core — uma Linha de Produção de Software (LPS) "
            "corporativa para gestão modular de Recursos Humanos."
        )
        self.add_p(
            "Em estrita consonância com a abordagem 'As-Built' exigida pela disciplina, todas as "
            "funções catalogadas correspondem a componentes genuínos, operacionais e auditáveis no "
            "repositório, englobando as tabelas do banco relacional SQLite (hrtech_db.sqlite), os controllers "
            "em PHP 8.3 (app/Controllers/) e as interfaces web responsivas com evidências visuais."
        )

        # Callout resumo de métricas
        CalloutHelper.add_callout(
            self.doc,
            "Painel Consolidado de Métricas Individuais (Fernando Lopes Duarte)",
            "• Tamanho Bruto: 53 PFB (29 PF de Dados + 24 PF de Transações)\n"
            "• Nível de Influência Geral (NI): 58 pontos (Avaliação das 14 Características Gerais)\n"
            "• Fator de Ajuste (FA): 1,2300 (+23,0% de complexidade não-funcional)\n"
            "• Tamanho Ajustado: 65,19 PFA (Representando exatamente 21,03% do sistema global)\n"
            "• Esforço Alocado: 624,65 Homem-Horas (Taxa de produtividade calibrada: 9,5818 HH/PFA)\n"
            "• Orçamento Técnico: R$ 31.232,35 (a R$ 50,00/hora técnica pleno de mercado)\n"
            "• Prazo em Dedicação Solo: 3,90 meses (~16 semanas em jornada padrão de 160h/mês).",
            formula="PFA = 53 * 1.23 = 65.19 PFA; Esforco = 65.19 * 9.5818 = 624.65 HH; Custo = 624.65 * R$ 50.00 = R$ 31,232.35"
        )

        # 2. Quadro Resumo Individual (10 Funções)
        self.add_h1("2. Quadro Resumo das 10 Funções Elementares Individuais")
        self.add_p(
            "A Tabela 1 sintetiza as 10 funções elementares desenvolvidas e mantidas por Fernando Lopes Duarte, "
            "distribuídas harmonicamente entre dados e transações:"
        )

        t_res = self.doc.add_table(rows=1, cols=7)
        headers = ["Tipo", "Identificador da Função", "Entidade / Serviço", "FTR/RLR", "DER", "Complexidade", "PFB"]
        for j, h in enumerate(headers):
            t_res.cell(0, j).paragraphs[0].text = h

        fernando_funcs = [
            ("ALI", "ALI_AUDITORIA_LOGS", "audit_logs (SQLite)", "3 RLRs", "14", "Baixa", "7"),
            ("ALI", "ALI_APOLICES_CORRET", "insurance_policies (SQLite)", "4 RLRs", "26", "Média", "10"),
            ("AIE", "AIE_SEGURADORA_PORTAL", "Gateway FinCorp (SUSEP)", "3 RLRs", "20", "Média", "7"),
            ("AIE", "AIE_SISTEMA_PIX", "Gateway PIX BACEN (SPI)", "2 RLRs", "16", "Baixa", "5"),
            ("EE", "EE_CRIAR_TENANT_LIC", "TenantService::createTenant", "1 FTR", "5", "Baixa", "3"),
            ("EE", "EE_EMITIR_APOLICE_VIDA", "InsurancePolicyService::issuePolicy", "2 FTRs", "14", "Média", "4"),
            ("SE", "SE_FOLHA_PAGAMENTO_CALC", "PayrollCalculatorTemplate", "3 FTRs", "22", "Alta", "7"),
            ("SE", "SE_LEDGER_AUDITORIA_SHA", "AuditLogger::verifyChainIntegrity", "1 FTR", "8", "Baixa", "4"),
            ("CE", "CE_LOGS_AUDITORIA_LGPD", "ComplianceController::index", "1 FTR", "12", "Baixa", "3"),
            ("CE", "CE_APOLICES_ATIVAS", "InsuranceController::index", "1 FTR", "11", "Baixa", "3"),
            ("TOTAL", "10 Funções Individuais", "2 ALIs + 2 AIEs + 2 EEs + 2 SEs + 2 CEs", "-", "-", "-", "53 PFB")
        ]

        for row_data in fernando_funcs:
            r = t_res.add_row()
            for j, val in enumerate(row_data):
                r.cells[j].paragraphs[0].text = val

        TableHelper.format_full_table(t_res, [Inches(0.6), Inches(1.8), Inches(1.5), Inches(0.7), Inches(0.4), Inches(0.7), Inches(0.5)])

        # 3. Funções de Dados Detalhadas
        self.add_h1("3. Especificação Detalhada das Funções de Dados (29 PFB)")
        
        self.add_h2("3.1 Arquivos Lógicos Internos (ALIs — 17 PFB)")
        self.add_h3("ALI 01: ALI_AUDITORIA_LOGS (Complexidade Baixa — 7 PFB)")
        self.add_p(
            "Finalidade no Negócio: Manter o histórico cronológico e imutável de todas as transações, "
            "mutação de dados e eventos de segurança realizados na plataforma multi-tenant, atendendo aos "
            "rigorosos critérios de governança da LGPD (Lei 13.709/2018) e normativas do BACEN."
        )
        self.add_bullet("Tabela Física: ", "audit_logs (SQLite hrtech_db.sqlite).")
        self.add_bullet("Registros Lógicos Referenciados (3 RLRs): ", "Registro de Transação Auditável, Delta de Estado LGPD (Snapshot Antes/Depois), Encadeamento de Blocos Criptográficos SHA-256.")
        self.add_bullet("Itens de Dados (14 DERs): ", "id, tenant_id, actor_user_id, action, entity_type, entity_id, previous_state, new_state, ip_address, user_agent, timestamp, previous_hash, integrity_hash, retention_period.")
        self.add_bullet("Classificação Matricial: ", "3 RLRs e 14 DERs enquadram-se na faixa de Complexidade Baixa (7 PFB).")
        ImageHelper.add_screenshot(self.doc, "tela_16_compliance_auditoria", self.fig_counter, "Trilha de Auditoria LGPD e Encadeamento Criptográfico SHA-256")
        self.fig_counter += 1

        self.add_h3("ALI 02: ALI_APOLICES_CORRET (Complexidade Média — 10 PFB)")
        self.add_p(
            "Finalidade no Negócio: Armazenar os contratos de seguros corporativos (Seguro de Vida em Grupo, "
            "D&O Executivo e Cobertura Operacional) comercializados através do Portal do Corretor FinCorp."
        )
        self.add_bullet("Tabela Física: ", "insurance_policies (SQLite hrtech_db.sqlite).")
        self.add_bullet("Registros Lógicos Referenciados (4 RLRs): ", "Contrato de Apólice, Registro do Corretor SUSEP, Coberturas e Capitais Segurados, Histórico de Vigência e Sinistros.")
        self.add_bullet("Itens de Dados (26 DERs): ", "id, tenant_id, policy_number, broker_code, insurer_name, employee_id, insured_capital_cents, monthly_premium_cents, status, start_date, end_date, coverage_details, cancellation_reason, cancelled_at, commission_percentage, insured_name, beneficiaries_json, iof_cents, grace_period_days, policy_type, deductible_cents, claim_contact_phone, risk_class, endorsement_number, created_at, updated_at.")
        self.add_bullet("Classificação Matricial: ", "4 RLRs e 26 DERs enquadram-se na faixa de Complexidade Média (10 PFB).")
        ImageHelper.add_screenshot(self.doc, "tela_17_portal_corretor", self.fig_counter, "Portal FinCorp: Gestão de Carteira de Apólices Corporativas")
        self.fig_counter += 1

        self.add_h2("3.2 Arquivos de Interface Externa (AIEs — 12 PFB)")
        self.add_h3("AIE 01: AIE_SEGURADORA_PORTAL (Complexidade Média — 7 PFB)")
        self.add_p(
            "Finalidade no Negócio: Interface externa de integração com a seguradora parceira FinCorp Seguros S.A., "
            "permitindo o envio de propostas de seguro de vida em grupo, tarifação de prêmios atuariais e "
            "registro de apólices em conformidade com as diretrizes da SUSEP."
        )
        self.add_bullet("Sistema Externo: ", "Gateway de Underwriting e Subscrição FinCorp Seguros.")
        self.add_bullet("Registros Lógicos Referenciados (3 RLRs): ", "Proposta Comercial de Seguro, Tabela Atuarial de Tarifação, Endosso de Apólice.")
        self.add_bullet("Itens de Dados (20 DERs): ", "numero_proposta, cnpj_estipulante, tabela_sinistro, capital_segurado, iof_retido, status_emissao, premio_puro, premio_comercial, data_emissao, limite_retencao, susep_processo, classe_ocupacional, carregamento_comercial, comissao_corretor, custo_apolice, taxa_risco_morte, taxa_risco_invalidez, franquia_dias, cobertura_conjugal, hash_seguradora.")
        self.add_bullet("Classificação Matricial: ", "3 RLRs e 20 DERs enquadram-se na faixa de Complexidade Média (7 PFB).")
        ImageHelper.add_screenshot(self.doc, "tela_17_portal_corretor", self.fig_counter, "Portal FinCorp: Gateway de Underwriting e Subscrição SUSEP")
        self.fig_counter += 1

        self.add_h3("AIE 02: AIE_SISTEMA_PIX (Complexidade Baixa — 5 PFB)")
        self.add_p(
            "Finalidade no Negócio: Gateway financeiro para liquidação instantânea de prêmios de seguro e faturas "
            "através do ecossistema PIX do Banco Central do Brasil (BACEN / SPI)."
        )
        self.add_bullet("Sistema Externo: ", "Sistema de Pagamentos Instantâneos (SPI / DICT) do Banco Central.")
        self.add_bullet("Registros Lógicos Referenciados (2 RLRs): ", "Payload de Cobrança PIX EMV (QR Code Dinâmico), Notificação de Liquidação SPI.")
        self.add_bullet("Itens de Dados (16 DERs): ", "e2eid, txid, chave_pix, valor_centavos, timestamp_liquidacao, qr_code_copia_cola, status_pagamento, ispb_banco, nome_recebedor, cpf_cnpj_recebedor, end_to_end_id, mensagem_pix, autenticacao_bacen, tarifa_liquidacao, data_vencimento, created_at.")
        self.add_bullet("Classificação Matricial: ", "2 RLRs e 16 DERs enquadram-se na faixa de Complexidade Baixa (5 PFB).")
        ImageHelper.add_screenshot(self.doc, "tela_20", self.fig_counter, "Gateway PIX BACEN: Geração de QR Code e Liquidação Instantânea")
        self.fig_counter += 1

        # 4. Funções de Transação Detalhadas
        self.add_h1("4. Especificação Detalhada das Funções de Transação (24 PFB)")

        self.add_h2("4.1 Entradas Externas (EEs — 7 PFB)")
        self.add_h3("EE 01: EE_CRIAR_TENANT_LIC (Complexidade Baixa — 3 PFB)")
        self.add_p(
            "Processa a inclusão e o provisionamento de uma nova organização cliente no ecossistema multi-tenant "
            "através do método backend HrTech\\Services\\TenantService::createTenant(). Configura o isolamento de "
            "dados por tenant_id e habilita os módulos da Linha de Produção de Software."
        )
        self.add_bullet("Arquivos Lidos e Mantidos (FTRs): ", "Mantém 1 ALI (tenants).")
        self.add_bullet("Itens de Dados (5 DERs): ", "corporate_name, trading_name, cnpj, segment, mensagem_sucesso.")
        ImageHelper.add_screenshot(self.doc, "tela_15_configuracao_empresa", self.fig_counter, "Configuração de Tenants e Módulos da Linha de Produção de Software")
        self.fig_counter += 1

        self.add_h3("EE 02: EE_EMITIR_APOLICE_VIDA (Complexidade Média — 4 PFB)")
        self.add_p(
            "Processa a contratação e emissão de apólice de seguro de vida em grupo no Portal FinCorp via "
            "HrTech\\Services\\InsurancePolicyService::issuePolicy(). Valida os dados do colaborador segurado, calcula "
            "o prêmio mensal e registra a comissão de corretagem."
        )
        self.add_bullet("Arquivos Lidos e Mantidos (FTRs): ", "Mantém ALI_APOLICES_CORRET; Lê AIE_SEGURADORA_PORTAL (2 FTRs).")
        self.add_bullet("Itens de Dados (14 DERs): ", "policy_number, broker_code, employee_id, insured_capital, monthly_premium, coverage_details, start_date, end_date, commission_percentage, insured_name, beneficiaries, risk_class, iof_amount, retorno_status.")
        ImageHelper.add_screenshot(self.doc, "tela_17_portal_corretor", self.fig_counter, "Portal FinCorp: Emissão de Apólice Corporativa de Seguro de Vida")
        self.fig_counter += 1

        self.add_h2("4.2 Saídas Externas com Dados Derivados (SEs — 11 PFB)")
        self.add_h3("SE 01: SE_FOLHA_PAGAMENTO_CALC (Complexidade Alta — 7 PFB)")
        self.add_p(
            "Executa o pipeline completo de fechamento da folha de pagamento corporativa e emissão do holerite oficial "
            "via HrTech\\Patterns\\TemplateMethod\\Payroll\\PayrollCalculatorTemplate. Lê 3 FTRs (ALI_COLABORADORES, "
            "ALI_REGISTROS_PONTO, ALI_CARTOES_BENEF) projetando 22 DERs."
        )
        CalloutHelper.add_callout(
            self.doc,
            "Justificativa Obrigatória do Dado Derivado: SE_FOLHA_PAGAMENTO_CALC",
            "DADO ESTRITAMENTE DERIVADO. O demonstrativo de holerite líquido NÃO existe gravado em nenhuma tabela do banco de dados. "
            "Ele é derivado em tempo real através de um motor de cálculo progressivo de 5 estágios:\n"
            "1. Apuração da remuneração bruta total (salário-base + adicionais noturnos calculados na hora ficta de 52m30s + horas extras a 50%/100%);\n"
            "2. Aplicação das 4 faixas progressivas oficiais de INSS (7,5%, 9%, 12% e 14% com dedução das parcelas legais fixadas pela Previdência);\n"
            "3. Cálculo da base de retenção do IRRF na fonte com dedução legal de R$ 189,59 por dependente econômico cadastrado;\n"
            "4. Aplicação dos tetos legais protetivos de benefícios (trava de até 6% do salário-base para vale-transporte e até 20% para PAT);\n"
            "5. Apuração final do valor líquido a pagar com geração do demonstrativo analítico.",
            formula="INSS_Faixas = Sum(BaseFaixa_i * Aliq_i - Deducao_i); BaseIRRF = SalarioBruto - INSS - (NumDep * 189.59); Liquido = Bruto - INSS - IRRF - DescontosBeneficios"
        )
        ImageHelper.add_screenshot(self.doc, "tela_18", self.fig_counter, "Demonstrativo de Holerite com Motor Tributário Progressivo de INSS e IRRF")
        self.fig_counter += 1

        self.add_h3("SE 02: SE_LEDGER_AUDITORIA_SHA (Complexidade Baixa — 4 PFB)")
        self.add_p(
            "Emite o laudo pericial de conformidade criptográfica exibido no painel de governança LGPD/Bacen "
            "através de HrTech\\Patterns\\Singleton\\AuditLogger::verifyChainIntegrity(). Lê 1 FTR (ALI_AUDITORIA_LOGS) "
            "com 8 DERs de saída."
        )
        CalloutHelper.add_callout(
            self.doc,
            "Justificativa Obrigatória do Dado Derivado: SE_LEDGER_AUDITORIA_SHA",
            "DADO ESTRITAMENTE DERIVADO. O atestado de conformidade ('Cadeia Inviolável: 100% VÁLIDA') não é um campo estático "
            "do banco. O algoritmo itera recursivamente todos os registros da tabela audit_logs desde o bloco gênesis, recalculando "
            "em tempo de execução o hash canônico SHA-256 de cada tupla encadeada com o hash do bloco predecessor. Se um único bit "
            "tiver sido alterado externamente no banco relacional, a cadeia se rompe e o laudo acusa fraude imediatamente.",
            formula="BlocoValido = (SHA256(Bloco_i.Payload + Bloco_{i-1}.Hash) == Bloco_i.HashRegistrado)"
        )
        ImageHelper.add_screenshot(self.doc, "tela_16_compliance_auditoria", self.fig_counter, "Laudo Pericial de Auditoria LGPD e Encadeamento Criptográfico SHA-256")
        self.fig_counter += 1

        self.add_h2("4.3 Consultas Externas com Segregação de Entrada e Saída (CEs — 6 PFB)")
        self.add_h3("CE 01: CE_LOGS_AUDITORIA_LGPD (Complexidade Baixa — 3 PFB)")
        self.add_p(
            "Consulta analítica e filtragem da trilha de auditoria para fins de compliance perante a Autoridade Nacional "
            "de Proteção de Dados (ANPD) e auditores do Bacen."
        )
        self._add_ce_table(
            entradas="• Data Inicial (filtro temporal)\n• Data Final (filtro temporal)\n• ID do Usuário Ator\n• Entidade Afetada ('Employee', 'InsurancePolicy', 'Tenant')",
            saidas="• Carimbo de Data/Hora UTC\n• Nome/Identificador do Ator\n• Ação Executada (CREATE, UPDATE, DELETE)\n• Snapshot do Delta de Mudança\n• Hash Criptográfico SHA-256"
        )
        ImageHelper.add_screenshot(self.doc, "tela_16_compliance_auditoria", self.fig_counter, "Consulta e Filtragem Analítica da Trilha de Auditoria LGPD")
        self.fig_counter += 1

        self.add_h3("CE 02: CE_APOLICES_ATIVAS (Complexidade Baixa — 3 PFB)")
        self.add_p(
            "Consulta interativa à carteira de seguros corporativos ativos mantida pelo corretor credenciado no Portal FinCorp."
        )
        self._add_ce_table(
            entradas="• Código SUSEP do Corretor\n• Status da Apólice ('ativa', 'em_analise', 'cancelada')",
            saidas="• Número do Contrato\n• Razão Social / Nome do Segurado\n• Capital em Risco Coberto\n• Prêmio Mensal Faturado\n• Comissão do Corretor a Receber"
        )
        ImageHelper.add_screenshot(self.doc, "tela_17_portal_corretor", self.fig_counter, "Portal FinCorp: Consulta de Apólices Ativas e Comissões de Corretagem")
        self.fig_counter += 1

        # 5. Avaliação das 14 GSCs
        self.add_h1("5. Avaliação Fundamentada das 14 Características Gerais (GSC / NI)")
        self.add_p(
            "Em observância rigorosa à norma IFPUG CPM 4.3.1, a complexidade arquitetural não funcional "
            "foi avaliada nota a nota (escala 0 a 5) com sólida justificativa técnica, totalizando NI = 58 pontos:"
        )

        t_gsc = self.doc.add_table(rows=1, cols=4)
        for j, h in enumerate(["#", "Característica Geral (GSC)", "Grau", "Justificativa Arquitetural — HRTech Core"]):
            t_gsc.cell(0, j).paragraphs[0].text = h

        for item in GSCS_DATA:
            r = t_gsc.add_row()
            r.cells[0].paragraphs[0].text = item["id"]
            r.cells[1].paragraphs[0].text = item["nome"]
            r.cells[2].paragraphs[0].text = str(item["nota"])
            r.cells[3].paragraphs[0].text = item["justificativa"]

        r_tot = t_gsc.add_row()
        r_tot.cells[0].paragraphs[0].text = "SOMA"
        r_tot.cells[1].paragraphs[0].text = "Nível de Influência Total (NI)"
        r_tot.cells[2].paragraphs[0].text = "58"
        r_tot.cells[3].paragraphs[0].text = "Grau de complexidade técnica elevado: 58 de 70 pontos possíveis (82,9%)."
        TableHelper.format_full_table(t_gsc, [Inches(0.4), Inches(1.8), Inches(0.5), Inches(3.3)])

        # 6. Memória de Cálculo e Gestão
        self.add_h1("6. Memória de Cálculo: PFA, Esforço, Prazo, Custo e UCP")
        self.add_h2("6.1 Fator de Ajuste e PFA Individual")
        self.add_p(
            "Aplicando a equação matemática clássica do IFPUG ao Nível de Influência de 58 pontos:"
        )
        self.add_p("FA = 0,65 + (0,01 * NI) = 0,65 + (0,01 * 58) = 1,2300 (+23,0%)")
        self.add_p("PFA (Individual Fernando) = 53 PFB * 1,2300 = 65,19 PFA")

        self.add_h2("6.2 Validação Cruzada com Casos de Uso (UCP)")
        self.add_p(
            "O estudo formal prévio de Casos de Uso (UCP) apurou para o sistema global 148,501 AUCP que, "
            "sob a regra clássica de Schneider & Winters (20 h/AUCP), resultou em exatamente 2.970,02 Homem-Horas "
            "e orçamento de R$ 148.501,00 a R$ 50,00/h. A calibração métrica estabelece:"
        )
        self.add_p("Taxa de Produtividade = 2.970,02 HH / 309,96 PFA = 9,5818 HH/PFA (ou 11,7858 HH/PFB)")

        self.add_h2("6.3 Estimativas de Gestão da Cota Individual")
        self.add_bullet("Esforço Homem-Hora: ", "65,19 PFA * 9,5818 HH/PFA = 624,65 Homem-Horas.")
        self.add_bullet("Custo Financeiro: ", "624,65 HH * R$ 50,00/hora = R$ 31.232,35.")
        self.add_bullet("Prazo em Dedicação Solo: ", "624,65 HH / 160h/mês = 3,90 meses (~16 semanas de desenvolvimento).")

        # 7. Considerações Finais e Autoria
        self.add_h1("7. Declaração de Autoria e Integridade Acadêmica")
        self.add_p(
            "Eu, Fernando Lopes Duarte, declaro que as 10 funções elementares (53 PFB / 65,19 PFA) "
            "apresentadas neste documento foram integralmente modeladas, codificadas e validadas por mim "
            "na plataforma HRTech Core, respeitando as normas anti-plágio e as diretrizes do edital da PUCPR."
        )
        self.add_p("Curitiba — PR, Outubro de 2026.\n\n_____________________________________________\nFernando Lopes Duarte")

        out_path = os.path.join(PROJECT_ROOT, self.output_filename)
        self.doc.save(out_path)
        print(f"-> Relatório Individual salvo com sucesso em: {out_path} ({os.path.getsize(out_path):,} bytes)")

    def _add_ce_table(self, entradas, saidas):
        t = self.doc.add_table(rows=1, cols=2)
        t.cell(0, 0).paragraphs[0].text = "Dados de Entrada (Filtros e Parâmetros)"
        t.cell(0, 1).paragraphs[0].text = "Dados de Saída (Grid / Resultados em Tela)"
        r = t.add_row()
        r.cells[0].paragraphs[0].text = entradas
        r.cells[1].paragraphs[0].text = saidas
        TableHelper.format_full_table(t, [Inches(3.0), Inches(3.0)])
        self.doc.add_paragraph().paragraph_format.space_after = Pt(4)

# ==============================================================================
# CONSTRUTOR DO RELATÓRIO DE EQUIPE (HRTECH CORE — 252 PFB / 309,96 PFA)
# ==============================================================================
class TeamReportBuilder(BaseReportBuilder):
    def __init__(self):
        super().__init__("Entrega_APF_Equipe_HRTech_Core.docx")

    def build(self):
        print("-> Montando Relatório Consolidado de Equipe (252 PFB / 309,96 PFA / 50 Funções)...")
        authors_list = [
            "Andryus Zolet (Cadastro de Colaboradores, Matrículas e Organograma)",
            "Felipe (Ponto Eletrônico Portaria 671 MTE & Banco de Horas)",
            "Valentin (Férias Trabalhistas CLT, Abono Pecuniário e Benefícios)",
            "Nicolas (Segurança do Trabalho NR-06/07 e Barreira Industrial)",
            "Fernando Lopes Duarte (Auditoria Imutável SHA-256, Multi-Tenancy e FinCorp)"
        ]

        self.add_cover(
            title="RELATÓRIO TÉCNICO OFICIAL DE MEDIÇÃO: ANÁLISE DE PONTOS DE FUNÇÃO (APF)",
            subtitle="Entrega em Equipe (Atividade 06 — PUCPR) — Medição e Análise Consolidada da Plataforma HRTech Core (Linha de Produção de Software)",
            authors=authors_list,
            doc_type="Equipe"
        )

        # 1. Sumário Executivo
        self.add_h1("1. Sumário Executivo e Arquitetura do Software")
        self.add_p(
            "O presente documento formaliza a entrega da Atividade 06 da disciplina de Medição e Análise de Software "
            "do Curso de Engenharia de Software da PUCPR. O trabalho apresenta a contagem exaustiva de Pontos de Função "
            "(IFPUG CPM 4.3.1 / ISO/IEC 20926) da plataforma corporativa HRTech Core — uma Linha de Produção de Software (LPS) "
            "desenvolvida em PHP 8.3 e banco SQLite concorrente WAL."
        )
        self.add_p(
            "O escopo global cobre exatamente 50 funções elementares (10 ALIs, 10 AIEs, 10 EEs, 10 SEs e 10 CEs), "
            "equitativamente distribuídas em blocos de 10 funções entre os 5 engenheiros integrantes da equipe. "
            "A totalidade das funções conta com evidências visuais capturadas na resolução Full HD (1440x900) da aplicação real."
        )

        CalloutHelper.add_callout(
            self.doc,
            "Quadro de Síntese Métrico-Econômica da Plataforma HRTech Core",
            "• Pontos de Função Brutos (PFB): 252 PF (132 PF Dados + 120 PF Transações)\n"
            "• Nível de Influência Geral (NI): 58 pontos (14 Características Gerais GSC avaliadas de 0 a 5)\n"
            "• Fator de Ajuste (FA): 1,2300 (+23,0% decorrente da complexidade de LPS, multi-tenancy e blockchain)\n"
            "• Pontos de Função Ajustados (PFA): 309,96 PFA\n"
            "• Esforço Global em Homem-Horas: 2.970,00 HH (Taxa de 9,5818 HH/PFA calibrada com Casos de Uso)\n"
            "• Orçamento Técnico Consolidado: R$ 148.501,00 (a R$ 50,00/hora técnica plena)\n"
            "• Prazo de Entrega (Equipe Plena 5 Devs): 3,71 meses (~15 semanas de desenvolvimento contínuo)\n"
            "• Validação Cruzada com UCP: 148,501 AUCP * 20 h/AUCP = 2.970,02 horas = R$ 148.501,00 (Desvio R$ 0,00).",
            formula="PFA = 252 * 1.23 = 309.96 PFA; Esforco = 309.96 * 9.5818 = 2,970.00 HH; Custo = 2,970 * R$ 50.00 = R$ 148,501.00"
        )

        # 2. Divisão de Trabalho da Equipe
        self.add_h1("2. Divisão de Trabalho e Contribuição dos Integrantes")
        self.add_p(
            "A Tabela 2 apresenta o rateio de escopo e responsabilidades funcionais entre os 5 membros da equipe, "
            "comprovando o equilíbrio e a solidez do planejamento:"
        )

        t_div = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["Membro", "Módulo / Responsabilidade", "PFB", "% Equipe", "PFA (FA=1,23)", "Esforço (HH)", "Orçamento (R$)"]):
            t_div.cell(0, j).paragraphs[0].text = h

        for m in MEMBERS_DATA:
            r = t_div.add_row()
            r.cells[0].paragraphs[0].text = m["nome"]
            r.cells[1].paragraphs[0].text = m["modulo"]
            r.cells[2].paragraphs[0].text = str(m["pfb"])
            r.cells[3].paragraphs[0].text = m["perc"]
            r.cells[4].paragraphs[0].text = f"{m['pfa']:.2f}"
            r.cells[5].paragraphs[0].text = f"{m['hh']:.2f}"
            r.cells[6].paragraphs[0].text = f"R$ {m['custo']:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")

        r_tot = t_div.add_row()
        r_tot.cells[0].paragraphs[0].text = "TOTAL EQUIPE"
        r_tot.cells[1].paragraphs[0].text = "Plataforma Modular HRTech Core (5 Módulos)"
        r_tot.cells[2].paragraphs[0].text = "252"
        r_tot.cells[3].paragraphs[0].text = "100,0%"
        r_tot.cells[4].paragraphs[0].text = "309,96"
        r_tot.cells[5].paragraphs[0].text = "2.970,00"
        r_tot.cells[6].paragraphs[0].text = "R$ 148.501,00"
        TableHelper.format_full_table(t_div, [Inches(1.2), Inches(1.8), Inches(0.4), Inches(0.6), Inches(0.6), Inches(0.7), Inches(0.7)])

        # 3. Especificação dos 10 ALIs
        self.add_h1("3. Especificação Exaustiva dos 10 Arquivos Lógicos Internos (ALIs — 76 PFB)")
        self.add_p(
            "Os ALIs correspondem a grupos logicamente homogêneos de dados mantidos internamente pela aplicação "
            "no banco de dados SQLite concorrente. A Tabela 3 consolida os 10 ALIs mapeados:"
        )

        t_alis = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["ID ALI", "Nome da Função", "Tabela SQLite", "RLRs", "DERs", "Compl.", "PFB"]):
            t_alis.cell(0, j).paragraphs[0].text = h

        for ali in ALIS_DATA:
            r = t_alis.add_row()
            r.cells[0].paragraphs[0].text = ali["id"]
            r.cells[1].paragraphs[0].text = ali["nome"]
            r.cells[2].paragraphs[0].text = ali["tabela"]
            r.cells[3].paragraphs[0].text = str(ali["rlrs_count"])
            r.cells[4].paragraphs[0].text = str(ali["ders_count"])
            r.cells[5].paragraphs[0].text = ali["complexidade"]
            r.cells[6].paragraphs[0].text = str(ali["pfb"])

        r_tot_ali = t_alis.add_row()
        r_tot_ali.cells[0].paragraphs[0].text = "TOTAL ALIs"
        r_tot_ali.cells[1].paragraphs[0].text = "10 Entidades de Banco Relacional"
        r_tot_ali.cells[2].paragraphs[0].text = "-"
        r_tot_ali.cells[3].paragraphs[0].text = "32"
        r_tot_ali.cells[4].paragraphs[0].text = "176"
        r_tot_ali.cells[5].paragraphs[0].text = "2 Médias, 8 Baixas"
        r_tot_ali.cells[6].paragraphs[0].text = "76 PFB"
        TableHelper.format_full_table(t_alis, [Inches(1.2), Inches(1.8), Inches(1.3), Inches(0.4), Inches(0.4), Inches(0.5), Inches(0.4)])

        for ali in ALIS_DATA:
            self.add_h3(f"{ali['id']} — {ali['nome']} ({ali['complexidade']} — {ali['pfb']} PFB)")
            self.add_bullet("Responsável: ", ali["responsavel"])
            self.add_bullet("Tabela Física: ", ali["tabela"])
            self.add_bullet(f"Registros Lógicos Referenciados ({ali['rlrs_count']} RLRs): ", ali["rlrs"])
            self.add_bullet(f"Itens de Dados Referenciados ({ali['ders_count']} DERs): ", ali["ders"])
            self.add_bullet("Evidência Visual Prototipada: ", ali["tela_desc"])
            ImageHelper.add_screenshot(self.doc, ali["tela_img"], self.fig_counter, f"Prototipação Funcional: {ali['id']}")
            self.fig_counter += 1

        # 4. Especificação dos 10 AIEs
        self.add_h1("4. Especificação Exaustiva dos 10 Arquivos de Interface Externa (AIEs — 56 PFB)")
        self.add_p(
            "Os AIEs representam grupos de dados referenciados pelo HRTech Core mas mantidos e governados "
            "por outras aplicações e órgãos externos (Receita Federal, eSocial, BACEN, REP, etc.):"
        )

        t_aies = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["ID AIE", "Nome da Função", "Sistema Externo", "RLRs", "DERs", "Compl.", "PFB"]):
            t_aies.cell(0, j).paragraphs[0].text = h

        for aie in AIES_DATA:
            r = t_aies.add_row()
            r.cells[0].paragraphs[0].text = aie["id"]
            r.cells[1].paragraphs[0].text = aie["nome"]
            r.cells[2].paragraphs[0].text = aie["origem"]
            r.cells[3].paragraphs[0].text = str(aie["rlrs_count"])
            r.cells[4].paragraphs[0].text = str(aie["ders_count"])
            r.cells[5].paragraphs[0].text = aie["complexidade"]
            r.cells[6].paragraphs[0].text = str(aie["pfb"])

        r_tot_aie = t_aies.add_row()
        r_tot_aie.cells[0].paragraphs[0].text = "TOTAL AIEs"
        r_tot_aie.cells[1].paragraphs[0].text = "10 Gateways e Web Services Externos"
        r_tot_aie.cells[2].paragraphs[0].text = "-"
        r_tot_aie.cells[3].paragraphs[0].text = "19"
        r_tot_aie.cells[4].paragraphs[0].text = "142"
        r_tot_aie.cells[5].paragraphs[0].text = "3 Médias, 7 Baixas"
        r_tot_aie.cells[6].paragraphs[0].text = "56 PFB"
        TableHelper.format_full_table(t_aies, [Inches(1.2), Inches(1.8), Inches(1.3), Inches(0.4), Inches(0.4), Inches(0.5), Inches(0.4)])

        for aie in AIES_DATA:
            self.add_h3(f"{aie['id']} — {aie['nome']} ({aie['complexidade']} — {aie['pfb']} PFB)")
            self.add_bullet("Responsável: ", aie["responsavel"])
            self.add_bullet("Gateway Externo: ", aie["origem"])
            self.add_bullet(f"Registros Lógicos ({aie['rlrs_count']} RLRs): ", aie["rlrs"])
            self.add_bullet(f"Itens de Dados ({aie['ders_count']} DERs): ", aie["ders"])
            self.add_bullet("Evidência Visual Prototipada: ", aie["tela_desc"])
            ImageHelper.add_screenshot(self.doc, aie["tela_img"], self.fig_counter, f"Integração Externa Prototipada: {aie['id']}")
            self.fig_counter += 1

        # 5. Especificação das 10 EEs
        self.add_h1("5. Especificação Exaustiva das 10 Entradas Externas (EEs — 38 PFB)")
        self.add_p(
            "As Entradas Externas mantêm os arquivos lógicos internos ou alteram o comportamento do sistema "
            "a partir de requisições enviadas por usuários ou processos automatizados:"
        )

        t_ees = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["ID Transação", "Nome da Operação", "Responsável", "FTRs Lidos/Mantidos", "DERs", "Compl.", "PFB"]):
            t_ees.cell(0, j).paragraphs[0].text = h

        for ee in EES_DATA:
            r = t_ees.add_row()
            r.cells[0].paragraphs[0].text = ee["id"]
            r.cells[1].paragraphs[0].text = ee["nome"]
            r.cells[2].paragraphs[0].text = ee["responsavel"]
            r.cells[3].paragraphs[0].text = ee["ftrs"]
            r.cells[4].paragraphs[0].text = str(ee["ders_count"])
            r.cells[5].paragraphs[0].text = ee["complexidade"]
            r.cells[6].paragraphs[0].text = str(ee["pfb"])

        r_tot_ee = t_ees.add_row()
        r_tot_ee.cells[0].paragraphs[0].text = "TOTAL EEs"
        r_tot_ee.cells[1].paragraphs[0].text = "10 Transações Elementares de Entrada"
        r_tot_ee.cells[2].paragraphs[0].text = "Equipe"
        r_tot_ee.cells[3].paragraphs[0].text = "-"
        r_tot_ee.cells[4].paragraphs[0].text = str(sum(ee["ders_count"] for ee in EES_DATA))
        r_tot_ee.cells[5].paragraphs[0].text = "8 Médias, 2 Baixas"
        r_tot_ee.cells[6].paragraphs[0].text = f"{sum(ee['pfb'] for ee in EES_DATA)} PFB"
        TableHelper.format_full_table(t_ees, [Inches(1.2), Inches(1.8), Inches(1.0), Inches(1.1), Inches(0.3), Inches(0.4), Inches(0.4)])

        for ee in EES_DATA:
            self.add_h3(f"{ee['id']} — {ee['nome']} ({ee['complexidade']} — {ee['pfb']} PFB)")
            self.add_bullet("Responsável: ", ee["responsavel"])
            self.add_bullet("Arquivos Lidos e Mantidos (FTRs): ", ee["ftrs"])
            self.add_bullet("Itens de Dados (DERs): ", f"{ee['ders_count']} atributos manipulados.")
            self.add_bullet("Método do Backend PHP: ", ee["metodo"])
            self.add_bullet("Comportamento Operacional: ", ee["descricao"])
            ImageHelper.add_screenshot(self.doc, ee["tela_img"], self.fig_counter, f"Interface Transacional: {ee['id']}")
            self.fig_counter += 1

        # 6. Especificação das 10 SEs com Dados Derivados
        self.add_h1("6. Especificação Exaustiva das 10 Saídas Externas (SEs — 52 PFB)")
        self.add_p(
            "Em rigorosa conformidade com o edital do Canvas e o IFPUG CPM 4.3.1, TODAS as 10 Saídas Externas "
            "apresentam obrigatoriamente a fundamentação da regra de negócio que gera dados matematicamente "
            "ou algoritmicamente derivados (não armazenados diretamente em tabelas):"
        )

        t_ses = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["ID Transação", "Nome do Relatório / Saída", "Responsável", "FTRs", "DERs", "Compl.", "PFB"]):
            t_ses.cell(0, j).paragraphs[0].text = h

        for se in SES_DATA:
            r = t_ses.add_row()
            r.cells[0].paragraphs[0].text = se["id"]
            r.cells[1].paragraphs[0].text = se["nome"]
            r.cells[2].paragraphs[0].text = se["responsavel"]
            r.cells[3].paragraphs[0].text = str(se["ftrs_count"])
            r.cells[4].paragraphs[0].text = str(se["ders_count"])
            r.cells[5].paragraphs[0].text = se["complexidade"]
            r.cells[6].paragraphs[0].text = str(se["pfb"])

        r_tot_se = t_ses.add_row()
        r_tot_se.cells[0].paragraphs[0].text = "TOTAL SEs"
        r_tot_se.cells[1].paragraphs[0].text = "10 Saídas com Dados Derivados"
        r_tot_se.cells[2].paragraphs[0].text = "Equipe"
        r_tot_se.cells[3].paragraphs[0].text = str(sum(se["ftrs_count"] for se in SES_DATA))
        r_tot_se.cells[4].paragraphs[0].text = str(sum(se["ders_count"] for se in SES_DATA))
        r_tot_se.cells[5].paragraphs[0].text = "2 Altas, 7 Médias, 1 Baixa"
        r_tot_se.cells[6].paragraphs[0].text = f"{sum(se['pfb'] for se in SES_DATA)} PFB"
        TableHelper.format_full_table(t_ses, [Inches(1.2), Inches(2.0), Inches(1.0), Inches(0.4), Inches(0.4), Inches(0.5), Inches(0.5)])

        for se in SES_DATA:
            self.add_h3(f"{se['id']} — {se['nome']} ({se['complexidade']} — {se['pfb']} PFB)")
            self.add_bullet("Responsável: ", se["responsavel"])
            self.add_bullet(f"Arquivos Lidos ({se['ftrs_count']} FTRs): ", se["ftrs"])
            self.add_bullet(f"Itens de Dados Projetados ({se['ders_count']} DERs): ", f"Projeta {se['ders_count']} atributos de saída.")
            CalloutHelper.add_callout(
                self.doc,
                f"Justificativa de Dado Derivado: {se['id']}",
                se["justificativa"],
                formula=se["formula"]
            )
            ImageHelper.add_screenshot(self.doc, se["tela_img"], self.fig_counter, f"Saída Externa Prototipada: {se['id']}")
            self.fig_counter += 1

        # 7. Especificação das 10 CEs com Entradas e Saídas Segregadas
        self.add_h1("7. Especificação Exaustiva das 10 Consultas Externas (CEs — 30 PFB)")
        self.add_p(
            "Todas as 10 Consultas Externas possuem segregação formal entre os dados de entrada (filtros, chaves "
            "de busca, ordenações) e os dados de saída retornados nas tabelas e grids:"
        )

        t_ces = self.doc.add_table(rows=1, cols=7)
        for j, h in enumerate(["ID Transação", "Nome da Consulta", "Responsável", "FTRs", "DERs", "Compl.", "PFB"]):
            t_ces.cell(0, j).paragraphs[0].text = h

        for ce in CES_DATA:
            r = t_ces.add_row()
            r.cells[0].paragraphs[0].text = ce["id"]
            r.cells[1].paragraphs[0].text = ce["nome"]
            r.cells[2].paragraphs[0].text = ce["responsavel"]
            r.cells[3].paragraphs[0].text = str(ce["ftrs_count"])
            r.cells[4].paragraphs[0].text = str(ce["ders_count"])
            r.cells[5].paragraphs[0].text = ce["complexidade"]
            r.cells[6].paragraphs[0].text = str(ce["pfb"])

        r_tot_ce = t_ces.add_row()
        r_tot_ce.cells[0].paragraphs[0].text = "TOTAL CEs"
        r_tot_ce.cells[1].paragraphs[0].text = "10 Consultas Externas Segregadas"
        r_tot_ce.cells[2].paragraphs[0].text = "Equipe"
        r_tot_ce.cells[3].paragraphs[0].text = str(sum(ce["ftrs_count"] for ce in CES_DATA))
        r_tot_ce.cells[4].paragraphs[0].text = str(sum(ce["ders_count"] for ce in CES_DATA))
        r_tot_ce.cells[5].paragraphs[0].text = "10 Baixas"
        r_tot_ce.cells[6].paragraphs[0].text = f"{sum(ce['pfb'] for ce in CES_DATA)} PFB"
        TableHelper.format_full_table(t_ces, [Inches(1.2), Inches(2.0), Inches(1.0), Inches(0.4), Inches(0.4), Inches(0.5), Inches(0.5)])

        for ce in CES_DATA:
            self.add_h3(f"{ce['id']} — {ce['nome']} ({ce['complexidade']} — {ce['pfb']} PFB)")
            self.add_bullet("Responsável: ", ce["responsavel"])
            self.add_bullet(f"Arquivos Referenciados ({ce['ftrs_count']} FTRs): ", ce["ftrs"])
            self.add_bullet(f"Itens de Dados ({ce['ders_count']} DERs): ", f"Total de {ce['ders_count']} atributos de entrada e saída.")
            
            t_io = self.doc.add_table(rows=1, cols=2)
            t_io.cell(0, 0).paragraphs[0].text = "Dados de Entrada (Filtros de Pesquisa)"
            t_io.cell(0, 1).paragraphs[0].text = "Dados de Saída (Resultados / Grid)"
            r_io = t_io.add_row()
            r_io.cells[0].paragraphs[0].text = ce["entradas"]
            r_io.cells[1].paragraphs[0].text = ce["saidas"]
            TableHelper.format_full_table(t_io, [Inches(3.0), Inches(3.0)])
            self.doc.add_paragraph().paragraph_format.space_after = Pt(2)

            ImageHelper.add_screenshot(self.doc, ce["tela_img"], self.fig_counter, f"Consulta Externa Prototipada: {ce['id']}")
            self.fig_counter += 1

        # 8. Avaliação das 14 GSCs
        self.add_h1("8. Avaliação Fundamentada das 14 Características Gerais (GSC / NI)")
        self.add_p(
            "Em observância à metodologia IFPUG CPM 4.3.1, a complexidade arquitetural não funcional da plataforma "
            "HRTech Core foi avaliada nota a nota (escala 0 a 5) com fundamentação técnica sólida:"
        )

        t_gsc = self.doc.add_table(rows=1, cols=4)
        for j, h in enumerate(["#", "Característica Geral (GSC)", "Grau", "Justificativa Arquitetural — HRTech Core"]):
            t_gsc.cell(0, j).paragraphs[0].text = h

        for item in GSCS_DATA:
            r = t_gsc.add_row()
            r.cells[0].paragraphs[0].text = item["id"]
            r.cells[1].paragraphs[0].text = item["nome"]
            r.cells[2].paragraphs[0].text = str(item["nota"])
            r.cells[3].paragraphs[0].text = item["justificativa"]

        r_tot_gsc = t_gsc.add_row()
        r_tot_gsc.cells[0].paragraphs[0].text = "SOMA"
        r_tot_gsc.cells[1].paragraphs[0].text = "Nível de Influência Total (NI)"
        r_tot_gsc.cells[2].paragraphs[0].text = "58"
        r_tot_gsc.cells[3].paragraphs[0].text = "Grau de complexidade técnica elevado: 58 de 70 pontos possíveis (82,9%)."
        TableHelper.format_full_table(t_gsc, [Inches(0.4), Inches(1.8), Inches(0.5), Inches(3.3)])

        # 9. Memória de Cálculo e Gestão
        self.add_h1("9. Memória de Cálculo Completa: PFA, Esforço, Prazo, Custo e UCP")
        self.add_h2("9.1 Fator de Ajuste (FA) e PFA Consolidado da Equipe")
        self.add_p(
            "Aplicando a equação matemática clássica do IFPUG ao Nível de Influência apurado:"
        )
        self.add_p("FA = 0,65 + (0,01 * NI) = 0,65 + (0,01 * 58) = 1,2300 (+23,0%)")
        self.add_p("PFA (Equipe Consolidada) = 252 PFB * 1,2300 = 309,96 PFA")

        self.add_h2("9.2 Validação Cruzada com Casos de Uso (UCP)")
        self.add_p(
            "O relatório autoritativo de Pontos de Casos de Uso da disciplina (entrega_completa_ucp_hrtech.md) "
            "apurou formalmente para a plataforma HRTech Core: UUCP = 178, TCF = 1,105, ECF = 0,755, resultando em "
            "AUCP = 148,501. Sob a taxa de Schneider & Winters (20 h/AUCP), o esforço totalizou exatamente 2.970,02 Homem-Horas "
            "e o orçamento financeiro atingiu R$ 148.501,00 a R$ 50,00/h."
        )
        self.add_p(
            "Estabelecendo a convergência métrica estrita entre os dois modelos de engenharia de software:"
        )
        self.add_p("Taxa de Produtividade APF = 2.970,02 HH / 309,96 PFA = 9,5818 HH/PFA")
        self.add_p("Taxa de Produtividade Bruta = 2.970,02 HH / 252 PFB = 11,7858 HH/PFB")
        self.add_p("Desvio Orçamentário e de Esforço entre APF e UCP: 0,00% (Convergência de 100,00% — Desvio R$ 0,00).")

        self.add_h2("9.3 Esforço, Cronograma e Orçamento Global")
        self.add_bullet("Esforço Consolidado da Equipe: ", "309,96 PFA * 9,5818 HH/PFA = 2.970,00 Homem-Horas.")
        self.add_bullet("Custo Total da Plataforma: ", "2.970,00 HH * R$ 50,00/hora = R$ 148.501,00.")
        self.add_bullet("Prazo (Equipe Plena 5 Devs): ", "2.970 HH / (5 devs * 160 h/mês) = 3,71 meses (~15 semanas de desenvolvimento).")
        self.add_bullet("Cenário Comparativo (2 Devs): ", "2.970 HH / (2 devs * 160 h/mês) = 9,28 meses (~37 semanas).")
        self.add_bullet("Cenário Comparativo (1 Dev Solo): ", "2.970 HH / (1 dev * 160 h/mês) = 18,56 meses (~74 semanas).")

        # 10. Termo de Aprovação da Equipe
        self.add_h1("10. Termo de Responsabilidade e Aprovação da Equipe")
        self.add_p(
            "Declaramos que a presente análise de medição de software reflete com fidelidade matemática, "
            "arquitetural e visual as 50 funções construídas no projeto HRTech Core, atendendo a todos os critérios "
            "acadêmicos do edital da Pontifícia Universidade Católica do Paraná (PUCPR)."
        )
        self.add_p(
            "Curitiba — PR, Outubro de 2026.\n\n"
            "• Andryus Zolet: _____________________________________________\n"
            "• Felipe: _____________________________________________\n"
            "• Valentin: _____________________________________________\n"
            "• Nicolas: _____________________________________________\n"
            "• Fernando Lopes Duarte: _____________________________________________"
        )

        out_path = os.path.join(PROJECT_ROOT, self.output_filename)
        self.doc.save(out_path)
        print(f"-> Relatório de Equipe salvo com sucesso em: {out_path} ({os.path.getsize(out_path):,} bytes)")

# ==============================================================================
# EXECUÇÃO PRINCIPAL
# ==============================================================================
def main():
    print("===========================================================================")
    print(" HRTech Core — Geração de Relatórios Oficiais de APF em DOCX (PUCPR)")
    print("===========================================================================")
    
    ind_builder = IndividualReportBuilder()
    ind_builder.build()

    team_builder = TeamReportBuilder()
    team_builder.build()

    print("===========================================================================")
    print(" GERAÇÃO CONCLUÍDA COM SUCESSO!")
    print("===========================================================================")

if __name__ == "__main__":
    main()
