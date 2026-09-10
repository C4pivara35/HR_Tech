import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
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
    set_cell_margins(cell, top=120, bottom=120, left=150, right=150)
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
    run.font.size = Pt(16)
    run.font.bold = True
    run.font.color.rgb = RGBColor(15, 23, 42) # Dark Slate Blue

def add_heading_2(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(13)
    run.font.bold = True
    run.font.color.rgb = RGBColor(30, 58, 138) # Deep Blue

def add_heading_3(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(10)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(11)
    run.font.bold = True
    run.font.color.rgb = RGBColor(51, 65, 85)

def add_body_paragraph(doc, text, bold_prefix="", italic=False):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(6)
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
    
    # Page Setup - Margins 2.5cm
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)

    # ---------------------------------------------------------
    # COVER / HEADER BLOCK
    # ---------------------------------------------------------
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_before = Pt(20)
    p_title.paragraph_format.space_after = Pt(4)
    run_t = p_title.add_run("PONTIFÍCIA UNIVERSIDADE CATÓLICA DO PARANÁ\nESCOLA DE POLITÉCNICA — ENGENHARIA DE SOFTWARE")
    run_t.font.name = 'Arial'
    run_t.font.size = Pt(12)
    run_t.font.bold = True
    run_t.font.color.rgb = RGBColor(71, 85, 105)

    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sub.paragraph_format.space_before = Pt(36)
    p_sub.paragraph_format.space_after = Pt(12)
    run_s = p_sub.add_run("RELATÓRIO TÉCNICO DE ENGENHARIA DE SOFTWARE E MÉTRICAS\nESTIMATIVA COMPLETA DE PONTOS POR CASOS DE USO (UCP)")
    run_s.font.name = 'Arial'
    run_s.font.size = Pt(18)
    run_s.font.bold = True
    run_s.font.color.rgb = RGBColor(15, 23, 42)

    p_proj = doc.add_paragraph()
    p_proj.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_proj.paragraph_format.space_before = Pt(6)
    p_proj.paragraph_format.space_after = Pt(40)
    run_p = p_proj.add_run("Projeto: HRTech Core — Plataforma Modular de Gestão de RH com Linha de Produção de Software (LPS)")
    run_p.font.name = 'Arial'
    run_p.font.size = Pt(12)
    run_p.font.italic = True
    run_p.font.color.rgb = RGBColor(30, 58, 138)

    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p_meta.paragraph_format.space_before = Pt(40)
    p_meta.paragraph_format.space_after = Pt(60)
    run_m = p_meta.add_run("Autor: Fernando Lopes Duarte\nDisciplina: Medição e Análise de Software\nProfessor Orientador: Prof. da Disciplina\nData: 10 de Setembro de 2026\nVersão: 1.0 (Consolidada Final)")
    run_m.font.name = 'Arial'
    run_m.font.size = Pt(10.5)
    run_m.font.color.rgb = RGBColor(51, 65, 85)

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 1: CASOS DE USO & MATRIZ DE RASTREABILIDADE
    # ---------------------------------------------------------
    add_heading_1(doc, "1. Casos de Uso, Matriz de Rastreabilidade e Especificação dos Casos de Uso")
    
    add_heading_2(doc, "1.1 Relação Geral dos Casos de Uso (UC01 a UC17)")
    add_body_paragraph(doc, "A tabela abaixo apresenta os 17 casos de uso identificados para a plataforma HRTech Core, abrangendo as funcionalidades do Core da aplicação, os módulos de variabilidade por tenant (Tech, Indústria e Financeiro) e a feature exclusiva do cliente FinCorp Seguros.")

    # Table UC01-UC17
    ucs_data = [
        ("UC01", "Autenticar no Sistema", "Colaborador / Gestor / Admin", "Todos os Tenants", "Autenticação segura com MFA, verificação de perfil e seleção de tenant."),
        ("UC02", "Manter Colaboradores (CRUD)", "Administrador / RH", "Core / Todos os Tenants", "Cadastro, edição, inativação, listagem e busca avançada de colaboradores."),
        ("UC03", "Visualizar Organograma", "Colaborador / Gestor / Admin", "Core / Todos os Tenants", "Exibição gráfica hierárquica da estrutura organizacional."),
        ("UC04", "Registrar Ponto Eletrônico", "Colaborador", "Core (Tech / Ind / Fin)", "Marcação de horário via Web/Mobile com geolocalização e IP."),
        ("UC05", "Solicitar Ajuste de Ponto", "Colaborador", "Core (Tech / Ind / Fin)", "Solicitação de correção de marcação com anexo de comprovante."),
        ("UC06", "Solicitar Férias", "Colaborador", "Core (Tech / Ind / Fin)", "Agendamento e solicitação de períodos de férias com simulação de 1/3."),
        ("UC07", "Aprovar Solicitações da Equipe", "Gestor", "Core (Tech / Ind / Fin)", "Avaliação, aprovação ou rejeição de ajustes de ponto e pedidos de férias."),
        ("UC08", "Visualizar Banco de Horas", "Colaborador / Gestor", "Core (Tech / Ind / Fin)", "Consulta de saldo positivo/negativo de horas acumuladas e compensações."),
        ("UC09", "Gerenciar Ativação LPS & Variabilidade", "Administrador / RH", "Core / Módulo LPS", "Ativação/desativação de módulos condicionais (EPIs, Corretor, Compliance)."),
        ("UC10", "Visualizar Dashboard e Indicadores", "Colaborador / Gestor / Admin", "Core / Todos os Tenants", "Exibição de KPIs de turnover, assiduidade, férias vencidas e métricas de RH."),
        ("UC11", "Central de Aprovações da Equipe", "Gestor / Admin", "Core / Todos os Tenants", "Painel consolidado para aprovações em lote e pendências operacionais."),
        ("UC12", "Gerenciar Benefícios", "Administrador / RH", "Core (Tech / Ind / Fin)", "Gestão de cartões de benefícios (VR, VA, plano de saúde, transporte)."),
        ("UC13", "Gerenciar Desempenho e OKRs", "Gestor / Colaborador", "Módulo Tech / Financeiro", "Acompanhamento de metas trimestrais, OKRs e avaliações 360."),
        ("UC14", "Gerenciar EPIs e ASOs", "Administrador / RH", "Módulo Indústria", "Controle de distribuição de Equipamentos de Proteção e exames ocupacionais."),
        ("UC15", "Configurar Módulos do Tenant", "Administrador / RH", "Core / Todos os Tenants", "Parametrização de regras de negócio específicas da empresa contratante."),
        ("UC16", "Visualizar Logs de Auditoria & Compliance", "Administrador / RH / Auditor", "Módulo Financeiro", "Trilha de auditoria imutável com logs criptografados SHA-256 e LGPD."),
        ("UC17", "Operar Portal do Corretor", "Corretor / Admin FinCorp", "Feature FinCorp", "Emissão e gestão de apólices corporativas e sincronização de sinistros.")
    ]

    t_uc = doc.add_table(rows=len(ucs_data) + 1, cols=5)
    t_uc.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_uc)

    headers = ["ID", "Nome do Caso de Uso", "Ator Principal", "Escopo / Tenant", "Descrição Resumida"]
    widths = [Inches(0.6), Inches(2.0), Inches(1.5), Inches(1.4), Inches(2.2)]

    for j, h in enumerate(headers):
        cell = t_uc.cell(0, j)
        cell.width = widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(ucs_data):
        bg = "F8FAFC" if i % 2 == 1 else "FFFFFF"
        for j, val in enumerate(row):
            cell = t_uc.cell(i + 1, j)
            cell.width = widths[j]
            format_cell(cell, val, bold=(j==0), bg_color=bg)

    # 1.2 Matriz de Rastreabilidade
    add_heading_2(doc, "1.2 Matriz de Rastreabilidade Bidirecional")
    add_body_paragraph(doc, "A matriz de rastreabilidade garante a integridade e rastreabilidade total entre os Requisitos Funcionais (RF), os Casos de Uso (UC), as Telas do Protótipo (Tela) e os Ativos Reutilizáveis da LPS (ART).")

    rastr_data = [
        ("RF01", "Autenticação segura multi-tenant com perfil", "UC01", "Tela 01 — Login", "ART-01 (Módulo Autenticação)"),
        ("RF02", "Gestão completa de cadastros de colaboradores", "UC02", "Tela 04, 05, 06", "ART-02 (Gestão Colaboradores)"),
        ("RF03", "Visualização de organograma interativo", "UC03", "Tela 07 — Organograma", "ART-02 (Gestão Colaboradores)"),
        ("RF04", "Registro de ponto eletrônico portaria 671", "UC04", "Tela 08 — Espelho Ponto", "ART-03 (Controle Frequência)"),
        ("RF05", "Solicitador de ajustes de marcação de ponto", "UC05", "Tela 09 — Ajuste Ponto", "ART-03 (Controle Frequência)"),
        ("RF06", "Gestão e solicitação de férias CLT", "UC06", "Tela 10 — Férias", "ART-04 (Gestão Férias)"),
        ("RF07", "Workflow de aprovação por gestores", "UC07, UC11", "Tela 11 — Aprovações", "ART-04 (Gestão Férias)"),
        ("RF08", "Cálculo automatizado de banco de horas", "UC08", "Tela 08 — Espelho Ponto", "ART-03 (Controle Frequência)"),
        ("RF09", "Motor de variabilidade e seleção de tenants", "UC09, UC15", "Tela 15 — Configurações", "ART-05 (Motor Variabilidade LPS)"),
        ("RF10", "Painéis analíticos e dashboards dinâmicos", "UC10", "Tela 02, Tela 03", "ART-02 (Gestão Colaboradores)"),
        ("RF11", "Gestão flexível de benefícios corporativos", "UC12", "Tela 12 — Benefícios", "ART-02 (Gestão Colaboradores)"),
        ("RF12", "Ciclos de avaliação 360° e acompanhamento OKR", "UC13", "Tela 13 — Desempenho", "ART-02 (Gestão Colaboradores)"),
        ("RF13", "Controle estrito de EPIs e ASO ocupacional", "UC14", "Tela 14 — EPIs / ASO", "ART-06 (Módulos Plug-ins)"),
        ("RF14", "Trilha de auditoria criptografada e compliance", "UC16", "Tela 16 — Compliance", "ART-06 (Módulos Plug-ins)"),
        ("RF15", "Portal do Corretor de Seguros Corporativos", "UC17", "Tela 17 — Portal Corretor", "ART-06 (Módulos Plug-ins)")
    ]

    t_ras = doc.add_table(rows=len(rastr_data) + 1, cols=5)
    t_ras.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_ras)

    r_headers = ["Requisito", "Descrição do Requisito", "Caso de Uso", "Tela Protótipo", "Ativo LPS Reutilizável"]
    r_widths = [Inches(0.9), Inches(2.2), Inches(1.1), Inches(1.5), Inches(2.0)]

    for j, h in enumerate(r_headers):
        cell = t_ras.cell(0, j)
        cell.width = r_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(rastr_data):
        bg = "F8FAFC" if i % 2 == 1 else "FFFFFF"
        for j, val in enumerate(row):
            cell = t_ras.cell(i + 1, j)
            cell.width = r_widths[j]
            format_cell(cell, val, bold=(j==0), bg_color=bg)

    # 1.3 Especificação dos Casos de Uso Representativos
    add_heading_2(doc, "1.3 Especificação Detalhada de Casos de Uso Representativos")

    # UC02
    add_heading_3(doc, "Especificação: UC02 — Manter Colaboradores (CRUD Completo)")
    add_body_paragraph(doc, "Complexo (Peso 15) | 9 Transações | Atores: Administrador/RH, eSocial Gateway", bold_prefix="Classificação: ")
    add_body_paragraph(doc, "Usuário autenticado com perfil de RH; Tenant ativo selecionado.", bold_prefix="Pré-condições: ")
    add_body_paragraph(doc, "Dados cadastrais persistidos no banco relacional; evento eSocial disparado.", bold_prefix="Pós-condições: ")
    add_body_paragraph(doc, 
        "1. O Administrador acessa a lista de colaboradores (Tela 04).\n"
        "2. O sistema consulta a base de dados do tenant ativo e exibe a listagem paginada.\n"
        "3. O Administrador clica em 'Novo Colaborador'.\n"
        "4. O sistema apresenta o formulário de cadastro (Tela 05).\n"
        "5. O Administrador insere dados pessoais, cargo, departamento, salário e admissão.\n"
        "6. O Administrador submete o formulário.\n"
        "7. O sistema valida os campos obrigatórios e formato de CPF/Email.\n"
        "8. O sistema persiste a entidade Colaborador associada ao TenantID.\n"
        "9. O sistema aciona o gateway eSocial de forma assíncrona e exibe mensagem de sucesso.",
        bold_prefix="Fluxo Principal:\n"
    )

    # UC04
    add_heading_3(doc, "Especificação: UC04 — Registrar Ponto Eletrônico")
    add_body_paragraph(doc, "Médio (Peso 10) | 5 Transações | Atores: Colaborador, System Clock, API Ponto GPS", bold_prefix="Classificação: ")
    add_body_paragraph(doc, "Colaborador autenticado na sessão ativa.", bold_prefix="Pré-condições: ")
    add_body_paragraph(doc, "Marcação armazenada imutavelmente com hash SHA-256 e carimbo de tempo.", bold_prefix="Pós-condições: ")
    add_body_paragraph(doc, 
        "1. O Colaborador acessa a tela de espelho de ponto (Tela 08).\n"
        "2. O sistema recupera a hora certa do servidor e a geolocalização do dispositivo.\n"
        "3. O Colaborador clica no botão 'Registrar Ponto (Entrada/Saída)'.\n"
        "4. O sistema gera a marcação com hash SHA-256 e IP de origem.\n"
        "5. O sistema exibe o comprovante digital e atualiza o extrato diário de horas.",
        bold_prefix="Fluxo Principal:\n"
    )

    # UC17
    add_heading_3(doc, "Especificação: UC17 — Operar Portal do Corretor (FinCorp Seguros)")
    add_body_paragraph(doc, "Complexo (Peso 15) | 10 Transações | Atores: Corretor de Seguros, API Seguradora", bold_prefix="Classificação: ")
    add_body_paragraph(doc, "Feature Exclusiva ativada para o Tenant FinCorp Seguros.", bold_prefix="Pré-condições: ")
    add_body_paragraph(doc, "Apólice emitida ou alterada; registro associado ao colaborador.", bold_prefix="Pós-condições: ")
    add_body_paragraph(doc, 
        "1. O Corretor aciona o menu exclusivo 'Portal do Corretor' (Tela 17).\n"
        "2. O sistema valida a permissão da feature customizada.\n"
        "3. O Corretor pesquisa colaboradores elegíveis ao seguro corporativo.\n"
        "4. O Corretor seleciona o plano e o valor da cobertura.\n"
        "5. O Corretor solicita a emissão da apólice corporativa.\n"
        "6. O sistema transmite a proposta para a API REST da seguradora parceira.\n"
        "7. O sistema recebe a confirmação e o número da apólice.\n"
        "8. O sistema salva o registro na tabela ApolicesCorretor.\n"
        "9. O sistema associa a apólice ao colaborador no módulo de RH.\n"
        "10. O sistema exibe o painel atualizado com status da cobertura.",
        bold_prefix="Fluxo Principal:\n"
    )

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 2: MODELO LÓGICO DAS ENTIDADES
    # ---------------------------------------------------------
    add_heading_1(doc, "2. Modelo Lógico das Entidades")
    add_body_paragraph(doc, "O modelo de dados relacional foi projetado com isolamento multi-tenant nativo através da chave estrangeira tenant_id em todas as tabelas transacionais, garantindo segurança e segregação estrita de dados.")

    entities_data = [
        ("Tenants", "id (PK, INT)", "-", "nome_empresa, cnpj, segmento_lps, modulos_ativos (JSON), data_criacao", "1 : N com Usuarios, Colaboradores, etc."),
        ("Usuarios", "id (PK, INT)", "tenant_id (FK)", "nome, email, senha_hash, perfil (ADMIN/GESTOR/DEV/CORRETOR)", "N : 1 com Tenants, 1 : 1 com Colaboradores"),
        ("Departamentos", "id (PK, INT)", "tenant_id (FK), gestor_id (FK)", "nome_departamento", "N : 1 com Tenants, 1 : N com Colaboradores"),
        ("Cargos", "id (PK, INT)", "tenant_id (FK)", "titulo_cargo, nivel_hierarquico", "N : 1 com Tenants, 1 : N com Colaboradores"),
        ("Colaboradores", "id (PK, INT)", "tenant_id, usuario_id, depto_id, cargo_id", "matricula, nome_completo, cpf, data_admissao, salario_base, status", "Central do domínio; relaciona-se com Ponto, Férias, etc."),
        ("RegistrosPonto", "id (PK, BIGINT)", "tenant_id, colaborador_id", "data_hora_registro, tipo_registro, ip_origem, geolocalizacao, hash_sha256", "N : 1 com Colaboradores, 1 : 1 com AjustesPonto"),
        ("SolicitacoesAjustePonto", "id (PK, INT)", "tenant_id, registro_ponto_id, colaborador_id, gestor_id", "nova_data_hora, justificativa, anexo_url, status", "N : 1 com Colaboradores e RegistrosPonto"),
        ("SolicitacoesFerias", "id (PK, INT)", "tenant_id, colaborador_id, gestor_id", "data_inicio, dias_duracao, abono_pecuniario, status", "N : 1 com Colaboradores e Usuarios (Gestor)"),
        ("Beneficios", "id (PK, INT)", "tenant_id, colaborador_id", "tipo_beneficio (VR/VA/Saúde/VT), valor_mensal", "N : 1 com Colaboradores"),
        ("EPIs_ASOs", "id (PK, INT)", "tenant_id, colaborador_id", "categoria (EPI/ASO), descricao, ca_numero, data_emissao, vencimento, status", "N : 1 com Colaboradores (Exclusivo Indústria)"),
        ("AvaliacoesDesempenho", "id (PK, INT)", "tenant_id, colaborador_id", "ciclo, meta_okr, nota_score, feedback_texto", "N : 1 com Colaboradores (Exclusivo Tech/Fin)"),
        ("AuditoriaLogs", "id (PK, BIGINT)", "tenant_id, usuario_id", "acao_executada, detalhes_json, hash_sha256, timestamp_evento", "Imutável; N : 1 com Tenants e Usuarios"),
        ("ApolicesCorretor", "id (PK, INT)", "tenant_id, colaborador_id", "numero_apolice, valor_cobertura, vigencia_inicio, vigencia_fim, status", "N : 1 com Colaboradores (Feature FinCorp)")
    ]

    t_ent = doc.add_table(rows=len(entities_data) + 1, cols=5)
    t_ent.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_ent)

    e_headers = ["Tabela / Entidade", "Chave Primária", "Chaves Estrangeiras", "Atributos Principais", "Cardinalidade / Relacionamentos"]
    e_widths = [Inches(1.5), Inches(1.1), Inches(1.4), Inches(2.2), Inches(1.5)]

    for j, h in enumerate(e_headers):
        cell = t_ent.cell(0, j)
        cell.width = e_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(entities_data):
        bg = "F8FAFC" if i % 2 == 1 else "FFFFFF"
        for j, val in enumerate(row):
            cell = t_ent.cell(i + 1, j)
            cell.width = e_widths[j]
            format_cell(cell, val, bold=(j==0), bg_color=bg)

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 3: ESTIMATIVA DE CLASSES NECESSÁRIAS
    # ---------------------------------------------------------
    add_heading_1(doc, "3. Estimativa da Quantidade de Classes Necessárias")
    add_body_paragraph(doc, "A arquitetura do HRTech Core adota os princípios da Clean Architecture e padrões estritos de POO em Laravel/PHP e Vue.js/JavaScript, resultando na seguinte distribuição de classes por camada:")

    classes_data = [
        ("Camada de Apresentação (Frontend SPA)", "Componentes Vue/JS das 17 telas + Header, Sidebar e TenantGuard.", "20 classes"),
        ("Controllers (API HTTP)", "17 Controllers RESTful (AuthController, EmployeeController, TimeLogController, etc.).", "17 classes"),
        ("Services (Regras de Negócio)", "15 Service Classes (AuthService, EmployeeService, VacationService, LPSTenantService, etc.).", "15 classes"),
        ("Form Requests & DTOs", "15 Classes de Validação de formulários e DTOs (StoreEmployeeRequest, LPSConfigDTO, etc.).", "15 classes"),
        ("Models (Eloquent Entidades)", "13 Mapeamentos ORM das entidades de banco de dados (Tenant, User, Employee, etc.).", "13 classes"),
        ("Repositories & Interfaces", "13 Repositórios para abstração da camada de persistência de dados.", "13 classes"),
        ("TOTAL CONSOLIDADO DE CLASSES", "Soma consolidada de todas as camadas arquiteturais do sistema.", "93 Classes")
    ]

    t_cls = doc.add_table(rows=len(classes_data) + 1, cols=3)
    t_cls.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_cls)

    c_headers = ["Camada Arquitetural", "Responsabilidade & Escopo de Implementação", "Quantidade Estimada"]
    c_widths = [Inches(2.2), Inches(4.0), Inches(1.5)]

    for j, h in enumerate(c_headers):
        cell = t_cls.cell(0, j)
        cell.width = c_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(classes_data):
        bg = "EFF6FF" if i == len(classes_data)-1 else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        is_total = (i == len(classes_data)-1)
        for j, val in enumerate(row):
            cell = t_cls.cell(i + 1, j)
            cell.width = c_widths[j]
            format_cell(cell, val, bold=is_total or (j==0), color=RGBColor(30,58,138) if is_total else RGBColor(51,51,51), bg_color=bg)

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 4: CÁLCULO DOS PESOS DE ATORES E CASOS DE USO
    # ---------------------------------------------------------
    add_heading_1(doc, "4. Calcular os Pesos dos Atores (UAW) e Casos de Uso (UUCW)")

    add_heading_2(doc, "4.1 Pesos dos Atores (UAW — Unadjusted Actor Weight)")
    uaw_data = [
        ("API Ponto Externa", "API REST / JSON para relógios de ponto físicos", "Simples", "1"),
        ("System Clock (Timer)", "Cron job / serviço interno de agendamento do sistema", "Simples", "1"),
        ("Gateway eSocial", "API REST / SOAP de comunicação com governo federal", "Simples", "1"),
        ("Sistema Benefícios (VR/VA)", "Integrador REST API de operadoras de benefícios", "Simples", "1"),
        ("Colaborador", "Interface Web SPA Responsiva / App Mobile", "Complexo", "3"),
        ("Gestor", "Interface Web SPA com painéis e aprovações", "Complexo", "3"),
        ("Administrador / RH", "Interface Web SPA completa de parametrização e CRUDs", "Complexo", "3"),
        ("TOTAL UAW", "Soma dos Pesos dos Atores (4 × 1 + 3 × 3)", "-", "13")
    ]

    t_uaw = doc.add_table(rows=len(uaw_data) + 1, cols=4)
    t_uaw.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_uaw)

    ua_headers = ["Nome do Ator", "Tipo de Interface / Interação", "Classificação", "Peso Atribuído"]
    ua_widths = [Inches(1.8), Inches(3.5), Inches(1.2), Inches(1.2)]

    for j, h in enumerate(ua_headers):
        cell = t_uaw.cell(0, j)
        cell.width = ua_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(uaw_data):
        is_tot = (i == len(uaw_data)-1)
        bg = "EFF6FF" if is_tot else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_uaw.cell(i + 1, j)
            cell.width = ua_widths[j]
            format_cell(cell, val, bold=is_tot or (j==0), color=RGBColor(30,58,138) if is_tot else RGBColor(51,51,51), bg_color=bg)

    add_heading_2(doc, "4.2 Pesos dos Casos de Uso (UUCW — Unadjusted Use Case Weight)")
    uucw_data = [
        ("UC01", "Autenticar no Sistema", "3", "Simples", "5"),
        ("UC02", "Manter Colaboradores (CRUD Completo)", "9", "Complexo", "15"),
        ("UC03", "Visualizar Organograma", "3", "Simples", "5"),
        ("UC04", "Registrar Ponto Eletrônico", "5", "Médio", "10"),
        ("UC05", "Solicitar Ajuste de Ponto", "6", "Médio", "10"),
        ("UC06", "Solicitar Férias", "6", "Médio", "10"),
        ("UC07", "Aprovar Solicitações da Equipe", "5", "Médio", "10"),
        ("UC08", "Visualizar Banco de Horas", "3", "Simples", "5"),
        ("UC09", "Gerenciar Ativação LPS & Variabilidade", "8", "Complexo", "15"),
        ("UC10", "Visualizar Dashboard e Indicadores", "3", "Simples", "5"),
        ("UC11", "Central de Aprovações da Equipe", "8", "Complexo", "15"),
        ("UC12", "Gerenciar Benefícios", "5", "Médio", "10"),
        ("UC13", "Gerenciar Desempenho e OKRs", "6", "Médio", "10"),
        ("UC14", "Gerenciar EPIs e ASOs", "6", "Médio", "10"),
        ("UC15", "Configurar Módulos do Tenant", "5", "Médio", "10"),
        ("UC16", "Visualizar Logs de Auditoria & Compliance", "3", "Simples", "5"),
        ("UC17", "Operar Portal do Corretor (FinCorp)", "10", "Complexo", "15"),
        ("TOTAL UUCW", "Soma dos Pesos dos Casos de Uso (5×5 + 8×10 + 4×15)", "-", "-", "165")
    ]

    t_uucw = doc.add_table(rows=len(uucw_data) + 1, cols=5)
    t_uucw.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_uucw)

    uu_headers = ["ID", "Nome do Caso de Uso", "Qtd. Transações", "Categoria", "Peso"]
    uu_widths = [Inches(0.8), Inches(3.2), Inches(1.3), Inches(1.2), Inches(1.2)]

    for j, h in enumerate(uu_headers):
        cell = t_uucw.cell(0, j)
        cell.width = uu_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(uucw_data):
        is_tot = (i == len(uucw_data)-1)
        bg = "EFF6FF" if is_tot else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_uucw.cell(i + 1, j)
            cell.width = uu_widths[j]
            format_cell(cell, val, bold=is_tot or (j==0), color=RGBColor(30,58,138) if is_tot else RGBColor(51,51,51), bg_color=bg)

    # ---------------------------------------------------------
    # SECTION 5: UUCP
    # ---------------------------------------------------------
    add_heading_1(doc, "5. Realizar o Cálculo dos UCP Não Ajustados (UUCP)")
    add_body_paragraph(doc, "O cálculo do UUCP (Unadjusted Use Case Points) resulta da soma direta do peso acumulado dos atores (UAW) e dos casos de uso (UUCW):")
    add_body_paragraph(doc, "UUCP = UAW + UUCW", bold_prefix="Fórmula: ")
    add_body_paragraph(doc, "UUCP = 13 + 165 = 178 UUCP", bold_prefix="Cálculo: ")

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 6: TCF
    # ---------------------------------------------------------
    add_heading_1(doc, "6. Calcular e Justificar o Valor Atribuído ao Fator de Complexidade Técnica (FCT / TCF)")
    add_body_paragraph(doc, "O Fator de Complexidade Técnica (TCF) é calculado a partir de 13 fatores técnicos (T1 a T13). Cada fator possui um peso fixo Wi e recebe uma nota Ni de 0 a 5.")
    add_body_paragraph(doc, "TCF = 0,6 + (0,01 × ∑(Wi × Ni))", bold_prefix="Fórmula: ")

    tcf_data = [
        ("T1", "Sistema Distribuído", "2.0", "4.0", "8.0", "Arquitetura REST desacoplada entre SPA e Backend Multi-tenant."),
        ("T2", "Tempo de Resposta / Desempenho", "1.0", "4.0", "4.0", "Exigência de resposta sub-segundo no registro de ponto e dashboards."),
        ("T3", "Eficiência do Usuário (Usabilidade)", "1.0", "5.0", "5.0", "Design UX/UI moderno com temas adaptativos e dark mode."),
        ("T4", "Processamento Interno Complexo", "1.0", "3.0", "3.0", "Algoritmos de horas extras, banco de horas e validação eSocial."),
        ("T5", "Reutilização de Código (LPS)", "1.0", "5.0", "5.0", "Plataforma baseada em LPS com ativos reutilizáveis em 3 tenants."),
        ("T6", "Facilidade de Instalação", "0.5", "4.0", "2.0", "Deployment automatizado via containers Docker e CI/CD."),
        ("T7", "Facilidade de Operação / Manutenção", "0.5", "4.0", "2.0", "Logs centralizados, migrations automatizadas e rollback fácil."),
        ("T8", "Portabilidade / Multi-tenant", "2.0", "4.0", "8.0", "Suporte nativo a multi-tenancy e isolamento de dados."),
        ("T9", "Facilidade de Mudança / Extensibilidade", "1.0", "4.0", "4.0", "Padrões Strategy/Factory para adição de novos tenants sem quebras."),
        ("T10", "Concorrência e Acessos Simultâneos", "1.0", "3.0", "3.0", "Suporte a picos de acessos na troca de turnos."),
        ("T11", "Recursos de Segurança / LGPD", "1.0", "4.0", "4.0", "Criptografia AES-256, hashes SHA-256 e conformidade LGPD."),
        ("T12", "Acesso Direto a Terceiros / APIs", "1.0", "2.5", "2.5", "Integrações REST/SOAP com eSocial e seguradoras."),
        ("T13", "Treinamento Especial do Usuário", "1.0", "0.0", "0.0", "Sistema autosserviço intuitivo que dispensa treinamento."),
        ("SOMA", "Subtotal Ponderado Técnico (∑TF)", "-", "-", "50.5", "Soma total ponderada dos 13 fatores técnicos")
    ]

    t_tcf = doc.add_table(rows=len(tcf_data) + 1, cols=6)
    t_tcf.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_tcf)

    tf_headers = ["Fator", "Descrição do Fator Técnico", "Peso", "Nota", "Subtotal", "Justificativa Técnica"]
    tf_widths = [Inches(0.6), Inches(2.2), Inches(0.7), Inches(0.7), Inches(0.8), Inches(2.7)]

    for j, h in enumerate(tf_headers):
        cell = t_tcf.cell(0, j)
        cell.width = tf_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(tcf_data):
        is_tot = (i == len(tcf_data)-1)
        bg = "EFF6FF" if is_tot else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_tcf.cell(i + 1, j)
            cell.width = tf_widths[j]
            format_cell(cell, val, bold=is_tot or (j==0), color=RGBColor(30,58,138) if is_tot else RGBColor(51,51,51), bg_color=bg)

    add_body_paragraph(doc, "TCF = 0,6 + (0,01 × 50,5) = 0,6 + 0,505 = 1,105", bold_prefix="Cálculo Final do TCF: ")
    add_body_paragraph(doc, "O TCF de 1,105 indica que a complexidade técnica e arquitetural acresce 10,5% ao tamanho bruto em pontos de casos de uso do sistema.")

    doc.add_page_break()

    # ---------------------------------------------------------
    # SECTION 7: ECF
    # ---------------------------------------------------------
    add_heading_1(doc, "7. Calcular e Justificar o Valor Atribuído ao Fator de Complexidade Ambiental (FCA / ECF)")
    add_body_paragraph(doc, "O Fator de Complexidade Ambiental (ECF) avalia a maturidade da equipe e estabilidade do ambiente de desenvolvimento. São 8 fatores (F1 a F8) com notas de 0 a 5.")
    add_body_paragraph(doc, "ECF = 1,4 + (-0,03 × ∑(Wi × Ni))", bold_prefix="Fórmula: ")

    ecf_data = [
        ("F1", "Familiaridade com o Processo (Scrum)", "1.5", "4.0", "6.0", "Adoção de Scrum com Sprints quinzenais e Code Review."),
        ("F2", "Experiência na Aplicação (RH)", "0.5", "2.0", "1.0", "Equipe técnica júnior/acadêmica com pouca vivência CLT."),
        ("F3", "Experiência em POO / Clean Code", "1.0", "4.0", "4.0", "Domínio de POO e boas práticas em PHP/Laravel/JS."),
        ("F4", "Capacidade do Analista Líder", "0.5", "4.0", "2.0", "Liderança proativa na modelagem de requisitos e arquitetura."),
        ("F5", "Motivação da Equipe", "1.0", "4.0", "4.0", "Equipe altamente motivada pelo desafio de construir uma LPS."),
        ("F6", "Estabilidade dos Requisitos", "2.0", "1.0", "2.0", "Requisitos dinâmicos ajustados ao longo da disciplina."),
        ("F7", "Pessoal em Tempo Parcial", "-1.0", "0.0", "0.0", "Nenhum desenvolvedor em regime parcial crítico."),
        ("F8", "Dificuldade da Linguagem de Programação", "-1.0", "2.5", "-2.5", "Laravel e Vue.js possuem excelente produtividade."),
        ("SOMA", "Subtotal Ponderado Ambiental (∑EF)", "-", "-", "21.5", "Soma total ponderada dos 8 fatores ambientais")
    ]

    t_ecf = doc.add_table(rows=len(ecf_data) + 1, cols=6)
    t_ecf.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_ecf)

    ef_headers = ["Fator", "Descrição do Fator Ambiental", "Peso", "Nota", "Subtotal", "Justificativa da Equipe"]
    ef_widths = [Inches(0.6), Inches(2.2), Inches(0.7), Inches(0.7), Inches(0.8), Inches(2.7)]

    for j, h in enumerate(ef_headers):
        cell = t_ecf.cell(0, j)
        cell.width = ef_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(ecf_data):
        is_tot = (i == len(ecf_data)-1)
        bg = "EFF6FF" if is_tot else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_ecf.cell(i + 1, j)
            cell.width = ef_widths[j]
            format_cell(cell, val, bold=is_tot or (j==0), color=RGBColor(30,58,138) if is_tot else RGBColor(51,51,51), bg_color=bg)

    add_body_paragraph(doc, "ECF = 1,4 + (-0,03 × 21,5) = 1,4 - 0,645 = 0,755", bold_prefix="Cálculo Final do ECF: ")
    add_body_paragraph(doc, "O ECF de 0,755 atua como um fator redutor de risco de 24,5%, devido ao alinhamento metodológico da equipe e domínio de orientações a objetos.")

    # ---------------------------------------------------------
    # SECTION 8: AUCP & COST ESTIMATION
    # ---------------------------------------------------------
    add_heading_1(doc, "8. Calcular os UCP Ajustados (AUCP) e Custos Conforme a Técnica")

    add_heading_2(doc, "8.1 Cálculo dos UCP Ajustados (AUCP)")
    add_body_paragraph(doc, "AUCP = UUCP × TCF × ECF", bold_prefix="Fórmula: ")
    add_body_paragraph(doc, "AUCP = 178 × 1,105 × 0,755 = 148,501 AUCP", bold_prefix="Cálculo: ")

    add_heading_2(doc, "8.2 Produtividade e Esforço (Regra de Schneider & Winters)")
    add_body_paragraph(doc, 
        "1. Contagem de Fatores F1..F6 com Nota < 3: F2 (nota 2.0) e F6 (nota 1.0) -> X = 2.\n"
        "2. Contagem de Fatores F7..F8 com Nota > 3: Nenhum -> Y = 0.\n"
        "3. Indicador de Risco Total X + Y = 2.\n"
        "4. Como X + Y <= 2, adota-se a taxa de produtividade padrão de 20 homem-horas por UCP.",
        bold_prefix="Avaliação de Risco:\n"
    )

    add_body_paragraph(doc, "Esforço Total = 148,501 AUCP × 20 h/UCP = 2.970,02 Horas (~2.970 Horas)", bold_prefix="Esforço Total: ")
    add_body_paragraph(doc, "Custo Total = 2.970,02 h × R$ 50,00/h = R$ 148.501,00", bold_prefix="Custo Financeiro Total (R$ 50,00/h): ")

    add_heading_2(doc, "8.3 Projeção de Cronograma por Tamanho de Equipe")

    sched_data = [
        ("1 Desenvolvedor", "160 h/mês", "18,56 meses", "~74 semanas"),
        ("2 Desenvolvedores", "320 h/mês", "9,28 meses", "~37 semanas"),
        ("4 Desenvolvedores", "640 h/mês", "4,64 meses", "~19 semanas (~4,5 meses)")
    ]

    t_sc = doc.add_table(rows=len(sched_data) + 1, cols=4)
    t_sc.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_sc)

    sc_headers = ["Tamanho da Equipe", "Capacidade Mensal", "Prazo Estimado (Meses)", "Prazo Estimado (Semanas)"]
    sc_widths = [Inches(2.0), Inches(1.7), Inches(2.0), Inches(2.0)]

    for j, h in enumerate(sc_headers):
        cell = t_sc.cell(0, j)
        cell.width = sc_widths[j]
        format_cell(cell, h, bold=True, color=RGBColor(255, 255, 255), bg_color="1E3A8A", align=WD_ALIGN_PARAGRAPH.LEFT)

    for i, row in enumerate(sched_data):
        bg = "EFF6FF" if i == 2 else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        for j, val in enumerate(row):
            cell = t_sc.cell(i + 1, j)
            cell.width = sc_widths[j]
            format_cell(cell, val, bold=(i==2), color=RGBColor(30,58,138) if i==2 else RGBColor(51,51,51), bg_color=bg)

    # ---------------------------------------------------------
    # SECTION 9: QUADRO RESUMO
    # ---------------------------------------------------------
    add_heading_1(doc, "9. Quadro Resumo de Métricas Consolidadas")

    resumo_data = [
        ("1. Peso dos Atores (UAW)", "13"),
        ("2. Peso dos Casos de Uso (UUCW)", "165"),
        ("3. Pontos de Caso de Uso Não Ajustados (UUCP)", "178 UCP"),
        ("4. Fator de Complexidade Técnica (TCF)", "1,105 (+10,5%)"),
        ("5. Fator de Complexidade Ambiental (ECF)", "0,755 (-24,5%)"),
        ("6. Pontos de Caso de Uso Ajustados (AUCP)", "148,501 AUCP"),
        ("7. Taxa de Produtividade (Schneider & Winters)", "20 homem-horas / UCP"),
        ("8. Esforço Total Estimado em Horas", "2.970,02 Horas"),
        ("9. Custo Total de Desenvolvimento (R$ 50/h)", "R$ 148.501,00"),
        ("10. Prazo Recomendo (Equipe de 4 Devs)", "4,64 Meses (~19 Semanas)")
    ]

    t_res = doc.add_table(rows=len(resumo_data) + 1, cols=2)
    t_res.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(t_res)

    format_cell(t_res.cell(0, 0), "Métrica / Indicador do HRTech Core", bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A")
    format_cell(t_res.cell(0, 1), "Valor Consolidado", bold=True, color=RGBColor(255, 255, 255), bg_color="0F172A")

    for i, (m, v) in enumerate(resumo_data):
        bg = "EFF6FF" if i in [5, 7, 8, 9] else ("F8FAFC" if i % 2 == 1 else "FFFFFF")
        cell_m = t_res.cell(i + 1, 0)
        cell_v = t_res.cell(i + 1, 1)
        cell_m.width = Inches(4.5)
        cell_v.width = Inches(3.2)
        format_cell(cell_m, m, bold=(i in [5, 7, 8, 9]), bg_color=bg)
        format_cell(cell_v, v, bold=True, color=RGBColor(30, 58, 138) if i in [5, 7, 8, 9] else RGBColor(51, 51, 51), bg_color=bg)

    output_path = "/home/fernando/Documentos/Faculdade/Projeto de medição e analise/HRTech_Core_Entrega_Completa_UCP.docx"
    doc.save(output_path)
    print(f"Documento Word salvo com sucesso em: {output_path}")

if __name__ == "__main__":
    main()
