#!/usr/bin/env python3
"""
scripts/capture_screenshots.py
==============================
Automated Seeding and Headless Chrome Screenshot Pipeline for HRTech Core.
Discipline: Medição e Análise de Software (PUCPR)
Milestone: M3 — Seeding & Automated Screenshots (50 Functions / 252 PFB)

Responsibilities:
1. Re-seed SQLite database with 17 relational tables via `php seed.php`.
2. Manage background PHP 8.3 built-in web server on 127.0.0.1:8899.
3. Capture 20 crystal-clear screenshots covering all 50 functions:
   tela_01.png through tela_20.png at 1440x900 using Google Chrome Headless.
4. Synchronize and refresh the 17 legacy screenshots (tela_01_login.png to tela_17_portal_corretor.png).
5. Rigorously validate image integrity, non-empty size (>20KB), and dimensions via Pillow.
6. Gracefully shut down background web server.
"""

from __future__ import annotations

import os
import shutil
import socket
import subprocess
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import List, Tuple
from PIL import Image

PROJECT_ROOT = Path(__file__).resolve().parent.parent
SCREENSHOTS_DIR = PROJECT_ROOT / "screenshots"
SERVER_HOST = "127.0.0.1"
SERVER_PORT = 8899
SERVER_BASE_URL = f"http://{SERVER_HOST}:{SERVER_PORT}"
CHROME_BIN = "/usr/bin/google-chrome"
CHROME_WINDOW_SIZE = "1440,900"
EXPECTED_WIDTH = 1440
EXPECTED_HEIGHT = 900
MIN_FILE_SIZE_BYTES = 20 * 1024  # 20 KB minimum


@dataclass(frozen=True)
class ScreenshotTarget:
    filename: str
    title: str
    route: str
    function_reference: str


TARGET_SCREENSHOTS: List[ScreenshotTarget] = [
    ScreenshotTarget(
        filename="tela_01.png",
        title="Dashboard Executivo / Indicadores e Turnover",
        route="/app/index.php?route=dashboard",
        function_reference="SE_REL_TURNO_CONTRATO / DASHBOARD",
    ),
    ScreenshotTarget(
        filename="tela_02.png",
        title="Ponto Eletrônico / Espelho com Hora Noturna 52m30s e NTP",
        route="/app/index.php?route=timelogs",
        function_reference="SE_EXTRATO_ESPELHO_PONTO / AIE_NTP_HORA_LEGAL / CE_BATIDAS_DO_DIA",
    ),
    ScreenshotTarget(
        filename="tela_03.png",
        title="Colaboradores / Busca e Filtros de Entrada/Saída",
        route="/app/index.php?route=employees",
        function_reference="CE_BUSCA_COLABORADOR / ALI_COLABORADORES",
    ),
    ScreenshotTarget(
        filename="tela_04.png",
        title="Banco de Horas / Relatório de Horas Extras 50%/100%",
        route="/app/index.php?route=timelogs&action=bank_hours",
        function_reference="SE_APURACAO_BANCO_HORAS / CE_CONSULTAR_BANCO_HORAS",
    ),
    ScreenshotTarget(
        filename="tela_05.png",
        title="Cadastro de Colaborador / Validação Módulo 11 Receita CPF",
        route="/app/index.php?route=employees&action=new",
        function_reference="EE_CADASTRAR_COLABORADOR / AIE_RECEITA_FEDERAL_CPF",
    ),
    ScreenshotTarget(
        filename="tela_06.png",
        title="Detalhe Colaborador / eSocial S-2200 XML Viewer",
        route="/app/index.php?route=employees&action=detail&id=1",
        function_reference="SE_XML_ESOCIAL_S2200 / AIE_ESOCIAL_S2200",
    ),
    ScreenshotTarget(
        filename="tela_07.png",
        title="Organograma Gráfico Corporativo Hierárquico",
        route="/app/index.php?route=organogram",
        function_reference="CE_ORGANOGRAMA_GRAFICO / ALI_DEPARTAMENTOS_CARGOS",
    ),
    ScreenshotTarget(
        filename="tela_08.png",
        title="Importação AFD Relógio REP Portaria 671 MTE",
        route="/app/index.php?route=timelogs",
        function_reference="AIE_RELOGIO_REP_671 / EE_BATER_PONTO_671",
    ),
    ScreenshotTarget(
        filename="tela_09.png",
        title="Solicitação de Ajuste e Abono de Ponto",
        route="/app/index.php?route=timelogs&action=request_adjustment",
        function_reference="EE_AJUSTE_ABONO_PONTO",
    ),
    ScreenshotTarget(
        filename="tela_10.png",
        title="Férias CLT / Recibo com 1/3 Constitucional e Abono 10d",
        route="/app/index.php?route=vacations&action=receipt&id=1",
        function_reference="SE_CALCULO_RECIBO_FERIAS / ALI_FERIAS_CONCESSOES",
    ),
    ScreenshotTarget(
        filename="tela_11.png",
        title="Central de Gestão e Aprovações de Férias e Ponto",
        route="/app/index.php?route=approvals",
        function_reference="EE_SOLICITAR_FERIAS / EE_AJUSTE_ABONO_PONTO",
    ),
    ScreenshotTarget(
        filename="tela_12.png",
        title="Benefícios / Fatura de Coparticipação VT 6% / PAT 20%",
        route="/app/index.php?route=benefits",
        function_reference="SE_DEMONSTRATIVO_BENEFICIOS / ALI_BENEFICIOS_CONTRATOS",
    ),
    ScreenshotTarget(
        filename="tela_13.png",
        title="Telemetria e Simuladores das Operadoras VR/VA e ANS Saúde",
        route="/app/index.php?route=external_simulators",
        function_reference="AIE_OPERADORA_BENEFICIOS / AIE_OPERADORA_SAUDE_ANS",
    ),
    ScreenshotTarget(
        filename="tela_14.png",
        title="SST / Catraca Fábrica Bloqueio RED por ASO/EPI Vencido",
        route="/app/index.php?route=safety&action=test_gate&id=2",
        function_reference="SE_BLOQUEIO_CHAO_FABRICA / AIE_CATALOGO_CAEPI_MTE",
    ),
    ScreenshotTarget(
        filename="tela_15.png",
        title="SST / PPP Eletrônico e Alertas NR-6/NR-7",
        route="/app/index.php?route=safety",
        function_reference="SE_PAINEL_ALERTAS_NR6_NR7 / SE_RELATORIO_PPP_ELETRONICO",
    ),
    ScreenshotTarget(
        filename="tela_16.png",
        title="Compliance / Trilha de Auditoria SHA-256 e Laudo Pericial",
        route="/app/index.php?route=compliance",
        function_reference="ALI_AUDIT_LOG_LEDGER / SE_EXPORTAR_TRILHA_AUDITORIA",
    ),
    ScreenshotTarget(
        filename="tela_17.png",
        title="Seguros FinCorp / Relatório de Sinistralidade Loss Ratio",
        route="/app/index.php?route=insurance",
        function_reference="SE_RELATORIO_SINISTRALIDADE / AIE_FINCORP_SEGURADORA",
    ),
    ScreenshotTarget(
        filename="tela_18.png",
        title="Folha de Pagamento / Holerite Progressivo INSS/IRRF",
        route="/app/index.php?route=payroll&action=payslip&id=1",
        function_reference="SE_EMITIR_HOLERITE_MENSAL / SE_RECIBO_PROVISAO_13_FERIAS",
    ),
    ScreenshotTarget(
        filename="tela_19.png",
        title="Central de Relatórios Executivos e Exportações",
        route="/app/index.php?route=reports",
        function_reference="CENTRAL_RELATORIOS_EXECUTIVOS",
    ),
    ScreenshotTarget(
        filename="tela_20.png",
        title="Gateway PIX BACEN / Liquidação Instantânea SPI",
        route="/app/index.php?route=pix",
        function_reference="AIE_PIX_BACEN_GATEWAY",
    ),
]

LEGACY_TARGETS: List[Tuple[str, str]] = [
    ("tela_01_login.png", "/index.html"),
    ("tela_02_dashboard_colaborador.png", "/app/index.php?route=timelogs&action=bank_hours"),
    ("tela_03_dashboard_rh.png", "/app/index.php?route=dashboard"),
    ("tela_04_lista_colaboradores.png", "/app/index.php?route=employees"),
    ("tela_05_cadastro_colaborador.png", "/app/index.php?route=employees&action=new"),
    ("tela_06_detalhes_colaborador.png", "/app/index.php?route=employees&action=detail&id=1"),
    ("tela_07_organograma.png", "/app/index.php?route=organogram"),
    ("tela_08_espelho_ponto.png", "/app/index.php?route=timelogs"),
    ("tela_09_ajuste_ponto.png", "/app/index.php?route=timelogs&action=request_adjustment"),
    ("tela_10_gestao_ferias.png", "/app/index.php?route=vacations"),
    ("tela_11_aprovacoes_gestor.png", "/app/index.php?route=approvals"),
    ("tela_12_gestao_beneficios.png", "/app/index.php?route=benefits"),
    ("tela_13_desempenho_okrs.png", "/app/index.php?route=okrs"),
    ("tela_14_modulo_industrial.png", "/app/index.php?route=safety&action=test_gate&id=2"),
    ("tela_15_configuracao_empresa.png", "/app/index.php?route=admin"),
    ("tela_16_compliance_auditoria.png", "/app/index.php?route=compliance"),
    ("tela_17_portal_corretor.png", "/app/index.php?route=insurance"),
]


class ServerContext:
    """Manages the background PHP built-in server process lifecycle."""

    def __init__(self, host: str = SERVER_HOST, port: int = SERVER_PORT, docroot: Path = PROJECT_ROOT):
        self.host = host
        self.port = port
        self.docroot = docroot
        self.process: subprocess.Popen | None = None

    def start(self) -> None:
        if self.is_port_in_use():
            print(f"[*] Port {self.port} is already listening. Reusing existing server.")
            return

        print(f"[*] Starting PHP server on {self.host}:{self.port} (docroot: {self.docroot})...")
        cmd = ["php", "-S", f"{self.host}:{self.port}", "-t", str(self.docroot)]
        self.process = subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        self.wait_until_ready()
        print(f"✔ PHP server successfully running at {SERVER_BASE_URL} (PID: {self.process.pid}).")

    def wait_until_ready(self, timeout_seconds: float = 10.0) -> None:
        start_time = time.time()
        while time.time() - start_time < timeout_seconds:
            if self.is_port_in_use():
                return
            time.sleep(0.1)
        raise TimeoutError(f"PHP server failed to bind to {self.host}:{self.port} within {timeout_seconds}s.")

    def is_port_in_use(self) -> bool:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
            sock.settimeout(0.3)
            return sock.connect_ex((self.host, self.port)) == 0

    def stop(self) -> None:
        if self.process:
            print(f"[*] Terminating PHP server (PID: {self.process.pid})...")
            self.process.terminate()
            try:
                self.process.wait(timeout=3)
            except subprocess.TimeoutExpired:
                self.process.kill()
            self.process = None
            print("✔ PHP server stopped gracefully.")


def reseed_database() -> None:
    """Executes php seed.php to establish fresh relational baseline data."""
    print("=" * 70)
    print("ETAPA 1: Povoamento do Banco de Dados Relacional (17 Tabelas)")
    print("=" * 70)
    seed_script = PROJECT_ROOT / "seed.php"
    result = subprocess.run(["php", str(seed_script)], cwd=PROJECT_ROOT, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"❌ Erro ao executar seed.php:\n{result.stderr}")
        sys.exit(1)
    print(result.stdout.strip())
    print("✔ Banco de dados re-semeado com integridade total.\n")


def capture_screenshot(url: str, output_path: Path) -> None:
    """Invokes Google Chrome headless to capture a pixel-perfect 1440x900 screenshot."""
    cmd = [
        CHROME_BIN,
        "--headless=new",
        "--no-sandbox",
        "--disable-gpu",
        f"--window-size={CHROME_WINDOW_SIZE}",
        "--hide-scrollbars",
        f"--screenshot={output_path}",
        url,
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        raise RuntimeError(f"Google Chrome capture failed for {url}:\n{result.stderr}")


def validate_image(path: Path) -> Tuple[bool, str]:
    """Validates that a file exists, has size > 20KB, and has valid PNG dimensions."""
    if not path.is_file():
        return False, "Arquivo não encontrado."

    size = path.stat().st_size
    if size < MIN_FILE_SIZE_BYTES:
        return False, f"Tamanho insuficiente ({size} bytes < {MIN_FILE_SIZE_BYTES} bytes)."

    try:
        with Image.open(path) as img:
            img.verify()
        with Image.open(path) as img:
            width, height = img.size
            if img.format != "PNG":
                return False, f"Formato inválido: {img.format} (esperado PNG)."
            if width != EXPECTED_WIDTH or height != EXPECTED_HEIGHT:
                return False, f"Dimensões incompatíveis: {width}x{height} (esperado {EXPECTED_WIDTH}x{EXPECTED_HEIGHT})."
    except Exception as exc:
        return False, f"Erro de validação Pillow: {exc}"

    return True, f"{size:,} bytes | {EXPECTED_WIDTH}x{EXPECTED_HEIGHT} | PNG"


def run_pipeline() -> None:
    """Coordinates full screenshot generation and verification."""
    SCREENSHOTS_DIR.mkdir(parents=True, exist_ok=True)
    reseed_database()

    server = ServerContext()
    server.start()

    try:
        print("=" * 70)
        print("ETAPA 2: Captura Automatizada de 20 Telas Oficiais (tela_01 a tela_20)")
        print("=" * 70)

        for idx, target in enumerate(TARGET_SCREENSHOTS, start=1):
            out_file = SCREENSHOTS_DIR / target.filename
            full_url = f"{SERVER_BASE_URL}{target.route}"
            print(f"[{idx:02d}/20] Capturando: {target.filename} — {target.title}")
            print(f"      URL: {full_url}")
            print(f"      Funções: {target.function_reference}")

            capture_screenshot(full_url, out_file)
            is_valid, msg = validate_image(out_file)
            if not is_valid:
                raise ValueError(f"❌ Falha de validação em {target.filename}: {msg}")
            print(f"      ✔ OK: {msg}\n")

        print("=" * 70)
        print("ETAPA 3: Atualização das 17 Telas Legadas para Retrocompatibilidade")
        print("=" * 70)

        for legacy_filename, route in LEGACY_TARGETS:
            out_file = SCREENSHOTS_DIR / legacy_filename
            full_url = f"{SERVER_BASE_URL}{route}"
            print(f"[*] Capturando tela legada: {legacy_filename} ({full_url})")
            capture_screenshot(full_url, out_file)
            is_valid, msg = validate_image(out_file)
            if not is_valid:
                raise ValueError(f"❌ Falha de validação na tela legada {legacy_filename}: {msg}")
            print(f"    ✔ OK: {msg}")

        print("\n" + "=" * 70)
        print("ETAPA 4: Auditoria e Verificação Final de Todos os Arquivos de Imagem")
        print("=" * 70)

        total_checked = 0
        total_size = 0
        all_passed = True

        all_expected_files = [t.filename for t in TARGET_SCREENSHOTS] + [l[0] for l in LEGACY_TARGETS]
        for fname in sorted(set(all_expected_files)):
            p = SCREENSHOTS_DIR / fname
            is_valid, msg = validate_image(p)
            total_checked += 1
            if is_valid:
                size = p.stat().st_size
                total_size += size
                print(f"  [PASS] {fname:36} -> {size:8,d} bytes | 1440x900 | OK")
            else:
                print(f"  [FAIL] {fname:36} -> {msg}")
                all_passed = False

        print("=" * 70)
        print(f"Total de Imagens Auditadas: {total_checked}")
        print(f"Tamanho Consolidado:       {total_size / (1024 * 1024):.2f} MB")
        print(f"Resultado Final:           {'APROVADO (100% CONFORME)' if all_passed else 'FALHA'}")
        print("=" * 70)

        if not all_passed:
            sys.exit(1)

    finally:
        server.stop()


if __name__ == "__main__":
    run_pipeline()
