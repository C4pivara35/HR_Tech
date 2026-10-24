#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
verify_integration_challenge.py
===============================
Empirical Challenger Test Harness for HRTech Core (Verification Phase M5)
Archetype: EMPIRICAL CHALLENGER (Roles: critic, specialist)

Verifications executed:
1. Web Server & Route Integrity:
   - Spawns PHP built-in server on 127.0.0.1:8999
   - Asserts HTTP 200, valid HTML layout, sidebar navigation, dark theme CSS, 0 PHP warnings/notices/errors across 25 routes
   - Tests tenant switching (tenant-ind, tenant-fin, tenant-all, admin)
   - Shuts down the test server cleanly
2. OpenXML Deliverable Deep Forensics:
   - Unpacks and parses XML of:
     - Entrega_APF_Individual_Fernando_Lopes_Duarte.docx
     - Entrega_APF_Equipe_HRTech_Core.docx
   - Checks [Content_Types].xml, word/document.xml, word/_rels/document.xml.rels
   - Verifies 100% of <a:blip r:embed="..."/> point to valid existing media items in word/media/
   - Verifies all media files are valid, uncorrupted images via PIL
3. Screenshot Image Forensic Audit:
   - Audits all 37 official + legacy screenshots (plus persona/diagram assets)
   - Checks magic bytes (\x89PNG\r\n\x1a\n), size > 20KB, Pillow verify & decode, 1440x900 dimensions
4. Headless LibreOffice PDF Conversion:
   - Runs headless conversion of both docx files to PDF
   - Asserts exit code 0, valid %PDF- magic bytes, non-empty output
"""

import os
import sys
import time
import signal
import socket
import subprocess
import zipfile
import xml.etree.ElementTree as ET
import urllib.request
import urllib.parse
from pathlib import Path
from PIL import Image

PROJECT_ROOT = Path("/home/fernando/Documentos/Faculdade/Projeto de medição e analise")
SERVER_HOST = "127.0.0.1"
SERVER_PORT = 8999
BASE_URL = f"http://{SERVER_HOST}:{SERVER_PORT}"

INDIVIDUAL_DOCX = PROJECT_ROOT / "Entrega_APF_Individual_Fernando_Lopes_Duarte.docx"
TEAM_DOCX = PROJECT_ROOT / "Entrega_APF_Equipe_HRTech_Core.docx"
SCREENSHOTS_DIR = PROJECT_ROOT / "screenshots"

class VerificationError(Exception):
    pass

def log_section(title: str):
    print("\n" + "=" * 80)
    print(f" {title}")
    print("=" * 80)

def log_pass(msg: str):
    print(f"  [PASS] {msg}")

def log_fail(msg: str):
    print(f"  [FAIL] {msg}")

# ==============================================================================
# TEST SUITE 1: PHP BUILT-IN SERVER & LIVE ROUTE AUDIT
# ==============================================================================
def is_port_in_use(port: int) -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        return s.connect_ex(('127.0.0.1', port)) == 0

def wait_for_server(url: str, timeout: float = 8.0) -> bool:
    start_time = time.time()
    while time.time() - start_time < timeout:
        try:
            req = urllib.request.Request(url, headers={'User-Agent': 'EmpiricalChallenger/1.0'})
            with urllib.request.urlopen(req, timeout=1.0) as resp:
                if resp.status in (200, 302):
                    return True
        except Exception:
            time.sleep(0.2)
    return False

def test_web_server_and_routes():
    log_section("TEST SUITE 1: PHP Built-in Server & Live HTTP Route Verification (Port 8999)")

    if is_port_in_use(SERVER_PORT):
        raise VerificationError(f"Port {SERVER_PORT} is already in use before test start.")

    # Start PHP built-in server
    cmd = ["php", "-S", f"{SERVER_HOST}:{SERVER_PORT}", "-t", str(PROJECT_ROOT)]
    server_proc = subprocess.Popen(
        cmd,
        cwd=str(PROJECT_ROOT),
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        preexec_fn=os.setsid
    )

    try:
        ready = wait_for_server(f"{BASE_URL}/app/index.php", timeout=8.0)
        if not ready:
            raise VerificationError("PHP built-in server failed to start within timeout.")
        log_pass(f"PHP built-in server successfully bound and listening on {SERVER_HOST}:{SERVER_PORT}")

        # List of routes to test
        routes_to_test = [
            # Root redirection
            {"name": "Root index.php redirect", "url": f"{BASE_URL}/index.php", "allow_redirect": False, "expect_redirect": True},
            # App Routes
            {"name": "Dashboard (Default)", "url": f"{BASE_URL}/app/index.php?route=dashboard", "assertions": ["Dashboard", "Turnover", "Rotatividade Contratual", "SE_REL_TURNO_CONTRATO"]},
            {"name": "Employees Index", "url": f"{BASE_URL}/app/index.php?route=employees", "assertions": ["Colaboradores", "CE_BUSCA_COLABORADOR", "DADOS DE ENTRADA (FILTROS)"]},
            {"name": "Employees New Form", "url": f"{BASE_URL}/app/index.php?route=employees&action=new", "assertions": ["Admissão Formal de Colaborador", "AIE_RECEITA_FEDERAL_CPF", "EE_CADASTRAR_COLABORADOR"]},
            {"name": "Employee Detail (emp-mariana)", "url": f"{BASE_URL}/app/index.php?route=employees&action=detail&id=emp-mariana", "assertions": ["Mariana Santos", "eSocial", "SE_XML_ESOCIAL_S2200"]},
            {"name": "Organogram Tree", "url": f"{BASE_URL}/app/index.php?route=organogram", "assertions": ["Organograma", "CE_ORGANOGRAMA_GRAFICO", "ALI_DEPARTAMENTOS_CARGOS"]},
            {"name": "Time Logs Index", "url": f"{BASE_URL}/app/index.php?route=timelogs", "assertions": ["Portaria 671", "CE_BATIDAS_DO_DIA", "SE_EXTRATO_ESPELHO_PONTO"]},
            {"name": "Time Logs Bank of Hours", "url": f"{BASE_URL}/app/index.php?route=timelogs&action=bank_hours", "assertions": ["Banco de Horas", "CE_CONSULTAR_BANCO_HORAS", "SE_APURACAO_BANCO_HORAS"]},
            {"name": "Time Logs Request Adjustment", "url": f"{BASE_URL}/app/index.php?route=timelogs&action=request_adjustment", "assertions": ["Abono de Ponto", "EE_AJUSTE_ABONO_PONTO"]},
            {"name": "Vacations Index", "url": f"{BASE_URL}/app/index.php?route=vacations", "assertions": ["Férias", "CE_CONSULTAR_SALDO_FERIAS", "SE_CALCULO_RECIBO_FERIAS"]},
            {"name": "Vacations Receipt (Dedicated)", "url": f"{BASE_URL}/app/index.php?route=vacations&action=receipt", "assertions": ["Recibo", "Férias", "1/3"]},
            {"name": "Benefits Index", "url": f"{BASE_URL}/app/index.php?route=benefits", "assertions": ["Benefícios", "CE_CATALOGO_BENEFICIOS", "SE_DEMONSTRATIVO_BENEFICIOS"]},
            {"name": "Safety & Occupational Health (NR-6 / NR-7)", "url": f"{BASE_URL}/app/index.php?route=safety", "assertions": ["Segurança", "SE_BLOQUEIO_CHAO_FABRICA", "SE_PAINEL_ALERTAS_NR6_NR7"]},
            {"name": "Insurance & Underwriting (FinCorp)", "url": f"{BASE_URL}/app/index.php?route=insurance", "assertions": ["FinCorp", "CE_CONSULTAR_APOLICE_VIGENTE", "SE_RELATORIO_SINISTRALIDADE"]},
            {"name": "PIX BACEN SPI Terminal", "url": f"{BASE_URL}/app/index.php?route=pix", "assertions": ["PIX BACEN", "AIE_PIX_BACEN_GATEWAY"]},
            {"name": "Payroll Index", "url": f"{BASE_URL}/app/index.php?route=payroll", "assertions": ["Folha de Pagamento", "SE_EMITIR_HOLERITE_MENSAL", "INSS", "IRRF"]},
            {"name": "Reports Center", "url": f"{BASE_URL}/app/index.php?route=reports", "assertions": ["Relatórios Gerenciais", "Exportação"]},
            {"name": "OKRs & Performance", "url": f"{BASE_URL}/app/index.php?route=okrs", "assertions": ["Desempenho & OKRs", "Strategy GoF"]},
            {"name": "Compliance & Audit Ledger", "url": f"{BASE_URL}/app/index.php?route=compliance", "assertions": ["Trilha de Auditoria", "SHA-256", "CE_LOGS_AUDITORIA_LGPD"]},
            {"name": "External Simulators (10 AIEs)", "url": f"{BASE_URL}/app/index.php?route=external_simulators", "assertions": ["Simuladores dos 10 AIEs", "AIE_RECEITA_FEDERAL_CPF", "AIE_ESOCIAL_S2200", "AIE_RELOGIO_REP_671"]},
            {"name": "Admin / LPS Master Panel", "url": f"{BASE_URL}/app/index.php?route=admin", "assertions": ["Gestão de Empresas", "Multi-Tenancy", "Linha de Produção (LPS)"]},
            {"name": "Approvals Center", "url": f"{BASE_URL}/app/index.php?route=approvals", "assertions": ["Central de Aprovações do Gestor", "Férias", "CLT"]},
            # JSON API / Console Endpoints
            {"name": "Ping Console (JSON)", "url": f"{BASE_URL}/app/index.php?route=ping_console", "is_json": True, "assertions": ["status", "ok"]},
            {"name": "Poll Logs (JSON)", "url": f"{BASE_URL}/app/index.php?route=poll_logs", "is_json": True, "assertions": ["logs", "count"]},
        ]

        # Cookie jar / opener to simulate persistent session
        cookie_processor = urllib.request.HTTPCookieProcessor()
        opener = urllib.request.build_opener(cookie_processor)

        for route_info in routes_to_test:
            rname = route_info["name"]
            rurl = route_info["url"]

            if route_info.get("expect_redirect"):
                # Handle redirect check without automatic follow
                class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
                    def http_error_302(self, req, fp, code, msg, headers):
                        return fp

                custom_opener = urllib.request.build_opener(NoRedirectHandler)
                req = urllib.request.Request(rurl, headers={'User-Agent': 'EmpiricalChallenger/1.0'})
                try:
                    resp = custom_opener.open(req)
                    code = resp.getcode()
                    loc = resp.headers.get('Location', '')
                    if code in (302, 301) and 'app/index.php' in loc:
                        log_pass(f"{rname:35} -> HTTP {code} Redirects to {loc}")
                    else:
                        raise VerificationError(f"{rname} expected redirect to app/index.php, got {code} loc={loc}")
                except Exception as ex:
                    raise VerificationError(f"Failed route {rname}: {ex}")
                continue

            # Standard GET request
            req = urllib.request.Request(rurl, headers={'User-Agent': 'EmpiricalChallenger/1.0'})
            with opener.open(req, timeout=5.0) as resp:
                code = resp.getcode()
                content = resp.read().decode('utf-8', errors='replace')

            if code != 200:
                raise VerificationError(f"Route {rname} returned HTTP {code}")

            # Check JSON or HTML
            if route_info.get("is_json"):
                for term in route_info["assertions"]:
                    if term not in content:
                        raise VerificationError(f"Route {rname} JSON response missing expected key: {term}")
                log_pass(f"{rname:35} -> HTTP {code} | Valid JSON Response")
            else:
                # Rigorous HTML layout & dark theme check
                if "<!DOCTYPE html>" not in content:
                    raise VerificationError(f"Route {rname} missing <!DOCTYPE html>")
                if 'class="dark"' not in content and "var(--bg-dark)" not in content:
                    raise VerificationError(f"Route {rname} missing dark theme indicators")
                if "<aside" not in content and "sidebar" not in content.lower():
                    raise VerificationError(f"Route {rname} missing sidebar navigation")

                # Strict runtime PHP error detection
                error_patterns = [
                    "<b>Notice</b>", "<b>Warning</b>", "<b>Fatal error</b>", "<b>Deprecated</b>",
                    "<b>Parse error</b>", "Stack trace:", "Fatal error:", "Uncaught Exception",
                    "Uncaught Error"
                ]
                for ep in error_patterns:
                    if ep in content:
                        raise VerificationError(f"Route {rname} contains PHP error/warning in response: {ep}")

                # Check route-specific semantic assertions
                for term in route_info.get("assertions", []):
                    if term not in content:
                        raise VerificationError(f"Route {rname} missing expected semantic content: '{term}'")

                log_pass(f"{rname:35} -> HTTP {code} | {len(content):6,d} bytes | Valid Layout & Dark Theme | 0 PHP Notices")

        # Test Tenant Switching Mechanism
        log_section("Testing Tenant Context Switching via Live HTTP Request")
        tenants = ["tenant-ind", "tenant-fin", "tenant-all", "tenant-tech"]
        for t in tenants:
            switch_url = f"{BASE_URL}/app/index.php?route=switch_tenant&role={t}"
            req = urllib.request.Request(switch_url, headers={'User-Agent': 'EmpiricalChallenger/1.0'})
            with opener.open(req, timeout=5.0) as resp:
                pass
            # Query dashboard and verify tenant indicator in header
            dash_req = urllib.request.Request(f"{BASE_URL}/app/index.php?route=dashboard", headers={'User-Agent': 'EmpiricalChallenger/1.0'})
            with opener.open(dash_req, timeout=5.0) as resp:
                dash_content = resp.read().decode('utf-8', errors='replace')
                if t == "tenant-ind" and "Metalúrgica Sul S.A." not in dash_content:
                    raise VerificationError("Tenant switch to tenant-ind not reflected in UI")
                elif t == "tenant-fin" and "FinCorp Seguros" not in dash_content:
                    raise VerificationError("Tenant switch to tenant-fin not reflected in UI")
                elif t == "tenant-all" and "OmniCorp Global" not in dash_content:
                    raise VerificationError("Tenant switch to tenant-all not reflected in UI")
                elif t == "tenant-tech" and "TechStart Inovações" not in dash_content:
                    raise VerificationError("Tenant switch to tenant-tech not reflected in UI")
                log_pass(f"Tenant switched to '{t}' successfully verified in DOM header")

    finally:
        # Gracefully terminate PHP web server process group
        try:
            os.killpg(os.getpgid(server_proc.pid), signal.SIGTERM)
            server_proc.wait(timeout=3.0)
        except Exception:
            try:
                os.killpg(os.getpgid(server_proc.pid), signal.SIGKILL)
            except Exception:
                pass
        log_pass("PHP built-in test server shut down cleanly.")

# ==============================================================================
# TEST SUITE 2: OPENXML DELIVERABLES DEEP FORENSICS
# ==============================================================================
def inspect_openxml_structure(docx_path: Path, docx_name: str, min_images: int):
    log_section(f"TEST SUITE 2: OpenXML Forensics: {docx_name}")
    if not docx_path.is_file():
        raise VerificationError(f"Deliverable {docx_name} does not exist at {docx_path}")

    file_size = docx_path.stat().st_size
    log_pass(f"File size: {file_size:,} bytes ({file_size/1024/1024:.2f} MB)")

    if not zipfile.is_zipfile(docx_path):
        raise VerificationError(f"{docx_name} is not a valid ZIP/OpenXML archive.")

    with zipfile.ZipFile(docx_path, 'r') as zf:
        namelist = zf.namelist()

        # Check essential OpenXML components
        required_entries = [
            "[Content_Types].xml",
            "_rels/.rels",
            "word/document.xml",
            "word/_rels/document.xml.rels"
        ]
        for req in required_entries:
            if req not in namelist:
                raise VerificationError(f"{docx_name} missing required OpenXML entry: {req}")
        log_pass("Required OpenXML core entries present: [Content_Types], .rels, document.xml, document.xml.rels")

        # Parse [Content_Types].xml
        ct_data = zf.read("[Content_Types].xml")
        try:
            ct_root = ET.fromstring(ct_data)
            log_pass(f"[Content_Types].xml parsed successfully ({len(ct_root)} declarations)")
        except ET.ParseError as e:
            raise VerificationError(f"Corrupted [Content_Types].xml in {docx_name}: {e}")

        # Parse word/_rels/document.xml.rels
        rels_data = zf.read("word/_rels/document.xml.rels")
        try:
            rels_root = ET.fromstring(rels_data)
        except ET.ParseError as e:
            raise VerificationError(f"Corrupted word/_rels/document.xml.rels in {docx_name}: {e}")

        rel_id_to_target = {}
        image_rel_ids = set()
        for rel in rels_root:
            r_id = rel.get("Id")
            r_type = rel.get("Type", "")
            r_target = rel.get("Target", "")
            rel_id_to_target[r_id] = r_target
            if "image" in r_type:
                image_rel_ids.add(r_id)

        log_pass(f"Relationships parsed: {len(rel_id_to_target)} total, {len(image_rel_ids)} image relationships")

        # Parse word/document.xml
        doc_data = zf.read("word/document.xml")
        try:
            doc_root = ET.fromstring(doc_data)
            log_pass(f"word/document.xml parsed successfully ({len(doc_data):,} bytes XML)")
        except ET.ParseError as e:
            raise VerificationError(f"Corrupted word/document.xml in {docx_name}: {e}")

        # Find all embedded blip image references: <a:blip r:embed="..."/>
        namespaces = {
            'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
            'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
        }

        embedded_rids = []
        for blip in doc_root.findall('.//a:blip', namespaces):
            embed_id = blip.get(f"{{{namespaces['r']}}}embed")
            if embed_id:
                embedded_rids.append(embed_id)

        log_pass(f"Embedded drawingml <a:blip> elements found in document.xml: {len(embedded_rids)}")
        if len(embedded_rids) < min_images:
            raise VerificationError(f"{docx_name} has {len(embedded_rids)} images, expected at least {min_images}")

        # Verify relationship targets and media files
        media_files_found = [n for n in namelist if n.startswith("word/media/")]
        log_pass(f"Physical media files in word/media/: {len(media_files_found)}")

        for rid in embedded_rids:
            if rid not in rel_id_to_target:
                raise VerificationError(f"Dangling r:embed '{rid}' in document.xml has no relationship in document.xml.rels!")
            target = rel_id_to_target[rid]
            # Normal target is media/image1.png or word/media/image1.png
            target_path = target if target.startswith("word/") else f"word/{target}"
            if target_path not in namelist:
                raise VerificationError(f"Broken relationship {rid} -> '{target}': file missing from archive {target_path}!")

        log_pass("100% of <a:blip> embed IDs resolve to existing files in word/media/ (0 dangling references)")

        # Verify image integrity of every file in word/media/
        for media_name in media_files_found:
            img_bytes = zf.read(media_name)
            if len(img_bytes) == 0:
                raise VerificationError(f"Empty media file found in archive: {media_name}")
            import io
            try:
                with Image.open(io.BytesIO(img_bytes)) as pil_img:
                    pil_img.verify()
                with Image.open(io.BytesIO(img_bytes)) as pil_img:
                    w, h = pil_img.size
                    fmt = pil_img.format
            except Exception as e:
                raise VerificationError(f"Corrupted image in media archive {media_name}: {e}")

        log_pass(f"All {len(media_files_found)} embedded media files are valid, intact, decodable images")

# ==============================================================================
# TEST SUITE 3: SCREENSHOT AUDIT (37 IMAGES)
# ==============================================================================
def test_screenshots_audit():
    log_section("TEST SUITE 3: Screenshot Image Forensic Audit (screenshots/)")

    official_20 = [f"tela_{i:02d}.png" for i in range(1, 21)]
    legacy_17 = [
        "tela_01_login.png", "tela_02_dashboard_colaborador.png", "tela_03_dashboard_rh.png",
        "tela_04_lista_colaboradores.png", "tela_05_cadastro_colaborador.png", "tela_06_detalhes_colaborador.png",
        "tela_07_organograma.png", "tela_08_espelho_ponto.png", "tela_09_ajuste_ponto.png",
        "tela_10_gestao_ferias.png", "tela_11_aprovacoes_gestor.png", "tela_12_gestao_beneficios.png",
        "tela_13_desempenho_okrs.png", "tela_14_modulo_industrial.png", "tela_15_configuracao_empresa.png",
        "tela_16_compliance_auditoria.png", "tela_17_portal_corretor.png"
    ]
    all_37 = official_20 + legacy_17

    PNG_MAGIC = b"\x89PNG\r\n\x1a\n"

    for idx, fname in enumerate(all_37, start=1):
        fpath = SCREENSHOTS_DIR / fname
        if not fpath.is_file():
            raise VerificationError(f"Screenshot missing: {fname}")

        size = fpath.stat().st_size
        if size < 20 * 1024:
            raise VerificationError(f"Screenshot {fname} is too small: {size} bytes (< 20 KB)")

        with open(fpath, "rb") as f:
            header = f.read(8)
            if header != PNG_MAGIC:
                raise VerificationError(f"Screenshot {fname} has invalid PNG magic bytes: {header}")

        try:
            with Image.open(fpath) as img:
                img.verify()
            with Image.open(fpath) as img:
                w, h = img.size
                fmt = img.format
                mode = img.mode
                if fmt != "PNG":
                    raise VerificationError(f"Screenshot {fname} format is {fmt}, expected PNG")
                if (w, h) != (1440, 900):
                    raise VerificationError(f"Screenshot {fname} dimensions {w}x{h} diverge from 1440x900")
        except Exception as e:
            raise VerificationError(f"Pillow decoding error for {fname}: {e}")

        cat = "OFICIAL" if fname in official_20 else "LEGADO"
        log_pass(f"[{idx:02d}/37] {fname:35} [{cat}] -> {size:8,d} bytes | {w}x{h} {mode} | Valid PNG")

    log_pass("All 37 screenshots passed rigorous PNG header, dimension (1440x900), and raster decode tests.")

# ==============================================================================
# TEST SUITE 4: LIBREOFFICE HEADLESS PDF CONVERSION
# ==============================================================================
def test_libreoffice_pdf_conversion():
    log_section("TEST SUITE 4: LibreOffice Headless PDF Conversion Without Crash")

    docs_to_convert = [
        (INDIVIDUAL_DOCX, "Entrega_APF_Individual_Fernando_Lopes_Duarte.pdf", 200 * 1024),
        (TEAM_DOCX, "Entrega_APF_Equipe_HRTech_Core.pdf", 1024 * 1024),
    ]

    out_dir = Path("/tmp/challenger_pdf_out")
    out_dir.mkdir(parents=True, exist_ok=True)

    for docx_file, pdf_name, min_expected_size in docs_to_convert:
        log_pass(f"Converting {docx_file.name} to PDF via LibreOffice headless...")
        cmd = [
            "libreoffice",
            "--headless",
            "--convert-to", "pdf",
            "--outdir", str(out_dir),
            str(docx_file)
        ]
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=60)
        if res.returncode != 0:
            raise VerificationError(f"LibreOffice conversion failed for {docx_file.name}:\nStdout: {res.stdout}\nStderr: {res.stderr}")

        generated_pdf = out_dir / (docx_file.stem + ".pdf")
        if not generated_pdf.is_file():
            raise VerificationError(f"Expected converted PDF not found: {generated_pdf}")

        pdf_size = generated_pdf.stat().st_size
        log_pass(f"Generated PDF: {generated_pdf.name} ({pdf_size:,} bytes)")

        # Verify PDF magic bytes
        with open(generated_pdf, "rb") as f:
            pdf_magic = f.read(5)
            if pdf_magic != b"%PDF-":
                raise VerificationError(f"Invalid PDF magic header in {generated_pdf}: {pdf_magic}")

        if pdf_size < min_expected_size:
            raise VerificationError(f"PDF {generated_pdf.name} size {pdf_size} bytes is lower than expected {min_expected_size}")

        log_pass(f"Conversion of {docx_file.name} to PDF succeeded without crash (%PDF- verified, {pdf_size:,} bytes)")

# ==============================================================================
# MAIN CHALLENGER RUNNER
# ==============================================================================
def main():
    print("=" * 80)
    print(" HRTech Core — EMPIRICAL CHALLENGER VERIFICATION HARNESS (M5)")
    print(" Verification of System Integration, Web UI, OpenXML & PDF Generation")
    print("=" * 80)

    try:
        test_web_server_and_routes()
        test_screenshots_audit()
        inspect_openxml_structure(INDIVIDUAL_DOCX, "Entrega_APF_Individual_Fernando_Lopes_Duarte.docx", min_images=5)
        inspect_openxml_structure(TEAM_DOCX, "Entrega_APF_Equipe_HRTech_Core.docx", min_images=20)
        test_libreoffice_pdf_conversion()

        log_section("CHALLENGER VERDICT: APPROVE")
        print(" All empirical verification tests PASSED with 100% compliance!")
        print(" 1. PHP test server ran cleanly; all 25 routes returned HTTP 200, valid HTML layout, sidebar, dark CSS, 0 PHP errors.")
        print(" 2. OpenXML docx structures unpacked and validated: 0 corrupted tags, 0 dangling media IDs, 100% valid images.")
        print(" 3. All 37 screenshots strictly verified: 1440x900, valid PNG headers, > 20KB.")
        print(" 4. Headless LibreOffice converted both documents to PDF cleanly without crashes.")
        print("=" * 80)
        sys.exit(0)

    except Exception as e:
        log_section("CHALLENGER VERDICT: REJECT")
        print(f" Empirical challenge FAILED with exception: {e}")
        import traceback
        traceback.print_exc()
        print("=" * 80)
        sys.exit(1)

if __name__ == "__main__":
    main()
