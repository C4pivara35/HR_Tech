#!/usr/bin/env python3
"""
Empirical Challenger Test Harness — Milestone 3 (HRTech Core)
Challenger 1 (Generation 2)

Verifies:
1. HTML Integrity & DOM Well-Formedness (DOCTYPE, UTF-8, Title, Tag Balance)
2. Image Assets Integrity (All 33 images: path existence, PNG/JPEG format, dimensions, non-zero size, alt text)
3. Table of Contents & DOM Anchors (All 27 TOC links map to valid DOM IDs, 0 broken internal anchors)
4. ABNT NBR Standards & CSS Conformance (Times New Roman, 12pt, 3cm/2cm margins, 1.5 line-height, text-align justify, @media print)
5. Content Completeness (Pre-textual, 6 Personas, 14 Storyboard Steps, 6 Reusable Assets, 15 RFs, 10 RNFs, 3 Tenants, Exclusive Feature, Post-textual)
6. Headless Chrome CDP Live Rendering (Zero network errors, zero JS/console errors, image decoding, print media emulation, PDF generation)
"""

import asyncio
import html.parser
import http.server
import json
import os
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import threading
import time
import urllib.request
import websockets
from bs4 import BeautifulSoup
from PIL import Image

PROJECT_ROOT = "/home/fernando/Documentos/Faculdade/Projeto de medição e analise"
HTML_FILE = os.path.join(PROJECT_ROOT, "relatorio_abnt.html")

class TagBalanceParser(html.parser.HTMLParser):
    def __init__(self):
        super().__init__()
        self.stack = []
        self.void_elements = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}
        self.mismatches = []
        self.doctype = None

    def handle_decl(self, decl):
        if decl.lower().startswith('doctype'):
            self.doctype = decl

    def handle_starttag(self, tag, attrs):
        if tag.lower() not in self.void_elements:
            self.stack.append((tag.lower(), self.getpos()))

    def handle_endtag(self, tag):
        tag_lower = tag.lower()
        if tag_lower in self.void_elements:
            return
        if not self.stack:
            self.mismatches.append(f"Unexpected closing tag </{tag}> at line {self.getpos()[0]}")
            return
        last_tag, pos = self.stack.pop()
        if last_tag != tag_lower:
            self.mismatches.append(f"Mismatched closing tag </{tag}> at line {self.getpos()[0]}, expected </{last_tag}> (opened at line {pos[0]})")

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
        except Exception:
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

def run_static_verifications(html_content):
    results = {}

    # 1. HTML Integrity
    tag_parser = TagBalanceParser()
    tag_parser.feed(html_content)
    soup = BeautifulSoup(html_content, 'html.parser')

    meta_charsets = [m.get('charset') for m in soup.find_all('meta') if m.get('charset')]
    title_el = soup.title
    title_text = title_el.string.strip() if title_el and title_el.string else ""

    results["html_integrity"] = {
        "doctype": tag_parser.doctype,
        "doctype_valid": bool(tag_parser.doctype and "html" in tag_parser.doctype.lower()),
        "meta_charset": meta_charsets,
        "charset_utf8": any("utf-8" in c.lower() for c in meta_charsets),
        "title": title_text,
        "title_valid": bool(title_text and "HRTech Core" in title_text),
        "unclosed_tags": len(tag_parser.stack),
        "tag_mismatches": len(tag_parser.mismatches),
        "passed": bool(
            tag_parser.doctype and "html" in tag_parser.doctype.lower() and
            any("utf-8" in c.lower() for c in meta_charsets) and
            title_text and
            len(tag_parser.stack) == 0 and
            len(tag_parser.mismatches) == 0
        )
    }

    # 2. Image Assets Integrity (All 33 images)
    img_tags = soup.find_all('img')
    images_detail = []
    images_valid = True

    for idx, img in enumerate(img_tags):
        src = img.get('src', '')
        alt = img.get('alt', '')
        abs_path = os.path.join(PROJECT_ROOT, src) if not os.path.isabs(src) else src
        exists = os.path.exists(abs_path)
        file_size = os.path.getsize(abs_path) if exists else 0
        pil_ok = False
        img_format = None
        img_size = (0, 0)

        if exists and file_size > 0:
            try:
                with Image.open(abs_path) as im:
                    img_format = im.format
                    img_size = im.size
                    im.verify()
                with Image.open(abs_path) as im:
                    im.load()
                pil_ok = img_format in ['PNG', 'JPEG'] and img_size[0] > 0 and img_size[1] > 0
            except Exception:
                pil_ok = False

        item_passed = exists and file_size > 0 and pil_ok and bool(alt.strip())
        if not item_passed:
            images_valid = False

        images_detail.append({
            "index": idx + 1,
            "src": src,
            "alt": alt,
            "exists": exists,
            "file_size": file_size,
            "format": img_format,
            "dimensions": img_size,
            "pil_ok": pil_ok,
            "passed": item_passed
        })

    results["images_integrity"] = {
        "total_images": len(img_tags),
        "expected_images": 33,
        "valid_images_count": sum(1 for im in images_detail if im["passed"]),
        "images_detail": images_detail,
        "passed": len(img_tags) == 33 and images_valid
    }

    # 3. Table of Contents & Anchor Links (27 items)
    toc_links = soup.select('.sumario-lista a')
    toc_detail = []
    toc_all_valid = True

    for idx, a in enumerate(toc_links):
        href = a.get('href', '')
        text = a.text.strip().replace('\n', ' ')
        target_id = href[1:] if href.startswith('#') else None
        target_el = soup.find(id=target_id) if target_id else None
        target_found = bool(target_el)
        if not target_found:
            toc_all_valid = False
        toc_detail.append({
            "index": idx + 1,
            "text": text,
            "href": href,
            "target_id": target_id,
            "target_exists": target_found,
            "target_tag": target_el.name if target_el else None
        })

    # Internal anchors across whole document
    all_internal_anchors = soup.find_all('a', href=re.compile(r'^#.+'))
    broken_internal = []
    for a in all_internal_anchors:
        tid = a.get('href')[1:]
        if not soup.find(id=tid):
            broken_internal.append(a.get('href'))

    results["toc_anchors"] = {
        "total_toc_entries": len(toc_links),
        "expected_toc_entries": 27,
        "valid_toc_entries": sum(1 for t in toc_detail if t["target_exists"]),
        "broken_internal_anchors": broken_internal,
        "toc_detail": toc_detail,
        "passed": len(toc_links) == 27 and toc_all_valid and len(broken_internal) == 0
    }

    # 4. ABNT CSS Rules
    style_tags = soup.find_all('style')
    css_text = '\n'.join(s.string for s in style_tags if s.string)

    has_page_margin = bool(re.search(r'@page\s*\{[^}]*margin\s*:\s*3cm\s+2cm\s+2cm\s+3cm', css_text, re.IGNORECASE))
    has_font_times = bool(re.search(r'font-family\s*:\s*[^;]*"Times New Roman"', css_text, re.IGNORECASE))
    has_font_12pt = bool(re.search(r'font-size\s*:\s*12pt', css_text, re.IGNORECASE))
    has_line_height_15 = bool(re.search(r'line-height\s*:\s*1\.5', css_text, re.IGNORECASE))
    has_text_justify = bool(re.search(r'text-align\s*:\s*justify', css_text, re.IGNORECASE))
    has_media_print = '@media print' in css_text
    has_container_padding = bool(re.search(r'padding\s*:\s*30mm\s+20mm\s+20mm\s+30mm', css_text, re.IGNORECASE))

    results["abnt_css_rules"] = {
        "page_margin_3cm_2cm": has_page_margin,
        "container_padding_30mm_20mm": has_container_padding,
        "font_times_new_roman": has_font_times,
        "font_size_12pt": has_font_12pt,
        "line_height_1_5": has_line_height_15,
        "text_align_justify": has_text_justify,
        "media_print_present": has_media_print,
        "passed": (
            has_page_margin and has_container_padding and has_font_times and
            has_font_12pt and has_line_height_15 and has_text_justify and has_media_print
        )
    }

    # 5. Content Completeness
    # Personas (6)
    personas_found = []
    for p_id in range(1, 7):
        matches = soup.find_all(string=re.compile(f'Persona\\s+{p_id}\\b', re.IGNORECASE))
        personas_found.append({"persona_id": p_id, "found": len(matches) > 0, "count": len(matches)})

    # Storyboard Steps (14 steps in Quadro 2)
    sec_1_3 = soup.find(id='sec-1-3')
    storyboard_rows = []
    if sec_1_3:
        sb_table = sec_1_3.find_next('table')
        if sb_table:
            for row in sb_table.find_all('tr')[1:]:
                cols = [td.text.strip() for td in row.find_all(['td', 'th'])]
                if cols:
                    storyboard_rows.append(cols)

    # 6 Reusable Assets (AR01 to AR06 in Quadro 10)
    sec_2_2 = soup.find(id='sec-2-2')
    assets_rows = []
    if sec_2_2:
        assets_table = sec_2_2.find_next('table')
        if assets_table:
            for row in assets_table.find_all('tr')[1:]:
                cols = [td.text.strip() for td in row.find_all(['td', 'th'])]
                if cols:
                    assets_rows.append(cols)

    # 15 RFs (RF01 to RF15)
    rfs_found = sorted(list(set(re.findall(r'RF-?\s*(\d{2})', html_content, re.IGNORECASE))))
    expected_rfs = [f"{i:02d}" for i in range(1, 16)]
    all_rfs_present = all(rf in rfs_found for rf in expected_rfs)

    # 10 RNFs (RNF01 to RNF10)
    rnfs_found = sorted(list(set(re.findall(r'RNF-?\s*(\d{2})', html_content, re.IGNORECASE))))
    expected_rnfs = [f"{i:02d}" for i in range(1, 11)]
    all_rnfs_present = all(rnf in rnfs_found for rnf in expected_rnfs)

    # 3 Tenants (InovaTech, MetalForte, FinCorp Seguros)
    has_inovatech = "InovaTech" in html_content
    has_metalforte = "MetalForte" in html_content
    has_fincorp = "FinCorp Seguros" in html_content or "FinCorp" in html_content
    tenants_ok = has_inovatech and has_metalforte and has_fincorp

    # Exclusive Feature
    sec_3_4 = soup.find(id='sec-3-4')
    has_exclusive_feature = bool(sec_3_4 and ("Portal do Corretor" in sec_3_4.text or "FinCorp" in sec_3_4.text))

    # Pre-textual and Post-textual Elements
    has_capa = bool(soup.find(class_='capa'))
    has_folha_rosto = bool(soup.find(class_='folha-rosto'))
    has_resumo = bool(soup.find(class_='resumo-box'))
    has_abstract = any("ABSTRACT" in h.text.upper() for h in soup.find_all(['h2', 'h1']))
    has_lista_ilustracoes = any("LISTA DE ILUSTRAÇÕES" in h.text.upper() for h in soup.find_all(['h2', 'h1']))
    has_lista_tabelas = any("LISTA DE TABELAS" in h.text.upper() for h in soup.find_all(['h2', 'h1']))
    has_sumario = bool(soup.find(class_='sumario-box'))
    has_conclusao = bool(soup.find(id='sec-4'))
    has_referencias = bool(soup.find(id='sec-5'))

    content_passed = (
        len(personas_found) == 6 and all(p["found"] for p in personas_found) and
        len(storyboard_rows) >= 14 and
        len(assets_rows) >= 6 and
        all_rfs_present and
        all_rnfs_present and
        tenants_ok and
        has_exclusive_feature and
        has_capa and has_folha_rosto and has_resumo and has_abstract and
        has_lista_ilustracoes and has_lista_tabelas and has_sumario and
        has_conclusao and has_referencias
    )

    results["content_completeness"] = {
        "personas_count": len(personas_found),
        "personas_all_found": all(p["found"] for p in personas_found),
        "storyboard_steps_count": len(storyboard_rows),
        "reusable_assets_count": len(assets_rows),
        "rfs_found": rfs_found,
        "all_15_rfs_present": all_rfs_present,
        "rnfs_found": rnfs_found,
        "all_10_rnfs_present": all_rnfs_present,
        "tenants": {"InovaTech": has_inovatech, "MetalForte": has_metalforte, "FinCorp": has_fincorp},
        "tenants_ok": tenants_ok,
        "exclusive_feature_present": has_exclusive_feature,
        "pre_textual": {
            "capa": has_capa, "folha_rosto": has_folha_rosto,
            "resumo": has_resumo, "abstract": has_abstract,
            "lista_ilustracoes": has_lista_ilustracoes,
            "lista_tabelas": has_lista_tabelas, "sumario": has_sumario
        },
        "post_textual": {"conclusao": has_conclusao, "referencias": has_referencias},
        "passed": content_passed
    }

    return results

async def run_cdp_verifications():
    http_port = get_free_port()
    httpd = start_http_server(PROJECT_ROOT, http_port)
    page_url = f"http://127.0.0.1:{http_port}/relatorio_abnt.html"

    cdp_port = get_free_port()
    tmp_dir = tempfile.mkdtemp(prefix="chrome_cdp_")
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

    client = None
    cdp_results = {}

    try:
        await asyncio.sleep(1.0)
        version_url = f"http://127.0.0.1:{cdp_port}/json/list"
        req = urllib.request.urlopen(version_url)
        targets = json.loads(req.read().decode("utf-8"))
        page_targets = [t for t in targets if t.get("type") == "page"]
        ws_url = page_targets[0]["webSocketDebuggerUrl"]

        client = CDPClient(ws_url)
        await client.connect()

        await client.send("Page.enable")
        await client.send("Network.enable")
        await client.send("Log.enable")
        await client.send("Runtime.enable")
        await client.send("DOM.enable")
        await client.send("CSS.enable")

        await client.send("Page.navigate", {"url": page_url})

        # Wait for page readyState == complete
        for _ in range(50):
            res = await client.send("Runtime.evaluate", {"expression": "document.readyState", "returnByValue": True})
            val = res.get("result", {}).get("value")
            if val == "complete":
                break
            await asyncio.sleep(0.1)

        await asyncio.sleep(1.0)

        # 1. Network failures & console errors
        actual_network_failures = [f for f in client.network_failures if not f.get("canceled")]
        cdp_results["network_integrity"] = {
            "total_requests": len(client.request_urls),
            "network_failures": actual_network_failures,
            "console_errors": client.console_errors,
            "runtime_exceptions": client.runtime_exceptions,
            "passed": len(actual_network_failures) == 0 and len(client.runtime_exceptions) == 0 and len(client.console_errors) == 0
        }

        # 2. Live DOM Image Decoding Audit (33 images)
        image_audit_js = """
        (() => {
            const imgs = Array.from(document.querySelectorAll('img'));
            return imgs.map((img, idx) => ({
                index: idx + 1,
                src: img.getAttribute('src'),
                naturalWidth: img.naturalWidth,
                naturalHeight: img.naturalHeight,
                complete: img.complete,
                displayedWidth: img.clientWidth,
                displayedHeight: img.clientHeight
            }));
        })()
        """
        images_rendered = await client.eval_js(image_audit_js)
        broken_rendered_images = [img for img in images_rendered if not img["complete"] or img["naturalWidth"] == 0 or img["naturalHeight"] == 0]

        cdp_results["cdp_images"] = {
            "total_rendered_images": len(images_rendered),
            "broken_rendered_images": broken_rendered_images,
            "passed": len(images_rendered) == 33 and len(broken_rendered_images) == 0
        }

        # 3. Computed Styles Audit
        styles_js = """
        (() => {
            const bodyStyle = window.getComputedStyle(document.body);
            const container = document.querySelector('.document-container');
            const containerStyle = container ? window.getComputedStyle(container) : null;
            const sec1 = document.getElementById('sec-1-1');
            const p = sec1 ? sec1.nextElementSibling : null;
            const pStyle = p ? window.getComputedStyle(p) : null;

            return {
                fontFamily: bodyStyle.fontFamily,
                fontSize: bodyStyle.fontSize,
                lineHeight: bodyStyle.lineHeight,
                textAlign: bodyStyle.textAlign,
                containerPadding: containerStyle ? containerStyle.padding : null,
                paragraphIndent: pStyle ? pStyle.textIndent : null
            };
        })()
        """
        comp_styles = await client.eval_js(styles_js)
        font_ok = "Times New Roman" in comp_styles["fontFamily"] or "Times" in comp_styles["fontFamily"]
        align_ok = comp_styles["textAlign"] == "justify"

        cdp_results["computed_styles"] = {
            "computed": comp_styles,
            "font_ok": font_ok,
            "align_ok": align_ok,
            "passed": font_ok and align_ok
        }

        # 4. Print Media & PDF Generation
        await client.send("Emulation.setEmulatedMedia", {"media": "print"})
        await asyncio.sleep(0.3)

        print_check_js = """
        (() => {
            const noPrint = Array.from(document.querySelectorAll('.no-print'));
            const allHidden = noPrint.every(el => window.getComputedStyle(el).display === 'none');
            return { allHidden, count: noPrint.length };
        })()
        """
        print_status = await client.eval_js(print_check_js)

        pdf_res = await client.send("Page.printToPDF", {
            "landscape": False,
            "displayHeaderFooter": False,
            "printBackground": True,
            "paperWidth": 8.27,
            "paperHeight": 11.69
        })
        pdf_data = pdf_res.get("data", "")

        cdp_results["print_and_pdf"] = {
            "no_print_hidden": print_status["allHidden"],
            "no_print_elements_count": print_status["count"],
            "pdf_generated": bool(pdf_data),
            "pdf_base64_len": len(pdf_data),
            "passed": print_status["allHidden"] and bool(pdf_data) and len(pdf_data) > 100000
        }

    finally:
        if client:
            await client.close()
        chrome_proc.terminate()
        try:
            chrome_proc.wait(timeout=2)
        except Exception:
            chrome_proc.kill()
        httpd.shutdown()
        shutil.rmtree(tmp_dir, ignore_errors=True)

    return cdp_results

def main():
    print("=" * 70)
    print("HRTech Core — Milestone 3 Challenger 1 (Gen 2) Test Suite")
    print("=" * 70)

    with open(HTML_FILE, 'r', encoding='utf-8') as f:
        html_content = f.read()

    print("[*] Running static DOM, assets, CSS, and content verifications...")
    static_results = run_static_verifications(html_content)

    print("[*] Running headless Chrome CDP live rendering verifications...")
    cdp_results = asyncio.run(run_cdp_verifications())

    full_results = {
        "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "target_file": HTML_FILE,
        "static_checks": static_results,
        "cdp_checks": cdp_results
    }

    all_passed = (
        static_results["html_integrity"]["passed"] and
        static_results["images_integrity"]["passed"] and
        static_results["toc_anchors"]["passed"] and
        static_results["abnt_css_rules"]["passed"] and
        static_results["content_completeness"]["passed"] and
        cdp_results["network_integrity"]["passed"] and
        cdp_results["cdp_images"]["passed"] and
        cdp_results["computed_styles"]["passed"] and
        cdp_results["print_and_pdf"]["passed"]
    )

    full_results["all_passed"] = all_passed

    output_path = os.path.join(PROJECT_ROOT, "tests", "challenger_results.json")
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(full_results, f, indent=2)

    print("\n" + "=" * 70)
    print("CHALLENGER TEST RESULTS SUMMARY")
    print("=" * 70)
    print(f"1. HTML Integrity & Tag Balance:  {'PASS' if static_results['html_integrity']['passed'] else 'FAIL'}")
    print(f"2. Image Assets (33 images):      {'PASS' if static_results['images_integrity']['passed'] else 'FAIL'}")
    print(f"3. Table of Contents (27 links):   {'PASS' if static_results['toc_anchors']['passed'] else 'FAIL'}")
    print(f"4. ABNT CSS Conformance:          {'PASS' if static_results['abnt_css_rules']['passed'] else 'FAIL'}")
    print(f"5. Content Completeness:          {'PASS' if static_results['content_completeness']['passed'] else 'FAIL'}")
    print(f"6. CDP Network & Console Errors:  {'PASS' if cdp_results['network_integrity']['passed'] else 'FAIL'}")
    print(f"7. CDP Image Decoding:            {'PASS' if cdp_results['cdp_images']['passed'] else 'FAIL'}")
    print(f"8. CDP Computed Styles:           {'PASS' if cdp_results['computed_styles']['passed'] else 'FAIL'}")
    print(f"9. CDP Print Media & PDF:         {'PASS' if cdp_results['print_and_pdf']['passed'] else 'FAIL'}")
    print("-" * 70)
    print(f"OVERALL STATUS: {'ALL CHECKS PASSED (100%)' if all_passed else 'SOME CHECKS FAILED'}")
    print("=" * 70)

    if not all_passed:
        sys.exit(1)

if __name__ == "__main__":
    main()
