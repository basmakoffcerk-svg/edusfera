import React, { useMemo, useState } from 'react';
import { marked } from 'marked';
import katex from 'katex';
import { 
    Copy, 
    Check, 
    Printer, 
    Download, 
    Sparkles, 
    AlertTriangle, 
    Lightbulb, 
    BookOpen, 
    FileText,
    ZoomIn,
    ZoomOut,
    RotateCcw
} from 'lucide-react';

/**
 * Enhanced LaTeX & Markdown parser
 * Handles:
 * - Block math $$ ... $$ and \[ ... \]
 * - Inline math $ ... $ and \( ... \)
 * - Callout blocks (> [!WARNING], > [!NOTE], etc.)
 * - GFM tables, code blocks, lists
 */
export function formatAiMarkdown(rawText) {
    if (!rawText || typeof rawText !== 'string') return '';

    let text = rawText;

    // 1. Pre-process callouts before markdown parsing
    text = text.replace(
        /^>\s*\[!(WARNING|CAUTION|DANGER)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Внимание / Ловушка РИКЗ';
            return `\n\n<div class="ai-callout ai-callout-warning">\n<div class="ai-callout-title">⚠️ ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    text = text.replace(
        /^>\s*\[!(TIP|SUCCESS|RECOMMENDED)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Методический совет';
            return `\n\n<div class="ai-callout ai-callout-tip">\n<div class="ai-callout-title">💡 ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    text = text.replace(
        /^>\s*\[!(NOTE|INFO|IMPORTANT)\]\s*(.*?)$((?:\n>.*)*)/gim,
        (match, type, title, body) => {
            const cleanBody = body.replace(/^>\s?/gm, '').trim();
            const heading = title.trim() || 'Важное примечание';
            return `\n\n<div class="ai-callout ai-callout-info">\n<div class="ai-callout-title">📌 ${heading}</div>\n<div class="ai-callout-body">\n\n${cleanBody}\n\n</div>\n</div>\n\n`;
        }
    );

    // 2. Render display/block math $$...$$
    text = text.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
        try {
            const rendered = katex.renderToString(formula.trim(), {
                displayMode: true,
                throwOnError: false,
                output: 'html',
            });
            return `\n\n<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 overflow-x-auto text-center shadow-xs transition-colors">${rendered}</div>\n\n`;
        } catch (e) {
            return match;
        }
    });

    // 3. Render LaTeX \[ ... \] blocks
    text = text.replace(/\\\[([\s\S]+?)\\\]/g, (match, formula) => {
        try {
            const rendered = katex.renderToString(formula.trim(), {
                displayMode: true,
                throwOnError: false,
                output: 'html',
            });
            return `\n\n<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 overflow-x-auto text-center shadow-xs transition-colors">${rendered}</div>\n\n`;
        } catch (e) {
            return match;
        }
    });

    // 4. Render inline math $...$
    // Ensure we avoid matching single dollar currency like $50 or $ 100
    text = text.replace(/(?<!\\|\$)\$([^\$\n]+?)\$(?!\$)/g, (match, formula) => {
        if (/^\s*\d+([.,]\d+)?\s*$/.test(formula)) {
            return match;
        }
        try {
            return katex.renderToString(formula.trim(), {
                displayMode: false,
                throwOnError: false,
                output: 'html',
            });
        } catch (e) {
            return match;
        }
    });

    // 5. Render LaTeX \( ... \) inline formulas
    text = text.replace(/\\\(([\s\S]+?)\\\)/g, (match, formula) => {
        try {
            return katex.renderToString(formula.trim(), {
                displayMode: false,
                throwOnError: false,
                output: 'html',
            });
        } catch (e) {
            return match;
        }
    });

    // Configure marked options
    marked.setOptions({
        gfm: true,
        breaks: true,
    });

    return marked.parse(text);
}

export default function AiFormattedOutput({
    content = '',
    isLoading = false,
    onRegenerate = null,
    title = '',
    subtitle = '',
    className = '',
}) {
    const [copiedFormat, setCopiedFormat] = useState(null);
    const [fontSize, setFontSize] = useState('normal'); // 'compact' | 'normal' | 'large'

    const htmlContent = useMemo(() => {
        return formatAiMarkdown(content);
    }, [content]);

    const handleCopyMarkdown = async () => {
        try {
            await navigator.clipboard.writeText(content);
            setCopiedFormat('md');
            setTimeout(() => setCopiedFormat(null), 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    };

    const handleCopyPlainText = async () => {
        try {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlContent;
            const plain = tempDiv.innerText || tempDiv.textContent || content;
            await navigator.clipboard.writeText(plain);
            setCopiedFormat('text');
            setTimeout(() => setCopiedFormat(null), 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    };

    const handlePrint = () => {
        window.print();
    };

    const handleDownloadDoc = () => {
        const element = document.createElement('a');
        const file = new Blob([content], { type: 'text/markdown;charset=utf-8' });
        element.href = URL.createObjectURL(file);
        element.download = `${title || 'edusfera-ai-output'}-${new Date().toISOString().slice(0, 10)}.md`;
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    };

    const fontSizeStyles = {
        compact: 'text-[12.5px] leading-relaxed',
        normal: 'text-[14px] leading-relaxed',
        large: 'text-[15.5px] leading-loose',
    }[fontSize];

    if (isLoading) {
        return (
            <div className={`rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/90 p-8 shadow-sm ${className}`}>
                <div className="flex items-center gap-3 mb-6">
                    <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-violet-500/20 animate-pulse">
                        <Sparkles className="w-5 h-5 animate-spin" style={{ animationDuration: '4s' }} />
                    </div>
                    <div>
                        <h4 className="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Генерация ответа через Edusfera AI</span>
                            <span className="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        </h4>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            Анализ методики, спецификаций РИКЗ 2026 и составление конспекта...
                        </p>
                    </div>
                </div>

                <div className="space-y-4 animate-pulse pt-2">
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-3/4"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-full"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-5/6"></div>
                    
                    <div className="my-6 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 space-y-2">
                        <div className="h-3 bg-violet-200 dark:bg-violet-900/40 rounded-full w-1/3"></div>
                        <div className="h-8 bg-slate-200 dark:bg-slate-800 rounded-xl w-4/5"></div>
                    </div>

                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-11/12"></div>
                    <div className="h-4 bg-slate-200 dark:bg-slate-800 rounded-full w-2/3"></div>
                </div>
            </div>
        );
    }

    if (!content) {
        return null;
    }

    return (
        <div className={`rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all overflow-hidden ${className}`}>
            {/* Top Toolbar */}
            <div className="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60 dark:bg-slate-800/40">
                <div className="flex items-center gap-2.5">
                    <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-sm font-bold">
                        <Sparkles className="w-4 h-4 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <div className="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                            <span>{title || 'Ответ ИИ-Помощника'}</span>
                            <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                Готово ✓
                            </span>
                        </div>
                        {subtitle && (
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">{subtitle}</p>
                        )}
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex items-center gap-1.5 ml-auto">
                    {/* Font scale buttons */}
                    <div className="flex items-center bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 rounded-xl p-0.5 mr-1 shadow-2xs">
                        <button
                            type="button"
                            onClick={() => setFontSize('compact')}
                            className={`px-2 py-1 text-[11px] font-semibold rounded-lg transition-colors ${fontSize === 'compact' ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'}`}
                            title="Компактный шрифт"
                        >
                            A-
                        </button>
                        <button
                            type="button"
                            onClick={() => setFontSize('normal')}
                            className={`px-2 py-1 text-[11px] font-semibold rounded-lg transition-colors ${fontSize === 'normal' ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'}`}
                            title="Обычный шрифт"
                        >
                            A
                        </button>
                        <button
                            type="button"
                            onClick={() => setFontSize('large')}
                            className={`px-2 py-1 text-[11px] font-semibold rounded-lg transition-colors ${fontSize === 'large' ? 'bg-violet-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'}`}
                            title="Крупный шрифт"
                        >
                            A+
                        </button>
                    </div>

                    {/* Copy Markdown */}
                    <button
                        type="button"
                        onClick={handleCopyMarkdown}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 transition-all shadow-2xs"
                        title="Скопировать весь Markdown-текст"
                    >
                        {copiedFormat === 'md' ? (
                            <>
                                <Check className="w-3.5 h-3.5 text-emerald-500" />
                                <span className="text-emerald-600 dark:text-emerald-400 font-semibold">Скопировано!</span>
                            </>
                        ) : (
                            <>
                                <Copy className="w-3.5 h-3.5 text-slate-500" />
                                <span>Копировать</span>
                            </>
                        )}
                    </button>

                    {/* Print / PDF */}
                    <button
                        type="button"
                        onClick={handlePrint}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 transition-all shadow-2xs"
                        title="Распечатать или сохранить в PDF"
                    >
                        <Printer className="w-3.5 h-3.5 text-slate-500" />
                        <span className="hidden sm:inline">Печать / PDF</span>
                    </button>

                    {/* Download .md */}
                    <button
                        type="button"
                        onClick={handleDownloadDoc}
                        className="p-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-all shadow-2xs"
                        title="Скачать файл .md"
                    >
                        <Download className="w-3.5 h-3.5" />
                    </button>

                    {/* Regenerate if handler passed */}
                    {onRegenerate && (
                        <button
                            type="button"
                            onClick={onRegenerate}
                            className="p-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-all shadow-2xs"
                            title="Сгенерировать заново"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                        </button>
                    )}
                </div>
            </div>

            {/* Content Body Canvas */}
            <div className="p-6 sm:p-8" id="printable-ai-content">
                <div
                    className={`ai-content-canvas ${fontSizeStyles} text-slate-800 dark:text-slate-100`}
                    dangerouslySetInnerHTML={{ __html: htmlContent }}
                />
            </div>

            {/* Bottom Meta Footer */}
            <div className="px-6 py-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/40 dark:bg-slate-900 flex flex-wrap items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 gap-2">
                <div className="flex items-center gap-3">
                    <span className="flex items-center gap-1.5">
                        <span className="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                        Сгенерировано Edusfera AI
                    </span>
                    <span>•</span>
                    <span>Формулы KaTeX активированы</span>
                    <span>•</span>
                    <span>Стандарты РИКЗ 2026</span>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={handleCopyPlainText}
                        className="hover:text-violet-600 dark:hover:text-violet-400 font-medium transition-colors"
                    >
                        {copiedFormat === 'text' ? '✓ Текст скопирован' : 'Копировать чистым текстом'}
                    </button>
                </div>
            </div>
        </div>
    );
}
