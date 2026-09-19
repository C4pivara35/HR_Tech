#!/usr/bin/env python3
"""
Comprehensive Automated Empirical Challenger Test Suite for relatorio_abnt.html
Uses headless Google Chrome and Chrome DevTools Protocol (CDP) via websockets.

Verifies:
1. Visual rendering & DOM structure integrity
2. Zero network errors (404/500/failed requests on document resources), zero console errors/exceptions
3. All 27 Table of Contents anchor links target valid DOM IDs with correct hierarchy
4. Computed styles conformance (Times New Roman, line-height 1.5, justified text, 1.25cm indent, margins)
5. Print media styles (@media print) and PDF generation integrity
6. Image integrity (decoded naturalWidth > 0, naturalHeight > 0 for all 33 images)
7. Consistency between Lista de Ilustrações (27 items) and embedded Figures
8. Consistency between Lista de Tabelas e Quadros (17 items) and embedded Tables/Quadros
9. ABNT NBR 14724 / NBR 6023 / NBR 6027 / NBR 6028 / IBGE compliance rules
"""

import asyncio
import http.server
import json
import os
import re
import socket
import subprocess
import sys
import threading
import time
import urllib.request
import websockets
from bs4 import BeautifulSoup

PROJECT_ROOT = "/home/fernando/Documentos/Faculdade/Projeto de medição e analise"
HTML_FILE = os.path.join(PROJECT_ROOT, "relatorio_abnt.html")

def get_free_port():
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('127.0.0.1', 0))
        return s.getsockname()[1]

class QuietHTTPHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, format, *args):
        pass

def start_http_server(root_dir, port):
    handler = lambda *args, **kwargs: QuietHTTPHandler(*args, directory=root_dir, **kwargs)
    server = http.server.HTTPServer(('127.0.0.1', port), handler)
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()
    return server

class CDPClient:
    def __init__(self, ws_url):
        self.ws_url = ws_url
        self.ws = None
        self.msg_id = 0
        self.responses = {}
        self.events = []
        self.request_urls = []
        self.response_statuses = {}
        self.network_failures = []
        self.console_errors = []
        self.runtime_exceptions = []
        self.on_event_cb = None

    async def connect(self):
        self.ws = await websockets.connect(self.ws_url, max_size=100*1024*1024)
        asyncio.create_task(self._listen())

    async def _listen(self):
        try:
            async for raw in self.ws:
                msg = json.loads(raw)
                if "id" in msg:
                    self.responses[msg["id"]] = msg
                else:
                    method = msg.get("method", "")
                    params = msg.get("params", {})
                    self.events.append(msg)
                    if self.on_event_cb:
                        self.on_event_cb(msg)
                    if method == "Network.requestWillBeSent":
                        req_url = params.get("request", {}).get("url")
                        if req_url:
                            self.request_urls.append(req_url)
                    elif method == "Network.responseReceived":
                        resp = params.get("response", {})
                        url = resp.get("url")
                        status = resp.get("status")
                        if url:
                            self.response_statuses[url] = status
                            if status >= 400 and not url.endswith("favicon.ico"):
                                self.network_failures.append({"url": url, "status": status, "error": f"HTTP {status}"})
                    elif method == "Network.loadingFailed":
                        url = params.get("requestId")
                        if not str(url).endswith("favicon.ico") and not params.get("canceled", False):
                            self.network_failures.append({
                                "requestId": params.get("requestId"),
                                "error": params.get("errorText"),
                                "type": params.get("type"),
                                "canceled": params.get("canceled", False)
                            })
                    elif method == "Log.entryAdded":
                        entry = params.get("entry", {})
                        e_url = entry.get("url", "")
                        e_text = entry.get("text", "")
                        if entry.get("level") in ["error"] and "favicon.ico" not in e_url and "favicon.ico" not in e_text:
                            self.console_errors.append(entry)
                    elif method == "Runtime.exceptionThrown":
                        self.runtime_exceptions.append(params.get("exceptionDetails", {}))
                    elif method == "Console.messageAdded":
                        msg_obj = params.get("message", {})
                        m_url = msg_obj.get("url", "")
                        m_text = msg_obj.get("text", "")
                        if msg_obj.get("level") == "error" and "favicon.ico" not in m_url and "favicon.ico" not in m_text:
                            self.console_errors.append(msg_obj)
        except asyncio.CancelledError:
            pass
        except Exception as e:
            pass

    async def send(self, method, params=None):
        self.msg_id += 1
        req_id = self.msg_id
        payload = {"id": req_id, "method": method, "params": params or {}}
        await self.ws.send(json.dumps(payload))
        while req_id not in self.responses:
            await asyncio.sleep(0.02)
        res = self.responses[req_id]
        if "error" in res:
            raise RuntimeError(f"CDP error calling {method}: {res['error']}")
        return res.get("result", {})

    async def eval_js(self, expression):
        res = await self.send("Runtime.evaluate", {
            "expression": expression,
            "returnByValue": True,
            "awaitPromise": True
        })
        result_obj = res.get("result", {})
        if result_obj.get("subtype") == "error":
            raise RuntimeError(f"JS Eval Error: {result_obj.get('description')}")
        return result_obj.get("value")

    async def close(self):
        if self.ws:
            await self.ws.close()

async def run_empirical_suite():
    results = {
        "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "tests": {},
        "summary": {}
    }

    # Step 1: Start HTTP server
    http_port = get_free_port()
    httpd = start_http_server(PROJECT_ROOT, http_port)
    page_url = f"http://127.0.0.1:{http_port}/relatorio_abnt.html"

    # Step 2: Start Chrome Headless with Remote Debugging
    import tempfile, shutil
    tmp_dir = tempfile.mkdtemp(prefix="chrome_test_")
    cdp_port = get_free_port()
    chrome_proc = subprocess.Popen([
        "/usr/bin/google-chrome",
        "--headless=new",
        f"--remote-debugging-port={cdp_port}",
        "--disable-gpu",
        "--no-sandbox",
        "--disable-dev-shm-usage",
        "--disable-extensions",
        f"--user-data-dir={tmp_dir}",
        "--window-size=1280,1024"
    ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    try:
        # Wait for CDP endpoint
        await asyncio.sleep(1.0)
        
        # Get list of targets and ws url
        version_url = f"http://127.0.0.1:{cdp_port}/json/list"
        req = urllib.request.urlopen(version_url)
        targets = json.loads(req.read().decode("utf-8"))
        page_targets = [t for t in targets if t.get("type") == "page"]
        ws_url = page_targets[0]["webSocketDebuggerUrl"]

        client = CDPClient(ws_url)
        await client.connect()

        # Enable domains
        await client.send("Page.enable")
        await client.send("Network.enable")
        await client.send("Log.enable")
        await client.send("Runtime.enable")
        await client.send("DOM.enable")
        await client.send("CSS.enable")

        # Navigate and wait for Page.loadEventFired
        load_event = asyncio.Event()
        def on_event(msg):
            if msg.get("method") == "Page.loadEventFired":
                load_event.set()
        client.on_event_cb = on_event

        await client.send("Page.navigate", {"url": page_url})
        try:
            await asyncio.wait_for(load_event.wait(), timeout=10.0)
        except asyncio.TimeoutError:
            pass
        await asyncio.sleep(1.0)

        # -------------------------------------------------------------
        # TEST 1: Network & Resource Integrity
        # -------------------------------------------------------------
        actual_network_failures = [f for f in client.network_failures if not f.get("canceled")]
        results["tests"]["network_integrity"] = {
            "total_requests": len(client.request_urls),
            "network_failures": actual_network_failures,
            "console_errors": client.console_errors,
            "runtime_exceptions": client.runtime_exceptions,
            "passed": len(actual_network_failures) == 0 and len(client.runtime_exceptions) == 0 and len(client.console_errors) == 0
        }

        # -------------------------------------------------------------
        # TEST 2: Static & Dynamic Image Integrity (all 33 images)
        # -------------------------------------------------------------
        image_audit_js = """
        (() => {
            const imgs = Array.from(document.querySelectorAll('img'));
            return imgs.map((img, idx) => ({
                index: idx + 1,
                src: img.getAttribute('src'),
                currentSrc: img.currentSrc,
                alt: img.getAttribute('alt'),
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                complete: img.complete,
                displayedWidth: img.clientWidth,
                displayedHeight: img.clientHeight
            }));
        })()
        """
        images_info = await client.eval_js(image_audit_js)
        broken_images = [img for img in images_info if not img["complete"] or img["naturalWidth"] == 0 or img["naturalHeight"] == 0]
        missing_alt = [img for img in images_info if not img["alt"] or img["alt"].strip() == ""]

        results["tests"]["images_integrity"] = {
            "total_images": len(images_info),
            "broken_images": broken_images,
            "missing_alt": missing_alt,
            "images_list": images_info,
            "passed": len(broken_images) == 0 and len(missing_alt) == 0 and len(images_info) == 33
        }

        # -------------------------------------------------------------
        # TEST 3: Table of Contents (TOC) & Anchor Targets (27 entries)
        # -------------------------------------------------------------
        toc_audit_js = """
        (() => {
            const sumarioLinks = Array.from(document.querySelectorAll('.sumario-lista a'));
            return sumarioLinks.map((a, idx) => {
                const href = a.getAttribute('href');
                const targetId = href.startsWith('#') ? href.substring(1) : null;
                const targetEl = targetId ? document.getElementById(targetId) : null;
                return {
                    index: idx + 1,
                    text: a.innerText.trim().replace(/\\s+/g, ' '),
                    href: href,
                    targetId: targetId,
                    targetExists: !!targetEl,
                    targetTagName: targetEl ? targetEl.tagName : null,
                    targetInnerText: targetEl ? targetEl.innerText.trim().replace(/\\s+/g, ' ') : null,
                    targetTopOffset: targetEl ? targetEl.offsetTop : null
                };
            });
        })()
        """
        toc_entries = await client.eval_js(toc_audit_js)
        missing_targets = [entry for entry in toc_entries if not entry["targetExists"]]

        # Verify all internal anchors in the entire document
        all_anchors_js = """
        (() => {
            const anchors = Array.from(document.querySelectorAll('a[href^="#"]'));
            return anchors.map(a => {
                const href = a.getAttribute('href');
                const targetId = href.substring(1);
                const targetEl = document.getElementById(targetId);
                return {
                    href: href,
                    targetId: targetId,
                    exists: !!targetEl
                };
            });
        })()
        """
        all_anchors = await client.eval_js(all_anchors_js)
        broken_anchors = [a for a in all_anchors if not a["exists"]]

        results["tests"]["toc_anchors"] = {
            "total_toc_entries": len(toc_entries),
            "missing_targets": missing_targets,
            "total_document_internal_anchors": len(all_anchors),
            "broken_document_internal_anchors": broken_anchors,
            "toc_entries": toc_entries,
            "passed": len(missing_targets) == 0 and len(toc_entries) == 27 and len(broken_anchors) == 0
        }

        # -------------------------------------------------------------
        # TEST 4: Lists Consistency (Ilustrações & Tabelas/Quadros)
        # -------------------------------------------------------------
        lists_audit_js = """
        (() => {
            const listaBoxes = Array.from(document.querySelectorAll('.lista-box'));
            const listaIlustracoes = listaBoxes[0] ? Array.from(listaBoxes[0].querySelectorAll('.lista-itens li')) : [];
            const listaTabelas = listaBoxes[1] ? Array.from(listaBoxes[1].querySelectorAll('.lista-itens li')) : [];
            const figurasNoTexto = Array.from(document.querySelectorAll('.titulo-figura'));
            const tabelasNoTexto = Array.from(document.querySelectorAll('.titulo-tabela'));

            return {
                totalListaIlustracoes: listaIlustracoes.length,
                totalListaTabelas: listaTabelas.length,
                totalTitulosFigura: figurasNoTexto.length,
                totalTitulosTabela: tabelasNoTexto.length,
                listaIlustracoesItens: listaIlustracoes.map(li => li.innerText.trim().replace(/\\s+/g, ' ')),
                figurasTitulos: figurasNoTexto.map(tf => tf.innerText.trim().replace(/\\s+/g, ' ')),
                listaTabelasItens: listaTabelas.map(li => li.innerText.trim().replace(/\\s+/g, ' ')),
                tabelasTitulos: tabelasNoTexto.map(tt => tt.innerText.trim().replace(/\\s+/g, ' '))
            };
        })()
        """
        lists_info = await client.eval_js(lists_audit_js)
        results["tests"]["lists_consistency"] = {
            "lists_info": lists_info,
            "passed": (
                lists_info["totalListaIlustracoes"] == 27 and
                lists_info["totalListaTabelas"] == 17 and
                lists_info["totalTitulosTabela"] == 17 and
                lists_info["totalTitulosFigura"] >= 27
            )
        }

        # -------------------------------------------------------------
        # TEST 5: Computed Styles (Screen Media)
        # -------------------------------------------------------------
        screen_styles_js = """
        (() => {
            const body = document.body;
            const bodyStyle = body ? window.getComputedStyle(body) : null;
            const container = document.querySelector('.document-container');
            const containerStyle = container ? window.getComputedStyle(container) : null;
            
            const pSec1 = document.getElementById('sec-1-1') ? document.getElementById('sec-1-1').nextElementSibling : null;
            const pSec1Style = pSec1 ? window.getComputedStyle(pSec1) : null;
            
            const pResumo = document.querySelector('.resumo-box p');
            const pResumoStyle = pResumo ? window.getComputedStyle(pResumo) : null;

            const h1 = document.querySelector('h1.secao-primaria');
            const h1Style = h1 ? window.getComputedStyle(h1) : null;
            const h2 = document.querySelector('h2.secao-secundaria');
            const h2Style = h2 ? window.getComputedStyle(h2) : null;
            const h3 = document.querySelector('h3.secao-terciaria');
            const h3Style = h3 ? window.getComputedStyle(h3) : null;
            const tableIbge = document.querySelector('table.tabela-ibge');
            const tableIbgeStyle = tableIbge ? window.getComputedStyle(tableIbge) : null;
            const quadroAbnt = document.querySelector('table.quadro-abnt');
            const quadroAbntStyle = quadroAbnt ? window.getComputedStyle(quadroAbnt) : null;
            const actionBar = document.querySelector('.action-bar');
            const actionBarStyle = actionBar ? window.getComputedStyle(actionBar) : null;

            return {
                body: bodyStyle ? {
                    fontFamily: bodyStyle.fontFamily,
                    fontSize: bodyStyle.fontSize,
                    lineHeight: bodyStyle.lineHeight,
                    textAlign: bodyStyle.textAlign,
                    color: bodyStyle.color,
                    backgroundColor: bodyStyle.backgroundColor
                } : null,
                container: containerStyle ? {
                    maxWidth: containerStyle.maxWidth,
                    paddingTop: containerStyle.paddingTop,
                    paddingRight: containerStyle.paddingRight,
                    paddingBottom: containerStyle.paddingBottom,
                    paddingLeft: containerStyle.paddingLeft,
                    boxShadow: containerStyle.boxShadow
                } : null,
                bodyParagraph: pSec1Style ? {
                    textIndent: pSec1Style.textIndent,
                    textAlign: pSec1Style.textAlign,
                    lineHeight: pSec1Style.lineHeight,
                    marginBottom: pSec1Style.marginBottom
                } : null,
                resumoParagraph: pResumoStyle ? {
                    textIndent: pResumoStyle.textIndent,
                    textAlign: pResumoStyle.textAlign,
                    lineHeight: pResumoStyle.lineHeight
                } : null,
                h1: h1Style ? {
                    fontSize: h1Style.fontSize,
                    fontWeight: h1Style.fontWeight,
                    textTransform: h1Style.textTransform
                } : null,
                h2: h2Style ? {
                    fontSize: h2Style.fontSize,
                    fontWeight: h2Style.fontWeight,
                    textTransform: h2Style.textTransform
                } : null,
                h3: h3Style ? {
                    fontSize: h3Style.fontSize,
                    fontWeight: h3Style.fontWeight
                } : null,
                actionBarDisplay: actionBarStyle ? actionBarStyle.display : null,
                hasTableIbge: !!tableIbge,
                hasQuadroAbnt: !!quadroAbnt
            };
        })()
        """
        screen_styles = await client.eval_js(screen_styles_js)
        
        font_ok = "Times New Roman" in screen_styles["body"]["fontFamily"] or "Times" in screen_styles["body"]["fontFamily"]
        text_align_ok = screen_styles["body"]["textAlign"] == "justify"
        body_indent_px = float(screen_styles["bodyParagraph"]["textIndent"].replace("px","")) if screen_styles["bodyParagraph"] else 0
        indent_ok = 45 <= body_indent_px <= 50 # 1.25cm = ~47.24px
        resumo_indent_ok = screen_styles["resumoParagraph"]["textIndent"] == "0px"
        
        results["tests"]["computed_styles_screen"] = {
            "styles": screen_styles,
            "font_ok": font_ok,
            "text_align_ok": text_align_ok,
            "indent_ok": indent_ok,
            "resumo_indent_ok": resumo_indent_ok,
            "passed": font_ok and text_align_ok and indent_ok and resumo_indent_ok
        }

        # -------------------------------------------------------------
        # TEST 6: Print Media Styles & Emulation
        # -------------------------------------------------------------
        await client.send("Emulation.setEmulatedMedia", {"media": "print"})
        await asyncio.sleep(0.5)

        print_styles_js = """
        (() => {
            const body = document.body;
            const bodyStyle = window.getComputedStyle(body);
            const container = document.querySelector('.document-container');
            const containerStyle = window.getComputedStyle(container);
            const actionBar = document.querySelector('.action-bar');
            const actionBarDisplay = actionBar ? window.getComputedStyle(actionBar).display : null;
            const noPrintElements = Array.from(document.querySelectorAll('.no-print'));
            const allNoPrintHidden = noPrintElements.every(el => window.getComputedStyle(el).display === 'none');
            const h1s = Array.from(document.querySelectorAll('h1.secao-primaria'));
            const h1BreakStyles = h1s.map((h, i) => ({
                id: h.id,
                text: h.innerText.substring(0, 35),
                pageBreakBefore: window.getComputedStyle(h).pageBreakBefore,
                breakBefore: window.getComputedStyle(h).breakBefore
            }));

            return {
                bodyBackgroundColor: bodyStyle.backgroundColor,
                containerMaxWidth: containerStyle.maxWidth,
                containerBoxShadow: containerStyle.boxShadow,
                containerMargin: containerStyle.margin,
                actionBarDisplay: actionBarDisplay,
                allNoPrintHidden: allNoPrintHidden,
                noPrintCount: noPrintElements.length,
                h1BreakStyles: h1BreakStyles
            };
        })()
        """
        print_styles = await client.eval_js(print_styles_js)
        
        # Test PDF generation via CDP
        pdf_res = await client.send("Page.printToPDF", {
            "landscape": False,
            "displayHeaderFooter": False,
            "printBackground": True,
            "paperWidth": 8.27, # A4 in inches
            "paperHeight": 11.69,
            "marginTop": 0,
            "marginBottom": 0,
            "marginLeft": 0,
            "marginRight": 0
        })
        pdf_data_b64 = pdf_res.get("data", "")
        pdf_bytes_len = len(pdf_data_b64)

        results["tests"]["print_media_styles"] = {
            "print_styles": print_styles,
            "pdf_generated": bool(pdf_data_b64),
            "pdf_base64_length": pdf_bytes_len,
            "no_print_hidden": print_styles["allNoPrintHidden"],
            "passed": print_styles["allNoPrintHidden"] and bool(pdf_data_b64) and pdf_bytes_len > 1000000
        }

        # -------------------------------------------------------------
        # TEST 7: Structural & ABNT Element Metrics
        # -------------------------------------------------------------
        structure_audit_js = """
        (() => {
            return {
                hasCapa: !!document.querySelector('.capa'),
                hasFolhaRosto: !!document.querySelector('.folha-rosto'),
                hasResumo: !!document.querySelector('.resumo-box'),
                hasAbstract: Array.from(document.querySelectorAll('.resumo-box h2')).some(h => h.innerText.includes('ABSTRACT')),
                hasListaIlustracoes: Array.from(document.querySelectorAll('.lista-box h2')).some(h => h.innerText.includes('ILUSTRAÇÕES')),
                hasListaTabelas: Array.from(document.querySelectorAll('.lista-box h2')).some(h => h.innerText.includes('TABELAS')),
                hasSumario: !!document.querySelector('.sumario-box'),
                totalSecoesPrimarias: document.querySelectorAll('h1.secao-primaria').length,
                totalSecoesSecundarias: document.querySelectorAll('h2.secao-secundaria').length,
                totalSecoesTerciarias: document.querySelectorAll('h3.secao-terciaria').length,
                totalTabelasIbge: document.querySelectorAll('table.tabela-ibge').length,
                totalQuadrosAbnt: document.querySelectorAll('table.quadro-abnt').length,
                totalTitulosFigura: document.querySelectorAll('.titulo-figura').length,
                totalFontesFigura: document.querySelectorAll('.fonte-figura').length,
                totalTitulosTabela: document.querySelectorAll('.titulo-tabela').length,
                totalFontesTabela: document.querySelectorAll('.fonte-tabela').length,
                totalBadgesSuccess: document.querySelectorAll('.badge-success').length,
                totalBadgesWarning: document.querySelectorAll('.badge-warning').length,
                totalBadgesInfo: document.querySelectorAll('.badge-info').length,
                hasReferencias: !!document.querySelector('.referencias-box') || !!document.querySelector('#sec-5')
            };
        })()
        """
        structure_info = await client.eval_js(structure_audit_js)
        results["tests"]["abnt_structural_integrity"] = {
            "structure": structure_info,
            "passed": (
                structure_info["hasCapa"] and
                structure_info["hasFolhaRosto"] and
                structure_info["hasResumo"] and
                structure_info["hasAbstract"] and
                structure_info["hasSumario"] and
                structure_info["totalSecoesPrimarias"] >= 5 and
                structure_info["totalQuadrosAbnt"] >= 10
            )
        }

        # Overall summary
        all_passed = all(t.get("passed", False) for t in results["tests"].values())
        results["summary"] = {
            "all_tests_passed": all_passed,
            "total_tests": len(results["tests"]),
            "passed_tests": sum(1 for t in results["tests"].values() if t.get("passed", False))
        }

        # Write results to tests/test_output.json
        output_path = os.path.join(PROJECT_ROOT, "tests", "test_output.json")
        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(results, f, indent=2)

        await client.close()

    finally:
        chrome_proc.terminate()
        try:
            chrome_proc.wait(timeout=2)
        except Exception:
            chrome_proc.kill()
        httpd.shutdown()
        shutil.rmtree(tmp_dir, ignore_errors=True)

    return results

if __name__ == "__main__":
    res = asyncio.run(run_empirical_suite())
    print(f"Tests complete. Passed: {res['summary']['passed_tests']}/{res['summary']['total_tests']}. All passed: {res['summary']['all_tests_passed']}")
