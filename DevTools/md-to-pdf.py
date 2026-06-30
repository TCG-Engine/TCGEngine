#!/usr/bin/env python3
"""Convert a Markdown file to PDF.

A no-system-binary dev tool (pip-only deps): Markdown -> HTML -> PDF. It prefers the
`weasyprint` backend when installed (nicest output) and falls back to `xhtml2pdf`
(pure-Python, no native libraries) so it works on a bare machine.

Usage:
    python3 DevTools/md-to-pdf.py <input.md...> [--output PATH] [--backend auto|weasyprint|xhtml2pdf]
                                  [--css PATH] [--title TEXT]

  <input.md...>   One or more Markdown files. --output is only valid with a single input.
  --output PATH   Output path (single input only). Default: same name with a .pdf extension.
  --backend NAME  auto (default) | weasyprint | xhtml2pdf. auto = weasyprint if available, else xhtml2pdf.
  --css PATH      Extra CSS file appended after the built-in stylesheet (overrides defaults).
  --title TEXT    <title> for the document (default: the input file name).

Dependencies (install whichever backend you want):
    python3 -m pip install markdown weasyprint     # best fidelity (needs cairo/pango on some OSes)
    python3 -m pip install markdown xhtml2pdf       # pure-Python fallback, zero native deps

Exit code is non-zero if any file fails to convert.
"""
import argparse
import os
import sys

try:
    import markdown  # python-markdown
except ImportError:
    sys.exit("error: missing dependency 'markdown'. Install with:\n"
             "    python3 -m pip install markdown xhtml2pdf")

# Built-in stylesheet. Kept simple so both backends render it consistently.
DEFAULT_CSS = """
@page { size: A4; margin: 2cm; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.45; color: #1a1a1a; }
h1 { font-size: 20pt; border-bottom: 2px solid #444; padding-bottom: 4px; margin-top: 0; }
h2 { font-size: 15pt; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-top: 18px; }
h3 { font-size: 12.5pt; margin-top: 14px; }
h4 { font-size: 11pt; margin-top: 12px; }
p, li { font-size: 11pt; }
a { color: #1565c0; text-decoration: none; }
code { font-family: "Courier New", monospace; font-size: 9.5pt; background: #f2f2f2; padding: 1px 3px; }
pre { background: #f6f6f6; border: 1px solid #ddd; padding: 8px; font-family: "Courier New", monospace;
      font-size: 9pt; white-space: pre-wrap; word-wrap: break-word; }
pre code { background: transparent; padding: 0; }
blockquote { color: #555; border-left: 3px solid #ccc; margin-left: 0; padding-left: 12px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #bbb; padding: 5px 8px; font-size: 10pt; text-align: left; vertical-align: top; }
th { background: #ececec; }
hr { border: none; border-top: 1px solid #ccc; margin: 16px 0; }
"""

MD_EXTENSIONS = ["extra", "sane_lists", "toc", "nl2br"]


def render_html(md_text, title, extra_css):
    body = markdown.markdown(md_text, extensions=MD_EXTENSIONS)
    css = DEFAULT_CSS + ("\n" + extra_css if extra_css else "")
    return (
        "<!DOCTYPE html><html><head><meta charset='utf-8'>"
        f"<title>{title}</title><style>{css}</style></head>"
        f"<body>{body}</body></html>"
    )


def pick_backend(requested):
    """Return a (name, writer) pair. writer(html, out_path) -> None, raising on failure."""
    def weasy_writer(html, out_path):
        from weasyprint import HTML
        HTML(string=html, base_url=".").write_pdf(out_path)

    def xhtml_writer(html, out_path):
        from xhtml2pdf import pisa
        with open(out_path, "wb") as fh:
            result = pisa.CreatePDF(src=html, dest=fh)
        if result.err:
            raise RuntimeError(f"xhtml2pdf reported {result.err} error(s)")

    def have(mod):
        try:
            __import__(mod)
            return True
        except ImportError:
            return False

    if requested == "weasyprint":
        return "weasyprint", weasy_writer
    if requested == "xhtml2pdf":
        return "xhtml2pdf", xhtml_writer
    # auto: best available
    if have("weasyprint"):
        return "weasyprint", weasy_writer
    if have("xhtml2pdf"):
        return "xhtml2pdf", xhtml_writer
    sys.exit("error: no PDF backend available. Install one of:\n"
             "    python3 -m pip install weasyprint      # best fidelity\n"
             "    python3 -m pip install xhtml2pdf        # pure-Python, no native deps")


def main():
    ap = argparse.ArgumentParser(description="Convert Markdown file(s) to PDF.")
    ap.add_argument("inputs", nargs="+", help="Markdown file(s) to convert.")
    ap.add_argument("--output", help="Output PDF path (single input only).")
    ap.add_argument("--backend", choices=["auto", "weasyprint", "xhtml2pdf"], default="auto")
    ap.add_argument("--css", help="Extra CSS file appended to the built-in stylesheet.")
    ap.add_argument("--title", help="Document <title> (default: input file name).")
    args = ap.parse_args()

    if args.output and len(args.inputs) > 1:
        sys.exit("error: --output is only valid with a single input file.")

    extra_css = ""
    if args.css:
        with open(args.css, encoding="utf-8") as fh:
            extra_css = fh.read()

    backend_name, write_pdf = pick_backend(args.backend)

    failures = 0
    for src in args.inputs:
        if not os.path.isfile(src):
            print(f"skip: not a file: {src}", file=sys.stderr)
            failures += 1
            continue
        out_path = args.output or (os.path.splitext(src)[0] + ".pdf")
        title = args.title or os.path.basename(src)
        try:
            with open(src, encoding="utf-8") as fh:
                html = render_html(fh.read(), title, extra_css)
            write_pdf(html, out_path)
            print(f"ok: {src} -> {out_path}  [{backend_name}]")
        except Exception as exc:  # noqa: BLE001 - report and continue to next file
            print(f"fail: {src}: {exc}", file=sys.stderr)
            failures += 1

    sys.exit(1 if failures else 0)


if __name__ == "__main__":
    main()
