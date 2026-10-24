#!/usr/bin/env python3
"""
verify_screenshots.py
=====================
Independent Verification and Forensic Audit Script for HRTech Core Screenshots.
Discipline: Medição e Análise de Software (PUCPR)
Milestone: M3 — Seeding & Automated Screenshots

Verifies:
1. Complete existence of 20 official screenshots (tela_01.png - tela_20.png).
2. Complete existence of 17 legacy screenshots (tela_01_login.png - tela_17_portal_corretor.png).
3. Non-empty file constraint (> 20 KB per image).
4. Image integrity and validity confirmed via Pillow.
5. Canvas / report resolution compliance (1440x900).
"""

from __future__ import annotations

import sys
from pathlib import Path
from PIL import Image

PROJECT_ROOT = Path(__file__).resolve().parent
SCREENSHOTS_DIR = PROJECT_ROOT / "screenshots"

OFFICIAL_FILES = [f"tela_{i:02d}.png" for i in range(1, 21)]

LEGACY_FILES = [
    "tela_01_login.png",
    "tela_02_dashboard_colaborador.png",
    "tela_03_dashboard_rh.png",
    "tela_04_lista_colaboradores.png",
    "tela_05_cadastro_colaborador.png",
    "tela_06_detalhes_colaborador.png",
    "tela_07_organograma.png",
    "tela_08_espelho_ponto.png",
    "tela_09_ajuste_ponto.png",
    "tela_10_gestao_ferias.png",
    "tela_11_aprovacoes_gestor.png",
    "tela_12_gestao_beneficios.png",
    "tela_13_desempenho_okrs.png",
    "tela_14_modulo_industrial.png",
    "tela_15_configuracao_empresa.png",
    "tela_16_compliance_auditoria.png",
    "tela_17_portal_corretor.png",
]

MIN_SIZE_BYTES = 20 * 1024  # 20 KB


def verify_file(filename: str, category: str) -> bool:
    path = SCREENSHOTS_DIR / filename
    if not path.is_file():
        print(f"  [FAIL] {filename:35} [{category}] -> ARQUIVO INEXISTENTE")
        return False

    size = path.stat().st_size
    if size < MIN_SIZE_BYTES:
        print(f"  [FAIL] {filename:35} [{category}] -> TAMANHO INSUFICIENTE ({size} bytes < {MIN_SIZE_BYTES})")
        return False

    try:
        with Image.open(path) as img:
            img.verify()
        with Image.open(path) as img:
            w, h = img.size
            fmt = img.format
            if fmt != "PNG":
                print(f"  [FAIL] {filename:35} [{category}] -> FORMATO INCORRETO: {fmt}")
                return False
            if w != 1440 or h != 900:
                print(f"  [FAIL] {filename:35} [{category}] -> DIMENSÕES INVÁLIDAS: {w}x{h} (esperado 1440x900)")
                return False
    except Exception as exc:
        print(f"  [FAIL] {filename:35} [{category}] -> CORRUPÇÃO / ERRO PILLOW: {exc}")
        return False

    print(f"  [PASS] {filename:35} [{category}] -> {size:8,d} bytes | {w}x{h} | {fmt} | VÁLIDO")
    return True


def main() -> int:
    print("=" * 75)
    print(" HRTech Core — Suíte de Verificação e Auditoria Forense de Screenshots")
    print(" Diretório: " + str(SCREENSHOTS_DIR))
    print("=" * 75)

    if not SCREENSHOTS_DIR.is_dir():
        print(f"ERRO CRÍTICO: Diretório {SCREENSHOTS_DIR} não encontrado.")
        return 1

    failures = 0
    total = len(OFFICIAL_FILES) + len(LEGACY_FILES)

    print("\n1. Verificação das 20 Telas Oficiais (Milestone M3):")
    for f in OFFICIAL_FILES:
        if not verify_file(f, "OFICIAL"):
            failures += 1

    print("\n2. Verificação das 17 Telas Legadas (Retrocompatibilidade):")
    for f in LEGACY_FILES:
        if not verify_file(f, "LEGADO"):
            failures += 1

    print("\n" + "=" * 75)
    print(f" Total de Telas Auditadas: {total}")
    print(f" Aprovadas:                {total - failures}")
    print(f" Falhas:                   {failures}")
    print("=" * 75)

    if failures == 0:
        print("🎉 100% DAS TELAS FORAM AUDITADAS COM SUCESSO! INTEGRIDADE TOTAL.")
        return 0
    else:
        print("❌ FORAM DETECTADAS FALHAS NA AUDITORIA DE SCREENSHOTS.")
        return 1


if __name__ == "__main__":
    sys.exit(main())
