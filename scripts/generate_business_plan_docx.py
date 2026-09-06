#!/usr/bin/env python3

from __future__ import annotations

import argparse
import html
import re
from datetime import datetime, timezone
from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZIP_DEFLATED, ZipFile


W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
CP_NS = "http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
DC_NS = "http://purl.org/dc/elements/1.1/"
DCTERMS_NS = "http://purl.org/dc/terms/"
XSI_NS = "http://www.w3.org/2001/XMLSchema-instance"

ET.register_namespace("w", W_NS)
ET.register_namespace("cp", CP_NS)
ET.register_namespace("dc", DC_NS)
ET.register_namespace("dcterms", DCTERMS_NS)
ET.register_namespace("xsi", XSI_NS)


def qn(tag: str) -> str:
    return f"{{{W_NS}}}{tag}"


def collapse_ws(text: str) -> str:
    return re.sub(r"\s+", " ", html.unescape(text or "")).strip()


def extract_body(html_text: str) -> str:
    match = re.search(r"<body[^>]*>(.*)</body>", html_text, flags=re.S | re.I)
    if not match:
        raise ValueError("HTML body not found")
    return match.group(1)


def extract_text(node: ET.Element) -> str:
    return collapse_ws("".join(node.itertext()))


class DocxBuilder:
    def __init__(self) -> None:
        self.document = ET.Element(qn("document"))
        self.body = ET.SubElement(self.document, qn("body"))

    def add_page_break(self) -> None:
        p = ET.SubElement(self.body, qn("p"))
        r = ET.SubElement(p, qn("r"))
        ET.SubElement(r, qn("br"), {qn("type"): "page"})

    def add_paragraph(
        self,
        text: str,
        *,
        align: str = "both",
        bold: bool = False,
        size: int = 28,
        spacing_after: int = 160,
        shading: str | None = None,
    ) -> None:
        text = collapse_ws(text)
        if not text:
            return

        p = ET.SubElement(self.body, qn("p"))
        p_pr = ET.SubElement(p, qn("pPr"))
        if align:
            ET.SubElement(p_pr, qn("jc"), {qn("val"): align})
        ET.SubElement(p_pr, qn("spacing"), {qn("after"): str(spacing_after), qn("line"): "360", qn("lineRule"): "auto"})
        if shading:
            ET.SubElement(p_pr, qn("shd"), {qn("val"): "clear", qn("fill"): shading})

        r = ET.SubElement(p, qn("r"))
        r_pr = ET.SubElement(r, qn("rPr"))
        ET.SubElement(r_pr, qn("rFonts"), {qn("ascii"): "Times New Roman", qn("hAnsi"): "Times New Roman", qn("cs"): "Times New Roman"})
        ET.SubElement(r_pr, qn("sz"), {qn("val"): str(size)})
        ET.SubElement(r_pr, qn("szCs"), {qn("val"): str(size)})
        if bold:
            ET.SubElement(r_pr, qn("b"))
        t = ET.SubElement(r, qn("t"))
        if text.startswith(" ") or text.endswith(" ") or "  " in text:
            t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
        t.text = text

    def add_table(self, rows: list[list[str]], header_rows: int = 1) -> None:
        rows = [[collapse_ws(cell) for cell in row] for row in rows if any(collapse_ws(cell) for cell in row)]
        if not rows:
            return

        cols = max(len(row) for row in rows)
        widths = [str(int(9000 / cols))] * cols

        tbl = ET.SubElement(self.body, qn("tbl"))
        tbl_pr = ET.SubElement(tbl, qn("tblPr"))
        ET.SubElement(tbl_pr, qn("tblW"), {qn("w"): "5000", qn("type"): "pct"})
        ET.SubElement(tbl_pr, qn("tblLayout"), {qn("type"): "fixed"})
        borders = ET.SubElement(tbl_pr, qn("tblBorders"))
        for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
            ET.SubElement(
                borders,
                qn(edge),
                {qn("val"): "single", qn("sz"): "8", qn("space"): "0", qn("color"): "555555"},
            )

        tbl_grid = ET.SubElement(tbl, qn("tblGrid"))
        for width in widths:
            ET.SubElement(tbl_grid, qn("gridCol"), {qn("w"): width})

        for idx, row in enumerate(rows):
            tr = ET.SubElement(tbl, qn("tr"))
            padded_row = row + [""] * (cols - len(row))
            for cell_text in padded_row:
                tc = ET.SubElement(tr, qn("tc"))
                tc_pr = ET.SubElement(tc, qn("tcPr"))
                ET.SubElement(tc_pr, qn("tcW"), {qn("w"): widths[0], qn("type"): "dxa"})
                if idx < header_rows:
                    ET.SubElement(tc_pr, qn("shd"), {qn("val"): "clear", qn("fill"): "DCE6F1"})

                p = ET.SubElement(tc, qn("p"))
                p_pr = ET.SubElement(p, qn("pPr"))
                ET.SubElement(p_pr, qn("jc"), {qn("val"): "both"})
                ET.SubElement(p_pr, qn("spacing"), {qn("after"): "80", qn("line"): "300", qn("lineRule"): "auto"})

                r = ET.SubElement(p, qn("r"))
                r_pr = ET.SubElement(r, qn("rPr"))
                ET.SubElement(r_pr, qn("rFonts"), {qn("ascii"): "Times New Roman", qn("hAnsi"): "Times New Roman", qn("cs"): "Times New Roman"})
                ET.SubElement(r_pr, qn("sz"), {qn("val"): "28"})
                ET.SubElement(r_pr, qn("szCs"), {qn("val"): "28"})
                if idx < header_rows:
                    ET.SubElement(r_pr, qn("b"))
                t = ET.SubElement(r, qn("t"))
                t.text = cell_text

        self.add_paragraph("", align="both")

    def finalize(self) -> bytes:
        sect_pr = ET.SubElement(self.body, qn("sectPr"))
        ET.SubElement(sect_pr, qn("pgSz"), {qn("w"): "11906", qn("h"): "16838"})
        ET.SubElement(
            sect_pr,
            qn("pgMar"),
            {
                qn("top"): "1134",
                qn("right"): "1134",
                qn("bottom"): "1134",
                qn("left"): "1134",
                qn("header"): "708",
                qn("footer"): "708",
                qn("gutter"): "0",
            },
        )
        return ET.tostring(self.document, encoding="utf-8", xml_declaration=True)


def build_styles_xml() -> bytes:
    styles = ET.Element(qn("styles"))

    doc_defaults = ET.SubElement(styles, qn("docDefaults"))
    rpr_default = ET.SubElement(doc_defaults, qn("rPrDefault"))
    rpr = ET.SubElement(rpr_default, qn("rPr"))
    ET.SubElement(rpr, qn("rFonts"), {qn("ascii"): "Times New Roman", qn("hAnsi"): "Times New Roman", qn("cs"): "Times New Roman"})
    ET.SubElement(rpr, qn("sz"), {qn("val"): "28"})
    ET.SubElement(rpr, qn("szCs"), {qn("val"): "28"})

    ppr_default = ET.SubElement(doc_defaults, qn("pPrDefault"))
    ppr = ET.SubElement(ppr_default, qn("pPr"))
    ET.SubElement(ppr, qn("jc"), {qn("val"): "both"})
    ET.SubElement(ppr, qn("spacing"), {qn("after"): "160", qn("line"): "360", qn("lineRule"): "auto"})

    style = ET.SubElement(styles, qn("style"), {qn("type"): "paragraph", qn("default"): "1", qn("styleId"): "Normal"})
    ET.SubElement(style, qn("name"), {qn("val"): "Normal"})
    return ET.tostring(styles, encoding="utf-8", xml_declaration=True)


def build_content_types_xml() -> bytes:
    return b"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"""


def build_root_rels_xml() -> bytes:
    return b"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"""


def build_document_rels_xml() -> bytes:
    return b"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
"""


def build_core_xml() -> bytes:
    root = ET.Element(
        f"{{{CP_NS}}}coreProperties",
        {
            f"xmlns:cp": CP_NS,
            f"xmlns:dc": DC_NS,
            f"xmlns:dcterms": DCTERMS_NS,
            f"xmlns:xsi": XSI_NS,
        },
    )
    ET.SubElement(root, f"{{{DC_NS}}}title").text = "Бизнес-план проекта Edusfera"
    ET.SubElement(root, f"{{{DC_NS}}}creator").text = "OpenAI Codex"
    ET.SubElement(root, f"{{{CP_NS}}}lastModifiedBy").text = "OpenAI Codex"
    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    ET.SubElement(root, f"{{{DCTERMS_NS}}}created", {f"{{{XSI_NS}}}type": "dcterms:W3CDTF"}).text = now
    ET.SubElement(root, f"{{{DCTERMS_NS}}}modified", {f"{{{XSI_NS}}}type": "dcterms:W3CDTF"}).text = now
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def build_app_xml() -> bytes:
    return b"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>OpenAI Codex</Application>
</Properties>
"""


def process_node(node: ET.Element, builder: DocxBuilder, *, inside_cover: bool = False) -> None:
    tag = node.tag
    cls = node.attrib.get("class", "")

    if tag == "div":
        if "page-break" in cls:
            builder.add_page_break()
            return

        if "cover" in cls:
            for child in list(node):
                process_node(child, builder, inside_cover=True)
            builder.add_page_break()
            return

        if "note" in cls:
            builder.add_paragraph(extract_text(node), align="both", size=28, spacing_after=180, shading="F2F2F2")
            return

        for child in list(node):
            process_node(child, builder, inside_cover=inside_cover)
        return

    if tag == "h1":
        builder.add_paragraph(extract_text(node), align="center", bold=True, size=28, spacing_after=260)
        return

    if tag == "h2":
        builder.add_paragraph(extract_text(node), align="left", bold=True, size=28, spacing_after=180)
        return

    if tag == "h3":
        builder.add_paragraph(extract_text(node), align="left", bold=True, size=28, spacing_after=140)
        return

    if tag == "h4":
        builder.add_paragraph(extract_text(node), align="left", bold=True, size=28, spacing_after=140)
        return

    if tag == "p":
        align = "center" if inside_cover or "meta" in cls else "both"
        builder.add_paragraph(extract_text(node), align=align, size=28, spacing_after=160)
        return

    if tag == "ul":
        for li in node.findall("li"):
            builder.add_paragraph(f"• {extract_text(li)}", align="both", size=28, spacing_after=120)
        return

    if tag == "ol":
        for idx, li in enumerate(node.findall("li"), start=1):
            builder.add_paragraph(f"{idx}. {extract_text(li)}", align="both", size=28, spacing_after=120)
        return

    if tag == "table":
        rows: list[list[str]] = []
        for tr in node.findall("tr"):
            row: list[str] = []
            for cell in list(tr):
                if cell.tag in {"th", "td"}:
                    row.append(extract_text(cell))
            if row:
                rows.append(row)
        builder.add_table(rows, header_rows=1)
        return


def generate_docx(html_path: Path, output_path: Path) -> None:
    body_html = extract_body(html_path.read_text(encoding="utf-8"))
    root = ET.fromstring(f"<root>{body_html}</root>")

    builder = DocxBuilder()
    for child in list(root):
        process_node(child, builder)

    document_xml = builder.finalize()
    styles_xml = build_styles_xml()

    output_path.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(output_path, "w", compression=ZIP_DEFLATED) as zf:
        zf.writestr("[Content_Types].xml", build_content_types_xml())
        zf.writestr("_rels/.rels", build_root_rels_xml())
        zf.writestr("word/document.xml", document_xml)
        zf.writestr("word/styles.xml", styles_xml)
        zf.writestr("word/_rels/document.xml.rels", build_document_rels_xml())
        zf.writestr("docProps/core.xml", build_core_xml())
        zf.writestr("docProps/app.xml", build_app_xml())


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("html_path", type=Path)
    parser.add_argument("output_path", type=Path)
    args = parser.parse_args()
    generate_docx(args.html_path, args.output_path)


if __name__ == "__main__":
    main()
