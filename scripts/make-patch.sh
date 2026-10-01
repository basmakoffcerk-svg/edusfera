#!/usr/bin/env bash
set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

OUTPUT_ZIP="${1:-edusfera-patch.zip}"
rm -f "$OUTPUT_ZIP"

echo "🔍 Поиск измененных рабочих файлов..."

FILES=()

while IFS= read -r file; do
    # Исключаем тесты, документацию, git, node_modules, storage, env, архивы, маркетинговые скриншоты
    if [[ "$file" =~ ^(tests/|docs/|scratch/|ad-assets/|public/ad-assets/|\.marketing/|\.env|\.git|\.codegraph|node_modules/|storage/) ]]; then
        continue
    fi
    if [[ "$file" =~ \.(tar\.gz|zip)$ ]]; then
        continue
    fi
    if [ -d "$file" ]; then
        while IFS= read -r subfile; do
            if [ -f "$subfile" ]; then
                FILES+=("$subfile")
            fi
        done < <(find "$file" -type f)
    elif [ -f "$file" ]; then
        FILES+=("$file")
    fi
done < <(git status --porcelain | awk '{print $2}')

# Дополнительно гарантируем ключевые файлы промокодов, миграций, ИИ и кабинетов
CRITICAL_FILES=(
    "app/Services/Ai/GeminiService.php"
    "app/Services/Classroom/AiService.php"
    "app/Http/Controllers/AiCopilotController.php"
    "resources/views/partials/ai-copilot-widget.blade.php"
    "app/Filament/Pages/TutorAiAssistantPage.php"
    "resources/views/filament/pages/tutor-ai-assistant-page.blade.php"
    "resources/js/app.jsx"
    "resources/css/app.css"
    "resources/js/components/TutorAiAssistant/TutorAiAssistant.jsx"
    "resources/js/components/TutorAiAssistant/AiFormattedOutput.jsx"
    "resources/js/components/TutorAiAssistant/LessonPlanTab.jsx"
    "resources/js/components/TutorAiAssistant/QuizGeneratorTab.jsx"
    "resources/js/components/TutorAiAssistant/MethodistChatTab.jsx"
    "resources/js/components/TutorAiAssistant/PromptLibraryModal.jsx"
    "app/Filament/Pages/TutorNpdPage.php"
    "resources/views/filament/pages/tutor-npd-page.blade.php"
    "app/Filament/Widgets/TutorFinanceOverview.php"
    "app/Filament/Widgets/CommissionLadderWidget.php"
    "app/Filament/Pages/WalletPage.php"
    "app/Filament/Resources/TransactionResource.php"
    "app/Filament/Resources/LessonResource.php"
    "app/Services/PromoCodeService.php"
    "app/Filament/Pages/TutorSubscriptionPage.php"
    "app/Filament/Resources/PromoCodeResource.php"
    "app/Filament/Resources/PromoCodeResource/Pages/CreatePromoCode.php"
    "app/Filament/Resources/PromoCodeResource/Pages/EditPromoCode.php"
    "app/Filament/Resources/PromoCodeResource/Pages/ListPromoCodes.php"
    "app/Models/PromoCode.php"
    "app/Models/PromoCodeUsage.php"
    "app/Domain/Subscription/Models/SubscriptionInvoice.php"
    "app/Domain/Subscription/Models/Subscription.php"
    "app/Domain/Subscription/Enums/SubscriptionPlan.php"
    "app/Domain/Subscription/Services/SubscriptionService.php"
    "app/Providers/AppServiceProvider.php"
    "database/migrations/2026_09_13_142311_add_is_onboarded_to_subscriptions_table.php"
    "database/migrations/2026_09_13_193000_add_npd_fields_to_lessons_table.php"
    "database/migrations/2026_09_18_170519_create_promo_codes_and_usages_tables.php"
    "database/migrations/2026_09_18_210500_add_subscription_grant_fields_to_promo_codes_table.php"
    "resources/views/filament/pages/tutor-subscription-page.blade.php"
    "public/migrate.php"
    "public/manifest.json"
    "public/manifest.webmanifest"
    "public/sw.js"
    "public/offline.html"
    "public/apple-touch-icon.png"
    "public/icons/apple-touch-icon.png"
    "public/icons/icon-192x192.png"
    "public/icons/icon-512x512.png"
    "public/icons/icon-maskable-192x192.png"
    "public/icons/icon-maskable-512x512.png"
    "resources/views/partials/pwa-meta.blade.php"
    "resources/views/partials/pwa-prompt.blade.php"
    "app/Providers/Filament/AdminPanelProvider.php"
    "app/Providers/Filament/SiteAdminPanelProvider.php"
    "app/Http/Controllers/PwaController.php"
    "app/Models/PushSubscription.php"
    "database/migrations/2026_09_19_180000_create_push_subscriptions_table.php"
    "nginx/nginx.conf"
    "nginx/nginx-bare-metal.conf"
    "config/classroom.php"
    "app/Services/Classroom/LiveKitService.php"
    "app/Console/Commands/PrepareClassroomTestCommand.php"
    "lang/ru/validation.php"
    "lang/en/validation.php"
    "resources/views/classroom/maintenance.blade.php"
    "resources/views/filament/resources/lesson-resource/pages/list-lessons.blade.php"
    "app/Models/PaymentWebhookLog.php"
    "database/migrations/2026_09_19_213000_rename_webpay_webhook_logs_table.php"
    "app/Http/Controllers/ClassroomController.php"
    "app/Filament/Pages/MicroservicesDashboard.php"
    "resources/views/filament/pages/microservices-dashboard.blade.php"
    "resources/js/classroom.js"
    "resources/views/classroom/show.blade.php"
    "resources/css/classroom.css"
    "resources/js/excalidraw-wrapper.jsx"
    "routes/web.php"
    "resources/views/filament/widgets/partials/student-dashboard-styles.blade.php"
    "app/Filament/Pages/Dashboard.php"
    "app/Filament/Widgets/StudentWelcomeWidget.php"
    "app/Filament/Widgets/StudentUpcomingLessonsWidget.php"
    "app/Filament/Widgets/StudentTutorsWidget.php"
    "resources/views/filament/widgets/student-welcome-widget.blade.php"
    "resources/views/filament/widgets/student-upcoming-lessons-widget.blade.php"
    "app/Models/Lesson.php",
    "app/Http/Controllers/LessonBookingController.php",
    "app/Services/BookingService.php",
    "app/Notifications/LessonBookedTutorNotification.php",
    "app/Notifications/LessonBookedStudentNotification.php",
    "resources/views/catalog/show.blade.php",
    "app/Filament/Resources/LessonRequestResource.php",
    "public/migrate.php"
)

for cf in "${CRITICAL_FILES[@]}"; do
    if [ -f "$cf" ]; then
        FILES+=("$cf")
    fi
done

# Добавляем скомпилированные ассеты из public/build, если они есть
if [ -d "public/build" ]; then
    while IFS= read -r bfile; do
        if [ -f "$bfile" ]; then
            FILES+=("$bfile")
        fi
    done < <(find public/build -type f)
fi

# Удаляем дубликаты
UNIQUE_FILES=($(printf "%s\n" "${FILES[@]}" | sort -u))

echo "📦 Упаковка ${#UNIQUE_FILES[@]} файлов в легковесный патч $OUTPUT_ZIP..."
zip -q -9 "$OUTPUT_ZIP" "${UNIQUE_FILES[@]}"

TAR_GZ="${OUTPUT_ZIP%.*}.tar.gz"
tar -czf "$TAR_GZ" "${UNIQUE_FILES[@]}"

# Гарантируем копирование в папку public/ для прямого доступа через migrate.php
cp -f "$OUTPUT_ZIP" "public/$OUTPUT_ZIP"
cp -f "$TAR_GZ" "public/$TAR_GZ"

echo "✅ Легковесный архив обновлений готов (в корне и в public/):"
ls -lh "$OUTPUT_ZIP" "public/$OUTPUT_ZIP" "$TAR_GZ" "public/$TAR_GZ"
