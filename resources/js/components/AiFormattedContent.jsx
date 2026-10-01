import React, { useMemo } from 'react';
import { marked } from 'marked';
import katex from 'katex';

/**
 * Robust mathematical formula and Markdown parser
 * Converts:
 * - Block math: $$ ... $$ and \[ ... \] into KaTeX display blocks
 * - Inline math: $ ... $ and \( ... \) into KaTeX inline spans
 * - Full GitHub Flavored Markdown into semantic HTML with 2026 design styling
 */
export function renderMathAndMarkdown(rawText) {
    if (!rawText || typeof rawText !== 'string') return '';

    let text = rawText;

    // 1. Render display/block math $$...$$
    text = text.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
        try {
            const rendered = katex.renderToString(formula.trim(), {
                displayMode: true,
                throwOnError: false,
                output: 'html',
            });
            return `\n\n<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 overflow-x-auto text-center shadow-xs transition-colors">${rendered}</div>\n\n`;
        } catch (e) {
            return match;
        }
    });

    // 2. Render LaTeX \[ ... \] blocks
    text = text.replace(/\\\[([\s\S]+?)\\\]/g, (match, formula) => {
        try {
            const rendered = katex.renderToString(formula.trim(), {
                displayMode: true,
                throwOnError: false,
                output: 'html',
            });
            return `\n\n<div class="katex-display-wrapper my-4 py-3 px-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 overflow-x-auto text-center shadow-xs transition-colors">${rendered}</div>\n\n`;
        } catch (e) {
            return match;
        }
    });

    // 3. Render inline math $...$
    // Ensure we don't accidentally match isolated dollar amounts like $50 or \$
    text = text.replace(/(?<!\\|\$)\$([^\$\n]+?)\$(?!\$)/g, (match, formula) => {
        // Skip pure currency like $10 or $ 10
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

    // 4. Render LaTeX \( ... \) inline formulas
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

export default function AiFormattedContent({ content, fontSize = 'normal', className = '' }) {
    const html = useMemo(() => {
        return renderMathAndMarkdown(content);
    }, [content]);

    const fontSizeClasses = {
        compact: 'text-[12px] leading-relaxed',
        normal: 'text-[13.5px] leading-relaxed',
        large: 'text-[15px] leading-loose',
    }[fontSize] || 'text-[13.5px] leading-relaxed';

    return (
        <div
            className={`ai-content-canvas ${fontSizeClasses} text-slate-800 dark:text-slate-100 ${className}`}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
