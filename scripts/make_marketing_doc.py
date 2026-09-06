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

def set_cell_margins(cell, top=100, bottom=100, left=140, right=140):
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

def add_callout(doc, text_list, title="", border_color="2563EB", bg_color="F8FAFC"):
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = tbl.cell(0, 0)
    set_cell_background(cell, bg_color)
    set_cell_margins(cell, top=140, bottom=140, left=200, right=180)
    
    tcPr = cell._tc.get_or_add_tcPr()
    ns = nsdecls('w')
    borders = parse_xml(
        f'<w:tcBorders {ns}>'
        f'<w:left w:val="single" w:sz="24" w:space="0" w:color="{border_color}"/>'
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

def create_marketing_doc():
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
    run_badge = p_meta.add_run("EDUSFERA • МАРКЕТИНГ И GTM СТРАТЕГИЯ (ФАЗА 1)")
    run_badge.bold = True
    run_badge.font.size = Pt(8.5)
    run_badge.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)

    # Title
    p_title = doc.add_paragraph()
    p_title.paragraph_format.space_before = Pt(2)
    p_title.paragraph_format.space_after = Pt(3)
    run_title = p_title.add_run("СТРУКТУРА ПРОДАЮЩЕГО ПРЕДЛОЖЕНИЯ И ЛЕНДИНГА")
    run_title.bold = True
    run_title.font.size = Pt(16)
    run_title.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)

    p_sub = doc.add_paragraph()
    p_sub.paragraph_format.space_after = Pt(10)
    run_sub = p_sub.add_run("Конверсионная структура лендинга для репетиторов, аутрич-питч для личных сообщений, скрипты отработки возражений и комплаенс-ограничения Фазы 1")
    run_sub.font.size = Pt(10)
    run_sub.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)

    # Meta Table
    tbl_meta = doc.add_table(rows=2, cols=4)
    tbl_meta.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl_meta, color="CBD5E1", sz="4")
    
    meta_headers = ["Целевая аудитория", "Основной оффер", "Ключевой триггер", "Комплаенс-режим"]
    meta_values = ["Репетиторы Беларуси", "1 месяц бесплатно + статус Основателя", "Калькулятор потерь + Дефицит ниш", "Без транзита / Честная гарантия"]
    
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

    # 1. Лендинг для репетиторов
    p_h1 = doc.add_paragraph()
    style_heading(p_h1, "1. ЛЕНДИНГ ДЛЯ РЕПЕТИТОРОВ (структура и тексты)", level=1)

    # Блок 1
    p_b1 = doc.add_paragraph()
    style_heading(p_b1, "Блок 1. Хедлайн (обещание результата, не процесса)", level=2)
    add_callout(
        doc,
        [
            "Больше не ищите учеников. Преподавайте.",
            "Edusfera приводит заявки от родителей, ведёт расписание, сама напоминает ученикам о занятиях и считает ваш доход. Вы тратите время только на уроки.",
            "Подзаголовок-рефрейм цены: Подписка стоит как один ваш урок. Один ученик, найденный на платформе, оплачивает год подписки."
        ],
        title="🎯 Главный экран (Hero Block)"
    )

    # Блок 2
    p_b2 = doc.add_paragraph()
    style_heading(p_b2, "Блок 2. Боль (три колонки, узнавание)", level=2)
    
    tbl_pain = doc.add_table(rows=2, cols=3)
    tbl_pain.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl_pain, color="CBD5E1", sz="4")
    
    pain_headers = ["Пустые окна", "Рутина съедает вечера", "Вы — один из сотни"]
    pain_texts = [
        "Ученик отменил, забыл, исчез после двух занятий. Это 2–4 сорванных урока в месяц — минус 60–160 BYN, которые никто не компенсирует.",
        "Переписки в мессенджерах, согласование времени, напоминания об оплате: 5–7 часов в неделю, которые вы могли бы преподавать или отдыхать.",
        "На бесплатных досках ваша анкета тонет среди сотен одинаковых. Родители выбирают тех, кто выше в списке, а не тех, кто лучше преподаёт."
    ]
    for c_idx, h in enumerate(pain_headers):
        cell_h = tbl_pain.cell(0, c_idx)
        set_cell_background(cell_h, "0F172A")
        set_cell_margins(cell_h, top=60, bottom=60, left=70, right=70)
        p = cell_h.paragraphs[0]
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8.5)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
        cell_v = tbl_pain.cell(1, c_idx)
        set_cell_background(cell_v, "F8FAFC")
        set_cell_margins(cell_v, top=60, bottom=60, left=70, right=70)
        p = cell_v.paragraphs[0]
        r = p.add_run(pain_texts[c_idx])
        r.font.size = Pt(8.5)

    doc.add_paragraph().paragraph_format.space_after = Pt(4)

    # Блок 3
    p_b3 = doc.add_paragraph()
    style_heading(p_b3, "Блок 3. Решение (позиционирование: рабочая среда, а не доска объявлений)", level=2)
    p = doc.add_paragraph()
    p.add_run("Edusfera — не ещё одна доска объявлений. Это специализированная рабочая среда репетитора:")
    
    sol_items = [
        "Заявки от родителей по вашему предмету и району — при этом в каждой нише ограниченное число анкет, чтобы заявки не размывались на сотни конкурентов.",
        "Расписание и автонапоминания ученикам — «забыл» и «не пришёл» случаются кратно реже.",
        "Чат с файлами и голосовыми — вся переписка по уроку в одном месте.",
        "Журнал доходов и отчёты для НПД — конец месяца закрывается за 5 минут.",
        "Профиль, который продаёт: отзывы, сертификаты, видео-визитка."
    ]
    for si in sol_items:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(si)
        r.font.size = Pt(9)

    # Блок 4
    p_b4 = doc.add_paragraph()
    style_heading(p_b4, "Блок 4. Калькулятор потерь (интерактив, главный конвертер)", level=2)
    add_callout(
        doc,
        [
            "Интерактивный виджет: репетитор вводит свою ставку за урок (например, 35 BYN) и количество отмен/пропусков в месяц (например, 3 урока) → мгновенно видит годовую сумму потерь.",
            "Расчет потерь: Ставка 35 BYN × 3 сорванных урока в месяц = 1 260 BYN потерь в год.",
            "Сравнение: Подписка Pro — 480 BYN в год. Чистая сохраненная выгода: 780 BYN в год.",
            "Психологический эффект: Перевод восприятия цены подписки из категории «расходы» в категорию «страховка от потерь» (Loss Aversion)."
        ],
        title="🧮 Виджет калькулятора потерь",
        border_color="059669",
        bg_color="F0FDF4"
    )

    # Блок 5
    p_b5 = doc.add_paragraph()
    style_heading(p_b5, "Блок 5. Стек ценности (якоря «сколько это стоит на рынке»)", level=2)
    p = doc.add_paragraph()
    p.add_run("Что вы получаете в тарифе Pro и сколько это стоит вне платформы:")
    
    val_items = [
        "Поток заявок — привлечение одного ученика через рекламу обходится в 25–50 BYN. Здесь заявки приходят сами.",
        "5–7 часов свободного времени в неделю — расписание и напоминания ведутся автоматически.",
        "Меньше пропусков — автонапоминания и политика отмен в профиле.",
        "Отчёты для НПД — экономия бухгалтера и вечеров в конце месяца.",
        "Рыночная стоимость этого набора — 300+ BYN в месяц. Цена Pro — 40 BYN."
    ]
    for vi in val_items:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(vi)
        r.font.size = Pt(9)

    # Блок 6
    p_b6 = doc.add_paragraph()
    style_heading(p_b6, "Блок 6. Тарифы (декой-эффект, Pro выделен)", level=2)
    
    tbl_tar = doc.add_table(rows=4, cols=3)
    tbl_tar.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(tbl_tar, color="CBD5E1", sz="4")
    
    t_headers = ["Basic — 20 BYN", "Pro — 40 BYN (Выбор большинства)", "Premium — 60 BYN"]
    for c_idx, h in enumerate(t_headers):
        cell_h = tbl_tar.cell(0, c_idx)
        set_cell_background(cell_h, "1E3A8A" if c_idx == 1 else "0F172A")
        set_cell_margins(cell_h, top=60, bottom=60, left=70, right=70)
        p = cell_h.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run(h)
        r.bold = True
        r.font.size = Pt(8.5)
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        
    t_data = [
        ("Профиль в каталоге + заявки", "Расширенный профиль + до 10 откликов", "Топ-5 в поиске + безлимит + автоподбор"),
        ("Базовый чат", "Расписание + автонапоминания", "Видео-звонки + SMS + приоритет 2ч"),
        ("Email-уведомления", "Аналитика доходов + НПД", "Расширенная аналитика + экспорт")
    ]
    for r_idx, row in enumerate(t_data):
        bg = "EFF6FF" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row):
            cell = tbl_tar.cell(r_idx + 1, c_idx)
            set_cell_background(cell, "DBEAFE" if c_idx == 1 and bg == "EFF6FF" else bg)
            set_cell_margins(cell, top=50, bottom=50, left=60, right=60)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            r = p.add_run(val)
            r.font.size = Pt(8)

    p_spec = doc.add_paragraph()
    p_spec.paragraph_format.space_before = Pt(3)
    p_spec.paragraph_format.space_after = Pt(4)
    r1 = p_spec.add_run("Первый месяц — бесплатно на любом тарифе. ")
    r1.bold = True
    r2 = p_spec.add_run("Годовая оплата — скидка 20% (фактически 2 месяца в подарок).")
    r2.font.color.rgb = RGBColor(0x25, 0x63, 0xEB)

    # Блок 7-9
    p_b7 = doc.add_paragraph()
    style_heading(p_b7, "Блоки 7–9. Гарантия, дефицит, статус и призыв к действию (CTA)", level=2)
    
    b79_items = [
        ("Блок 7. Гарантия (реверс риска)", "30 дней без заявок — следующий месяц бесплатно. Если за первый оплаченный месяц на тарифе Pro или Premium вы не получили ни одной заявки — продлеваем подписку бесплатно, пока заявка не придёт."),
        ("Блок 8. Дефицит и статус (честная механика)", "Ниши ограничены: в каждой связке «предмет + город» мы держим ограниченное число Pro- и Premium-профилей. Когда места заняты — запись в лист ожидания. Статус основателя: первым 50 репетиторам цена зафиксирована навсегда и присваивается бейдж «Основатель платформы» в профиле."),
        ("Блок 9. Главный призыв (CTA)", "Основная кнопка: [ Занять место в своей нише ] | Вторичная кнопка: [ Посмотреть, свободно ли место в моей нише ]")
    ]
    for b_title, b_text in b79_items:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(f"• {b_title}: ")
        r.bold = True
        p.add_run(b_text)

    # 2. Питч
    p_h2 = doc.add_paragraph()
    style_heading(p_h2, "2. КОРОТКИЙ ПИТЧ ДЛЯ ЛИЧНЫХ СООБЩЕНИЙ (Outreach)", level=1)
    add_callout(
        doc,
        [
            "Здравствуйте, [Имя]! Вижу, вы преподаёте [предмет] — у вас сильные отзывы.",
            "Я делаю Edusfera.by — платформу, где родители оставляют заявки репетиторам, а расписание и напоминания ведутся автоматически. Сейчас открываем ранний доступ: первый месяц бесплатно, цена основателя фиксируется навсегда, а в каждой нише ограниченное число мест, чтобы заявки не размывались на сотни анкет.",
            "Место в нише «[предмет] + [город]» пока свободно. Хотите занять его до публичного запуска?"
        ],
        title="💬 Готовый скрипт для мессенджеров / Instagram / Telegram",
        border_color="2563EB"
    )

    # 3. Возражения
    p_h3 = doc.add_paragraph()
    style_heading(p_h3, "3. ОТРАБОТКА ВОЗРАЖЕНИЙ (для чата поддержки и созвонов)", level=1)
    
    objections = [
        ("«Есть бесплатные доски»", "Там вы платите не деньгами, а временем и конкуренцией: сотни анкет, ноль инструментов. Здесь ниша ограничена, и у вас есть рабочая среда, а не строчка в списке."),
        ("«У меня ученики по сарафану»", "Сарафан — потолок и сезонность. Платформа — второй канал, который заполняет пустые окна между рекомендациями."),
        ("«Дорого»", "Подписка равна одному вашему уроку. Один ученик с платформы окупает её на 8–12 месяцев."),
        ("«Не будет заявок»", "На этот случай есть гарантия: месяц без заявок = следующий месяц бесплатно.")
    ]
    for obj_q, obj_a in objections:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        r1 = p.add_run(f"• {obj_q} — ")
        r1.bold = True
        r1.font.color.rgb = RGBColor(0x1E, 0x3A, 0x8A)
        p.add_run(obj_a)

    # 4. Что нельзя обещать
    p_h4 = doc.add_paragraph()
    style_heading(p_h4, "4. ЧТО НЕЛЬЗЯ ОБЕЩАТЬ В ФАЗЕ 1 (Честность и комплаенс)", level=1)
    
    no_promise = [
        ("1. Гарантированное число учеников", "Никаких «5 учеников за месяц» — это рекламный риск и источник возвратов. Используйте условную гарантию (месяц бесплатно), зафиксированную в блоке 7."),
        ("2. Финансовую No-Show Protection", "В Фазе 1 платежи за уроки не проходят через платформу, поэтому гарантировать денежную выплату при неявке нельзя. Позиционируйте автонапоминания и правила отмен как инструмент снижения пропусков."),
        ("3. Выдуманные отзывы и цифры пользователей", "Социальное доказательство стройте на реальных данных: лист ожидания, счётчик занятых ниш, бейджи основателей. Фейковые отзывы разрушают доверие и нарушают закон РБ о рекламе."),
        ("4. Неоформленное обещание «цена навсегда»", "Фиксируйте это положение в публичной оферте как одностороннее безусловное обязательство сервиса, чтобы обещание было юридически защищённым.")
    ]
    for np_title, np_desc in no_promise:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        r1 = p.add_run(f"⚠️ {np_title}: ")
        r1.bold = True
        r1.font.color.rgb = RGBColor(0xDC, 0x26, 0x26)
        p.add_run(np_desc)

    # 5. Приоритет внедрения
    p_h5 = doc.add_paragraph()
    style_heading(p_h5, "5. ПРИОРИТЕТ ВНЕДРЕНИЯ", level=1)
    
    prio = [
        "1. Калькулятор потерь + гарантия + дефицит ниш — триггеры с наибольшей конверсией, размещаются в первом экране сразу после хедлайна.",
        "2. Сбор листа ожидания (реальные счётчики) — ключевое социальное доказательство при запуске.",
        "3. A/B-тестирование хедлайна: «Больше не ищите учеников» против «Один ученик окупает год подписки»."
    ]
    for pr in prio:
        bp = doc.add_paragraph(style="List Bullet")
        bp.paragraph_format.space_after = Pt(1.5)
        r = bp.add_run(pr)
        r.font.size = Pt(9)

    return doc

doc = create_marketing_doc()
dirs = [
    "/Users/sergei/Desktop/edusfera.by/Документы на платформу",
    "/Users/sergei/Desktop/edusfera.by/Документы",
    "/Users/sergei/Desktop/edusfera.by/docs"
]

filename = "Маркетинговое предложение и Лендинг для репетиторов (Фаза 1).docx"

for d in dirs:
    os.makedirs(d, exist_ok=True)
    path = os.path.join(d, filename)
    doc.save(path)
    print("Saved marketing doc:", path)
