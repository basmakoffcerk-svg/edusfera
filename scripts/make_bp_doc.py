import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    ns = nsdecls('w')
    shd = parse_xml(f'<w:shd {ns} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=120, bottom=120, left=180, right=180):
    tcPr = cell._tc.get_or_add_tcPr()
    ns = nsdecls('w')
    tcMar = parse_xml(
        f'<w:tcMar {ns}>'
        f'<w:top w:w="{top}" w:type="dxa"/>'
        f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
        f'<w:left w:w="{left}" w:type="dxa"/>'
        f'<w:right w:w="{right}" w:type="dxa"/>'
        f'</w:tcMar>'
    )
    tcPr.append(tcMar)

def set_table_borders(table, color="CBD5E1", sz="4", val="single"):
    tblPr = table._tbl.tblPr
    ns = nsdecls('w')
    borders = parse_xml(
        f'<w:tblBorders {ns}>'
        f'<w:top w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:insideH w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)

def add_callout(doc, text_list, title=""):
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = tbl.cell(0, 0)
    set_cell_background(cell, "F8FAFC")
    set_cell_margins(cell, top=140, bottom=140, left=200, right=180)
    
    tcPr = cell._tc.get_or_add_tcPr()
    ns = nsdecls('w')
    borders = parse_xml(
        f'<w:tcBorders {ns}>'
        f'<w:left w:val="single" w:sz="24" w:space="0" w:color="2563EB"/>'
        f'<w:top w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'<w:bottom w:val="none"/>'
        f'</w:tcBorders>'
    )
    tcPr.append(borders)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    if title:
        run_title = p.add_run(title + "\n")
        run_title.bold = True
        run_title.font.name = "Arial"
        run_title.font.size = Pt(10.5)
        run_title.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)
        
    for i, t in enumerate(text_list):
        if i > 0:
            p = cell.add_paragraph()
            p.paragraph_format.space_before = Pt(2)
            p.paragraph_format.space_after = Pt(3)
            p.paragraph_format.line_spacing = 1.15
        run = p.add_run(t)
        run.font.name = "Arial"
        run.font.size = Pt(9.5)
        run.font.color.rgb = RGBColor(0x33, 0x41, 0x55)
    
    doc.add_paragraph().paragraph_format.space_after = Pt(4)

def style_heading(p, text, level=1):
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.bold = True
    run.font.name = "Arial"
    if level == 1:
        p.paragraph_format.space_before = Pt(16)
        p.paragraph_format.space_after = Pt(6)
        run.font.size = Pt(13.5)
        run.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    elif level == 2:
        p.paragraph_format.space_before = Pt(12)
        p.paragraph_format.space_after = Pt(4)
        run.font.size = Pt(11.5)
        run.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)
    elif level == 3:
        p.paragraph_format.space_before = Pt(8)
        p.paragraph_format.space_after = Pt(3)
        run.font.size = Pt(10)
        run.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

def create_document():
    doc = Document()
    
    for section in doc.sections:
        section.top_margin = Inches(0.75)
        section.bottom_margin = Inches(0.75)
        section.left_margin = Inches(0.75)
        section.right_margin = Inches(0.75)
        
    style_normal = doc.styles["Normal"]
    style_normal.font.name = "Arial"
    style_normal.font.size = Pt(9.5)
    style_normal.font.color.rgb = RGBColor(0x1E, 0x29, 0x3B)
    style_normal.paragraph_format.line_spacing = 1.15
    style_normal.paragraph_format.space_after = Pt(4)

    # Document Header / Badge
    p_meta = doc.add_paragraph()
    p_meta.paragraph_format.space_after = Pt(2)
    run_badge = p_meta.add_run("EDUSFERA • СТРАТЕГИЧЕСКОЕ ДОПОЛНЕНИЕ К БИЗНЕС-ПЛАНУ 7.1")
    run_badge.bold = True
    run_badge.font.size = Pt(8.5)
    run_badge.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)

    # Title
    p_title = doc.add_paragraph()
    p_title.paragraph_format.space_before = Pt(2)
    p_title.paragraph_format.space_after = Pt(3)
    run_title = p_title.add_run("ВРЕМЕННАЯ БИЗНЕС-МОДЕЛЬ EDUSFERA (SaaS-ПОДПИСКА)")
    run_title.bold = True
    run_title.font.size = Pt(16)
    run_title.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    p_sub = doc.add_paragraph()
    p_sub.paragraph_format.space_after = Pt(10)
    run_sub = p_sub.add_run("Стратегия быстрого запуска (Pre-PPU Launch), тарифная сетка, юнит-экономика, финансовый план и дорожная карта перехода к целевой модели Buyer-Pays")
    run_sub.font.size = Pt(10)
    run_sub.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)

    # Meta Table
    tbl_meta = doc.add_table(rows=2, cols=4)
    tbl_meta.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl_meta, color="CBD5E1", sz="4")
    
    meta_headers = ["Юрисдикция", "Валюта расчетов", "Налоговый режим", "Статус модели"]
    meta_values = ["Республика Беларусь", "Белорусский рубль (BYN)", "УСН 6% (без НДС)", "Фаза 1 (Месяцы 1–6)"]
    
    for c_idx, h in enumerate(meta_headers):
        cell_h = tbl_meta.cell(0, c_idx)
        set_cell_background(cell_h, "F1F5F9")
        set_cell_margins(cell_h, top=60, bottom=60, left=80, right=80)
        p = cell_h.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8)
        r.font.color.rgb = RGBColor(0x47, 0x55, 0x69)
        
        cell_v = tbl_meta.cell(1, c_idx)
        set_cell_background(cell_v, "FFFFFF")
        set_cell_margins(cell_v, top=60, bottom=60, left=80, right=80)
        p = cell_v.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(meta_values[c_idx])
        r.bold = True
        r.font.size = Pt(9)
        r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    doc.add_paragraph().paragraph_format.space_after = Pt(6)

    # Callout: Суть модели
    add_callout(
        doc,
        [
            "Платформа бесплатна для учеников и родителей. Репетиторы платят ежемесячную подписку за доступ к инструментарию платформы (каталог, бронирование, расписание, аналитика, чат).",
            "Модель устраняет регуляторные барьеры запуска: платформа не проводит клиентские платежи через себя (нет транзитных счетов), не требует немедленного статуса ППУ от Нацбанка РБ и запускается в коммерческую эксплуатацию с 1-го дня через прямой ЕРИП и безналичные счета."
        ],
        title="💡 Суть модели (одним предложением)"
    )

    # 0. Контекст интеграции с основным БП 7.1
    p_h0 = doc.add_paragraph()
    style_heading(p_h0, "0. Аналитический контекст и связь с Бизнес-планом 7.1 (Buyer-Pays)", level=1)
    
    p = doc.add_paragraph()
    p.add_run(
        "В основном Бизнес-плане 7.1 (Investor Edition) заложена долгосрочная целевая модель Buyer-Pays (сервисный сбор 15% с родителя, 0% комиссии с репетитора) с расщеплением платежа (сплитованием). "
        "Однако полноценное внедрение Buyer-Pays требует предварительного прохождения регуляторных процедур: включения в реестр поставщиков платежных услуг (ППУ) Нацбанка РБ либо заключения специализированных трехсторонних договоров с банками-эквайерами (Альфа-Банк, Банк Дабрабыт).\n\n"
        "Временная модель SaaS-подписки выступает идеальным бутстрэп-мостом (Fast Go-To-Market Bridge), позволяющим:"
    )
    
    bullets = [
        "Начать коммерческую эксплуатацию и генерацию выручки (MRR) с нулевого дня без регуляторных задержек.",
        "Протестировать продуктовые гипотезы, ценность личного кабинета преподавателя и метрики удержания (Retention).",
        "Сформировать пул из 50–100 активных репетиторов, генерирующих стабильный денежный поток и подтверждающих рыночный спрос.",
        "Обеспечить чистую маржинальность 92% на этапе валидации и плавно перейти к гибридной модели в Фазе 3."
    ]
    for b in bullets:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(2)
        bp.paragraph_format.line_spacing = 1.15
        r = bp.add_run(b)
        r.font.size = Pt(9.5)

    # 1. Тарифная сетка
    p_h1 = doc.add_paragraph()
    style_heading(p_h1, "1. ТАРИФНАЯ СЕТКА", level=1)

    # Table 1: Тарифы
    tbl1 = doc.add_table(rows=10, cols=4)
    tbl1.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl1, color="CBD5E1", sz="4")
    
    headers1 = ["Функционал", "Basic", "Pro", "Premium"]
    for c_idx, h in enumerate(headers1):
        cell = tbl1.cell(0, c_idx)
        set_cell_background(cell, "0F172A" if c_idx == 0 else ("1E3A8A" if c_idx == 2 else "1E293B"))
        set_cell_margins(cell, top=70, bottom=70, left=80, right=80)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER if c_idx > 0 else WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(9)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
    data1 = [
        ("Цена", "20 BYN/мес", "40 BYN/мес", "60 BYN/мес"),
        ("Профиль в каталоге", "✅ Базовый (фото, описание, цены)", "✅ Расширенный (+ сертификаты, отзывы)", "✅ Премиум (видео-визитка, топ-5 в поиске)"),
        ("Поиск учеников", "✅ Просмотр заявок", "✅ Отклик на заявки (до 10/мес)", "✅ Безлимит + автоподбор учеников"),
        ("Бронирование уроков", "❌", "✅ Календарь + расписание", "✅ Календарь + синхронизация с Google Calendar"),
        ("Чат с учениками", "✅ Базовый", "✅ С файлами и голосовыми", "✅ С видео-звонками (до 45 мин)"),
        ("Аналитика", "❌", "✅ Статистика по урокам (20+ отчётов)", "✅ Расширенная аналитика + экспорт"),
        ("Уведомления", "✅ Email", "✅ Email + Push", "✅ Email + Push + SMS"),
        ("Поддержка", "Email (48ч)", "Чат (12ч)", "Приоритетная (2ч)"),
        ("Первый месяц", "Бесплатно", "Бесплатно", "Бесплатно"),
    ]
    
    for r_idx, row in enumerate(data1):
        is_even = (r_idx % 2 == 1)
        bg = "F8FAFC" if is_even else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = tbl1.cell(r_idx + 1, c_idx)
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
            p = cell.paragraphs[0]
            if c_idx == 0:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                r = p.add_run(val)
                r.bold = True
                r.font.size = Pt(8.5)
            elif r_idx == 0: # Price row
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                r = p.add_run(val)
                r.bold = True
                r.font.size = Pt(9)
                r.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT if len(val) > 15 else WD_ALIGN_PARAGRAPH.CENTER
                r = p.add_run(val)
                r.font.size = Pt(8)

    doc.add_paragraph().paragraph_format.space_after = Pt(3)
    
    # Distribution and ARPU
    p_dist = doc.add_paragraph()
    p_dist.paragraph_format.space_after = Pt(2)
    r_dist = p_dist.add_run("Ожидаемое распределение подписчиков:")
    r_dist.bold = True
    
    dist_items = [
        "Basic: 50% (новички, пробующие платформу)",
        "Pro: 40% (активные репетиторы с 5+ учениками)",
        "Premium: 10% (профессионалы, работающие на полную ставку)"
    ]
    for di in dist_items:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(di)
        r.font.size = Pt(9)

    p_arpu = doc.add_paragraph()
    p_arpu.paragraph_format.space_before = Pt(2)
    p_arpu.paragraph_format.space_after = Pt(4)
    r = p_arpu.add_run("Средний чек (ARPU): ")
    r.bold = True
    r2 = p_arpu.add_run("32 BYN/мес")
    r2.bold = True
    r2.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)

    # 2. Юнит-экономика
    p_h2 = doc.add_paragraph()
    style_heading(p_h2, "2. ЮНИТ-ЭКОНОМИКА (на одного подписчика)", level=1)
    
    tbl2 = doc.add_table(rows=5, cols=5)
    tbl2.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl2, color="CBD5E1", sz="4")
    
    headers2 = ["Показатель", "Basic (20 BYN)", "Pro (40 BYN)", "Premium (60 BYN)", "Средний (32 BYN)"]
    for c_idx, h in enumerate(headers2):
        cell = tbl2.cell(0, c_idx)
        set_cell_background(cell, "0F172A" if c_idx == 0 else ("1E3A8A" if c_idx == 4 else "1E293B"))
        set_cell_margins(cell, top=70, bottom=70, left=70, right=70)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.RIGHT if c_idx > 0 else WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8.5)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
    data2 = [
        ("Выручка", "20,00", "40,00", "60,00", "32,00"),
        ("Эквайринг/ЕРИП (2%)", "−0,40", "−0,80", "−1,20", "−0,64"),
        ("УСН 6%", "−1,20", "−2,40", "−3,60", "−1,92"),
        ("Чистыми", "18,40", "36,80", "55,20", "29,44"),
    ]
    
    for r_idx, row in enumerate(data2):
        is_net = (r_idx == len(data2) - 1)
        bg = "E0F2FE" if is_net else ("F8FAFC" if r_idx % 2 == 1 else "FFFFFF")
        for c_idx, val in enumerate(row):
            cell = tbl2.cell(r_idx + 1, c_idx)
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.RIGHT if c_idx > 0 else WD_ALIGN_PARAGRAPH.LEFT
            r = p.add_run(val)
            if is_net or c_idx == 0:
                r.bold = True
            r.font.size = Pt(9 if is_net else 8.5)
            if is_net and c_idx > 0:
                r.font.color.rgb = RGBColor(0x0F, 0x76, 0x6E)

    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    
    p_mrg = doc.add_paragraph()
    r = p_mrg.add_run("Маржинальность: ")
    r.bold = True
    p_mrg.add_run("92% (почти вся выручка — чистая прибыль)")

    # 3. Точка безубыточности
    p_h3 = doc.add_paragraph()
    style_heading(p_h3, "3. ТОЧКА БЕЗУБЫТОЧНОСТИ", level=1)
    
    p = doc.add_paragraph()
    r = p.add_run("Постоянные расходы: ")
    r.bold = True
    p.add_run("300 BYN/мес")
    
    opex_items = [
        "Хостинг: 40 BYN",
        "Бухгалтерия (аутсорс): 120 BYN",
        "Домен/сервисы: 20 BYN",
        "Маркетинг: 100 BYN",
        "Прочее: 20 BYN"
    ]
    for item in opex_items:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(item)
        r.font.size = Pt(9)

    p_calc = doc.add_paragraph()
    p_calc.paragraph_format.space_before = Pt(3)
    p_calc.paragraph_format.space_after = Pt(2)
    r = p_calc.add_run("Расчёт: ")
    r.bold = True
    p_calc.add_run("300 / 29,44 = ")
    r3 = p_calc.add_run("10,2 → 11 платящих подписчиков")
    r3.bold = True
    r3.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)
    
    p_verd = doc.add_paragraph()
    p_verd.paragraph_format.space_after = Pt(4)
    r = p_verd.add_run("Вердикт: ")
    r.bold = True
    p_verd.add_run("Выход в безубыток при 11 активных репетиторах на платных тарифах.")

    # 4. Операционная модель
    p_h4 = doc.add_paragraph()
    style_heading(p_h4, "4. ОПЕРАЦИОННАЯ МОДЕЛЬ (без эквайринга)", level=1)
    
    p_h4_1 = doc.add_paragraph()
    style_heading(p_h4_1, "Приём оплаты", level=2)
    
    p_ch1 = doc.add_paragraph()
    p_ch1.paragraph_format.space_after = Pt(2)
    r = p_ch1.add_run("Канал 1: ЕРИП (основной)")
    r.bold = True
    r.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)
    
    erip_points = [
        "Подключение ЕРИП для приёма подписок (прямая выручка Edusfera, не транзит)",
        "Комиссия ~2%",
        "Родители/репетиторы платят через интернет-банкинг, ЕРИП-терминалы, мобильные приложения"
    ]
    for ep in erip_points:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(ep)
        r.font.size = Pt(9)

    p_ch2 = doc.add_paragraph()
    p_ch2.paragraph_format.space_before = Pt(3)
    p_ch2.paragraph_format.space_after = Pt(2)
    r = p_ch2.add_run("Канал 2: Банковская квитанция (резервный)")
    r.bold = True
    r.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)
    
    bank_points = [
        "Генерация счета-фактуры в ЛК репетитора",
        "Репетитор оплачивает через отделение банка или интернет-банк",
        "Комиссия 0% (но медленнее — 1-2 дня на зачисление)"
    ]
    for bp_item in bank_points:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(bp_item)
        r.font.size = Pt(9)

    p_h4_2 = doc.add_paragraph()
    style_heading(p_h4_2, "Биллинг-цикл", level=2)

    billing_steps = [
        ("День 1", "Репетитор регистрируется → получает 1 месяц бесплатно"),
        ("День 30", "Отправляется инвойс на следующий месяц"),
        ("День 32", "Если оплата не поступила — профиль скрывается из каталога"),
        ("День 35", "Автоматическое продление при оплате или деактивация")
    ]
    
    for step_title, step_desc in billing_steps:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(1.5)
        p.paragraph_format.space_after = Pt(1.5)
        p.paragraph_format.left_indent = Inches(0.15)
        r1 = p.add_run(f"{step_title}: ")
        r1.bold = True
        r1.font.size = Pt(9)
        r2 = p.add_run(step_desc)
        r2.font.size = Pt(9)

    p_auto = doc.add_paragraph()
    p_auto.paragraph_format.space_before = Pt(3)
    p_auto.paragraph_format.space_after = Pt(2)
    r = p_auto.add_run("Автоматизация:")
    r.bold = True
    
    auto_points = [
        "Cron-задача генерирует инвойсы за 3 дня до окончания пробного периода",
        "Email/SMS напоминания: за 3 дня, за 1 день, в день списания",
        "Автоматическое продление при успешной оплате"
    ]
    for ap in auto_points:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(ap)
        r.font.size = Pt(9)

    # 5. Финансовый план на 12 месяцев
    p_h5 = doc.add_paragraph()
    style_heading(p_h5, "5. ФИНАНСОВЫЙ ПЛАН НА 12 МЕСЯЦЕВ", level=1)
    
    tbl5 = doc.add_table(rows=13, cols=6)
    tbl5.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl5, color="CBD5E1", sz="4")
    
    headers5 = ["Месяц", "Подписчики", "Выручка", "Расходы", "Чистыми", "Накопленно"]
    for c_idx, h in enumerate(headers5):
        cell = tbl5.cell(0, c_idx)
        set_cell_background(cell, "0F172A" if c_idx == 0 else "1E3A8A")
        set_cell_margins(cell, top=70, bottom=70, left=60, right=60)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8.5)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
    data5 = [
        ("1", "5", "160", "313", "−153", "−153"),
        ("2", "8", "256", "320", "−64", "−217"),
        ("3", "12", "384", "331", "+53", "−164"),
        ("4", "17", "544", "344", "+200", "+36"),
        ("5", "23", "736", "359", "+377", "+413"),
        ("6", "30", "960", "377", "+583", "+996"),
        ("7", "38", "1 216", "397", "+819", "+1 815"),
        ("8", "47", "1 504", "420", "+1 084", "+2 899"),
        ("9", "58", "1 856", "446", "+1 408", "+4 307"),
        ("10", "70", "2 240", "475", "+1 761", "+6 068"),
        ("11", "85", "2 720", "508", "+2 203", "+8 271"),
        ("12", "100", "3 200", "544", "+2 644", "+10 915"),
    ]
    
    for r_idx, row in enumerate(data5):
        bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = tbl5.cell(r_idx + 1, c_idx)
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=50, bottom=50, left=60, right=60)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            r = p.add_run(val)
            r.font.size = Pt(8)
            if c_idx in [4, 5]:
                if val.startswith("+"):
                    r.font.color.rgb = RGBColor(0x05, 0x96, 0x69)
                elif val.startswith("−"):
                    r.font.color.rgb = RGBColor(0xDC, 0x26, 0x26)

    doc.add_paragraph().paragraph_format.space_after = Pt(3)
    
    p_yr = doc.add_paragraph()
    p_yr.paragraph_format.space_after = Pt(2)
    r = p_yr.add_run("Итог за год:")
    r.bold = True
    
    yr_items = [
        "Выручка: ~17 776 BYN",
        "Чистая прибыль: ~10 915 BYN",
        "Средняя выручка/мес: ~1 481 BYN"
    ]
    for yi in yr_items:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(yi)
        r.font.size = Pt(9)

    p_note = doc.add_paragraph()
    p_note.paragraph_format.space_before = Pt(2)
    p_note.paragraph_format.space_after = Pt(4)
    r = p_note.add_run("Примечание: ")
    r.bold = True
    p_note.add_run("Расходы растут линейно (маркетинг + поддержка), но медленнее выручки.")

    # 6. Дорожная карта перехода
    p_h6 = doc.add_paragraph()
    style_heading(p_h6, "6. ДОРОЖНАЯ КАРТА ПЕРЕХОДА НА ФИНАЛЬНУЮ МОДЕЛЬ", level=1)
    
    phases = [
        ("Фаза 1: SaaS-модель (месяцы 1-6)", 
         "Запуск, валидация продукта, набор первых 50 подписчиков",
         [
             "Запуск платформы с тремя тарифами",
             "Приём оплаты через ЕРИП и квитанции",
             "Активный маркетинг (таргет, контент, партнёрства)",
             "Сбор фидбека, итерации продукта"
         ],
         "50 платящих репетиторов, MRR 1 600 BYN"),
        ("Фаза 2: Подготовка к ППУ (месяцы 7-9)",
         "Подготовка документов, юридическая база для получения статуса ППУ",
         [
             "Подача документов в Нацбанк для включения в реестр ППУ",
             "Параллельно: переговоры с банками (Альфа, Дабрабыт) о сплитовании",
             "Разработка гибридной модели (подписка + комиссия)"
         ],
         "Поданы документы, получены предварительные согласия от банков"),
        ("Фаза 3: Гибридная модель (месяцы 10+)",
         "Максимизация выручки через комбинацию подписки и комиссии",
         [
             "Подписка (Basic/Pro/Premium) — базовый доступ к платформе",
             "Комиссия 10-15% — за проведение оплаты через платформу (сплитование)",
             "Логика: Репетиторы, которые не хотят подписку, платят комиссию с каждой сделки; репетиторы с подпиской получают скидку на комиссию (или 0% при Premium)"
         ],
         "100+ репетиторов, MRR 3 200 BYN + комиссионная выручка")
    ]
    
    for ph_title, ph_goal, ph_actions, ph_kpi in phases:
        p_ph = doc.add_paragraph()
        style_heading(p_ph, ph_title, level=2)
        
        p_g = doc.add_paragraph()
        p_g.paragraph_format.space_after = Pt(1.5)
        r = p_g.add_run("Цель: ")
        r.bold = True
        p_g.add_run(ph_goal)
        
        p_act = doc.add_paragraph()
        p_act.paragraph_format.space_before = Pt(1.5)
        p_act.paragraph_format.space_after = Pt(1.5)
        r = p_act.add_run("Действия:")
        r.bold = True
        
        for act in ph_actions:
            bp = doc.add_paragraph(style="List Bullet")
            bp.paragraph_format.space_after = Pt(1.5)
            r = bp.add_run(act)
            r.font.size = Pt(9)
            
        p_k = doc.add_paragraph()
        p_k.paragraph_format.space_before = Pt(1.5)
        p_k.paragraph_format.space_after = Pt(3)
        r = p_k.add_run("KPI: ")
        r.bold = True
        p_k.add_run(ph_kpi)

    # 7. Риски и митигация
    p_h7 = doc.add_paragraph()
    style_heading(p_h7, "7. РИСКИ И МИТИГАЦИЯ", level=1)
    
    tbl7 = doc.add_table(rows=6, cols=4)
    tbl7.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl7, color="CBD5E1", sz="4")
    
    headers7 = ["Риск", "Вероятность", "Влияние", "Митигация"]
    for c_idx, h in enumerate(headers7):
        cell = tbl7.cell(0, c_idx)
        set_cell_background(cell, "0F172A" if c_idx == 0 else "1E3A8A")
        set_cell_margins(cell, top=70, bottom=70, left=70, right=70)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER if c_idx in [1, 2] else WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8.5)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
    data7 = [
        ("Высокий отток (churn) репетиторов после пробного периода", "Высокая", "Высокое", "Улучшить онбординг, добавить ценность в первые 7 дней, снизить цену Basic до 15 BYN"),
        ("Низкая конверсия в платных подписчиков", "Средняя", "Высокое", "A/B тестирование тарифов, введение годовой подписки со скидкой 20%"),
        ("Конкуренция с бесплатными площадками (Prof.by, Avito)", "Высокая", "Среднее", "Акцент на уникальных фичах (бронирование, аналитика, No-Show Protection)"),
        ("Задержка получения статуса ППУ", "Средняя", "Среднее", "Параллельная работа с 2-3 банками, подготовка резервного плана"),
        ("Кассовый разрыв в первые 3 месяца", "Средняя", "Высокое", "Резерв 1 000 BYN из личных средств или микрозайм")
    ]
    
    for r_idx, row in enumerate(data7):
        bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = tbl7.cell(r_idx + 1, c_idx)
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=50, bottom=50, left=70, right=70)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER if c_idx in [1, 2] else WD_ALIGN_PARAGRAPH.LEFT
            r = p.add_run(val)
            if c_idx == 0:
                r.bold = True
                r.font.size = Pt(8)
            elif c_idx == 1:
                r.font.size = Pt(8)
                r.font.color.rgb = RGBColor(0xDC, 0x26, 0x26) if val == "Высокая" else RGBColor(0xD9, 0x77, 0x06)
            elif c_idx == 2:
                r.font.size = Pt(8)
                r.font.color.rgb = RGBColor(0xDC, 0x26, 0x26) if val == "Высокое" else RGBColor(0x25, 0x63, 0xEB)
            else:
                r.font.size = Pt(8)

    # 8. Ключевые метрики
    p_h8 = doc.add_paragraph()
    style_heading(p_h8, "8. КЛЮЧЕВЫЕ МЕТРИКИ (KPI)", level=1)
    
    p_m = doc.add_paragraph()
    p_m.paragraph_format.space_after = Pt(2)
    r = p_m.add_run("Ежемесячно:")
    r.bold = True
    
    m_kpis = [
        "MRR (Monthly Recurring Revenue): целевая выручка от подписок",
        "Churn rate: % оттока подписчиков (целевой <5%/мес)",
        "CAC (Customer Acquisition Cost): стоимость привлечения одного платящего репетитора (целевой <100 BYN)",
        "LTV (Lifetime Value): пожизненная ценность подписчика (целевой LTV:CAC ≥ 3:1)",
        "ARPU (Average Revenue Per User): средний чек с одного подписчика"
    ]
    for mk in m_kpis:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(mk)
        r.font.size = Pt(9)

    p_q = doc.add_paragraph()
    p_q.paragraph_format.space_before = Pt(2)
    p_q.paragraph_format.space_after = Pt(2)
    r = p_q.add_run("Ежеквартально:")
    r.bold = True
    
    q_kpis = [
        "Net Promoter Score (NPS): удовлетворённость репетиторов",
        "Feature adoption rate: % репетиторов, использующих ключевые фичи (бронирование, аналитика)"
    ]
    for qk in q_kpis:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(qk)
        r.font.size = Pt(9)

    # Резюме
    p_h9 = doc.add_paragraph()
    style_heading(p_h9, "РЕЗЮМЕ", level=1)
    
    add_callout(
        doc,
        [
            "Временная модель: SaaS-подписка (Basic 20 / Pro 40 / Premium 60 BYN/мес)",
            "Точка безубыточности: 11 платящих репетиторов",
            "Цель на 6 месяцев: 50 подписчиков, MRR 1 600 BYN",
            "Переход на финальную модель: Гибрид (подписка + комиссия) после получения статуса ППУ (месяцы 10+)",
            "Главное преимущество: Быстрый запуск без зависимости от банковского эквайринга и статуса ППУ.",
            "Главный риск: Высокий отток репетиторов после пробного периода — лечится улучшением онбординга и добавлением ценности в первые дни."
        ],
        title="📌 Итоги"
    )
    
    return doc

doc = create_document()
dirs = [
    "/Users/sergei/Desktop/edusfera.by/Документы на платформу",
    "/Users/sergei/Desktop/edusfera.by/Документы",
    "/Users/sergei/Desktop/edusfera.by/docs"
]

filename = "Дополнение к Бизнес-плану — Временная бизнес-модель Edusfera (SaaS-подписка).docx"

for d in dirs:
    os.makedirs(d, exist_ok=True)
    path = os.path.join(d, filename)
    doc.save(path)
    print("Successfully saved:", path)
