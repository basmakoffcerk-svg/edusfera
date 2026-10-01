<x-filament-panels::page>
    <style>
        .tutor-sub-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
            font-family: inherit;
        }

        /* ═══ Header & Status Banners ═════════════════════════════════ */
        .tutor-sub-hero {
            position: relative;
            background: linear-gradient(135deg, #0C0A14 0%, #1F1B2E 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 28px 32px;
            color: #FFFFFF;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(12, 10, 20, 0.5);
        }

        .tutor-sub-hero-badge-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .tutor-sub-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            line-height: 1;
        }

        .tutor-sub-badge--emerald {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 0 12px rgba(5, 150, 105, 0.4);
        }

        .tutor-sub-badge--purple {
            background: #7D39EB;
            color: #FFFFFF;
        }

        .tutor-sub-badge--founder {
            background: #F59E0B;
            color: #000000;
            font-weight: 900;
        }

        .tutor-sub-badge--outline {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #E5E7EB;
        }

        .tutor-sub-hero-title {
            margin: 0 0 8px 0;
            font-size: clamp(1.6rem, 1.2rem + 1.8vw, 2.35rem);
            font-weight: 900;
            letter-spacing: -0.025em;
            line-height: 1.15;
            color: #FFFFFF;
        }

        .tutor-sub-hero-subtitle {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #9CA3AF;
            max-width: 680px;
        }

        /* Grace Period Red/Amber Alert */
        .tutor-sub-alert-grace {
            background: #FEF2F2;
            border: 1.5px solid #F87171;
            border-radius: 18px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: #991B1B;
        }
        .dark .tutor-sub-alert-grace {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.35);
            color: #FCA5A5;
        }

        .tutor-sub-alert-icon {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            color: #DC2626;
        }
        .dark .tutor-sub-alert-icon {
            color: #F87171;
        }

        /* Payment Failure & Success Alerts */
        .tutor-sub-alert-failure {
            background: #FEF2F2;
            border: 1.5px solid #EF4444;
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: #991B1B;
            box-shadow: 0 4px 20px -5px rgba(239, 68, 68, 0.2);
            animation: fadeInSubAlert 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dark .tutor-sub-alert-failure {
            background: rgba(239, 68, 68, 0.14);
            border-color: rgba(239, 68, 68, 0.4);
            color: #FCA5A5;
        }

        .tutor-sub-alert-success {
            background: #ECFDF5;
            border: 1.5px solid #10B981;
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: #065F46;
            box-shadow: 0 4px 20px -5px rgba(16, 185, 129, 0.2);
            animation: fadeInSubAlert 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dark .tutor-sub-alert-success {
            background: rgba(16, 185, 129, 0.14);
            border-color: rgba(16, 185, 129, 0.4);
            color: #6EE7B7;
        }

        @keyframes fadeInSubAlert {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .tutor-sub-alert-failure-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #FEE2E2;
            color: #DC2626;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .tutor-sub-alert-failure-icon {
            background: rgba(239, 68, 68, 0.25);
            color: #F87171;
        }

        .tutor-sub-alert-success-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #D1FAE5;
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .tutor-sub-alert-success-icon {
            background: rgba(16, 185, 129, 0.25);
            color: #34D399;
        }

        .tutor-sub-alert-failure-content {
            flex: 1;
            min-width: 0;
        }

        .tutor-sub-alert-failure-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }

        .tutor-sub-alert-failure-title {
            margin: 0;
            font-size: 1.0625rem;
            font-weight: 800;
            color: #991B1B;
        }
        .dark .tutor-sub-alert-failure-title {
            color: #FCA5A5;
        }

        .tutor-sub-alert-success-title {
            margin: 0;
            font-size: 1.0625rem;
            font-weight: 800;
            color: #065F46;
        }
        .dark .tutor-sub-alert-success-title {
            color: #6EE7B7;
        }

        .tutor-sub-alert-failure-desc {
            margin: 0 0 14px 0;
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #7F1D1D;
        }
        .dark .tutor-sub-alert-failure-desc {
            color: #FECACA;
        }

        .tutor-sub-alert-close {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: currentColor;
            opacity: 0.6;
            transition: opacity 0.15s ease;
            font-size: 1rem;
            line-height: 1;
        }
        .tutor-sub-alert-close:hover {
            opacity: 1;
        }

        .tutor-sub-alert-failure-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .tutor-sub-alert-retry-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 18px;
            border-radius: 12px;
            background: #DC2626;
            color: #FFFFFF;
            font-size: 0.875rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }
        .tutor-sub-alert-retry-btn:hover:not(:disabled) {
            background: #B91C1C;
            transform: translateY(-1px);
        }
        .tutor-sub-alert-retry-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .tutor-sub-alert-cancel-btn {
            display: inline-flex;
            align-items: center;
            height: 38px;
            padding: 0 16px;
            border-radius: 12px;
            background: transparent;
            border: 1px solid rgba(153, 27, 27, 0.25);
            color: #991B1B;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .dark .tutor-sub-alert-cancel-btn {
            border-color: rgba(252, 165, 165, 0.3);
            color: #FCA5A5;
        }
        .tutor-sub-alert-cancel-btn:hover {
            background: rgba(153, 27, 27, 0.08);
        }

        /* Required Subscription Alert */
        .tutor-sub-alert-required {
            background: #FFFBEB;
            border: 1.5px solid #F59E0B;
            border-radius: 18px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: #92400E;
            box-shadow: 0 4px 20px -5px rgba(245, 158, 11, 0.2);
            animation: fadeInSubAlert 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dark .tutor-sub-alert-required {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.4);
            color: #FCD34D;
        }
        .tutor-sub-alert-required-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #FEF3C7;
            color: #D97706;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .tutor-sub-alert-required-icon {
            background: rgba(245, 158, 11, 0.25);
            color: #FBBF24;
        }

        /* Onboarding Banner */
        .tutor-sub-onboarding-banner {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #064E3B 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 28px 32px;
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-shadow: 0 16px 36px -10px rgba(30, 27, 75, 0.5);
            position: relative;
            overflow: hidden;
            animation: fadeInSubAlert 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media (min-width: 768px) {
            .tutor-sub-onboarding-banner {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        .tutor-sub-onboarding-content {
            max-width: 680px;
        }
        .tutor-sub-onboarding-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #A7F3D0;
            margin-bottom: 12px;
        }
        .tutor-sub-onboarding-title {
            margin: 0 0 10px 0;
            font-size: clamp(1.4rem, 1.1rem + 1.4vw, 1.95rem);
            font-weight: 900;
            line-height: 1.2;
            color: #FFFFFF;
        }
        .tutor-sub-onboarding-desc {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.55;
            color: #D1D5DB;
        }
        .tutor-sub-onboarding-cta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
            flex-shrink: 0;
        }
        .tutor-sub-onboarding-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            background: #10B981;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.9375rem;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .tutor-sub-onboarding-btn:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }
        .tutor-sub-onboarding-note {
            font-size: 0.75rem;
            color: #9CA3AF;
            padding-left: 4px;
        }
        .tutor-plan-btn--trial {
            margin-top: 8px;
            background: transparent;
            border: 1px solid #10B981;
            color: #059669;
            font-weight: 700;
            font-size: 0.8125rem;
            padding: 8px 14px;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .tutor-plan-btn--trial:hover {
            background: rgba(16, 185, 129, 0.08);
            border-color: #059669;
        }
        .dark .tutor-plan-btn--trial {
            border-color: #34D399;
            color: #34D399;
        }
        .dark .tutor-plan-btn--trial:hover {
            background: rgba(52, 211, 153, 0.12);
        }

        /* ═══ Billing Cycle Toggle ═══════════════════════════════════ */
        .tutor-sub-toggle-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 8px 0;
            text-align: center;
        }

        .tutor-sub-toggle-pill {
            display: inline-flex;
            align-items: center;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 9999px;
            padding: 4px;
            gap: 4px;
            user-select: none;
            cursor: pointer;
        }
        .dark .tutor-sub-toggle-pill {
            background: #1F2937;
            border-color: #374151;
        }

        .tutor-sub-toggle-btn {
            padding: 8px 20px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s ease;
            color: #6B7280;
            border: none;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .dark .tutor-sub-toggle-btn {
            color: #9CA3AF;
        }

        .tutor-sub-toggle-btn.is-active {
            background: #FFFFFF;
            color: #0C0A14;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .dark .tutor-sub-toggle-btn.is-active {
            background: #111827;
            color: #FFFFFF;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .tutor-sub-discount-tag {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            background: #059669;
            color: #FFFFFF;
        }

        /* ═══ Pricing Grid ═══════════════════════════════════════════ */
        .tutor-sub-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 768px) {
            .tutor-sub-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .tutor-sub-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .tutor-plan-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 24px;
            background: #FFFFFF;
            border: 1.5px solid #E5E7EB;
            padding: 32px;
            position: relative;
            transition: transform 0.15s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .dark .tutor-plan-card {
            background: #111827;
            border-color: #1F2937;
        }
        .tutor-plan-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.06);
        }
        .dark .tutor-plan-card:hover {
            border-color: #374151;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.3);
        }

        .tutor-plan-card--pro {
            border: 2px solid #059669;
            background: #FAFDFB;
        }
        .dark .tutor-plan-card--pro {
            border-color: #059669;
            background: rgba(5, 150, 105, 0.05);
        }

        .tutor-plan-card--premium {
            border: 2px solid #8B5CF6;
            background: #FAF5FF;
        }
        .dark .tutor-plan-card--premium {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }

        .tutor-plan-badge-hit {
            position: absolute;
            top: -13px;
            right: 28px;
            background: #059669;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 14px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
        }

        .tutor-plan-badge-top {
            position: absolute;
            top: -13px;
            right: 28px;
            background: #7C3AED;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 14px;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3);
        }

        .tutor-plan-price--premium {
            color: #7C3AED;
        }
        .dark .tutor-plan-price--premium {
            color: #C084FC;
        }

        .tutor-plan-btn--premium {
            background: #7C3AED;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }
        .tutor-plan-btn--premium:hover {
            background: #6D28D9;
        }

        .tutor-plan-header {
            margin-bottom: 24px;
        }

        .tutor-plan-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .tutor-plan-name {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin: 0;
            color: #0C0A14;
        }
        .dark .tutor-plan-name {
            color: #FFFFFF;
        }

        .tutor-plan-tag-current {
            background: #E5E7EB;
            color: #374151;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 9999px;
        }
        .dark .tutor-plan-tag-current {
            background: #374151;
            color: #E5E7EB;
        }

        .tutor-plan-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 12px;
        }

        .tutor-plan-price {
            font-size: 2.75rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1;
            color: #0C0A14;
            font-variant-numeric: tabular-nums;
        }
        .dark .tutor-plan-price {
            color: #FFFFFF;
        }

        .tutor-plan-price--pro {
            color: #059669;
        }
        .dark .tutor-plan-price--pro {
            color: #10B981;
        }

        .tutor-plan-unit {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #6B7280;
        }
        .dark .tutor-plan-unit {
            color: #9CA3AF;
        }

        .tutor-plan-sub-billing {
            margin-top: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #059669;
        }
        .dark .tutor-plan-sub-billing {
            color: #34D399;
        }

        /* Features List */
        .tutor-features-list {
            list-style: none;
            padding: 0;
            margin: 24px 0 32px 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .tutor-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
            line-height: 1.45;
            color: #374151;
        }
        .dark .tutor-feature-item {
            color: #D1D5DB;
        }

        .tutor-feature-check {
            width: 20px;
            height: 20px;
            border-radius: 9999px;
            background: rgba(5, 150, 105, 0.12);
            color: #059669;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }
        .dark .tutor-feature-check {
            background: rgba(5, 150, 105, 0.25);
            color: #34D399;
        }

        /* Plan Action Button */
        .tutor-plan-btn {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            font-size: 0.9375rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: none;
        }
        .tutor-plan-btn:active {
            transform: scale(0.98);
        }

        .tutor-plan-btn--primary {
            background: #059669;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }
        .tutor-plan-btn--primary:hover {
            background: #047857;
        }

        .tutor-plan-btn--outline {
            background: transparent;
            border: 1.5px solid #0C0A14;
            color: #0C0A14;
        }
        .dark .tutor-plan-btn--outline {
            border-color: #4B5563;
            color: #FFFFFF;
        }
        .tutor-plan-btn--outline:hover {
            background: #F3F4F6;
        }
        .dark .tutor-plan-btn--outline:hover {
            background: #1F2937;
        }

        .tutor-plan-btn--disabled {
            background: #F3F4F6;
            color: #9CA3AF;
            border: 1px solid #E5E7EB;
            cursor: not-allowed;
        }
        .dark .tutor-plan-btn--disabled {
            background: #1F2937;
            color: #6B7280;
            border-color: #374151;
        }

        /* ═══ Detailed Comparison Matrix ═══════════════════════════════ */
        .tutor-comparison-wrapper {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.04);
        }
        .dark .tutor-comparison-wrapper {
            background: #111827;
            border-color: #1F2937;
            box-shadow: none;
        }

        .tutor-comparison-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 28px auto;
        }

        .tutor-comparison-title {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #0F172A;
            margin: 0 0 8px 0;
        }
        .dark .tutor-comparison-title {
            color: #F8FAFC;
        }

        .tutor-comparison-subtitle {
            font-size: 0.9375rem;
            color: #64748B;
            margin: 0;
            line-height: 1.5;
        }
        .dark .tutor-comparison-subtitle {
            color: #94A3B8;
        }

        .tutor-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tutor-matrix-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .tutor-matrix-table th,
        .tutor-matrix-table td {
            padding: 13px 16px;
            text-align: left;
            border-bottom: 1px solid #F1F5F9;
        }
        .dark .tutor-matrix-table th,
        .dark .tutor-matrix-table td {
            border-bottom-color: #1E293B;
        }

        .tutor-matrix-table th {
            font-weight: 800;
            color: #0F172A;
            background: #F8FAFC;
            font-size: 0.875rem;
        }
        .dark .tutor-matrix-table th {
            background: #1E293B;
            color: #F8FAFC;
        }

        .tutor-matrix-col-feature {
            width: 40%;
        }
        .tutor-matrix-col-plan {
            width: 20%;
            text-align: center !important;
        }

        .tutor-matrix-category td {
            background: #F8FAFC;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #475569;
            padding: 18px 16px 10px 16px;
        }
        .dark .tutor-matrix-category td {
            background: rgba(30, 41, 59, 0.6);
            color: #94A3B8;
        }

        .tutor-matrix-highlight {
            background: rgba(124, 58, 237, 0.035);
        }
        .dark .tutor-matrix-highlight {
            background: rgba(124, 58, 237, 0.08);
        }

        .tutor-matrix-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #D1FAE5;
            color: #059669;
            font-weight: 800;
            font-size: 11px;
        }
        .dark .tutor-matrix-check {
            background: rgba(16, 185, 129, 0.2);
            color: #34D399;
        }

        .tutor-matrix-check-prem {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #EDE9FE;
            color: #7C3AED;
            font-weight: 800;
            font-size: 11px;
        }
        .dark .tutor-matrix-check-prem {
            background: rgba(124, 58, 237, 0.25);
            color: #A78BFA;
        }

        .tutor-matrix-cross {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #F3F4F6;
            color: #9CA3AF;
            font-size: 10px;
            font-weight: bold;
        }
        .dark .tutor-matrix-cross {
            background: #1F2937;
            color: #6B7280;
        }

        /* ═══ Payment Guarantee & Alfa Badge ═══════════════════════════ */
        .tutor-sub-guarantee-box {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 24px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 18px;
        }
        .dark .tutor-sub-guarantee-box {
            background: #111827;
            border-color: #1F2937;
        }

        .tutor-sub-guarantee-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tutor-sub-guarantee-icon {
            width: 32px;
            height: 32px;
            color: #059669;
            flex-shrink: 0;
        }

        /* ═══ Invoice & Action Hub ════════════════════════════════════ */
        .tutor-card-section {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            padding: 28px 32px;
        }
        .dark .tutor-card-section {
            background: #111827;
            border-color: #1F2937;
        }

        .tutor-card-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .tutor-card-section-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #0C0A14;
            margin: 0;
        }
        .dark .tutor-card-section-title {
            color: #FFFFFF;
        }

        /* Table styles */
        .tutor-invoices-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }
        .tutor-invoices-table th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6B7280;
            border-bottom: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        .dark .tutor-invoices-table th {
            color: #9CA3AF;
            border-color: #1F2937;
            background: rgba(31, 41, 55, 0.4);
        }
        .tutor-invoices-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #F3F4F6;
            color: #1F2937;
        }
        .dark .tutor-invoices-table td {
            border-color: #1F2937;
            color: #E5E7EB;
        }

        /* Modal Backdrop */
        .tutor-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(12, 10, 20, 0.7);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .tutor-modal-card {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
            padding: 32px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.3);
        }
        .dark .tutor-modal-card {
            background: #111827;
            border-color: #1F2937;
        }

        /* ═══ Pre-payment Checkout Modal (Mobile Optimized) ═══════════════ */
        .tutor-checkout-overlay {
            position: fixed;
            inset: 0;
            background: rgba(12, 10, 20, 0.72);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: tutorCheckoutFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes tutorCheckoutFadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }

        .tutor-checkout-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 28px;
            padding: 28px 24px;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.04);
            max-height: calc(100vh - 32px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
            box-sizing: border-box;
        }
        .dark .tutor-checkout-card {
            background: #111827;
            border-color: #1F2937;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }

        .tutor-checkout-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .tutor-checkout-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .tutor-checkout-icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(125, 57, 235, 0.3);
        }

        .tutor-checkout-icon {
            width: 20px;
            height: 20px;
        }

        .tutor-checkout-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.25;
            letter-spacing: -0.01em;
        }
        .dark .tutor-checkout-title {
            color: #F8FAFC;
        }

        .tutor-checkout-subtitle {
            margin: 2px 0 0 0;
            font-size: 0.75rem;
            color: #64748B;
            line-height: 1.35;
        }
        .dark .tutor-checkout-subtitle {
            color: #94A3B8;
        }

        .tutor-checkout-close-btn {
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            background: #F1F5F9;
            border: none;
            cursor: pointer;
            color: #64748B;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.15s ease;
            padding: 0;
        }
        .tutor-checkout-close-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }
        .dark .tutor-checkout-close-btn {
            background: #1E293B;
            color: #94A3B8;
        }
        .dark .tutor-checkout-close-btn:hover {
            background: #334155;
            color: #F8FAFC;
        }

        .tutor-checkout-plan-box {
            padding: 14px 16px;
            border-radius: 18px;
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            margin-bottom: 16px;
        }
        .dark .tutor-checkout-plan-box {
            background: rgba(30, 41, 59, 0.6);
            border-color: #334155;
        }

        .tutor-checkout-plan-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .tutor-checkout-plan-name-wrap {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px 8px;
        }

        .tutor-checkout-plan-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
        }
        .dark .tutor-checkout-plan-title {
            color: #F8FAFC;
        }

        .tutor-checkout-plan-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 9999px;
            background: #EEF2FF;
            color: #4F46E5;
            border: 1px solid #C7D2FE;
            line-height: 1;
            white-space: nowrap;
        }
        .dark .tutor-checkout-plan-badge {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
            color: #A5B4FC;
        }

        .tutor-checkout-plan-duration {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748B;
            background: #EDF2F7;
            padding: 3px 9px;
            border-radius: 9999px;
            white-space: nowrap;
            margin-left: auto;
        }
        .dark .tutor-checkout-plan-duration {
            background: #334155;
            color: #CBD5E1;
        }

        .tutor-checkout-plan-desc {
            margin: 0;
            font-size: 0.8125rem;
            color: #64748B;
            line-height: 1.4;
        }
        .dark .tutor-checkout-plan-desc {
            color: #94A3B8;
        }

        .tutor-checkout-promo-section {
            margin-bottom: 16px;
            width: 100%;
        }

        .tutor-checkout-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .dark .tutor-checkout-label {
            color: #CBD5E1;
        }

        .tutor-checkout-input-row {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        .tutor-checkout-input-wrapper {
            flex: 1 1 auto;
            min-width: 0;
        }

        .tutor-checkout-promo-input {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            border: 1.5px solid #CBD5E1;
            border-radius: 12px;
            font-family: monospace;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.875rem;
            background: #FFFFFF;
            color: #0F172A;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }
        .tutor-checkout-promo-input:focus {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }
        .dark .tutor-checkout-promo-input {
            background: #1E293B;
            border-color: #475569;
            color: #F8FAFC;
        }
        .dark .tutor-checkout-promo-input:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .tutor-checkout-promo-btn {
            height: 42px;
            padding: 0 16px;
            background: #0F172A;
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .tutor-checkout-promo-btn:hover {
            background: #1E293B;
        }
        .dark .tutor-checkout-promo-btn {
            background: #7D39EB;
        }
        .dark .tutor-checkout-promo-btn:hover {
            background: #6D28D9;
        }

        .tutor-checkout-promo-applied {
            background: #ECFDF5;
            border: 1.5px solid #6EE7B7;
            border-radius: 14px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .dark .tutor-checkout-promo-applied {
            background: rgba(5, 150, 105, 0.12);
            border-color: rgba(16, 185, 129, 0.35);
        }

        .tutor-checkout-promo-applied-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .tutor-checkout-promo-badge-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: #D1FAE5;
            color: #059669;
            font-weight: 900;
            font-size: 13px;
            flex-shrink: 0;
        }
        .dark .tutor-checkout-promo-badge-check {
            background: rgba(16, 185, 129, 0.25);
            color: #34D399;
        }

        .tutor-checkout-promo-details {
            min-width: 0;
        }

        .tutor-checkout-promo-code-text {
            font-weight: 800;
            font-family: monospace;
            font-size: 0.9375rem;
            color: #065F46;
            letter-spacing: 0.05em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .tutor-checkout-promo-code-text {
            color: #6EE7B7;
        }

        .tutor-checkout-promo-subtext {
            font-size: 0.75rem;
            color: #047857;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .tutor-checkout-promo-subtext {
            color: #A7F3D0;
        }

        .tutor-checkout-promo-reset-btn {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
            border-radius: 8px;
            padding: 5px 9px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s ease;
        }
        .tutor-checkout-promo-reset-btn:hover {
            background: #FECACA;
        }
        .dark .tutor-checkout-promo-reset-btn {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
            color: #FCA5A5;
        }

        .tutor-checkout-promo-error {
            margin-top: 6px;
            font-size: 0.8125rem;
            color: #DC2626;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .dark .tutor-checkout-promo-error {
            color: #F87171;
        }

        .tutor-checkout-calc-box {
            padding: 14px 16px;
            border-radius: 18px;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            margin-bottom: 18px;
        }
        .dark .tutor-checkout-calc-box {
            background: rgba(30, 41, 59, 0.7);
            border-color: #334155;
        }

        .tutor-checkout-calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 6px;
        }
        .dark .tutor-checkout-calc-row {
            color: #94A3B8;
        }

        .tutor-checkout-calc-value {
            font-weight: 700;
            color: #0F172A;
        }
        .dark .tutor-checkout-calc-value {
            color: #F8FAFC;
        }

        .tutor-checkout-calc-row--discount {
            color: #059669;
            font-weight: 700;
        }
        .dark .tutor-checkout-calc-row--discount {
            color: #34D399;
        }

        .tutor-checkout-calc-divider {
            border-top: 1px dashed #CBD5E1;
            margin: 8px 0 10px 0;
        }
        .dark .tutor-checkout-calc-divider {
            border-color: #475569;
        }

        .tutor-checkout-calc-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .tutor-checkout-calc-total-label {
            font-size: 1rem;
            font-weight: 800;
            color: #0F172A;
        }
        .dark .tutor-checkout-calc-total-label {
            color: #FFFFFF;
        }

        .tutor-checkout-calc-total-price {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .tutor-checkout-price-current {
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #0F172A;
            font-variant-numeric: tabular-nums;
        }
        .dark .tutor-checkout-price-current {
            color: #FFFFFF;
        }

        .tutor-checkout-price-old {
            font-size: 0.875rem;
            text-decoration: line-through;
            color: #94A3B8;
        }

        .tutor-checkout-price-free {
            font-size: 1.35rem;
            font-weight: 900;
            color: #059669;
        }
        .dark .tutor-checkout-price-free {
            color: #10B981;
        }

        .tutor-checkout-badge-free {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 9999px;
            background: #D1FAE5;
            color: #065F46;
        }

        .tutor-checkout-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        .tutor-checkout-submit-btn {
            width: 100%;
            height: 48px;
            border-radius: 14px;
            background: #059669;
            color: #FFFFFF;
            font-size: 0.9375rem;
            font-weight: 800;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
            transition: all 0.15s ease;
            box-sizing: border-box;
        }
        .tutor-checkout-submit-btn:hover:not(:disabled) {
            background: #047857;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
            transform: translateY(-1px);
        }
        .tutor-checkout-submit-btn:active:not(:disabled) {
            transform: scale(0.985);
        }
        .tutor-checkout-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .tutor-checkout-submit-btn--free {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .tutor-checkout-loading-state {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tutor-checkout-trust-row {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px 8px;
            font-size: 0.75rem;
            color: #64748B;
            line-height: 1.3;
            padding: 2px 0;
            text-align: center;
        }
        .dark .tutor-checkout-trust-row {
            color: #94A3B8;
        }

        .tutor-checkout-trust-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .tutor-checkout-trust-dot {
            color: #CBD5E1;
            font-size: 10px;
        }
        .dark .tutor-checkout-trust-dot {
            color: #475569;
        }

        .tutor-checkout-note {
            font-size: 0.75rem;
            color: #64748B;
            text-align: center;
            line-height: 1.3;
        }
        .dark .tutor-checkout-note {
            color: #94A3B8;
        }

        .tutor-checkout-cancel-btn {
            width: 100%;
            height: 38px;
            background: transparent;
            border: none;
            border-radius: 12px;
            color: #64748B;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .tutor-checkout-cancel-btn:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .dark .tutor-checkout-cancel-btn {
            color: #94A3B8;
        }
        .dark .tutor-checkout-cancel-btn:hover {
            background: #1E293B;
            color: #F8FAFC;
        }

        /* Responsive Mobile Overrides */
        @media (max-width: 640px) {
            .tutor-sub-hero {
                padding: 20px 18px;
                border-radius: 20px;
            }
            .tutor-sub-hero-title {
                font-size: 1.45rem;
            }
            .tutor-sub-toggle-pill {
                width: 100%;
                max-width: 320px;
            }
            .tutor-sub-toggle-btn {
                flex: 1;
                justify-content: center;
                padding: 7px 10px;
                font-size: 0.8125rem;
            }
            .tutor-plan-card {
                padding: 24px 18px;
                border-radius: 20px;
            }
            .tutor-checkout-overlay {
                padding: 12px;
            }
            .tutor-checkout-card {
                padding: 20px 16px 22px 16px;
                border-radius: 24px;
                max-height: calc(100vh - 24px);
            }
            .tutor-checkout-title {
                font-size: 1.0625rem;
            }
            .tutor-checkout-promo-input {
                font-size: 0.8125rem;
                padding: 0 10px;
            }
            .tutor-checkout-promo-btn {
                padding: 0 12px;
                font-size: 0.8125rem;
            }
        }
    </style>

    <div class="tutor-sub-container" x-data="{ isYearly: @entangle('isYearly') }">

        {{-- Payment Failure Alert Banner --}}
        @if($paymentErrorMessage)
            <div class="tutor-sub-alert-failure" x-data="{ open: true }" x-show="open" x-transition>
                <div class="tutor-sub-alert-failure-icon">
                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="tutor-sub-alert-failure-content">
                    <div class="tutor-sub-alert-failure-header">
                        <h4 class="tutor-sub-alert-failure-title">Платёж не прошёл</h4>
                        <button type="button" wire:click="$set('paymentErrorMessage', null)" class="tutor-sub-alert-close" aria-label="Закрыть">
                            ✕
                        </button>
                    </div>
                    <p class="tutor-sub-alert-failure-desc">
                        {{ $paymentErrorMessage }}
                    </p>
                    <div class="tutor-sub-alert-failure-actions">
                        <button 
                            wire:click="openPaymentModal('{{ $selectedPlanCode ?: ($subscription ? $subscription->plan->value : 'pro') }}')" 
                            type="button" 
                            class="tutor-sub-alert-retry-btn"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            Повторить оплату
                        </button>
                        <button 
                            wire:click="$set('paymentErrorMessage', null)" 
                            type="button" 
                            class="tutor-sub-alert-cancel-btn"
                        >
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Payment Success Alert Banner --}}
        @if($paymentSuccessMessage)
            <div class="tutor-sub-alert-success" x-data="{ open: true }" x-show="open" x-transition>
                <div class="tutor-sub-alert-success-icon">
                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="tutor-sub-alert-failure-content">
                    <div class="tutor-sub-alert-failure-header">
                        <h4 class="tutor-sub-alert-success-title">Оплата успешно завершена!</h4>
                        <button type="button" wire:click="$set('paymentSuccessMessage', null)" class="tutor-sub-alert-close" aria-label="Закрыть">
                            ✕
                        </button>
                    </div>
                    <p class="tutor-sub-alert-failure-desc" style="margin-bottom: 0; color: #065F46;">
                        {{ $paymentSuccessMessage }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Subscription Required Notice (Paywall Trigger) --}}
        @if(session('subscription_required') || ($subscription && (! $subscription->isActive() || ! $subscription->is_onboarded)))
            <div class="tutor-sub-alert-required">
                <div class="tutor-sub-alert-required-icon">
                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 800; color: #92400E;">Доступ к разделам платформы ограничен</h4>
                    <p style="margin: 0; font-size: 0.875rem; line-height: 1.5; color: #B45309;">
                        Для работы с расписанием, учениками, заявками и виртуальным классом требуется активная подписка. Выберите тарифный план ниже с бесплатным ознакомительным периодом.
                    </p>
                </div>
            </div>
        @endif

        {{-- Onboarding Welcome Banner --}}
        @if($isOnboarding || (isset($subscription) && ! $subscription->is_onboarded))
            <div class="tutor-sub-onboarding-banner">
                <div class="tutor-sub-onboarding-content">
                    <div class="tutor-sub-onboarding-badge">
                        <span>Шаг 1 из 1</span> • <span>Активация тарифа</span>
                    </div>
                    <h2 class="tutor-sub-onboarding-title">
                        Добро пожаловать в команду Edusfera! 🚀
                    </h2>
                    <p class="tutor-sub-onboarding-desc">
                        Выберите подходящий тариф для начала работы. Вы можете сразу приступить к работе с <strong>бесплатным пробным периодом</strong> (без списания) или оплатить подписку картой онлайн.
                    </p>
                </div>
                <div class="tutor-sub-onboarding-cta">
                    <button 
                        wire:click="activateTrialAndStart('pro')" 
                        wire:loading.attr="disabled"
                        type="button" 
                        class="tutor-sub-onboarding-btn"
                    >
                        <span wire:loading.remove wire:target="activateTrialAndStart">
                            Начать 14 дней бесплатно (Тариф «Про») →
                        </span>
                        <span wire:loading wire:target="activateTrialAndStart">Активация...</span>
                    </button>
                    <span class="tutor-sub-onboarding-note">
                        Банковская карта не требуется • Доступ сразу
                    </span>
                </div>
            </div>
        @endif

        @if($subscription)
            {{-- 1. Grace Period Alert (If applicable) --}}
            @if($subscription->isInGracePeriod())
                <div class="tutor-sub-alert-grace">
                    <svg class="tutor-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 800;">Платеж не прошёл</h4>
                        <p style="margin: 0; font-size: 0.875rem; line-height: 1.4;">
                            Действует льготный период (осталось <strong>{{ $subscription->graceDaysRemaining() }} {{ trans_choice('день|дня|дней', $subscription->graceDaysRemaining()) }}</strong>). Пожалуйста, обновите карту во избежание блокировки виртуального класса.
                        </p>
                        <div style="margin-top: 14px;">
                            <button wire:click="openPaymentModal('{{ $subscription->plan->value }}')" type="button" class="tutor-plan-btn tutor-plan-btn--primary" style="width: auto; height: 38px; padding: 0 18px; font-size: 0.8125rem;">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75v10.5A2.25 2.25 0 0 0 4.5 21Z"/></svg>
                                Привязать новую карту (Альфа-Банк)
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 2. Hero Overview & Current Status Badge --}}
            <div class="tutor-sub-hero">
                <div class="tutor-sub-hero-badge-row">
                    {{-- Status Badge --}}
                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL)
                        <span class="tutor-sub-badge tutor-sub-badge--emerald">
                            <span style="width: 6px; height: 6px; border-radius: 9999px; background: #FFFFFF;"></span>
                            Бесплатный ознакомительный период (Осталось {{ $subscription->daysRemaining() }} {{ trans_choice('день|дня|дней', $subscription->daysRemaining()) }})
                        </span>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        <span class="tutor-sub-badge tutor-sub-badge--emerald">
                            @if($subscription->current_period_ends_at && $subscription->current_period_ends_at->year >= 2035)
                                ✓ Бессрочный доступ к тарифу «{{ $subscription->plan->title() }}»
                            @else
                                ✓ Активная подписка: «{{ $subscription->plan->title() }}», действует до {{ $subscription->current_period_ends_at?->format('d.m.Y') }}
                            @endif
                        </span>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::CANCELED)
                        <span class="tutor-sub-badge tutor-sub-badge--outline">
                            Отменена (доступна до {{ $subscription->current_period_ends_at?->format('d.m.Y') }})
                        </span>
                    @else
                        <span class="tutor-sub-badge tutor-sub-badge--outline">
                            {{ $subscription->status->title() }}
                        </span>
                    @endif

                    @if($subscription->is_founder)
                        <span class="tutor-sub-badge tutor-sub-badge--founder">
                            👑 Основатель Edusfera
                        </span>
                    @endif
                </div>

                <h1 class="tutor-sub-hero-title">
                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL)
                        Ознакомительный период на тарифе «{{ $subscription->plan->title() }}»
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        Ваш тариф: «{{ $subscription->plan->title() }}»
                    @else
                        Управление тарифным планом
                    @endif
                </h1>

                <p class="tutor-sub-hero-subtitle">
                    Неограниченное число учеников, защищённый видеокласс SFU и автоматизация расписания. Выберите подходящий план для масштабирования частной практики.
                </p>

                <div style="margin-top: 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">

                    @if($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                        <button wire:click="openCancelModal" type="button" style="background: transparent; border: none; color: #9CA3AF; font-size: 0.8125rem; cursor: pointer; text-decoration: underline; padding: 6px 12px;">
                            Отменить подписку
                        </button>
                    @elseif($subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::CANCELED)
                        <button wire:click="resumeSubscription" type="button" style="background: transparent; border: 1px solid #059669; color: #34D399; border-radius: 12px; font-size: 0.8125rem; font-weight: 700; cursor: pointer; padding: 8px 16px;">
                            Возобновить подписку
                        </button>
                    @endif
                </div>
            </div>

            {{-- 3. Interactive Monthly / Yearly Toggle --}}
            <div class="tutor-sub-toggle-wrapper">
                <div class="tutor-sub-toggle-pill">
                    <button 
                        type="button" 
                        class="tutor-sub-toggle-btn" 
                        :class="{ 'is-active': !isYearly }" 
                        @click="isYearly = false; $wire.setYearly(false)"
                    >
                        Ежемесячно
                    </button>
                    <button 
                        type="button" 
                        class="tutor-sub-toggle-btn" 
                        :class="{ 'is-active': isYearly }" 
                        @click="isYearly = true; $wire.setYearly(true)"
                    >
                        <span>На 1 год</span>
                        <span class="tutor-sub-discount-tag">-20% выгода</span>
                    </button>
                </div>
                <p style="margin: 0; font-size: 0.8125rem; color: #6B7280;">
                    <span x-show="isYearly">Оплата один раз в год · 2 месяца в подарок</span>
                    <span x-show="!isYearly">Списание каждый месяц · Отмена в любой момент</span>
                </p>
            </div>

            {{-- 4. Plans Comparison Grid --}}
            <div class="tutor-sub-grid">

                {{-- PLAN 1: «Стандарт» --}}
                @php
                    $isCurrentStart = in_array($subscription->plan, [\App\Domain\Subscription\Enums\SubscriptionPlan::START, \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC], true) && $subscription->isActive();
                @endphp
                <div class="tutor-plan-card">
                    <div>
                        <div class="tutor-plan-header">
                            <div class="tutor-plan-title-row">
                                <h3 class="tutor-plan-name">«Стандарт»</h3>
                                @if($isCurrentStart)
                                    <span class="tutor-plan-tag-current">Текущий тариф</span>
                                @endif
                            </div>
                            <p style="margin: 0; font-size: 0.875rem; color: #6B7280;">
                                Для независимых преподавателей и комфортного ведения регулярных уроков.
                            </p>

                            <div class="tutor-plan-price-row">
                                <span class="tutor-plan-price" x-text="isYearly ? '16' : '20'">20</span>
                                <span class="tutor-plan-unit">BYN / мес</span>
                            </div>
                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(5, 150, 105, 0.1); color: #059669; font-size: 11px; font-weight: 800; margin-top: 6px;">
                                🎁 +5 дней триала при первой оплате
                            </div>
                            <div class="tutor-plan-sub-billing" x-show="isYearly">
                                192 BYN в год (вместо 240 BYN)
                            </div>
                        </div>

                        <ul class="tutor-features-list">
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Неограниченно учеников</strong> и карточек контактов</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Виртуальный класс (SFU)</strong> с видеосвязью и демонстрацией экрана</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Интерактивная доска</strong> для формул и заметок</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Расписание и CRM</strong>: бронирование и контроль посещаемости</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Персональная ссылка для записи</strong> учеников</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Базовый чат</strong> и уведомления</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        @if($isCurrentStart && $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                            <button type="button" disabled class="tutor-plan-btn tutor-plan-btn--disabled">
                                Тариф подключён
                            </button>
                        @else
                            <button 
                                wire:click="openPaymentModal('basic')" 
                                type="button" 
                                class="tutor-plan-btn tutor-plan-btn--outline"
                            >
                                <span>
                                    {{ $isCurrentStart ? 'Оплатить картой и продлить' : 'Подключить «Стандарт»' }}
                                </span>
                            </button>
                            @if($isOnboarding || (isset($subscription) && ! $subscription->is_onboarded))
                                <button 
                                    wire:click="activateTrialAndStart('basic')"
                                    wire:loading.attr="disabled"
                                    type="button" 
                                    class="tutor-plan-btn--trial"
                                >
                                    Начать 5 дней бесплатно
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- PLAN 2: «Про» (Hero Card) --}}
                @php
                    $isCurrentPro = ($subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO) && $subscription->isActive();
                @endphp
                <div class="tutor-plan-card tutor-plan-card--pro">
                    <span class="tutor-plan-badge-hit">★ Рекомендуемый</span>

                    <div>
                        <div class="tutor-plan-header">
                            <div class="tutor-plan-title-row">
                                <h3 class="tutor-plan-name" style="color: #059669;">«Про»</h3>
                                @if($isCurrentPro)
                                    <span class="tutor-plan-tag-current" style="background: rgba(5, 150, 105, 0.15); color: #059669;">Текущий тариф</span>
                                @endif
                            </div>
                            <p style="margin: 0; font-size: 0.875rem; color: #6B7280;">
                                Автоматизация рутины: ИИ-помощник, диагностика и авто-чеки НПД.
                            </p>

                            <div class="tutor-plan-price-row">
                                <span class="tutor-plan-price tutor-plan-price--pro" x-text="isYearly ? '32' : '40'">40</span>
                                <span class="tutor-plan-unit">BYN / мес</span>
                            </div>
                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(5, 150, 105, 0.1); color: #059669; font-size: 11px; font-weight: 800; margin-top: 6px;">
                                🎁 +14 дней триала при первой оплате
                            </div>
                            <div class="tutor-plan-sub-billing" x-show="isYearly">
                                384 BYN в год (вместо 480 BYN)
                            </div>
                        </div>

                        <ul class="tutor-features-list">
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Все функции тарифа «Стандарт»</strong></span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>ИИ-диагностика знаний (РИКЗ)</strong> для оценки пробелов ученика</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>ИИ-ассистент</strong>: генерация планов уроков, ДЗ и тестов</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Авто-генерация чеков НПД</strong> для МНС РБ</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Расширенный профиль</strong> (+ дипломы и отзывы)</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Календарь + автонапоминания</strong> ученикам</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check">✓</span>
                                <span><strong>Отклики на входящие заявки</strong> (до 10 в месяц)</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        @if($isCurrentPro && $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                            <button type="button" disabled class="tutor-plan-btn tutor-plan-btn--disabled">
                                Тариф подключён
                            </button>
                        @else
                            <button 
                                wire:click="openPaymentModal('pro')" 
                                type="button" 
                                class="tutor-plan-btn tutor-plan-btn--primary"
                            >
                                <span>
                                    {{ $isCurrentPro ? 'Оплатить картой и продлить' : 'Подключить «Про»' }}
                                </span>
                            </button>
                            @if($isOnboarding || (isset($subscription) && ! $subscription->is_onboarded))
                                <button 
                                    wire:click="activateTrialAndStart('pro')"
                                    wire:loading.attr="disabled"
                                    type="button" 
                                    class="tutor-plan-btn--trial"
                                >
                                    Начать 14 дней бесплатно
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- PLAN 3: «Премиум» (Top-Expert Card) --}}
                @php
                    $isCurrentPremium = ($subscription->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM) && $subscription->isActive();
                @endphp
                <div class="tutor-plan-card tutor-plan-card--premium">
                    <span class="tutor-plan-badge-top">👑 Топ-эксперт</span>

                    <div>
                        <div class="tutor-plan-header">
                            <div class="tutor-plan-title-row">
                                <h3 class="tutor-plan-name" style="color: #7C3AED;">«Премиум»</h3>
                                @if($isCurrentPremium)
                                    <span class="tutor-plan-tag-current" style="background: rgba(124, 58, 237, 0.15); color: #7C3AED;">Текущий тариф</span>
                                @endif
                            </div>
                            <p style="margin: 0; font-size: 0.875rem; color: #6B7280;">
                                Максимум учеников, топ-позиции в поиске и персональный бренд.
                            </p>

                            <div class="tutor-plan-price-row">
                                <span class="tutor-plan-price tutor-plan-price--premium" x-text="isYearly ? '48' : '60'">60</span>
                                <span class="tutor-plan-unit">BYN / мес</span>
                            </div>
                            <div style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(124, 58, 237, 0.12); color: #7C3AED; font-size: 11px; font-weight: 800; margin-top: 6px;">
                                🎁 +28 дней триала при первой оплате
                            </div>
                            <div class="tutor-plan-sub-billing" style="color: #7C3AED;" x-show="isYearly">
                                576 BYN в год (вместо 720 BYN)
                            </div>
                        </div>

                        <ul class="tutor-features-list">
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>Все возможности тарифов «Стандарт» и «Про»</strong></span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>👑 ТОП-1 в каталоге репетиторов</strong> + видео-визитка</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>🔥 БЕЗЛИМИТНЫЕ отклики на заявки</strong> + автоподбор</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>Синхронизация с Google Calendar</strong></span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>Собственный брендинг (White-label)</strong>: ваш логотип</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>100 ГБ облака + запись всех уроков в HD</strong></span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>Мгновенный вывод средств (Instant Payout)</strong> 0%</span>
                            </li>
                            <li class="tutor-feature-item">
                                <span class="tutor-feature-check" style="background: rgba(124, 58, 237, 0.12); color: #7C3AED;">✓</span>
                                <span><strong>Персональный VIP-куратор 24/7</strong> в Telegram</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        @if($isCurrentPremium && $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE)
                            <button type="button" disabled class="tutor-plan-btn tutor-plan-btn--disabled">
                                Тариф подключён
                            </button>
                        @else
                            <button 
                                wire:click="openPaymentModal('premium')" 
                                type="button" 
                                class="tutor-plan-btn tutor-plan-btn--premium"
                            >
                                <span>
                                    {{ $isCurrentPremium ? 'Оплатить картой и продлить' : 'Подключить «Премиум»' }}
                                </span>
                            </button>
                            @if($isOnboarding || (isset($subscription) && ! $subscription->is_onboarded))
                                <button 
                                    wire:click="activateTrialAndStart('premium')"
                                    wire:loading.attr="disabled"
                                    type="button" 
                                    class="tutor-plan-btn--trial"
                                    style="border-color: #7C3AED; color: #7C3AED;"
                                >
                                    Начать 28 дней бесплатно
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

            </div>

            {{-- 4b. Detailed Feature Comparison Table --}}
            <div class="tutor-comparison-wrapper">
                <div class="tutor-comparison-header">
                    <h3 class="tutor-comparison-title">Подробное сравнение тарифов</h3>
                    <p class="tutor-comparison-subtitle">
                        Выберите оптимальный уровень для ваших задач: от преподавания своим ученикам до гарантированного лидерства в каталоге с максимальным потоком заявок.
                    </p>
                </div>

                <div class="tutor-table-scroll">
                    <table class="tutor-matrix-table">
                        <thead>
                            <tr>
                                <th class="tutor-matrix-col-feature">Возможности и функции</th>
                                <th class="tutor-matrix-col-plan">«Стандарт»<br><span style="font-weight: 500; font-size: 0.75rem; color: #6B7280;" x-text="isYearly ? '16 BYN/мес' : '20 BYN/мес'">20 BYN/мес</span></th>
                                <th class="tutor-matrix-col-plan" style="color: #059669;">«Про»<br><span style="font-weight: 500; font-size: 0.75rem; color: #059669;" x-text="isYearly ? '32 BYN/мес' : '40 BYN/мес'">40 BYN/мес</span></th>
                                <th class="tutor-matrix-col-plan tutor-matrix-highlight" style="color: #7C3AED;">«Премиум» 👑<br><span style="font-weight: 600; font-size: 0.75rem; color: #7C3AED;" x-text="isYearly ? '48 BYN/мес' : '60 BYN/мес'">60 BYN/мес</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Category 1: Клиенты и каталог --}}
                            <tr class="tutor-matrix-category">
                                <td colspan="4">1. Привлечение новых учеников и каталог</td>
                            </tr>
                            <tr>
                                <td>Позиция и ранжирование в каталоге репетиторов</td>
                                <td class="tutor-matrix-col-plan" style="color: #6B7280;">Базовая</td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 700; color: #059669;">Приоритет x2</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">👑 ТОП-1 каталога</td>
                            </tr>
                            <tr>
                                <td>Золотой бейдж «👑 Топ-эксперт» и золотая рамка карточки</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>
                            <tr>
                                <td>Отклики на входящие заявки родителей с биржи</td>
                                <td class="tutor-matrix-col-plan" style="color: #9CA3AF;">0 заявок (только свои)</td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 700;">До 10 заявок / мес</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">🔥 БЕЗЛИМИТНО</td>
                            </tr>
                            <tr>
                                <td>Умный автоподбор целевых заявок платформой</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>
                            <tr>
                                <td>Видео-визитка прямо в поисковой выдаче каталога</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>
                            <tr>
                                <td>Персональная страница репетитора и ссылка на запись</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-check">✓</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-check">✓</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>

                            {{-- Category 2: Виртуальный класс --}}
                            <tr class="tutor-matrix-category">
                                <td colspan="4">2. Виртуальный класс и процесс обучения</td>
                            </tr>
                            <tr>
                                <td>Длительность 1 занятия в виртуальном классе SFU</td>
                                <td class="tutor-matrix-col-plan">До 45 минут</td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 600;">До 120 минут</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">Без ограничений</td>
                            </tr>
                            <tr>
                                <td>Интерактивная доска, видеочат и демонстрация экрана</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-check">✓</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-check">✓</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>
                            <tr>
                                <td>Собственный брендинг класса (логотип, цвета, White-label)</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>
                            <tr>
                                <td>Облачное хранилище учебных материалов и ДЗ</td>
                                <td class="tutor-matrix-col-plan">1 ГБ</td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 600;">15 ГБ</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">100 ГБ</td>
                            </tr>
                            <tr>
                                <td>Автоматическая запись всех онлайн-уроков в HD</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>

                            {{-- Category 3: ИИ и автоматизация --}}
                            <tr class="tutor-matrix-category">
                                <td colspan="4">3. ИИ-ассистент и налоговая автоматизация</td>
                            </tr>
                            <tr>
                                <td>ИИ-диагностика знаний и пробелов ученика (тесты РИКЗ)</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 600; color: #059669;">До 30 / мес</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">БЕЗЛИМИТНО</td>
                            </tr>
                            <tr>
                                <td>ИИ-помощник: планы уроков, конспекты, генерация тестов и ДЗ</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-check">✓</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓ (GPT-4o / Claude)</span></td>
                            </tr>
                            <tr>
                                <td>Авто-генерация фискальных чеков НПД для МНС РБ</td>
                                <td class="tutor-matrix-col-plan" style="color: #9CA3AF;">Ручной ввод</td>
                                <td class="tutor-matrix-col-plan" style="font-weight: 600; color: #059669;">В 1 клик</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">Полный автопилот</td>
                            </tr>
                            <tr>
                                <td>Двусторонняя авто-синхронизация с Google Calendar</td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan"><span class="tutor-matrix-cross">✕</span></td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight"><span class="tutor-matrix-check-prem">✓</span></td>
                            </tr>

                            {{-- Category 4: Финансы и сервис --}}
                            <tr class="tutor-matrix-category">
                                <td colspan="4">4. Выплаты, аналитика и сервис</td>
                            </tr>
                            <tr>
                                <td>Вывод средств на любую банковскую карту Беларуси</td>
                                <td class="tutor-matrix-col-plan">1-3 рабочих дня</td>
                                <td class="tutor-matrix-col-plan">Приоритетный (24 ч)</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">⚡ Мгновенно (0% комиссии)</td>
                            </tr>
                            <tr>
                                <td>Аналитика учеников, LTV и экспорт отчётов</td>
                                <td class="tutor-matrix-col-plan" style="color: #6B7280;">Базовая</td>
                                <td class="tutor-matrix-col-plan">Расширенная</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">Когорты, LTV, прогноз</td>
                            </tr>
                            <tr>
                                <td>Автонапоминания ученикам о занятиях</td>
                                <td class="tutor-matrix-col-plan" style="color: #6B7280;">Email</td>
                                <td class="tutor-matrix-col-plan">SMS + Telegram</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">SMS + Telegram + звонки</td>
                            </tr>
                            <tr>
                                <td>Уровень персональной поддержки</td>
                                <td class="tutor-matrix-col-plan" style="color: #6B7280;">Чат платформы</td>
                                <td class="tutor-matrix-col-plan">Приоритет в Telegram</td>
                                <td class="tutor-matrix-col-plan tutor-matrix-highlight" style="font-weight: 800; color: #7C3AED;">💎 VIP-куратор 24/7</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5. Safe Payment Guarantee Box --}}
            <div class="tutor-sub-guarantee-box">
                <div class="tutor-sub-guarantee-left">
                    <svg class="tutor-sub-guarantee-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <div>
                        <div style="font-size: 0.9375rem; font-weight: 800; color: #0C0A14;" class="dark:text-white">Безопасная оплата через Альфа-Банк</div>
                        <div style="font-size: 0.8125rem; color: #6B7280;">Протокол 3-D Secure 2.0. Карты Visa, Mastercard, БЕЛКАРТ, МИР. Данные карты не хранятся на сервере Edusfera.</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: #E5E7EB; color: #374151;">PCI DSS Level 1</span>
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; background: #DC2626; color: #FFFFFF;">Альфа-Банк</span>
                </div>
            </div>

            {{-- 7. Invoices & Billing History Table --}}
            <div class="tutor-card-section">
                <div class="tutor-card-section-head">
                    <div>
                        <h3 class="tutor-card-section-title">История счетов и оплат</h3>
                        <p style="margin: 4px 0 0 0; font-size: 0.8125rem; color: #6B7280;">
                            Квитанции и акты для налогового учёта (НПД).
                        </p>
                    </div>
                </div>

                <div style="overflow-x: auto; margin-top: 12px;">
                    <table class="tutor-invoices-table">
                        <thead>
                            <tr>
                                <th>Номер счёта</th>
                                <th>Тарифный план</th>
                                <th>Период</th>
                                <th>Сумма</th>
                                <th>Срок оплаты</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td style="font-family: monospace; font-weight: 700;">{{ $invoice->invoice_number }}</td>
                                    <td style="font-weight: 700;">{{ $invoice->plan->title() }}</td>
                                    <td>{{ $invoice->period_months >= 120 ? 'Бессрочно' : ($invoice->period_months === 12 ? '1 год (-20%)' : ($invoice->period_months === 6 ? '6 месяцев' : ($invoice->period_months === 3 ? '3 месяца' : '1 месяц'))) }}</td>
                                    <td style="font-weight: 800;">{{ $invoice->amountByn() }} BYN</td>
                                    <td style="color: #6B7280;">{{ $invoice->due_date?->format('d.m.Y') ?? '—' }}</td>
                                    <td>
                                        @if($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PAID)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(5, 150, 105, 0.12); color: #059669; font-size: 11px; font-weight: 800;">
                                                ✓ Оплачен
                                            </span>
                                        @elseif($invoice->status === \App\Domain\Subscription\Enums\InvoiceStatus::PENDING)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(245, 158, 11, 0.12); color: #D97706; font-size: 11px; font-weight: 800;">
                                                Ожидает оплаты
                                            </span>
                                        @else
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: rgba(107, 114, 128, 0.12); color: #6B7280; font-size: 11px; font-weight: 800;">
                                                {{ $invoice->status->title() }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px 16px; color: #9CA3AF;">
                                        Счетов пока нет. Бесплатный ознакомительный период действует автоматически.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 7.5 Pre-payment / Checkout Modal with Promo Code Input --}}
            @if($showPaymentModal)
                @php
                    $activePlan = $this->checkoutPlan;
                    $basePrice = $this->checkoutBaseAmount;
                    $finalPrice = $this->checkoutFinalAmount;
                @endphp
                <div class="tutor-checkout-overlay">
                    <div class="tutor-checkout-card">
                        {{-- Modal Header --}}
                        <div class="tutor-checkout-header">
                            <div class="tutor-checkout-header-left">
                                <div class="tutor-checkout-icon-wrap">
                                    <svg class="tutor-checkout-icon" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div style="min-width: 0;">
                                    <h3 class="tutor-checkout-title">
                                        Подключение тарифа
                                    </h3>
                                    <p class="tutor-checkout-subtitle">
                                        Проверьте условия и примените промокод
                                    </p>
                                </div>
                            </div>
                            <button wire:click="closePaymentModal" type="button" class="tutor-checkout-close-btn" aria-label="Закрыть">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Plan Overview Box --}}
                        <div class="tutor-checkout-plan-box">
                            <div class="tutor-checkout-plan-top">
                                <div class="tutor-checkout-plan-name-wrap">
                                    <span class="tutor-checkout-plan-title">
                                        Тариф «{{ $activePlan->title() }}»
                                    </span>
                                    @if($activePlan->badge())
                                        <span class="tutor-checkout-plan-badge">
                                            {{ $activePlan->badge() }}
                                        </span>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="tutor-checkout-plan-duration">
                                        {{ $isYearly ? '1 год (12 мес.)' : '1 месяц' }}
                                    </span>
                                    @if($this->hasFirstPaymentTrialBonus)
                                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; background: #ECFDF5; color: #059669; font-size: 11px; font-weight: 800;">
                                            +{{ $activePlan->trialDays() }} дней триала
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <p class="tutor-checkout-plan-desc">
                                {{ $activePlan->subtitle() }}
                            </p>
                        </div>

                        {{-- Promo Code Input Section --}}
                        <div class="tutor-checkout-promo-section">
                            <label class="tutor-checkout-label">
                                Промокод (если есть)
                            </label>

                            @if($appliedPromoCode)
                                <div class="tutor-checkout-promo-applied">
                                    <div class="tutor-checkout-promo-applied-info">
                                        <span class="tutor-checkout-promo-badge-check">✓</span>
                                        <div class="tutor-checkout-promo-details">
                                            <div class="tutor-checkout-promo-code-text">
                                                {{ $appliedPromoCode }}
                                            </div>
                                            <div class="tutor-checkout-promo-subtext">
                                                @if($isFreeActivation)
                                                    @if($promoDiscountType === 'lifetime')
                                                        Бессрочный бесплатный доступ
                                                    @else
                                                        Бесплатный период ({{ $promoSubscriptionPeriod === '1_month' ? '1 месяц' : ($promoSubscriptionPeriod === '3_months' ? '3 месяца' : ($promoSubscriptionPeriod === '6_months' ? '6 месяцев' : '1 год')) }})
                                                    @endif
                                                @else
                                                    Скидка: {{ number_format($promoDiscountAmount, 2) }} BYN
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            wire:click="removePromoCode" 
                                            class="tutor-checkout-promo-reset-btn"
                                    >
                                        ✕ Сбросить
                                    </button>
                                </div>
                            @else
                                <div class="tutor-checkout-input-row">
                                    <div class="tutor-checkout-input-wrapper">
                                        <input type="text" 
                                               wire:model="promoCode" 
                                               wire:keydown.enter.prevent="applyPromoCode"
                                               placeholder="Введите промокод" 
                                               class="tutor-checkout-promo-input"
                                        >
                                    </div>
                                    <button type="button" 
                                            wire:click="applyPromoCode" 
                                            wire:loading.attr="disabled"
                                            class="tutor-checkout-promo-btn"
                                    >
                                        <span wire:loading.remove wire:target="applyPromoCode">Применить</span>
                                        <span wire:loading wire:target="applyPromoCode" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                                    </button>
                                </div>
                            @endif

                            @if($promoErrorMessage)
                                <div class="tutor-checkout-promo-error">
                                    <svg style="width: 14px; height: 14px; flex-shrink: 0;" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $promoErrorMessage }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Calculation Breakdown Box --}}
                        <div class="tutor-checkout-calc-box">
                            <div class="tutor-checkout-calc-row">
                                <span>Стоимость тарифа:</span>
                                <span class="tutor-checkout-calc-value">{{ number_format($basePrice, 2) }} BYN</span>
                            </div>

                            @if($appliedPromoCode)
                                <div class="tutor-checkout-calc-row tutor-checkout-calc-row--discount">
                                    <span>Скидка по промокоду:</span>
                                    <span>-{{ number_format($isFreeActivation ? $basePrice : $promoDiscountAmount, 2) }} BYN</span>
                                </div>
                            @endif

                            @if($this->hasFirstPaymentTrialBonus && ! $isFreeActivation)
                                <div class="tutor-checkout-calc-row" style="color: #059669; font-weight: 600;">
                                    <span>Бонус первой оплаты:</span>
                                    <span>+{{ $activePlan->trialDays() }} {{ trans_choice('день|дня|дней', $activePlan->trialDays()) }} триала</span>
                                </div>
                            @endif

                            <div class="tutor-checkout-calc-divider"></div>

                            <div class="tutor-checkout-calc-total-row">
                                <span class="tutor-checkout-calc-total-label">К оплате:</span>
                                <div class="tutor-checkout-calc-total-price">
                                    @if($isFreeActivation)
                                        <span class="tutor-checkout-price-free">0.00 BYN</span>
                                        <span class="tutor-checkout-badge-free">Бесплатно</span>
                                    @elseif($appliedPromoCode && $promoDiscountAmount > 0)
                                        <span class="tutor-checkout-price-old">
                                            {{ number_format($basePrice, 2) }} BYN
                                        </span>
                                        <span class="tutor-checkout-price-current">
                                            {{ number_format($finalPrice, 2) }} BYN
                                        </span>
                                    @else
                                        <span class="tutor-checkout-price-current">
                                            {{ number_format($basePrice, 2) }} BYN
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="tutor-checkout-actions">
                            @if($isFreeActivation)
                                <button type="button" 
                                        wire:click="activateFreePromo" 
                                        wire:loading.attr="disabled"
                                        class="tutor-checkout-submit-btn tutor-checkout-submit-btn--free"
                                >
                                    <span wire:loading.remove wire:target="activateFreePromo">✓ Активировать подписку бесплатно</span>
                                    <span wire:loading wire:target="activateFreePromo" class="tutor-checkout-loading-state">
                                        <span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                                        Активация доступа...
                                    </span>
                                </button>
                                <div class="tutor-checkout-note">
                                    Банковская карта не требуется · Мгновенное открытие доступа
                                </div>
                            @else
                                <button type="button" 
                                        wire:click="proceedToAcquiring" 
                                        wire:loading.attr="disabled"
                                        class="tutor-checkout-submit-btn"
                                >
                                    <span wire:loading.remove wire:target="proceedToAcquiring">
                                        Перейти к оплате · {{ number_format($finalPrice, 2) }} BYN →
                                    </span>
                                    <span wire:loading wire:target="proceedToAcquiring" class="tutor-checkout-loading-state">
                                        <span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                                        Переход в Альфа-Банк...
                                    </span>
                                </button>

                                <div class="tutor-checkout-trust-row">
                                    <span class="tutor-checkout-trust-item">
                                        <svg style="width: 13px; height: 13px;" viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 0 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                                        </svg>
                                        Альфа-Банк
                                    </span>
                                    <span class="tutor-checkout-trust-dot">•</span>
                                    <span class="tutor-checkout-trust-item">3D-Secure</span>
                                    <span class="tutor-checkout-trust-dot">•</span>
                                    <span class="tutor-checkout-trust-item">Чек на e-mail</span>
                                </div>
                            @endif

                            <button type="button" 
                                    wire:click="closePaymentModal" 
                                    class="tutor-checkout-cancel-btn"
                            >
                                Отмена
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 8. Card Binding Modal (Alfa-Bank) --}}
            @if($showCardModal)
                <div class="tutor-modal-overlay">
                    <div class="tutor-modal-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: #DC2626; color: #FFFFFF; font-weight: 900; display: flex; align-items: center; justify-content: center; font-size: 14px;">А</div>
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0C0A14;" class="dark:text-white">Привязка карты</h3>
                            </div>
                            <button wire:click="closeCardModal" type="button" style="background: transparent; border: none; cursor: pointer; color: #9CA3AF; font-size: 20px;">✕</button>
                        </div>

                        <p style="font-size: 0.875rem; color: #6B7280; line-height: 1.5; margin: 0 0 20px 0;">
                            Шлюз Альфа-Банка готов к авторизации карты для тарифа <strong>«{{ $selectedPlanCode === 'start' ? 'Старт' : 'Про' }}»</strong>.
                        </p>

                        <div style="padding: 16px; border-radius: 14px; background: #F9FAFB; border: 1px solid #E5E7EB; margin-bottom: 24px;" class="dark:bg-gray-800 dark:border-gray-700">
                            <div style="font-size: 12px; color: #6B7280;">Идентификатор заказа в банке:</div>
                            <div style="font-family: monospace; font-size: 0.875rem; font-weight: 700; color: #111827; margin-top: 4px;" class="dark:text-white">
                                {{ $alfaOrderId ?? 'ALFA-' . strtoupper(bin2hex(random_bytes(4))) }}
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button wire:click="confirmCardPayment" type="button" class="tutor-plan-btn tutor-plan-btn--primary">
                                Подтвердить привязку карты
                            </button>
                            <button wire:click="closeCardModal" type="button" class="tutor-plan-btn tutor-plan-btn--outline">
                                Отмена
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 9. Cancel Subscription Confirmation Modal --}}
            @if($showCancelModal)
                <div class="tutor-modal-overlay">
                    <div class="tutor-modal-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #991B1B;">Отмена подписки</h3>
                            <button wire:click="closeCancelModal" type="button" style="background: transparent; border: none; cursor: pointer; color: #9CA3AF; font-size: 20px;">✕</button>
                        </div>

                        <p style="font-size: 0.875rem; color: #4B5563; line-height: 1.5; margin: 0 0 24px 0;" class="dark:text-gray-300">
                            Вы действительно хотите отменить автопродление подписки? Все возможности тарифа останутся доступны до конца текущего оплаченного периода (<strong>{{ $subscription->current_period_ends_at?->format('d.m.Y') ?? 'окончания тарифа' }}</strong>).
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button wire:click="cancelSubscription" type="button" class="tutor-plan-btn" style="background: #DC2626; color: #FFFFFF;">
                                Подтвердить отмену
                            </button>
                            <button wire:click="closeCancelModal" type="button" class="tutor-plan-btn tutor-plan-btn--outline">
                                Оставить подписку
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        @endif

    </div>
</x-filament-panels::page>
