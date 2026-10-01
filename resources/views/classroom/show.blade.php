<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <title>Виртуальный класс — {{ $lesson->subject ?? 'Онлайн-урок' }}</title>
    <meta name="description" content="Виртуальный класс Edusfera в стиле Excalidraw — интерактивная доска, видеосвязь, чат и задания">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/classroom.css', 'resources/js/classroom.js', 'resources/js/excalidraw-wrapper.jsx'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="cr-body" :class="{ 'theme-light': isLightTheme }">

<div x-data="classroom()"
     x-init="init()"
     class="cr-viewport"
     id="classroom-root"
     @mousemove.window="onVideoDragMove($event); onVideoResizeMove($event)"
     @mouseup.window="stopVideoDrag(); stopVideoResize()"
     @touchmove.window="onVideoTouchDragMove($event); onVideoTouchResizeMove($event)"
     @touchend.window="stopVideoDrag(); stopVideoResize()"
     @keydown.escape.window="if (isVideoFullscreen) toggleFullscreenVideo()">

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 1. FULLSCREEN EXCALIDRAW VECTOR CANVAS VIEWPORT    -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div id="excalidraw-mount-container" class="cr-excalidraw-surface"></div>
    <template x-if="!usingOfficialExcalidraw">
        <canvas id="cr-whiteboard-canvas" class="cr-canvas-surface"></canvas>
    </template>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 2. TOP HEADER ISLAND: LESSON INFO & MOBILE ACTIONS -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="cr-island cr-island-slides">
        <a href="{{ url('/admin') }}" class="cr-btn-icon" title="В панель управления">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>

        <div class="cr-slide-info">
            <span class="cr-slide-title">{{ $lesson->subject ?? 'Онлайн-урок' }}</span>
            <span class="cr-slide-sub">{{ $userRole === 'tutor' ? ($lesson->student->name ?? 'Ученик') : ($lesson->tutor->name ?? 'Преподаватель') }}</span>
        </div>

        <!-- Multi-page Slide Switcher (Only for legacy canvas engine) -->
        <template x-if="!usingOfficialExcalidraw">
            <div class="cr-slide-stepper">
                <button type="button" class="cr-btn-icon cr-btn-icon--sm" @click="wbPrevSlide()" :disabled="currentPageIndex <= 0" title="Предыдущий слайд">
                    ◀
                </button>
                <span class="cr-slide-counter" x-text="'Слайд ' + (currentPageIndex + 1) + ' / ' + totalPages"></span>
                <button type="button" class="cr-btn-icon cr-btn-icon--sm" @click="wbNextSlide()" :disabled="currentPageIndex >= totalPages - 1" title="Следующий слайд">
                    ▶
                </button>
                @if($userRole === 'tutor')
                <button type="button" class="cr-btn-icon cr-btn-icon--sm cr-btn-add-slide" @click="wbAddSlide()" title="Добавить новый слайд урока">
                    +
                </button>
                @endif
            </div>
        </template>

        <!-- Teacher Board Lock / Read-Only Mode -->
        @if($userRole === 'tutor')
            <button type="button"
                    class="cr-lock-toggle"
                    :class="isBoardLocked ? 'cr-lock-toggle--locked' : 'cr-lock-toggle--open'"
                    @click="toggleBoardLock()"
                    :title="isBoardLocked ? 'Разблокировать доску для ученика' : 'Заблокировать доску (режим только чтение)'">
                <span x-text="isBoardLocked ? '🔒 Заблокировано' : '🔓 Доступ открыт'"></span>
            </button>
        @else
            <div x-show="isBoardLocked" x-cloak class="cr-lock-badge">
                <span>🔒 Только чтение</span>
            </div>
        @endif

        <!-- Mobile Header Quick Action Capsule -->
        <div class="cr-mobile-header-actions">
            <!-- Toggle Fullscreen Video Call -->
            <button type="button"
                    class="cr-btn-icon cr-btn-icon--sm"
                    :class="{ 'cr-btn-icon--active': isVideoFullscreen }"
                    @click="toggleFullscreenVideo()"
                    title="Видеозвонок на весь экран">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 15px; height: 15px;">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                </svg>
            </button>

            <!-- Toggle Video Visibility -->
            <button type="button"
                    class="cr-btn-icon cr-btn-icon--sm"
                    :class="{ 'cr-btn-icon--active': !isVideoHidden }"
                    @click="isVideoHidden = !isVideoHidden"
                    :title="isVideoHidden ? 'Показать видео' : 'Скрыть видео (доска на весь экран)'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 15px; height: 15px;">
                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                </svg>
            </button>

            <!-- Theme Toggle -->
            <button type="button" class="cr-btn-icon cr-btn-icon--sm" @click="toggleTheme()" title="Тема">
                <span x-text="isLightTheme ? '🌙' : '☀️'" style="font-size: 13px;"></span>
            </button>

            <!-- Chat Drawer Toggle -->
            <button type="button"
                    class="cr-btn-icon cr-btn-icon--sm cr-relative"
                    :class="{ 'cr-btn-icon--active': isSidebarOpen && activeSidebarTab === 'chat' }"
                    @click="toggleSidebar('chat')"
                    title="Чат урока">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 15px; height: 15px;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span x-show="unreadChatCount > 0" x-cloak class="cr-unread-dot"></span>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 3. TOP-CENTER FALLBACK TOOL ISLAND (LEGACY ONLY)   -->
    <!-- ═══════════════════════════════════════════════════ -->
    <template x-if="!usingOfficialExcalidraw">
    <div class="cr-island cr-island-tools">
        <!-- 1. Selection Tool -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'select' }" @click="setWbTool('select')" title="1: Выделение">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3l7 18 3-7 7-3L3 3z"></path>
            </svg>
            <span class="cr-tool-key">1</span>
        </button>

        <!-- 2. Hand / Pan Tool -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'pan' }" @click="setWbTool('pan')" title="2: Рука / Панорамирование (или зажмите Пробел)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 11V6a2 2 0 0 0-4 0v5M14 10V4a2 2 0 0 0-4 0v7M10 10.5V6a2 2 0 0 0-4 0v8a7 7 0 0 0 14 0v-5a2 2 0 0 0-4 0v2"></path>
            </svg>
            <span class="cr-tool-key">2</span>
        </button>

        <!-- 3. Freehand Pen -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'pen' }" @click="setWbTool('pen')" title="3: Карандаш">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
            </svg>
            <span class="cr-tool-key">3</span>
        </button>

        <!-- 4. Highlighter -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'highlighter' }" @click="setWbTool('highlighter')" title="4: Маркер">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 11-6 6v3h3l6-6"></path>
                <path d="m22 2-7 7-4-4 7-7z"></path>
            </svg>
            <span class="cr-tool-key">4</span>
        </button>

        <!-- 5. Rectangle -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'rect' }" @click="setWbTool('rect')" title="5: Прямоугольник">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
            </svg>
            <span class="cr-tool-key">5</span>
        </button>

        <!-- 6. Circle / Ellipse -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'circle' }" @click="setWbTool('circle')" title="6: Круг / Эллипс">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"></circle>
            </svg>
            <span class="cr-tool-key">6</span>
        </button>

        <!-- 7. Arrow -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'arrow' }" @click="setWbTool('arrow')" title="7: Стрелка">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="19" x2="19" y2="5"></line>
                <polyline points="10 5 19 5 19 14"></polyline>
            </svg>
            <span class="cr-tool-key">7</span>
        </button>

        <!-- 8. Line -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'line' }" @click="setWbTool('line')" title="8: Линия">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="19" x2="19" y2="5"></line>
            </svg>
            <span class="cr-tool-key">8</span>
        </button>

        <!-- 9. Text -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'text' }" @click="setWbTool('text')" title="9: Текст">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="4 7 4 4 20 4 20 7"></polyline>
                <line x1="9" y1="20" x2="15" y2="20"></line>
                <line x1="12" y1="4" x2="12" y2="20"></line>
            </svg>
            <span class="cr-tool-key">9</span>
        </button>

        <!-- 0. Eraser -->
        <button class="cr-tool-btn" :class="{ 'active': wbTool === 'eraser' }" @click="setWbTool('eraser')" title="0: Ластик">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 20H7L3 16c-.8-.8-.8-2 0-2.8L14.6 1.6c.8-.8 2-.8 2.8 0L21 5.2c.8.8.8 2 0 2.8L11 18"></path>
            </svg>
            <span class="cr-tool-key">0</span>
        </button>

        <!-- Educational Stamps / Tasks on Canvas -->
        <div class="cr-relative">
            <button class="cr-tool-btn" :class="{ 'active': wbTool === 'stamp' }" @click="wbTool = 'stamp'; showStampMenu = !showStampMenu" title="Задания и штампы проверки">
                <span style="font-size: 14px;">📝</span>
            </button>
            <div x-show="showStampMenu" @click.away="showStampMenu = false" x-cloak class="cr-popover-menu cr-stamp-popover">
                <div class="cr-popover-item" @click="setWbStamp('task'); showStampMenu = false;">
                    <span>📝</span> <b>Задание</b> — добавить блок задачи
                </div>
                <div class="cr-popover-item" @click="setWbStamp('solution'); showStampMenu = false;">
                    <span>💡</span> <b>Решение</b> — блок ответа
                </div>
                <div class="cr-popover-item" @click="setWbStamp('correct'); showStampMenu = false;">
                    <span>🟢</span> <b>Верно!</b> — штамп проверки
                </div>
                <div class="cr-popover-item" @click="setWbStamp('incorrect'); showStampMenu = false;">
                    <span>🔴</span> <b>Ошибка</b> — штамп исправления
                </div>
            </div>
        </div>

        <div class="cr-island-divider"></div>

        <!-- History Actions: Undo / Redo / Clear / Export -->
        <button class="cr-tool-btn" @click="wbUndo()" title="Отменить (Ctrl+Z)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
        </button>
        <button class="cr-tool-btn" @click="wbRedo()" title="Повторить (Ctrl+Y)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.13-9.36L23 10"></path>
            </svg>
        </button>
        <button class="cr-tool-btn" @click="wbClear()" title="Очистить текущий слайд">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
        </button>

        <div class="cr-relative">
            <button class="cr-tool-btn" @click="showExportMenu = !showExportMenu" title="Экспорт доски">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </button>
            <div x-show="showExportMenu" @click.away="showExportMenu = false" x-cloak class="cr-popover-menu cr-export-popover">
                <div class="cr-popover-item" @click="wbExportPng(); showExportMenu = false;">
                    <span>🖼️</span> <b>Скачать PNG</b> (высокое разрешение)
                </div>
                <div class="cr-popover-item" @click="wbExportJson(); showExportMenu = false;">
                    <span>📦</span> <b>Экспорт JSON</b> (сохранить проект)
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 4. FLOATING PROPERTIES DRAWER (LEGACY ONLY)         -->
    <!-- ═══════════════════════════════════════════════════ -->
    <template x-if="!usingOfficialExcalidraw">
    <div class="cr-island cr-island-properties"
         x-show="['pen', 'highlighter', 'rect', 'circle', 'line', 'arrow', 'text'].includes(wbTool)"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-x-2"
         x-transition:enter-end="opacity-100 translate-x-0">

        <span class="cr-prop-label">Цвет</span>
        <div class="cr-color-swatches">
            <template x-for="c in excalidrawColors" :key="c">
                <button type="button"
                        class="cr-color-swatch"
                        :class="{ 'active': wbColor === c }"
                        :style="'background-color:' + c"
                        @click="setWbColor(c)"
                        :title="c"></button>
            </template>
        </div>

        <div class="cr-island-divider--horiz"></div>

        <span class="cr-prop-label">Толщина</span>
        <div class="cr-width-swatches">
            <button type="button" class="cr-width-btn" :class="{ 'active': wbLineWidth === 2 }" @click="setWbWidth(2)" title="Тонкая">
                <span class="cr-width-line" style="height: 2px;"></span>
            </button>
            <button type="button" class="cr-width-btn" :class="{ 'active': wbLineWidth === 4 }" @click="setWbWidth(4)" title="Средняя">
                <span class="cr-width-line" style="height: 4px;"></span>
            </button>
            <button type="button" class="cr-width-btn" :class="{ 'active': wbLineWidth === 8 }" @click="setWbWidth(8)" title="Толстая">
                <span class="cr-width-line" style="height: 7px;"></span>
            </button>
        </div>

        <template x-if="['rect', 'circle', 'line', 'arrow'].includes(wbTool)">
            <div>
                <div class="cr-island-divider--horiz"></div>
                <span class="cr-prop-label">Стиль линии</span>
                <div class="cr-style-swatches">
                    <button type="button" class="cr-style-btn" :class="{ 'active': wbStrokeStyle === 'solid' }" @click="setWbStrokeStyle('solid')" title="Сплошная">
                        <span>—</span>
                    </button>
                    <button type="button" class="cr-style-btn" :class="{ 'active': wbStrokeStyle === 'dashed' }" @click="setWbStrokeStyle('dashed')" title="Пунктир">
                        <span>- -</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
    </template>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 5. FLOATING & SCALABLE VIDEO ISLAND (PiP & FULLSCREEN CALL) -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="cr-island cr-island-video"
         :class="{
             'cr-island-video--minimized': isVideoMinimized && !isVideoFullscreen,
             'cr-island-video--dragging': isDraggingVideo,
             'cr-island-video--resizing': isResizingVideo,
             'cr-island-video--compact': videoScale === 'compact' && !isVideoFullscreen,
             'cr-island-video--large': videoScale === 'large' && !isVideoFullscreen,
             'cr-island-video--hidden': isVideoHidden && !isVideoFullscreen,
             'cr-island-video--fullscreen': isVideoFullscreen,
         }"
         :style="videoIslandStyle"
         x-show="!isVideoHidden || isVideoFullscreen"
         @touchend="handleVideoTap($event)"
         @dblclick="toggleFullscreenVideo()"
         id="cr-video-island">

        <!-- 1. Normal Floating Island Header (Hidden in Fullscreen) -->
        <div class="cr-video-island-header"
             x-show="!isVideoFullscreen"
             @mousedown="startVideoDrag($event)"
             @touchstart.passive="startVideoTouchDrag($event)">

            <div class="cr-video-drag-grip" title="Зажмите и перемещайте видео">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                    <circle cx="4" cy="4" r="1.5"/>
                    <circle cx="12" cy="4" r="1.5"/>
                    <circle cx="4" cy="8" r="1.5"/>
                    <circle cx="12" cy="8" r="1.5"/>
                    <circle cx="4" cy="12" r="1.5"/>
                    <circle cx="12" cy="12" r="1.5"/>
                </svg>
            </div>

            <div class="cr-video-island-status" style="pointer-events: none; user-select: none;">
                <span class="cr-live-dot" :class="remoteConnected ? 'cr-live-dot--active' : 'cr-live-dot--waiting'"></span>
                <span class="cr-video-status-text" x-text="remoteConnected ? (isMobileScreen ? 'В эфире (2)' : 'В эфире (2/2)') : (isLiveKit ? 'LiveKit SFU' : 'Ожидание...')"></span>
            </div>

            <div class="cr-video-island-actions" @mousedown.stop @touchstart.stop>
                <!-- Scale presets cycle: S (Компактный) -> M (Обычный) -> L (Большой) -->
                <button type="button"
                        class="cr-btn-icon cr-btn-icon--xs cr-scale-badge"
                        @click="cycleVideoScale()"
                        :title="'Масштаб видео: ' + (videoScale === 'compact' ? 'Компактный (S)' : (videoScale === 'large' ? 'Большой (L)' : 'Стандартный (M)'))">
                    <span x-text="videoScale === 'compact' ? 'S' : (videoScale === 'large' ? 'L' : 'M')"></span>
                </button>

                <!-- Fullscreen Video toggle (⛶) -->
                <button type="button"
                        class="cr-btn-icon cr-btn-icon--xs"
                        @click="toggleFullscreenVideo()"
                        title="Развернуть видеозвонок на весь экран">
                    <span style="font-size: 13px;">⛶</span>
                </button>

                <!-- Minimize / Expand toggle -->
                <button type="button"
                        class="cr-btn-icon cr-btn-icon--xs"
                        @click="isVideoMinimized = !isVideoMinimized"
                        :title="isVideoMinimized ? 'Развернуть видео' : 'Свернуть видео в плашку'">
                    <span x-text="isVideoMinimized ? '▢' : '—'"></span>
                </button>
            </div>
        </div>

        <!-- 2. Fullscreen Call Overlay Header (Shown ONLY when fullscreen) -->
        <div class="cr-fs-call-header" x-show="isVideoFullscreen" x-cloak>
            <button type="button" class="cr-fs-back-btn" @click.stop="toggleFullscreenVideo()" title="Вернуться к интерактивной доске">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>К доске</span>
            </button>

            <div class="cr-fs-call-title-box">
                <div class="cr-fs-call-title">{{ $lesson->subject ?? 'Онлайн-урок' }}</div>
                <div class="cr-fs-call-status">
                    <span class="cr-live-dot" :class="remoteConnected ? 'cr-live-dot--active' : 'cr-live-dot--waiting'"></span>
                    <span x-text="remoteConnected ? 'В эфире • ' + timerDisplay : 'Ожидание подключения...'"></span>
                </div>
            </div>

            <div class="cr-fs-header-actions">
                <button type="button" class="cr-fs-circle-btn" @click.stop="flipCamera()" title="Перевернуть камеру (фронт / назад)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 10c0-4.418-3.582-8-8-8s-8 3.582-8 8c0 2.21.896 4.21 2.343 5.657L4 18h6v-6l-2.343 2.343A5.98 5.98 0 0 1 6 10c0-3.314 2.686-6 6-6s6 2.686 6 6c0 1.657-.672 3.157-1.757 4.243l1.414 1.414C19.043 14.27 20 12.247 20 10z"></path>
                    </svg>
                </button>
                <button type="button" class="cr-fs-circle-btn" @click.stop="toggleFullscreenVideo()" title="Свернуть к доске">
                    <span>⤓</span>
                </button>
            </div>
        </div>

        <!-- 3. Minimized Floating Pill View -->
        <div x-show="isVideoMinimized && !isVideoFullscreen" class="cr-video-minimized-pill" @click="isVideoMinimized = false" title="Нажмите, чтобы развернуть видео">
            <span class="cr-live-dot" :class="remoteConnected ? 'cr-live-dot--active' : 'cr-live-dot--waiting'"></span>
            <span class="cr-mini-pill-status" x-text="remoteConnected ? '2/2' : '1/2'"></span>
            <div class="cr-mini-avatars">
                <span class="cr-mini-avatar" :class="{ 'speaking': tutorSpeaking }">
                    {{ mb_strtoupper(mb_substr($lesson->tutor->name ?? 'П', 0, 1)) }}
                </span>
                <span class="cr-mini-avatar" :class="{ 'speaking': studentSpeaking }">
                    {{ mb_strtoupper(mb_substr($lesson->student->name ?? 'У', 0, 1)) }}
                </span>
            </div>
            <span class="cr-mini-expand-ico">⤢</span>
        </div>

        <!-- 4. Video Tiles -->
        <div x-show="!isVideoMinimized || isVideoFullscreen" class="cr-video-tiles">

            <!-- ── Tutor Video Tile ── -->
            <div class="cr-video-tile" :class="{ 'cr-speaking': tutorSpeaking }">
                @if($userRole === 'tutor')
                    <!-- Local Tutor Camera -->
                    <video id="cr-tutor-local-video" autoplay muted playsinline webkit-playsinline x-show="isCameraOn" class="cr-video-feed cr-video-mirror"></video>
                    <div x-show="!isCameraOn" class="cr-video-placeholder cr-video-placeholder--actionable" @click="enableCamera()" title="Нажмите, чтобы включить камеру">
                        <div class="cr-video-avatar">
                            <span>{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'П', 0, 1)) }}</span>
                        </div>
                        <button type="button" class="cr-cam-start-chip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                            <span>Включить камеру</span>
                        </button>
                    </div>
                    <div class="cr-video-overlay-info">
                        <span class="cr-video-label">Вы (Преподаватель)</span>
                        <span x-show="!isMicOn" class="cr-video-muted-ico" title="Микрофон выключен">🔇</span>
                    </div>
                @else
                    <!-- Remote Tutor Camera -->
                    <video id="cr-tutor-remote-video" autoplay muted playsinline webkit-playsinline x-show="remoteConnected" class="cr-video-feed"></video>
                    <div x-show="!remoteConnected || !remoteVideoOn" class="cr-video-placeholder">
                        <div class="cr-video-avatar">
                            <span>{{ mb_strtoupper(mb_substr($lesson->tutor->name ?? 'П', 0, 1)) }}</span>
                        </div>
                        <span class="cr-video-waiting-label" x-text="remoteConnected ? 'Камера выключена' : 'Подключение...'"></span>
                    </div>
                    <div class="cr-video-overlay-info">
                        <span class="cr-video-label">{{ $lesson->tutor->name ?? 'Преподаватель' }}</span>
                        <span x-show="remoteConnected && !remoteAudioOn" class="cr-video-muted-ico" title="Микрофон выключен">🔇</span>
                    </div>
                @endif
            </div>

            <!-- ── Student Video Tile ── -->
            <div class="cr-video-tile" :class="{ 'cr-speaking': studentSpeaking }">
                @if($userRole !== 'tutor')
                    <!-- Local Student Camera -->
                    <video id="cr-student-local-video" autoplay muted playsinline webkit-playsinline x-show="isCameraOn" class="cr-video-feed cr-video-mirror"></video>
                    <div x-show="!isCameraOn" class="cr-video-placeholder cr-video-placeholder--actionable" @click="enableCamera()" title="Нажмите, чтобы включить камеру">
                        <div class="cr-video-avatar">
                            <span>{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'У', 0, 1)) }}</span>
                        </div>
                        <button type="button" class="cr-cam-start-chip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                            <span>Включить камеру</span>
                        </button>
                    </div>
                    <div class="cr-video-overlay-info">
                        <span class="cr-video-label">Вы (Ученик)</span>
                        <span x-show="!isMicOn" class="cr-video-muted-ico" title="Микрофон выключен">🔇</span>
                    </div>
                @else
                    <!-- Remote Student Camera -->
                    <video id="cr-student-remote-video" autoplay muted playsinline webkit-playsinline x-show="remoteConnected" class="cr-video-feed"></video>
                    <div x-show="!remoteConnected || !remoteVideoOn" class="cr-video-placeholder">
                        <div class="cr-video-avatar">
                            <span>{{ mb_strtoupper(mb_substr($lesson->student->name ?? 'У', 0, 1)) }}</span>
                        </div>
                        <span class="cr-video-waiting-label" x-text="remoteConnected ? 'Камера выключена' : 'Ожидание ученика'"></span>
                        <button type="button" @click="copyStudentLink()" x-show="!remoteConnected" class="cr-quick-invite-btn">
                            📎 Ссылка
                        </button>
                    </div>
                    <div class="cr-video-overlay-info">
                        <span class="cr-video-label">{{ $lesson->student->name ?? 'Ученик' }}</span>
                        <span x-show="remoteConnected && !remoteAudioOn" class="cr-video-muted-ico" title="Микрофон выключен">🔇</span>
                    </div>
                @endif
            </div>

        </div>

        <!-- 5. Corner Resize Handle (Smooth drag scaling) -->
        <div x-show="!isVideoMinimized && !isVideoFullscreen"
             class="cr-video-resize-handle"
             @mousedown.stop.prevent="startVideoResize($event)"
             @touchstart.stop.prevent="startVideoTouchResize($event)"
             title="Потяните для изменения размера видео">
            <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
                <path d="M9 1L1 9M9 5L5 9M9 9L9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- 6. Fullscreen Call Bottom Dock (Like VK / iOS FaceTime Reference) -->
        <div class="cr-fs-call-dock" x-show="isVideoFullscreen" x-cloak>
            <!-- Flip Camera (Front / Back) -->
            <button type="button" class="cr-fs-dock-btn" @click.stop="flipCamera()" title="Перевернуть камеру (фронт / назад)">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
            </button>

            <!-- Mic Toggle -->
            <button type="button" class="cr-fs-dock-btn" :class="isMicOn ? 'cr-fs-dock-btn--on' : 'cr-fs-dock-btn--danger'" @click.stop="toggleMic()" :title="isMicOn ? 'Выключить микрофон' : 'Включить микрофон'">
                <template x-if="isMicOn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </template>
                <template x-if="!isMicOn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path>
                        <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </template>
            </button>

            <!-- Return to Whiteboard Button -->
            <button type="button" class="cr-fs-dock-btn cr-fs-dock-btn--board" @click.stop="toggleFullscreenVideo()" title="Вернуться к интерактивной доске">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.375-9.375z"></path>
                </svg>
            </button>

            <!-- Camera Toggle -->
            <button type="button" class="cr-fs-dock-btn" :class="isCameraOn ? 'cr-fs-dock-btn--on' : 'cr-fs-dock-btn--off'" @click.stop="toggleCamera()" :title="isCameraOn ? 'Выключить камеру' : 'Включить камеру'">
                <template x-if="isCameraOn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                    </svg>
                </template>
                <template x-if="!isCameraOn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11"></path>
                        <line x1="2" y1="2" x2="22" y2="22"></line>
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    </svg>
                </template>
            </button>

            <!-- Chat Drawer Toggle -->
            <button type="button" class="cr-fs-dock-btn cr-relative" @click.stop="toggleSidebar('chat')" title="Открыть чат урока">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span class="cr-unread-badge" x-show="unreadChatCount > 0" x-text="unreadChatCount"></span>
            </button>
        </div>

        <!-- Dedicated Offscreen Remote Audio -->
        <audio id="cr-remote-audio" autoplay playsinline style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none;"></audio>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 6. SCREEN SHARE OVERLAY (IF ACTIVE)                -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="activeScreenShare" x-cloak class="cr-screenshare-box">
        <div class="cr-screenshare-top">
            <span class="cr-screenshare-badge">
                <span class="cr-live-dot cr-live-dot--active"></span>
                <span>Трансляция экрана: <b x-text="activeScreenShare?.peerName || 'Экран'"></b></span>
            </span>
            <template x-if="activeScreenShare?.isLocal">
                <button type="button" @click="toggleScreenShare()" class="cr-screenshare-stop-btn">
                    Остановить трансляцию
                </button>
            </template>
        </div>
        <video id="cr-screenshare-video" autoplay playsinline class="cr-screenshare-video"></video>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 7. FLOATING BOTTOM ACTION DOCK                     -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="cr-island cr-island-dock">
        <!-- 1. Mic (Primary - 1 tap) -->
        <button class="cr-dock-btn" :class="{ 'cr-dock-btn--off': !isMicOn, 'cr-dock-btn--live': isMicOn }" @click="toggleMic()" :title="isMicOn ? 'Выключить микрофон' : 'Включить микрофон'">
            <span class="cr-dock-icon">
                <template x-if="isMicOn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </template>
                <template x-if="!isMicOn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path>
                        <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </template>
            </span>
            <span class="cr-dock-label" x-text="isMicOn ? 'Микрофон' : 'Вкл. микр.'"></span>
        </button>

        <!-- 2. Camera (Primary - 1 tap) -->
        <button class="cr-dock-btn" :class="{ 'cr-dock-btn--off': !isCameraOn, 'cr-dock-btn--live': isCameraOn }" @click="toggleCamera()" :title="isCameraOn ? 'Выключить камеру' : 'Включить камеру'">
            <span class="cr-dock-icon">
                <template x-if="isCameraOn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                    </svg>
                </template>
                <template x-if="!isCameraOn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11"></path>
                        <line x1="2" y1="2" x2="22" y2="22"></line>
                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    </svg>
                </template>
            </span>
            <span class="cr-dock-label" x-text="isCameraOn ? 'Камера' : 'Вкл. камеру'"></span>
        </button>

        <!-- 3. Screen Share (Desktop only) -->
        <button class="cr-dock-btn cr-dock-btn--desktop-only" :class="{ 'cr-dock-btn--active': isScreenSharing }" @click="toggleScreenShare()" :title="isScreenSharing ? 'Остановить трансляцию' : 'Трансляция экрана'">
            <span class="cr-dock-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </span>
            <span class="cr-dock-label">Экран</span>
        </button>

        <!-- 4. Video Island Hide / Show Toggle (Desktop only) -->
        <button class="cr-dock-btn cr-dock-btn--desktop-only"
                :class="{ 'cr-dock-btn--active': !isVideoHidden, 'cr-dock-btn--off': isVideoHidden }"
                @click="isVideoHidden = !isVideoHidden"
                :title="isVideoHidden ? 'Показать видео' : 'Скрыть видео (доска на весь экран)'">
            <span class="cr-dock-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"></polygon>
                </svg>
            </span>
            <span class="cr-dock-label" x-text="isVideoHidden ? 'Показ. видео' : 'Окно видео'"></span>
        </button>

        <!-- 5. Fullscreen Video Call Toggle (Primary - 1 tap) -->
        <button class="cr-dock-btn cr-dock-btn--call"
                :class="{ 'cr-dock-btn--active': isVideoFullscreen }"
                @click="toggleFullscreenVideo()"
                :title="isVideoFullscreen ? 'Вернуться к доске' : 'Развернуть видеозвонок'">
            <span class="cr-dock-icon">
                <template x-if="isVideoFullscreen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18.375 2.625a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.375-9.375z"></path>
                    </svg>
                </template>
                <template x-if="!isVideoFullscreen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                    </svg>
                </template>
            </span>
            <span class="cr-dock-label" x-text="isVideoFullscreen ? 'К доске' : 'Звонок ⛶'"></span>
        </button>

        <!-- 6. Hand Raise (Student) or Lock Board (Tutor) (Desktop only) -->
        @if($userRole !== 'tutor')
            <button class="cr-dock-btn cr-dock-btn--hand cr-dock-btn--desktop-only" @click="raiseHand()" title="Поднять руку (преподаватель получит звуковой сигнал)">
                <span class="cr-dock-icon" style="font-size: 18px;">✋</span>
                <span class="cr-dock-label">Поднять руку</span>
            </button>
        @else
            <button class="cr-dock-btn cr-dock-btn--desktop-only" :class="{ 'cr-dock-btn--active': isBoardLocked }" @click="toggleBoardLock()" :title="isBoardLocked ? 'Разблокировать доску для ученика' : 'Заблокировать доску для ученика'">
                <span class="cr-dock-icon" x-text="isBoardLocked ? '🔒' : '🔓'"></span>
                <span class="cr-dock-label" x-text="isBoardLocked ? 'Заблокировано' : 'Блок. доску'"></span>
            </button>
        @endif

        <div class="cr-island-divider cr-dock-btn--desktop-only"></div>

        <!-- 7. Task & Session Timer (Desktop only) -->
        <div class="cr-relative cr-dock-btn--desktop-only">
            <button class="cr-dock-btn cr-dock-btn--timer" @click="taskTimerMenuOpen = !taskTimerMenuOpen" title="Таймер урока / задания">
                <span class="cr-dock-icon">⏱️</span>
                <span class="cr-dock-label" x-text="taskTimerRunning ? taskTimerDisplay : timerDisplay"></span>
            </button>
            <div x-show="taskTimerMenuOpen" @click.away="taskTimerMenuOpen = false" x-cloak class="cr-popover-menu cr-timer-popover">
                <span class="cr-popover-header">⏱️ Таймер выполнения задания</span>
                <div class="cr-timer-chips">
                    <button type="button" class="cr-timer-chip" @click="startTaskTimer(3)">3 мин</button>
                    <button type="button" class="cr-timer-chip" @click="startTaskTimer(5)">5 мин</button>
                    <button type="button" class="cr-timer-chip" @click="startTaskTimer(10)">10 мин</button>
                    <button type="button" class="cr-timer-chip" @click="startTaskTimer(15)">15 мин</button>
                </div>
                <template x-if="taskTimerRunning">
                    <button type="button" class="cr-timer-stop-btn" @click="stopTaskTimer()">Сбросить таймер задания</button>
                </template>
            </div>
        </div>

        <!-- 8. Chat / Drawer Toggle (Primary - 1 tap) -->
        <button class="cr-dock-btn" :class="{ 'cr-dock-btn--active': isSidebarOpen && activeSidebarTab === 'chat' }" @click="toggleSidebar('chat')" title="Чат и материалы урока">
            <span class="cr-dock-icon cr-relative">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span x-show="unreadChatCount > 0" x-text="unreadChatCount" class="cr-unread-badge" x-cloak></span>
            </span>
            <span class="cr-dock-label">Чат</span>
        </button>

        <!-- 9. AI Assistant (Desktop only) -->
        <button class="cr-dock-btn cr-dock-btn--desktop-only" :class="{ 'cr-dock-btn--active': isSidebarOpen && activeSidebarTab === 'ai' }" @click="toggleSidebar('ai')" title="ИИ-помощник урока">
            <span class="cr-dock-icon">🤖</span>
            <span class="cr-dock-label">ИИ</span>
        </button>

        <!-- 10. Theme Toggle (Desktop only) -->
        <button class="cr-dock-btn cr-dock-btn--desktop-only" @click="toggleTheme()" title="Переключить тему (светлая/тёмная)">
            <span class="cr-dock-icon" x-text="isLightTheme ? '🌙' : '☀️'"></span>
            <span class="cr-dock-label">Тема</span>
        </button>

        <div class="cr-island-divider cr-dock-btn--desktop-only"></div>

        <!-- 11. End / Leave Button (Desktop only) -->
        @if($userRole === 'tutor')
            <button class="cr-dock-btn cr-dock-btn--danger cr-dock-btn--desktop-only" @click="showEndModal = true" title="Завершить урок для всех">
                <span class="cr-dock-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path>
                        <line x1="23" y1="1" x2="1" y2="23"></line>
                    </svg>
                </span>
                <span class="cr-dock-label">Завершить</span>
            </button>
        @else
            <button class="cr-dock-btn cr-dock-btn--danger cr-dock-btn--desktop-only" @click="leaveSession()" title="Выйти из урока">
                <span class="cr-dock-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </span>
                <span class="cr-dock-label">Выйти</span>
            </button>
        @endif

        <!-- 12. Mobile "More" Actions Toggle (Primary 5th button on mobile - 1 tap) -->
        <button class="cr-dock-btn cr-dock-btn--mobile-more"
                :class="{ 'cr-dock-btn--active': mobileActionsOpen }"
                @click="mobileActionsOpen = !mobileActionsOpen"
                title="Все инструменты урока">
            <span class="cr-dock-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1.5"></circle>
                    <circle cx="19" cy="12" r="1.5"></circle>
                    <circle cx="5" cy="12" r="1.5"></circle>
                </svg>
            </span>
            <span class="cr-dock-label">Ещё</span>
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 7b. MOBILE GLASS ACTION SHEET (Rule of max 2 steps) -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="mobileActionsOpen"
         x-cloak
         class="cr-mobile-sheet-backdrop"
         @click="mobileActionsOpen = false"
         x-transition:enter="cr-trans-enter"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="cr-trans-leave"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="cr-mobile-sheet-card"
             @click.stop
             x-transition:enter="cr-trans-slide-enter"
             x-transition:enter-start="cr-sheet-down"
             x-transition:enter-end="cr-sheet-up"
             x-transition:leave="cr-trans-slide-leave"
             x-transition:leave-start="cr-sheet-up"
             x-transition:leave-end="cr-sheet-down">

            <div class="cr-mobile-sheet-drag" @click="mobileActionsOpen = false">
                <span class="cr-mobile-sheet-pill"></span>
            </div>

            <div class="cr-mobile-sheet-head">
                <div class="cr-mobile-sheet-title">Управление уроком</div>
                <button type="button" class="cr-mobile-sheet-close" @click="mobileActionsOpen = false">✕</button>
            </div>

            <div class="cr-mobile-sheet-grid">
                <!-- 1. Hand / Lock Board (Step 2) -->
                @if($userRole !== 'tutor')
                    <button type="button" class="cr-sheet-item" @click="raiseHand(); mobileActionsOpen = false;">
                        <div class="cr-sheet-item-ico cr-sheet-item-ico--amber">✋</div>
                        <div class="cr-sheet-item-text">
                            <span class="cr-sheet-item-title">Поднять руку</span>
                            <span class="cr-sheet-item-desc">Звук преподавателю</span>
                        </div>
                    </button>
                @else
                    <button type="button" class="cr-sheet-item" :class="{ 'cr-sheet-item--active': isBoardLocked }" @click="toggleBoardLock(); mobileActionsOpen = false;">
                        <div class="cr-sheet-item-ico" :class="isBoardLocked ? 'cr-sheet-item-ico--amber' : 'cr-sheet-item-ico--purple'" x-text="isBoardLocked ? '🔒' : '🔓'"></div>
                        <div class="cr-sheet-item-text">
                            <span class="cr-sheet-item-title" x-text="isBoardLocked ? 'Разблокировать' : 'Блок. доску'"></span>
                            <span class="cr-sheet-item-desc" x-text="isBoardLocked ? 'Доска заблокирована' : 'Запретить рисовать'"></span>
                        </div>
                    </button>
                @endif

                <!-- 2. Flip Camera (Step 2) -->
                <button type="button" class="cr-sheet-item" @click="flipCamera(); mobileActionsOpen = false;">
                    <div class="cr-sheet-item-ico cr-sheet-item-ico--cyan">🔄</div>
                    <div class="cr-sheet-item-text">
                        <span class="cr-sheet-item-title">Повернуть камеру</span>
                        <span class="cr-sheet-item-desc">Фронт / Основная</span>
                    </div>
                </button>

                <!-- 3. AI Assistant (Step 2) -->
                <button type="button" class="cr-sheet-item" @click="toggleSidebar('ai'); mobileActionsOpen = false;">
                    <div class="cr-sheet-item-ico cr-sheet-item-ico--violet">🤖</div>
                    <div class="cr-sheet-item-text">
                        <span class="cr-sheet-item-title">AI Репетитор</span>
                        <span class="cr-sheet-item-desc">Подсказки и разборы</span>
                    </div>
                </button>

                <!-- 4. Timer (Step 2) -->
                <button type="button" class="cr-sheet-item" @click="mobileActionsOpen = false; taskTimerMenuOpen = true;">
                    <div class="cr-sheet-item-ico cr-sheet-item-ico--blue">⏱️</div>
                    <div class="cr-sheet-item-text">
                        <span class="cr-sheet-item-title">Таймер задания</span>
                        <span class="cr-sheet-item-desc" x-text="taskTimerRunning ? taskTimerDisplay : '3, 5, 10 минут'"></span>
                    </div>
                </button>

                <!-- 5. Screen Share (Step 2) -->
                <button type="button" class="cr-sheet-item" :class="{ 'cr-sheet-item--active': isScreenSharing }" @click="toggleScreenShare(); mobileActionsOpen = false;">
                    <div class="cr-sheet-item-ico cr-sheet-item-ico--emerald">🖥️</div>
                    <div class="cr-sheet-item-text">
                        <span class="cr-sheet-item-title">Трансляция экрана</span>
                        <span class="cr-sheet-item-desc" x-text="isScreenSharing ? 'Идёт показ' : 'Включить экран'"></span>
                    </div>
                </button>

                <!-- 6. Theme Toggle (Step 2) -->
                <button type="button" class="cr-sheet-item" @click="toggleTheme(); mobileActionsOpen = false;">
                    <div class="cr-sheet-item-ico" x-text="isLightTheme ? '🌙' : '☀️'"></div>
                    <div class="cr-sheet-item-text">
                        <span class="cr-sheet-item-title" x-text="isLightTheme ? 'Тёмная тема' : 'Светлая тема'"></span>
                        <span class="cr-sheet-item-desc">Цвет интерфейса</span>
                    </div>
                </button>

                <!-- 7. Leave / End Lesson (Step 2) -->
                @if($userRole === 'tutor')
                    <button type="button" class="cr-sheet-item cr-sheet-item--danger" @click="mobileActionsOpen = false; showEndModal = true;">
                        <div class="cr-sheet-item-ico cr-sheet-item-ico--red">🚪</div>
                        <div class="cr-sheet-item-text">
                            <span class="cr-sheet-item-title">Завершить урок</span>
                            <span class="cr-sheet-item-desc">Закрыть для всех</span>
                        </div>
                    </button>
                @else
                    <button type="button" class="cr-sheet-item cr-sheet-item--danger" @click="mobileActionsOpen = false; leaveSession();">
                        <div class="cr-sheet-item-ico cr-sheet-item-ico--red">🚪</div>
                        <div class="cr-sheet-item-text">
                            <span class="cr-sheet-item-title">Выйти из урока</span>
                            <span class="cr-sheet-item-desc">Покинуть комнату</span>
                        </div>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 8. BOTTOM-LEFT ZOOM & PAN CONTROLS (LEGACY ONLY)    -->
    <!-- ═══════════════════════════════════════════════════ -->
    <template x-if="!usingOfficialExcalidraw">
    <div class="cr-island cr-island-zoom">
        <button class="cr-btn-icon cr-btn-icon--sm" @click="wbZoomOut()" title="Уменьшить масштаб (Ctrl + Wheel)">
            –
        </button>
        <button class="cr-zoom-val" @click="wbResetZoom()" title="Сбросить масштаб к 100%">
            <span x-text="zoomPercent + '%'"></span>
        </button>
        <button class="cr-btn-icon cr-btn-icon--sm" @click="wbZoomIn()" title="Увеличить масштаб (Ctrl + Wheel)">
            +
        </button>
    </div>
    </template>

    <!-- Mobile Drawer Backdrop Overlay -->
    <div x-show="isSidebarOpen"
         x-cloak
         class="cr-drawer-backdrop"
         @click="isSidebarOpen = false"></div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 9. SLIDE-OUT RIGHT DRAWER: CHAT, NOTES, FILES, AI  -->
    <!-- ═══════════════════════════════════════════════════ -->
    <aside class="cr-drawer-right"
           x-show="isSidebarOpen"
           x-cloak
           x-transition:enter="transition ease-out duration-250"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">

        <!-- Drawer Header & Tabs -->
        <div class="cr-drawer-header">
            <!-- Mobile Sheet Pull Indicator -->
            <div class="cr-drawer-pull-indicator" @click="isSidebarOpen = false"></div>

            <div class="cr-drawer-tabs">
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'chat' }" @click="activeSidebarTab = 'chat'; unreadChatCount = 0;">
                    <span>Чат</span>
                </button>
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'notes' }" @click="activeSidebarTab = 'notes'">
                    <span>Заметки</span>
                </button>
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'files' }" @click="activeSidebarTab = 'files'">
                    <span>Файлы</span>
                </button>
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'ai' }" @click="activeSidebarTab = 'ai'">
                    <span>ИИ-помощник</span>
                </button>
                @if($userRole === 'tutor')
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'report' }" @click="activeSidebarTab = 'report'">
                    <span>Отчёт</span>
                </button>
                <button class="cr-drawer-tab" :class="{ 'active': activeSidebarTab === 'homework' }" @click="activeSidebarTab = 'homework'">
                    <span>ДЗ</span>
                </button>
                @endif
            </div>
            <button type="button" @click="isSidebarOpen = false" class="cr-drawer-close-btn" title="Закрыть панель">
                ✕
            </button>
        </div>

        <!-- ── TAB: CHAT ── -->
        <div x-show="activeSidebarTab === 'chat'" class="cr-drawer-body" x-cloak>
            <div class="cr-chat-messages" x-ref="chatMessages">
                <template x-for="msg in chatMessages" :key="msg.id">
                    <div class="cr-chat-message" :class="{ 'mine': msg.userId == config.userId }">
                        <div class="cr-chat-avatar" x-text="msg.userName?.charAt(0)?.toUpperCase() || '?'"></div>
                        <div class="cr-chat-body">
                            <div class="cr-chat-sender" x-text="msg.userId == config.userId ? 'Вы' : msg.userName"></div>
                            <div class="cr-chat-bubble" x-text="msg.text"></div>
                            <div class="cr-chat-time" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>
                <div x-show="chatMessages.length === 0" class="cr-drawer-empty">
                    Сообщений пока нет. Напишите первое сообщение!
                </div>
            </div>
            <div class="cr-chat-input-bar">
                <input type="text"
                       x-model="newMessage"
                       @keydown.enter.prevent="sendMessage()"
                       placeholder="Написать сообщение в чат..."
                       id="chat-input">
                <button class="cr-chat-send-btn" @click="sendMessage()" title="Отправить">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>

        <!-- ── TAB: NOTES ── -->
        <div x-show="activeSidebarTab === 'notes'" class="cr-drawer-body" x-cloak>
            <div class="cr-notes-list">
                <template x-for="note in notes" :key="note.id">
                    <div class="cr-note-item">
                        <p class="cr-note-text" x-text="note.text"></p>
                        <span class="cr-note-time" x-text="note.time"></span>
                    </div>
                </template>
                <div x-show="notes.length === 0" class="cr-drawer-empty">Заметок пока нет</div>
            </div>
            <div class="cr-note-form">
                <textarea class="cr-form-textarea" x-model="newNote" placeholder="Добавить заметку к уроку..." rows="3"></textarea>
                <button class="cr-btn-primary" @click="saveNote()" :disabled="!newNote.trim()">Сохранить заметку</button>
            </div>
        </div>

        <!-- ── TAB: FILES ── -->
        <div x-show="activeSidebarTab === 'files'" class="cr-drawer-body" x-cloak>
            <div class="cr-file-drop"
                 @dragover.prevent="$el.classList.add('dragover')"
                 @dragleave="$el.classList.remove('dragover')"
                 @drop.prevent="$el.classList.remove('dragover'); handleFileDrop($event)">
                <div class="cr-file-drop-icon">📁</div>
                <span>Перетащите PDF или изображения сюда</span>
                <input type="file" @change="handleFileInput($event)" style="margin-top: 8px; font-size: 11px;">
            </div>
            <div x-show="isUploading" x-cloak class="cr-file-progress">
                <div class="cr-file-progress-bar" :style="'width:' + uploadProgress + '%'"></div>
            </div>
            <div class="cr-files-list">
                <template x-for="file in files" :key="file.id">
                    <a :href="file.url" target="_blank" class="cr-file-item">
                        <div class="cr-file-icon">📄</div>
                        <div class="cr-file-info">
                            <p class="cr-file-name" x-text="file.name"></p>
                            <p class="cr-file-size" x-text="file.size"></p>
                        </div>
                    </a>
                </template>
                <div x-show="files.length === 0 && !isUploading" class="cr-drawer-empty">Файлов пока нет</div>
            </div>
        </div>

        <!-- ── TAB: AI ASSISTANT ── -->
        <div x-show="activeSidebarTab === 'ai'" class="cr-drawer-body" x-cloak style="display:flex; flex-direction:column; height:100%;">
            {{-- Quick action chips --}}
            <div style="padding: 10px 14px 6px; border-bottom: 1px solid var(--cr-border); display: flex; gap: 6px; overflow-x: auto; flex-shrink: 0;">
                <button type="button" class="cr-badge" style="cursor:pointer; white-space:nowrap; font-size:11px; padding:4px 8px;" @click="newAiMessage = 'Объясни кратко суть этой темы и ключевые формулы'; askAi()">💡 Объясни тему</button>
                <button type="button" class="cr-badge" style="cursor:pointer; white-space:nowrap; font-size:11px; padding:4px 8px;" @click="newAiMessage = 'Придумай аналогичную проверочную задачу с решением'; askAi()">📝 Задача</button>
                <button type="button" class="cr-badge" style="cursor:pointer; white-space:nowrap; font-size:11px; padding:4px 8px;" @click="newAiMessage = 'Задай домашнее задание по теме этого урока на 3 дня'; askAi()">📋 Создай ДЗ</button>
                <button type="button" class="cr-badge" style="cursor:pointer; white-space:nowrap; font-size:11px; padding:4px 8px;" @click="newAiMessage = 'Зафиксируй пробел в знаниях: повторить формулы'; askAi()">⚠️ Пробел</button>
                <button type="button" class="cr-badge" style="cursor:pointer; white-space:nowrap; font-size:11px; padding:4px 8px;" @click="newAiMessage = 'Поставь таймер на 5 минут для решения задачи'; askAi()">⏱ Таймер</button>
            </div>

            <div class="cr-chat-messages" x-ref="aiMessages" style="flex:1; overflow-y:auto;">
                <template x-for="msg in aiMessages" :key="msg.id">
                    <div class="cr-chat-message" :class="{ 'mine': msg.role === 'user' }">
                        <div class="cr-chat-avatar" :style="msg.role === 'assistant' ? 'background: #7D39EB; color: #fff;' : ''" x-text="msg.role === 'user' ? 'Вы' : '✨'"></div>
                        <div class="cr-chat-body">
                            <div class="cr-chat-sender" x-text="msg.role === 'user' ? 'Вы' : 'ИИ-Ассистент (Gemini)'"></div>
                            <div class="cr-chat-bubble" style="white-space:pre-wrap;" x-text="msg.text"></div>

                            {{-- Entity execution badges --}}
                            <template x-if="msg.created_entities">
                                <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:6px;">
                                    <template x-for="hw in (msg.created_entities.homework || [])" :key="'hw-'+hw.id">
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:10px; background:rgba(34,197,94,0.15); color:#22c55e; border:1px solid rgba(34,197,94,0.3); border-radius:12px; padding:2px 8px; font-weight:600;">
                                            ✅ ДЗ назначено: <span x-text="hw.title"></span>
                                        </span>
                                    </template>
                                    <template x-for="gap in (msg.created_entities.gaps || [])" :key="'gap-'+gap.id">
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:10px; background:rgba(234,179,8,0.15); color:#eab308; border:1px solid rgba(234,179,8,0.3); border-radius:12px; padding:2px 8px; font-weight:600;">
                                            ⚠️ Пробел зафиксирован: <span x-text="gap.topic"></span>
                                        </span>
                                    </template>
                                    <template x-for="note in (msg.created_entities.notes || [])" :key="'note-'+note.id">
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:10px; background:rgba(125,57,235,0.15); color:#a855f7; border:1px solid rgba(125,57,235,0.3); border-radius:12px; padding:2px 8px; font-weight:600;">
                                            📌 Заметка в конспекте
                                        </span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <div x-show="isAiLoading" class="cr-chat-message">
                    <div class="cr-chat-avatar" style="background: #7D39EB; color: #fff;">✨</div>
                    <div class="cr-chat-body">
                        <div class="cr-chat-sender">ИИ-Ассистент (Gemini)</div>
                        <div class="cr-chat-bubble" style="color:var(--cr-text-muted); font-size:12px;">Печатает ответ...</div>
                    </div>
                </div>
                <div x-show="aiMessages.length === 0 && !isAiLoading" class="cr-drawer-empty">
                    Задайте вопрос ИИ: например, объясни теорему, составь задачу или проверь решение!
                </div>
            </div>
            <div class="cr-chat-input-bar">
                <input type="text" x-model="newAiMessage" @keydown.enter.prevent="askAi()" placeholder="Спросить у Gemini..." :disabled="isAiLoading">
                <button class="cr-chat-send-btn" @click="askAi()" title="Отправить" :disabled="isAiLoading || !newAiMessage.trim()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>

        @if($userRole === 'tutor')
        <!-- ── TAB: REPORT ── -->
        <div x-show="activeSidebarTab === 'report'" class="cr-drawer-body" x-cloak>
            <div style="display: flex; flex-direction: column; gap: 10px; padding: 14px;">
                <textarea class="cr-form-textarea" x-model="report.summary" placeholder="Итоги урока..." rows="3"></textarea>
                <textarea class="cr-form-textarea" x-model="report.focus" placeholder="Над чем работали..." rows="2"></textarea>
                <textarea class="cr-form-textarea" x-model="report.next_steps" placeholder="Следующие шаги..." rows="2"></textarea>
                <button class="cr-btn-primary" @click="submitReport()">Сохранить отчёт</button>
            </div>
        </div>

        <!-- ── TAB: HOMEWORK ── -->
        <div x-show="activeSidebarTab === 'homework'" class="cr-drawer-body" x-cloak>
            <div style="display: flex; flex-direction: column; gap: 10px; padding: 14px;">
                <input type="text" class="cr-form-input" x-model="homework.title" placeholder="Тема задания...">
                <textarea class="cr-form-textarea" x-model="homework.description" placeholder="Описание и ссылки..." rows="3"></textarea>
                <input type="date" class="cr-form-input" x-model="homework.due_date">
                <button class="cr-btn-primary" @click="assignHomework()">Назначить задание</button>
            </div>
        </div>
        @endif

    </aside>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 10. STUDENT HAND RAISE FLOATING ALERT (FOR TUTOR)  -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="handRaiseAlert"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="cr-hand-raise-alert">
        <span style="font-size: 20px;">✋</span>
        <div>
            <span style="font-weight: 700; font-size: 13px; display: block;" x-text="handRaiseAlert + ' поднял(а) руку!'"></span>
            <span style="font-size: 11px; opacity: 0.85;">Ученик хочет задать вопрос</span>
        </div>
        <button type="button" @click="handRaiseAlert = null" class="cr-hand-ack-btn">
            Понятно
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 11. AUDIO UNLOCK BANNER (AUTOPLAY POLICY RECOVERY) -->
    <!-- ═══════════════════════════════════════════════════ -->
    <button x-show="needsAudioUnlock"
            x-cloak
            @click="unlockAudio()"
            type="button"
            class="cr-audio-unlock-btn">
        <span style="font-size: 16px;">🔊</span>
        <span>Включить звук собеседника (нажмите здесь)</span>
    </button>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 12. TOAST NOTIFICATION                             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div x-show="toastMessage"
         x-cloak
         x-transition
         class="cr-toast-notification">
        <span style="color: #22c55e; font-weight: bold;">✔</span>
        <span x-text="toastMessage"></span>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 13. MODAL: END SESSION CONFIRMATION                -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="cr-modal-overlay"
         x-show="showEndModal"
         x-cloak
         @click.self="showEndModal = false">
        <div class="cr-modal-card">
            <h3 class="cr-modal-title">Завершить урок?</h3>
            <p class="cr-modal-text">Сессия завершится для обоих участников. Вы уверены?</p>
            <div class="cr-modal-actions">
                <button class="cr-btn-secondary" @click="showEndModal = false">Отмена</button>
                <button class="cr-btn-danger" @click="confirmEndSession()">Завершить урок</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 14. MODAL: WEBRTC LIVE DIAGNOSTICS HUD             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div class="cr-modal-overlay"
         x-show="showDiagModal"
         x-cloak
         @click.self="showDiagModal = false">
        <div class="cr-modal-card cr-diag-modal">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">📡</span>
                    <div>
                        <h3 class="cr-modal-title" style="margin: 0; font-size: 15px;">Диагностика LiveKit Cloud</h3>
                        <span style="font-size: 11px; opacity: 0.7;" x-text="'Роль: ' + (config.userRole === 'tutor' ? 'Преподаватель' : 'Ученик') + ' | Сервер: ' + (config.liveKitWsUrl || 'wss://edusfera.livekit.cloud')"></span>
                    </div>
                </div>
                <button type="button" @click="showDiagModal = false" class="cr-btn-icon cr-btn-icon--sm">✕</button>
            </div>

            <div class="cr-diag-grid">
                <div class="cr-diag-card">
                    <span class="cr-diag-k">Медиа-сервер</span>
                    <span class="cr-diag-v" style="color:#38bdf8;" x-text="isLiveKit ? 'LiveKit Cloud SFU' : 'P2P WebRTC'"></span>
                </div>
                <div class="cr-diag-card">
                    <span class="cr-diag-k">Статус связи</span>
                    <span class="cr-diag-v" :style="isConnected ? 'color:#22c55e;' : 'color:#eab308;'" x-text="isConnected ? 'В сети HD' : 'Подключение...'"></span>
                </div>
                <div class="cr-diag-card">
                    <span class="cr-diag-k">Собеседник</span>
                    <span class="cr-diag-v" :style="remoteConnected ? 'color:#22c55e;' : 'color:#94a3b8;'" x-text="remoteConnected ? 'В эфире' : 'Ожидание...'"></span>
                </div>
                <div class="cr-diag-card">
                    <span class="cr-diag-k">Комната ID</span>
                    <span class="cr-diag-v" style="font-size: 10px; color:#e2e8f0; overflow:hidden; text-overflow:ellipsis;" x-text="'lesson_' + config.lessonId"></span>
                </div>
            </div>

            <div class="cr-diag-candidates">
                <span><b>SFU Режим:</b> LiveKit Cloud Edge TURN Relay (Порт 443 WSS)</span>
                <span><b>Шифрование:</b> <span style="color:#22c55e;">DTLS-SRTP 256-bit</span></span>
            </div>

            <div class="cr-diag-logs">
                <template x-for="(log, idx) in diagLogs" :key="idx">
                    <div class="cr-diag-log-line">
                        <span style="opacity: 0.6;" x-text="'[' + log.time + ']'"></span>
                        <span :style="log.level === 'success' ? 'color: #4ade80;' : (log.level === 'error' ? 'color: #f87171;' : (log.level === 'warn' ? 'color: #facc15;' : 'color: #e2e8f0;'))" x-text="log.message"></span>
                    </div>
                </template>
                <div x-show="!diagLogs.length" style="text-align: center; opacity: 0.5; padding: 15px 0;">Логи подключения формируются...</div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 14px; gap: 8px;">
                <button type="button" class="cr-btn-primary" @click="restartConnection()" style="padding: 6px 14px; font-size: 11px;">
                    🔄 Переподключить видео
                </button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="cr-btn-secondary" @click="copyDiagLogs()" style="padding: 6px 12px; font-size: 11px;">
                        📋 Скопировать лог
                    </button>
                    <button type="button" class="cr-btn-secondary" @click="showDiagModal = false" style="padding: 6px 12px; font-size: 11px;">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════ -->
<!-- ALPINE CONTROLLER FOR EXCALIDRAW CLASSROOM         -->
<!-- ═══════════════════════════════════════════════════ -->
<script>
    window.__classroom = {
        lessonId: {{ $lesson->id }},
        roomId: '{{ $session->room_id }}',
        userId: {{ auth()->id() }},
        userName: @json(auth()->user()->name),
        userEmail: @json(auth()->user()->email ?? ''),
        userRole: '{{ $userRole }}',
        csrfToken: '{{ csrf_token() }}',
        studentInviteUrl: @json($studentInviteUrl),
        iceServers: @json($iceServers),
        liveKitConfigured: @json($liveKitConfigured ?? false),
        liveKitToken: @json($liveKitToken ?? null),
        liveKitWsUrl: @json($liveKitWsUrl ?? 'wss://edusfera.livekit.cloud'),
    };

    function classroom() {
        return {
            config: window.__classroom,

            // WebRTC Media & Connection States
            isLiveKit: false,
            liveKit: null,
            p2p: null,
            isConnected: false,
            remoteConnected: false,
            remoteVideoOn: false,
            remoteAudioOn: true,
            tutorSpeaking: false,
            studentSpeaking: false,
            isMicOn: true,
            isCameraOn: false,
            mobileActionsOpen: false,
            isScreenSharing: false,
            activeScreenShare: null,
            diagLogs: [],

            // Floating & Scalable Video Island State
            videoPos: { x: null, y: null },
            videoCustomWidth: null,
            videoScale: 'normal', // 'compact' | 'normal' | 'large'
            isVideoMinimized: false,
            isVideoHidden: false,
            isVideoFullscreen: false,
            isDraggingVideo: false,
            isResizingVideo: false,
            isMobileScreen: window.innerWidth <= 768,
            _dragStart: { mouseX: 0, mouseY: 0, posX: 0, posY: 0 },
            _dragDistance: 0,
            _resizeStart: { mouseX: 0, startWidth: 0 },
            _lastSavedBoardLength: 0,
            _lastVideoTap: 0,

            // Excalidraw Canvas & Multi-page
            whiteboard: null,
            wbTool: 'pen',
            wbColor: '#1e1e1e',
            wbLineWidth: 3,
            wbStrokeStyle: 'solid',
            wbStampType: 'task',
            showStampMenu: false,
            showExportMenu: false,
            zoomPercent: 100,
            currentPageIndex: 0,
            totalPages: 1,
            isBoardLocked: false,
            usingOfficialExcalidraw: true,
            handRaiseAlert: null,

            // Excalidraw Color Palette
            excalidrawColors: ['#1e1e1e', '#e03131', '#2f9e44', '#1971c2', '#f08c00', '#9c36b5', '#7D39EB', '#ffffff'],

            // Drawer & Tabs
            isSidebarOpen: false,
            activeSidebarTab: 'chat',
            unreadChatCount: 0,
            isLightTheme: localStorage.getItem('cr-theme') !== 'dark',

            // Timers
            timerDisplay: '00:00:00',
            taskTimerMinutes: 0,
            taskTimerSeconds: 0,
            taskTimerRunning: false,
            taskTimerMenuOpen: false,
            _taskTimerInterval: null,

            // Modals & Banners
            showEndModal: false,
            showDiagModal: false,
            toastMessage: '',
            needsAudioUnlock: false,

            // Data collections
            chatMessages: [],
            newMessage: '',
            notes: [],
            newNote: '',
            files: [],
            isUploading: false,
            uploadProgress: 0,
            aiMessages: @json($aiChatHistory ?? []),
            newAiMessage: '',
            isAiLoading: false,
            report: { summary: '', focus: '', next_steps: '' },
            homework: { title: '', description: '', due_date: '' },

            get videoIslandStyle() {
                if (this.isVideoFullscreen) {
                    return '';
                }
                let s = '';
                if (this.videoPos.x !== null && this.videoPos.y !== null) {
                    s += `top: ${this.videoPos.y}px; left: ${this.videoPos.x}px; right: auto; bottom: auto; `;
                }
                if (this.videoCustomWidth && !this.isVideoMinimized) {
                    s += `width: ${this.videoCustomWidth}px; `;
                }
                return s;
            },

            startVideoDrag(e) {
                if (e.target.closest('button') || e.target.closest('.cr-video-resize-handle')) return;
                this.isDraggingVideo = true;
                this._dragDistance = 0;
                const el = document.getElementById('cr-video-island');
                const rect = el?.getBoundingClientRect() || { left: 16, top: 16 };
                if (this.videoPos.x === null) {
                    this.videoPos.x = rect.left;
                    this.videoPos.y = rect.top;
                }
                this._dragStart = {
                    mouseX: e.clientX,
                    mouseY: e.clientY,
                    posX: this.videoPos.x,
                    posY: this.videoPos.y,
                };
                e.preventDefault();
            },

            onVideoDragMove(e) {
                if (!this.isDraggingVideo) return;
                const dx = e.clientX - this._dragStart.mouseX;
                const dy = e.clientY - this._dragStart.mouseY;
                this._dragDistance = Math.hypot(dx, dy);
                const el = document.getElementById('cr-video-island');
                const w = el?.offsetWidth || 280;
                const h = el?.offsetHeight || 180;
                const maxX = Math.max(8, window.innerWidth - w - 8);
                const maxY = Math.max(8, window.innerHeight - h - 8);
                this.videoPos.x = Math.max(8, Math.min(maxX, this._dragStart.posX + dx));
                this.videoPos.y = Math.max(8, Math.min(maxY, this._dragStart.posY + dy));
            },

            startVideoTouchDrag(e) {
                if (e.target.closest('button') || e.target.closest('.cr-video-resize-handle')) return;
                const touch = e.touches?.[0];
                if (!touch) return;
                this.isDraggingVideo = true;
                this._dragDistance = 0;
                const el = document.getElementById('cr-video-island');
                const rect = el?.getBoundingClientRect() || { left: 16, top: 16 };
                if (this.videoPos.x === null) {
                    this.videoPos.x = rect.left;
                    this.videoPos.y = rect.top;
                }
                this._dragStart = {
                    mouseX: touch.clientX,
                    mouseY: touch.clientY,
                    posX: this.videoPos.x,
                    posY: this.videoPos.y,
                };
            },

            onVideoTouchDragMove(e) {
                if (!this.isDraggingVideo) return;
                const touch = e.touches?.[0];
                if (!touch) return;
                const dx = touch.clientX - this._dragStart.mouseX;
                const dy = touch.clientY - this._dragStart.mouseY;
                this._dragDistance = Math.hypot(dx, dy);
                const el = document.getElementById('cr-video-island');
                const w = el?.offsetWidth || 200;
                const h = el?.offsetHeight || 140;
                const maxX = Math.max(8, window.innerWidth - w - 8);
                const maxY = Math.max(8, window.innerHeight - h - 8);
                this.videoPos.x = Math.max(8, Math.min(maxX, this._dragStart.posX + dx));
                this.videoPos.y = Math.max(8, Math.min(maxY, this._dragStart.posY + dy));
                if (e.cancelable) e.preventDefault();
            },

            stopVideoDrag() {
                if (this.isDraggingVideo && this._dragDistance > 4) {
                    const blockClick = (ev) => {
                        ev.stopPropagation();
                        ev.preventDefault();
                        window.removeEventListener('click', blockClick, true);
                    };
                    window.addEventListener('click', blockClick, true);
                }
                this.isDraggingVideo = false;
                this._snapVideoToEdge();
            },

            stopVideoTouchDrag() {
                this.stopVideoDrag();
            },

            _snapVideoToEdge() {
                if (this.isMobileScreen && this.videoPos.x !== null) {
                    const screenW = window.innerWidth;
                    const el = document.getElementById('cr-video-island');
                    const islandW = el?.offsetWidth || 175;
                    const midX = this.videoPos.x + islandW / 2;
                    if (midX < screenW / 2) {
                        this.videoPos.x = 8;
                    } else {
                        this.videoPos.x = Math.max(8, screenW - islandW - 8);
                    }
                }
            },

            toggleFullscreenVideo() {
                this.isVideoFullscreen = !this.isVideoFullscreen;
                if (this.isVideoFullscreen) {
                    this.isVideoMinimized = false;
                    this.isVideoHidden = false;
                    this.showToast('Полноэкранный видеозвонок');
                } else {
                    this.showToast('Интерактивная доска');
                }
            },


            handleVideoTap(e) {
                if (e.target.closest('button') || e.target.closest('.cr-video-resize-handle') || this.isDraggingVideo || (this._dragDistance && this._dragDistance > 6)) return;
                const now = Date.now();
                if (this._lastVideoTap && (now - this._lastVideoTap < 350)) {
                    this.toggleFullscreenVideo();
                    this._lastVideoTap = 0;
                } else {
                    this._lastVideoTap = now;
                }
            },

            cycleVideoScale() {
                this.videoCustomWidth = null;
                if (this.videoScale === 'compact') {
                    this.videoScale = 'normal';
                } else if (this.videoScale === 'normal') {
                    this.videoScale = 'large';
                } else {
                    this.videoScale = 'compact';
                }
                this.showToast('Масштаб: ' + (this.videoScale === 'compact' ? 'Компактный (S)' : (this.videoScale === 'large' ? 'Большой (L)' : 'Стандартный (M)')));
            },

            startVideoResize(e) {
                this.isResizingVideo = true;
                const el = document.getElementById('cr-video-island');
                this._resizeStart = {
                    mouseX: e.clientX,
                    startWidth: el?.offsetWidth || 300,
                };
                e.preventDefault();
            },

            onVideoResizeMove(e) {
                if (!this.isResizingVideo) return;
                const dx = e.clientX - this._resizeStart.mouseX;
                const minW = this.isMobileScreen ? 150 : 200;
                const maxW = Math.min(window.innerWidth - 16, 560);
                this.videoCustomWidth = Math.max(minW, Math.min(maxW, this._resizeStart.startWidth + dx));
            },

            stopVideoResize() {
                this.isResizingVideo = false;
            },

            startVideoTouchResize(e) {
                const touch = e.touches?.[0];
                if (!touch) return;
                this.isResizingVideo = true;
                const el = document.getElementById('cr-video-island');
                this._resizeStart = {
                    mouseX: touch.clientX,
                    startWidth: el?.offsetWidth || 300,
                };
            },

            onVideoTouchResizeMove(e) {
                if (!this.isResizingVideo) return;
                const touch = e.touches?.[0];
                if (!touch) return;
                const dx = touch.clientX - this._resizeStart.mouseX;
                const minW = this.isMobileScreen ? 150 : 200;
                const maxW = Math.min(window.innerWidth - 16, 560);
                this.videoCustomWidth = Math.max(minW, Math.min(maxW, this._resizeStart.startWidth + dx));
                if (e.cancelable) e.preventDefault();
            },

            async loadInitialWhiteboardState() {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/whiteboard`);
                    if (res.ok) {
                        const data = await res.json();
                        const elements = data.state?.elements || (Array.isArray(data.state) ? data.state : null);
                        if (elements && elements.length > 0 && window.__onExcalidrawRemoteSync) {
                            window.__onExcalidrawRemoteSync(elements);
                            this._lastSavedBoardLength = elements.length;
                        }
                    }
                } catch (e) {
                    console.warn('[Classroom] Initial whiteboard state load failed:', e);
                }
            },

            scheduleWhiteboardAutosave() {
                setInterval(async () => {
                    if (typeof window.getExcalidrawElements !== 'function') return;
                    const elements = window.getExcalidrawElements();
                    if (!elements || elements.length === 0) return;
                    if (this._lastSavedBoardLength === elements.length) return;
                    this._lastSavedBoardLength = elements.length;
                    try {
                        await fetch(`/classroom/${this.config.lessonId}/whiteboard`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.config.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                state: { elements, version: Date.now() }
                            }),
                        });
                    } catch (e) {}
                }, 20000);
            },

            async init() {
                window.classroomApp = this;
                this.applyTheme();

                // Screen size tracking for responsive glass dock
                window.addEventListener('resize', () => {
                    this.isMobileScreen = window.innerWidth <= 768;
                });

                // Auto-recover media and video playback when switching tabs or unlocking phone on Android
                document.addEventListener('visibilitychange', async () => {
                    if (document.visibilityState === 'visible') {
                        if (this.liveKit) {
                            try {
                                this.liveKit.syncRemoteTracks();
                                const remoteEl = this.config.userRole === 'tutor'
                                    ? document.getElementById('cr-student-remote-video')
                                    : document.getElementById('cr-tutor-remote-video');
                                remoteEl?.play?.().catch(() => {});

                                const localEl = this.config.userRole === 'tutor'
                                    ? document.getElementById('cr-tutor-local-video')
                                    : document.getElementById('cr-student-local-video');
                                localEl?.play?.().catch(() => {});

                                const remoteAudio = document.getElementById('cr-remote-audio');
                                remoteAudio?.play?.().catch(() => { this.needsAudioUnlock = true; });
                            } catch (e) {}
                        } else if (!this.isCameraOn && this.p2p) {
                            try {
                                if (navigator.permissions?.query) {
                                    const status = await navigator.permissions.query({ name: 'camera' });
                                    if (status.state === 'granted') {
                                        await this.enableCamera();
                                    }
                                }
                            } catch (e) {}
                        }
                    }
                });

                // Auto-unlock audio on first touch/click anywhere in the classroom
                const unlockAudio = () => {
                    const remoteAudio = document.getElementById('cr-remote-audio');
                    if (remoteAudio && remoteAudio.paused && remoteAudio.srcObject) {
                        remoteAudio.play().then(() => { this.needsAudioUnlock = false; }).catch(() => {});
                    }
                };
                window.addEventListener('pointerdown', unlockAudio, { passive: true });
                window.addEventListener('touchstart', unlockAudio, { passive: true });

                // 1. Initialize Excalidraw Whiteboard
                this.initWhiteboard();

                // 2. Wait for utility modules (LiveKit / P2P, Timer, Uploader)
                while (!window.ClassroomModules) {
                    await new Promise(r => setTimeout(r, 50));
                }
                const { LiveKitConnectionManager, ServerWhiteboardManager, P2PConnectionManager, SessionTimer, FileUploader } = window.ClassroomModules;

                // 2.1 Ultra-reliable Edusfera Server Whiteboard Manager (HTTP Long-Polling Sync)
                if (ServerWhiteboardManager && !this.serverWhiteboard) {
                    this.serverWhiteboard = new ServerWhiteboardManager({
                        lessonId: this.config.lessonId,
                        csrfToken: this.config.csrfToken,
                        initialLocked: this.isBoardLocked,
                        onRemoteSync: (elements) => {
                            if (window.__onExcalidrawRemoteSync) {
                                window.__onExcalidrawRemoteSync(elements);
                            }
                        },
                        onLockToggle: (isLocked) => {
                            this.isBoardLocked = isLocked;
                            if (window.__onExcalidrawLockToggle) {
                                window.__onExcalidrawLockToggle(isLocked);
                            }
                            if (this.config.userRole !== 'tutor') {
                                this.showToast(isLocked ? '🔒 Преподаватель заблокировал доску' : '🔓 Доска разблокирована для рисования');
                            }
                        }
                    });
                    this.serverWhiteboard.start();
                }

                // 3. Connect Video Media Engine (LiveKit SFU if valid credentials, otherwise resilient WebRTC P2P + OpenRelay TURN)
                let connected = false;
                if (this.config.liveKitToken && LiveKitConnectionManager) {
                    this.isLiveKit = true;
                    this.logDiag('Инициализация LiveKit Cloud SFU (Порт 443 WSS)...', 'info');

                    const lk = new LiveKitConnectionManager({
                        wsUrl: this.config.liveKitWsUrl || 'wss://edusfera.livekit.cloud',
                        token: this.config.liveKitToken,
                        userRole: this.config.userRole,
                        userName: this.config.userName,
                        onLocalTrack: (track, pub) => {
                            if (track.kind === 'video') this.isCameraOn = true;
                            if (track.kind === 'audio') this.isMicOn = true;
                            this.$nextTick(() => {
                                const localEl = this.config.userRole === 'tutor'
                                    ? document.getElementById('cr-tutor-local-video')
                                    : document.getElementById('cr-student-local-video');
                                if (localEl && track.kind === 'video') {
                                    track.attach(localEl);
                                    localEl.muted = true;
                                    localEl.play?.().catch(() => {});
                                }
                            });
                        },
                        onRemoteTrack: (track, pub, participant) => {
                            this.remoteConnected = true;
                            this.$nextTick(() => {
                                if (track.kind === 'video') {
                                    const remoteEl = this.config.userRole === 'tutor'
                                        ? document.getElementById('cr-student-remote-video')
                                        : document.getElementById('cr-tutor-remote-video');
                                    if (remoteEl) {
                                        track.attach(remoteEl);
                                        remoteEl.muted = true;
                                        remoteEl.play?.().catch(() => {});
                                        this.remoteVideoOn = true;
                                        if (track.mediaStreamTrack) {
                                            track.mediaStreamTrack.onunmute = () => {
                                                this.remoteVideoOn = true;
                                                remoteEl.play?.().catch(() => {});
                                            };
                                        }
                                    }
                                } else if (track.kind === 'audio') {
                                    const remoteAudio = document.getElementById('cr-remote-audio');
                                    if (remoteAudio) {
                                        track.attach(remoteAudio);
                                        remoteAudio.play?.().catch(() => { this.needsAudioUnlock = true; });
                                        this.remoteAudioOn = true;
                                    }
                                }
                            });
                        },
                        onRemoteTrackUnsubscribed: (track, pub) => {
                            if (track.kind === 'video') this.remoteVideoOn = false;
                            if (track.kind === 'audio') this.remoteAudioOn = false;
                        },
                        onRemoteTrackMuted: (isMuted) => {
                            if (isMuted) {
                                this.remoteVideoOn = false;
                            }
                        },
                        onRemoteTrackUnmuted: () => {
                            this.remoteVideoOn = true;
                            const remoteEl = this.config.userRole === 'tutor'
                                ? document.getElementById('cr-student-remote-video')
                                : document.getElementById('cr-tutor-remote-video');
                            if (remoteEl && remoteEl.paused) {
                                remoteEl.play?.().catch(() => {});
                            }
                        },
                        onRemoteAudioMuted: (isMuted) => {
                            this.remoteAudioOn = !isMuted;
                        },
                        onParticipantJoined: (participant) => {
                            this.remoteConnected = true;
                            this.showToast('Собеседник вошел в класс!');
                            this.sendBroadcast({ type: 'wb_excalidraw_request_sync' });
                        },
                        onParticipantLeft: () => {
                            this.remoteConnected = false;
                            this.showToast('Собеседник покинул класс');
                        },
                        onConnectionStateChange: (state) => {
                            if (state === 'connected') {
                                this.isConnected = true;
                                this.showToast('📡 Видеосвязь подключена!');
                                this.sendBroadcast({ type: 'wb_excalidraw_request_sync' });
                            } else {
                                this.isConnected = false;
                            }
                        },
                        onActiveSpeakers: (speakers) => {
                            const isTutorSpeaking = speakers.some(s => s.identity?.startsWith('tutor_') || (s.isLocal && this.config.userRole === 'tutor'));
                            const isStudentSpeaking = speakers.some(s => s.identity?.startsWith('student_') || (s.isLocal && this.config.userRole !== 'tutor'));
                            this.tutorSpeaking = isTutorSpeaking;
                            this.studentSpeaking = isStudentSpeaking;
                        },
                        onData: (data) => {
                            this.handleIncomingBroadcast(data);
                        },
                        onLog: (msg, level) => {
                            this.logDiag(msg, level);
                        }
                    });
                    this.liveKit = window.Alpine ? Alpine.raw(lk) : lk;

                    // LiveKit Watchdog: guarantees audio/video tracks remain attached and playing on Android
                    if (this._liveKitWatchdog) clearInterval(this._liveKitWatchdog);
                    this._liveKitWatchdog = setInterval(() => {
                        if (!this.liveKit || !this.liveKit.room) return;
                        const room = this.liveKit.room;
                        if (room.state === 'connected') {
                            this.isConnected = true;
                            let hasRemote = false;
                            let hasRemoteVideo = false;
                            room.remoteParticipants?.forEach((p) => {
                                hasRemote = true;
                                p.trackPublications?.forEach((pub) => {
                                    if (pub.kind === 'video' && pub.isSubscribed && pub.track) {
                                        hasRemoteVideo = true;
                                        const remoteEl = this.config.userRole === 'tutor'
                                            ? document.getElementById('cr-student-remote-video')
                                            : document.getElementById('cr-tutor-remote-video');
                                        if (remoteEl) {
                                            if (!remoteEl.srcObject || remoteEl.srcObject !== pub.track.mediaStream) {
                                                pub.track.attach(remoteEl);
                                            }
                                            if (remoteEl.paused) {
                                                remoteEl.play?.().catch(() => {});
                                            }
                                        }
                                    } else if (pub.kind === 'audio' && pub.isSubscribed && pub.track) {
                                        const remoteAudio = document.getElementById('cr-remote-audio');
                                        if (remoteAudio && remoteAudio.paused) {
                                            remoteAudio.play?.().catch(() => { this.needsAudioUnlock = true; });
                                        }
                                    }
                                });
                            });
                            if (hasRemote) this.remoteConnected = true;
                            if (hasRemoteVideo && !this.remoteVideoOn) this.remoteVideoOn = true;

                            if (this.isCameraOn && this.liveKit.localVideoTrack) {
                                const localEl = this.config.userRole === 'tutor'
                                    ? document.getElementById('cr-tutor-local-video')
                                    : document.getElementById('cr-student-local-video');
                                if (localEl && localEl.paused) {
                                    localEl.play?.().catch(() => {});
                                }
                            }
                        }
                    }, 2000);

                    try {
                        await this.liveKit.start();
                        connected = true;
                    } catch (e) {
                        this.logDiag('LiveKit ошибка: ' + e.message, 'warn');
                    }
                }

                if (!connected && P2PConnectionManager) {
                    if (this.config.liveKitToken) {
                        this.logDiag('LiveKit недоступен, резервный запуск P2P WebRTC + OpenRelay TURN...', 'warn');
                    }
                    await this.startP2PEngine();
                }

                // 4. Session Timer
                this.timer = new SessionTimer((val) => { this.timerDisplay = val; });
                this.timer.start();

                // 5. File Uploader
                this.fileUploader = new FileUploader(this.config.lessonId, this.config.csrfToken);

                // 6. Load initial data
                this.loadChatHistory();
                this.loadNotes();
                this.loadFiles();

                // Responsive mobile screen flag watcher
                window.addEventListener('resize', () => {
                    this.isMobileScreen = window.innerWidth <= 768;
                    this.whiteboard?.resize();
                });

                window.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isVideoFullscreen) {
                        this.toggleFullscreenVideo();
                    }
                });

                // Load initial whiteboard from server DB
                this.loadInitialWhiteboardState();

                // Periodic autosave to database
                this.scheduleWhiteboardAutosave();
            },

            async startP2PEngine() {
                const { P2PConnectionManager } = window.ClassroomModules || {};
                if (!P2PConnectionManager) return;
                this.isLiveKit = false;
                this.logDiag('Запуск WebRTC P2P (W3C Perfect Negotiation + OpenRelay TURN)...', 'info');

                this.p2p = new P2PConnectionManager({
                    lessonId: this.config.lessonId,
                    csrfToken: this.config.csrfToken,
                    userRole: this.config.userRole,
                    iceServers: this.config.iceServers,
                });

                this.p2p.onLocalStream = (stream) => {
                    this.$nextTick(() => {
                        const localEl = this.config.userRole === 'tutor'
                            ? document.getElementById('cr-tutor-local-video')
                            : document.getElementById('cr-student-local-video');
                        if (localEl) {
                            localEl.srcObject = stream;
                            localEl.muted = true;
                            localEl.play?.().catch(() => {});
                        }
                        const hasCam = !!(stream && stream.getVideoTracks().some(t => t.readyState === 'live' && t.enabled));
                        const hasMic = !!(stream && stream.getAudioTracks().some(t => t.readyState === 'live' && t.enabled));
                        this.isCameraOn = hasCam;
                        this.isMicOn = hasMic;
                    });
                };

                this.p2p.onRemoteTrack = (stream) => {
                    this.remoteConnected = true;
                    this.$nextTick(() => {
                        const hasVideo = !!(stream && stream.getVideoTracks().some(t => t.readyState === 'live' && !t.muted && t.enabled));
                        const hasAudio = !!(stream && stream.getAudioTracks().some(t => t.readyState === 'live' && !t.muted && t.enabled));
                        this.remoteVideoOn = hasVideo;
                        this.remoteAudioOn = hasAudio;

                        const remoteEl = this.config.userRole === 'tutor'
                            ? document.getElementById('cr-student-remote-video')
                            : document.getElementById('cr-tutor-remote-video');
                        if (remoteEl) {
                            if (remoteEl.srcObject !== stream) {
                                remoteEl.srcObject = stream;
                            }
                            remoteEl.muted = true; // Video element strictly muted to prevent double audio playback
                            if (hasVideo) {
                                remoteEl.play?.().catch(() => {});
                            }
                        }
                        const remoteAudio = document.getElementById('cr-remote-audio');
                        if (remoteAudio) {
                            if (remoteAudio.srcObject !== stream) {
                                remoteAudio.srcObject = stream;
                            }
                            remoteAudio.muted = false; // Primary single audio pipeline
                            remoteAudio.play?.().catch(() => { this.needsAudioUnlock = true; });
                        }
                    });
                };

                this.p2p.onConnectionStateChange = (state) => {
                    if (state === 'connected') {
                        this.isConnected = true;
                        this.remoteConnected = true;
                        this.showToast('✅ Видеосвязь установлена!');
                        this.sendBroadcast({ type: 'wb_excalidraw_request_sync' });
                    } else if (state === 'disconnected' || state === 'failed') {
                        this.isConnected = false;
                    }
                };

                // Watchdog to sync UI states and catch unmuted tracks on renegotiation
                if (this._p2pWatchdog) clearInterval(this._p2pWatchdog);
                this._p2pWatchdog = setInterval(() => {
                    const isConn = this.p2p?.isConnected ||
                        this.p2p?.pc?.connectionState === 'connected' ||
                        this.p2p?.pc?.iceConnectionState === 'connected';
                    if (isConn && !this.remoteConnected) {
                        this.remoteConnected = true;
                        this.isConnected = true;
                    }
                    if (this.p2p?.remoteStream) {
                        const hasVid = this.p2p.remoteStream.getVideoTracks().some(t => t.readyState === 'live' && !t.muted);
                        if (hasVid !== this.remoteVideoOn) {
                            this.remoteVideoOn = hasVid;
                            const remoteEl = this.config.userRole === 'tutor'
                                ? document.getElementById('cr-student-remote-video')
                                : document.getElementById('cr-tutor-remote-video');
                            if (remoteEl && remoteEl.srcObject !== this.p2p.remoteStream) {
                                remoteEl.srcObject = this.p2p.remoteStream;
                                remoteEl.muted = true;
                                remoteEl.play?.().catch(() => {});
                            }
                        }
                    }
                }, 2000);

                this.p2p.onData = (msg) => this.handleIncomingBroadcast(msg);

                this.p2p.onDiagnosticUpdate = (diag) => {
                    if (diag.logs && diag.logs.length > 0) {
                        const latest = diag.logs[0];
                        this.logDiag(latest.message, latest.level);
                    }
                };

                try {
                    await this.p2p.initMedia();
                    const hasCam = !!(this.p2p.localStream && this.p2p.localStream.getVideoTracks().some(t => t.readyState === 'live' && t.enabled));
                    const hasMic = !!(this.p2p.localStream && this.p2p.localStream.getAudioTracks().some(t => t.readyState === 'live' && t.enabled));
                    this.isCameraOn = hasCam;
                    this.isMicOn = hasMic;
                    await this.p2p.start();
                } catch (e) {
                    this.logDiag('Ошибка запуска P2P: ' + e.message, 'error');
                }
            },

            sendBroadcast(payload, options = {}) {
                let sent = false;
                if (this.liveKit) {
                    try {
                        this.liveKit.sendData(payload, options);
                        sent = true;
                    } catch (e) {}
                } else if (this.p2p) {
                    sent = this.p2p.sendData(payload);
                }
                // Fallback to HTTP signaling if DataChannel is buffering or not yet open
                if (!sent && this.p2p?.signaling) {
                    this.p2p.signaling.send('wb_broadcast', payload);
                }
            },

            handleIncomingBroadcast(msg) {
                if (!msg) return;
                if (msg.type === 'chat') {
                    if (!this.chatMessages.some(m => m.id === msg.data?.id)) {
                        this.chatMessages.push(msg.data);
                        this.scrollChatToBottom();
                        if (!this.isSidebarOpen || this.activeSidebarTab !== 'chat') {
                            this.unreadChatCount++;
                        }
                    }
                } else if (msg.type === 'wb_excalidraw_sync') {
                    const elements = msg.elements || msg;
                    if (window.__onExcalidrawRemoteSync) {
                        window.__onExcalidrawRemoteSync(elements);
                    }
                } else if (msg.type === 'wb_excalidraw_request_sync') {
                    const elements = typeof window.getExcalidrawElements === 'function' ? window.getExcalidrawElements() : [];
                    if (elements && elements.length > 0) {
                        this.sendBroadcast({
                            type: 'wb_excalidraw_sync',
                            elements: elements,
                            version: Date.now(),
                        });
                    }
                } else if (msg.type === 'wb_excalidraw_clear') {
                    if (typeof window.clearExcalidrawBoard === 'function') {
                        window.clearExcalidrawBoard();
                    }
                } else if (msg.type === 'wb_excalidraw_pointer') {
                    if (window.__onExcalidrawRemotePointer) {
                        window.__onExcalidrawRemotePointer(msg);
                    }
                } else if (msg.type === 'wb_excalidraw_lock') {
                    this.isBoardLocked = !!msg.isLocked;
                    if (window.__onExcalidrawLockToggle) {
                        window.__onExcalidrawLockToggle(this.isBoardLocked);
                    }
                    if (this.config.userRole !== 'tutor') {
                        this.showToast(msg.isLocked ? '🔒 Преподаватель заблокировал доску' : '🔓 Доска разблокирована для рисования');
                    }
                } else if (msg.type === 'hand-raise') {
                    this.handRaiseAlert = msg.studentName || 'Ученик';
                } else if (msg.type?.startsWith('wb-')) {
                    this.whiteboard?.applyRemoteAction(msg);
                }
            },

            logDiag(message, level = 'info') {
                const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                this.diagLogs.unshift({ time, message, level });
                if (this.diagLogs.length > 50) this.diagLogs.pop();
                console.log(`[Classroom] [${level}] ${message}`);
            },

            initWhiteboard() {
                const tryMount = (attempts = 0) => {
                    if (this.excalidrawRoot) return;

                    if (typeof window.mountExcalidraw === 'function') {
                        const mountContainer = document.getElementById('excalidraw-mount-container');
                        if (mountContainer) {
                            try {
                                this.excalidrawRoot = window.mountExcalidraw('excalidraw-mount-container', {
                                    isBoardLocked: this.isBoardLocked,
                                    isDark: !this.isLightTheme,
                                    onBroadcast: (payload) => {
                                        if (this.serverWhiteboard && payload.elements) {
                                            this.serverWhiteboard.broadcast(payload.elements, payload.version);
                                        }
                                        this.sendBroadcast(payload);
                                    },
                                    onPointerMove: (x, y) => {
                                        this.sendBroadcast({
                                            type: 'wb_excalidraw_pointer',
                                            userId: this.config.userId,
                                            userName: this.config.userName,
                                            x, y,
                                            color: this.config.userRole === 'tutor' ? '#7D39EB' : '#10b981',
                                        }, { reliable: false });
                                    }
                                });
                                this.usingOfficialExcalidraw = true;
                                console.log('[Classroom] Original Excalidraw mounted successfully');
                                return;
                            } catch (e) {
                                console.warn('[Classroom] Excalidraw mount error:', e);
                            }
                        }
                    }

                    if (attempts < 25) {
                        setTimeout(() => tryMount(attempts + 1), 100);
                        return;
                    }

                    // Fallback to legacy canvas engine if official Excalidraw bundle fails to load
                    console.warn('[Classroom] Excalidraw mount timeout, falling back to legacy canvas');
                    this.usingOfficialExcalidraw = false;
                    this.$nextTick(() => {
                        const canvas = document.getElementById('cr-whiteboard-canvas');
                        if (!canvas) return;
                        const { WhiteboardEngine } = window.ClassroomModules;
                        this.whiteboard = new WhiteboardEngine(
                            canvas,
                            { sendData: (data) => this.sendBroadcast(data) },
                            this.config.lessonId,
                            this.config.csrfToken,
                            this.config.userRole,
                            this.config.userName
                        );
                        this.whiteboard.init();
                        this.whiteboard.color = this.wbColor;
                        this.whiteboard.lineWidth = this.wbLineWidth;
                        this.whiteboard.tool = this.wbTool;

                        this.whiteboard.onPageChanged = (idx, pages) => {
                            this.currentPageIndex = idx;
                            this.totalPages = pages.length;
                        };

                        this.whiteboard.onLockChanged = (locked) => {
                            this.isBoardLocked = locked;
                        };

                        this.whiteboard.onHandRaised = (studentName) => {
                            this.handRaiseAlert = studentName;
                        };

                        this.whiteboard.onToast = (msg) => {
                            this.showToast(msg);
                        };

                        this.whiteboard.onZoomChanged = (val) => {
                            this.zoomPercent = val;
                        };
                    });
                };

                window.addEventListener('excalidraw:ready', () => tryMount(0));
                tryMount(0);
            },

            // ─── Whiteboard Tool Actions ─────
            setWbTool(t) {
                this.wbTool = t;
                if (this.whiteboard) this.whiteboard.tool = t;
            },

            setWbColor(c) {
                this.wbColor = c;
                if (this.whiteboard) this.whiteboard.color = c;
            },

            setWbWidth(w) {
                this.wbLineWidth = w;
                if (this.whiteboard) this.whiteboard.lineWidth = w;
            },

            setWbStrokeStyle(s) {
                this.wbStrokeStyle = s;
                if (this.whiteboard) this.whiteboard.strokeStyle = s;
            },

            setWbStamp(type) {
                this.wbStampType = type;
                this.wbTool = 'stamp';
                if (this.whiteboard) {
                    this.whiteboard.tool = 'stamp';
                    this.whiteboard.stampType = type;
                }
            },

            wbUndo() { this.whiteboard?.undo(); },
            wbRedo() { this.whiteboard?.redo(); },
            wbClear() {
                if (confirm('Очистить доску?')) {
                    if (typeof window.clearExcalidrawBoard === 'function') {
                        window.clearExcalidrawBoard();
                        if (this.serverWhiteboard) {
                            this.serverWhiteboard.clear();
                        }
                        this.sendBroadcast({
                            type: 'wb_excalidraw_clear',
                            version: Date.now(),
                        });
                        this.sendBroadcast({
                            type: 'wb_excalidraw_sync',
                            elements: [],
                            version: Date.now(),
                        });
                    }
                    this.whiteboard?.clear();
                    this.showToast('Доска очищена');
                }
            },
            wbZoomIn() { this.whiteboard?.zoomIn(); },
            wbZoomOut() { this.whiteboard?.zoomOut(); },
            wbResetZoom() { this.whiteboard?.resetView(); },

            wbAddSlide() { this.whiteboard?.addPage(); },
            wbPrevSlide() { this.whiteboard?.switchPage(this.currentPageIndex - 1); },
            wbNextSlide() { this.whiteboard?.switchPage(this.currentPageIndex + 1); },

            wbExportPng() { this.whiteboard?.exportPng(); },
            wbExportJson() { this.whiteboard?.exportJson(); },

            toggleBoardLock() {
                if (this.config.userRole !== 'tutor') return;
                const newLock = !this.isBoardLocked;
                this.isBoardLocked = newLock;
                this.whiteboard?.setLocked(newLock);
                if (window.__onExcalidrawLockToggle) {
                    window.__onExcalidrawLockToggle(newLock);
                }
                if (this.serverWhiteboard) {
                    this.serverWhiteboard.lockToggle(newLock);
                }
                this.sendBroadcast({
                    type: 'wb_excalidraw_lock',
                    isLocked: newLock
                });
                this.showToast(newLock ? '🔒 Доска заблокирована для ученика' : '🔓 Доступ к рисованию открыт');
            },

            raiseHand() {
                if (this.whiteboard) {
                    this.whiteboard.raiseHand();
                } else {
                    this.sendBroadcast({
                        type: 'hand-raise',
                        studentName: this.config.userName,
                        timestamp: Date.now()
                    });
                    this.showToast('✋ Вы подняли руку. Преподаватель видит сигнал.');
                }
            },

            // ─── Task Countdown Timer ────────
            startTaskTimer(min) {
                this.taskTimerMinutes = min;
                this.taskTimerSeconds = 0;
                this.taskTimerRunning = true;
                this.taskTimerMenuOpen = false;
                if (this._taskTimerInterval) clearInterval(this._taskTimerInterval);
                this._taskTimerInterval = setInterval(() => {
                    if (this.taskTimerSeconds > 0) {
                        this.taskTimerSeconds--;
                    } else if (this.taskTimerMinutes > 0) {
                        this.taskTimerMinutes--;
                        this.taskTimerSeconds = 59;
                    } else {
                        this.stopTaskTimer();
                        this.showToast('⏱️ Время выполнения задания завершено!');
                    }
                }, 1000);
                this.showToast(`⏱️ Таймер задания запущен на ${min} мин.`);
            },

            stopTaskTimer() {
                this.taskTimerRunning = false;
                this.taskTimerMinutes = 0;
                this.taskTimerSeconds = 0;
                if (this._taskTimerInterval) {
                    clearInterval(this._taskTimerInterval);
                    this._taskTimerInterval = null;
                }
            },

            get taskTimerDisplay() {
                const m = String(this.taskTimerMinutes).padStart(2, '0');
                const s = String(this.taskTimerSeconds).padStart(2, '0');
                return `${m}:${s}`;
            },

            // ─── Media Controls ──────────────
            async toggleMic() {
                this.isMicOn = !this.isMicOn;
                if (this.liveKit) {
                    await this.liveKit.setMicEnabled(this.isMicOn);
                } else if (this.p2p) {
                    this.p2p.setMicEnabled(this.isMicOn);
                }
            },

            async toggleCamera() {
                const target = !this.isCameraOn;
                if (this.liveKit) {
                    await this.liveKit.setCameraEnabled(target);
                    this.isCameraOn = target;
                } else if (this.p2p) {
                    const ok = await this.p2p.setCameraEnabled(target);
                    this.isCameraOn = ok ? target : false;
                    if (!ok && target) {
                        this.showToast('⚠️ Разрешите доступ к камере в браузере');
                    }
                }
            },

            async enableCamera() {
                if (!this.isCameraOn) {
                    await this.toggleCamera();
                }
            },

            async flipCamera() {
                if (this.liveKit) {
                    const mode = await this.liveKit.flipCamera();
                    this.showToast(`Камера: ${mode === 'environment' ? 'Основная' : 'Фронтальная'}`);
                } else if (this.p2p) {
                    const mode = await this.p2p.flipCamera();
                    if (mode) {
                        this.showToast(`Камера: ${mode === 'environment' ? 'Основная' : 'Фронтальная'}`);
                    } else {
                        this.showToast('⚠️ Не удалось переключить камеру');
                    }
                }
            },

            async toggleScreenShare() {
                this.isScreenSharing = !this.isScreenSharing;
                if (this.liveKit) {
                    await this.liveKit.toggleScreenShare(this.isScreenSharing);
                } else if (this.p2p) {
                    await this.p2p.toggleScreenShare(this.isScreenSharing);
                }
            },

            unlockAudio() {
                const remoteAudio = document.getElementById('cr-remote-audio');
                if (remoteAudio) remoteAudio.play().then(() => { this.needsAudioUnlock = false; }).catch(() => {});
                this.needsAudioUnlock = false;
            },

            // ─── Sidebar Drawer ──────────────
            toggleSidebar(tab) {
                if (this.isSidebarOpen && this.activeSidebarTab === tab) {
                    this.isSidebarOpen = false;
                } else {
                    this.activeSidebarTab = tab;
                    this.isSidebarOpen = true;
                    if (tab === 'chat') {
                        this.unreadChatCount = 0;
                        this.scrollChatToBottom();
                    }
                }
            },

            // ─── Chat ────────────────────────
            async loadChatHistory(silent = false) {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/chat`);
                    if (res.ok) {
                        const data = await res.json();
                        const messages = Array.isArray(data) ? data : (data.messages || []);
                        this.chatMessages = messages;
                        if (!silent) this.scrollChatToBottom();
                    }
                } catch (e) {}
            },

            async sendMessage() {
                const text = this.newMessage?.trim();
                if (!text) return;
                this.newMessage = '';

                const msgObj = {
                    id: Date.now(),
                    userId: this.config.userId,
                    userName: this.config.userName,
                    text: text,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                };

                this.chatMessages.push(msgObj);
                this.scrollChatToBottom();

                // 1. Instant DataChannel delivery via LiveKit
                this.sendBroadcast({ type: 'chat', data: msgObj });

                // 2. Database persistence
                try {
                    await fetch(`/classroom/${this.config.lessonId}/chat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message: text }),
                    });
                } catch (e) {}
            },

            scrollChatToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.chatMessages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            // ─── Notes ───────────────────────
            async loadNotes() {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/notes`);
                    if (res.ok) {
                        const data = await res.json();
                        this.notes = Array.isArray(data) ? data : (data.notes || []);
                    }
                } catch (e) {}
            },

            async saveNote() {
                const content = this.newNote?.trim();
                if (!content) return;
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/notes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ content }),
                    });
                    if (res.ok) {
                        this.newNote = '';
                        this.loadNotes();
                        this.showToast('Заметка сохранена');
                    }
                } catch (e) {}
            },

            // ─── Files ───────────────────────
            async loadFiles() {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/files`);
                    if (res.ok) {
                        const data = await res.json();
                        this.files = Array.isArray(data) ? data : (data.files || []);
                    }
                } catch (e) {}
            },

            handleFileInput(e) {
                const file = e.target.files?.[0];
                if (file) this.uploadSelectedFile(file);
            },

            handleFileDrop(e) {
                const file = e.dataTransfer.files?.[0];
                if (file) this.uploadSelectedFile(file);
            },

            async uploadSelectedFile(file) {
                if (!this.fileUploader) return;
                this.isUploading = true;
                this.uploadProgress = 0;
                try {
                    await this.fileUploader.upload(file, (p) => { this.uploadProgress = p; });
                    this.showToast('Файл успешно загружен');
                    this.loadFiles();
                } catch (e) {
                    alert('Ошибка загрузки: ' + e.message);
                } finally {
                    this.isUploading = false;
                }
            },

            // ─── AI Assistant ────────────────
            async askAi() {
                const prompt = this.newAiMessage?.trim();
                if (!prompt || this.isAiLoading) return;
                this.newAiMessage = '';

                const userMsg = { id: 'msg-' + Date.now(), role: 'user', text: prompt };
                this.aiMessages.push(userMsg);
                this.isAiLoading = true;
                this.$nextTick(() => {
                    if (this.$refs.aiMessages) this.$refs.aiMessages.scrollTop = this.$refs.aiMessages.scrollHeight;
                });

                try {
                    const recentHistory = this.aiMessages.slice(-10).map(m => ({
                        role: m.role,
                        text: m.text,
                    }));

                    const res = await fetch(`/classroom/${this.config.lessonId}/ai-chat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            message: prompt,
                            history: recentHistory,
                        }),
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.aiMessages.push({
                            id: 'msg-' + Date.now() + '-reply',
                            role: 'assistant',
                            text: data.reply || data.response || 'Готово!',
                            actions: data.actions || [],
                            created_entities: data.created_entities || null,
                        });

                        // Show visual toasts and refresh tab data if entities were created
                        if (data.created_entities?.homework?.length) {
                            this.showToast('✅ Назначено ДЗ: ' + data.created_entities.homework[0].title);
                        }
                        if (data.created_entities?.gaps?.length) {
                            this.showToast('⚠️ Пробел зафиксирован: ' + data.created_entities.gaps[0].topic);
                        }
                        if (data.created_entities?.notes?.length) {
                            this.showToast('📌 Заметка сохранена в конспект');
                            if (typeof this.loadNotes === 'function') this.loadNotes();
                        }
                    } else {
                        const err = await res.json().catch(() => ({}));
                        this.aiMessages.push({
                            id: 'msg-' + Date.now() + '-err',
                            role: 'assistant',
                            text: err.error || 'Не удалось получить ответ от ассистента.',
                        });
                    }
                } catch (e) {
                    this.aiMessages.push({
                        id: 'msg-' + Date.now() + '-conn',
                        role: 'assistant',
                        text: 'Ошибка соединения с ИИ-сервером.',
                    });
                } finally {
                    this.isAiLoading = false;
                }
                this.$nextTick(() => {
                    if (this.$refs.aiMessages) this.$refs.aiMessages.scrollTop = this.$refs.aiMessages.scrollHeight;
                });
            },

            // ─── Tutor Reports & Homework ────
            async submitReport() {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/report`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.report),
                    });
                    if (res.ok) this.showToast('Отчёт успешно сохранён!');
                } catch (e) {}
            },

            async assignHomework() {
                try {
                    const res = await fetch(`/classroom/${this.config.lessonId}/homework`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.homework),
                    });
                    if (res.ok) {
                        this.showToast('Домашнее задание назначено!');
                        this.homework = { title: '', description: '', due_date: '' };
                    }
                } catch (e) {}
            },

            // ─── Session Termination ─────────
            async confirmEndSession() {
                this.liveKit?.close();
                this.p2p?.close();
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/classroom/${this.config.lessonId}/end`;
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = this.config.csrfToken;
                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            },

            leaveSession() {
                if (confirm('Выйти из виртуального класса?')) {
                    this.liveKit?.close();
                    this.p2p?.close();
                    window.location.href = '/admin';
                }
            },

            // ─── Theme & Diagnostics Helpers ─
            toggleTheme() {
                this.isLightTheme = !this.isLightTheme;
                localStorage.setItem('cr-theme', this.isLightTheme ? 'light' : 'dark');
                this.applyTheme();
                if (window.__onExcalidrawThemeToggle) {
                    window.__onExcalidrawThemeToggle(!this.isLightTheme);
                }
                this.whiteboard?.redraw();
            },

            applyTheme() {
                if (this.isLightTheme) {
                    document.body.classList.add('theme-light');
                } else {
                    document.body.classList.remove('theme-light');
                }
            },

            showToast(msg) {
                this.toastMessage = msg;
                setTimeout(() => { if (this.toastMessage === msg) this.toastMessage = ''; }, 4500);
            },

            copyStudentLink() {
                const url = this.config.studentInviteUrl || (window.location.origin + '/classroom/' + this.config.lessonId);
                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.showToast('Ссылка скопирована! Ученик войдёт в 1 клик.');
                    }).catch(() => prompt('Ссылка для ученика:', url));
                } else {
                    prompt('Ссылка для ученика:', url);
                }
            },

            async restartConnection() {
                this.showToast('Переподключение к видеосвязи...');
                if (this.liveKit) {
                    try {
                        this.liveKit._initRoom();
                        await this.liveKit.start();
                        this.showToast('Связь LiveKit Cloud перезапущена');
                    } catch (e) {
                        this.showToast('Ошибка: ' + e.message);
                    }
                } else if (this.p2p) {
                    await this.p2p.restartConnection();
                }
            },

            copyDiagLogs() {
                const summary = [
                    '=== EDUSFERA LIVEKIT WEBRTC REPORT ===',
                    'Timestamp: ' + new Date().toISOString(),
                    'Engine: ' + (this.isLiveKit ? 'LiveKit Cloud SFU' : 'P2P WebRTC'),
                    'Server: ' + this.config.liveKitWsUrl,
                    'Room: lesson_' + this.config.lessonId,
                    'Role: ' + this.config.userRole,
                    'Connected: ' + this.isConnected,
                    'Peer connected: ' + this.remoteConnected,
                    'Mic on: ' + this.isMicOn + ' | Cam on: ' + this.isCameraOn,
                    'Logs:\n' + (this.diagLogs || []).map(l => `[${l.time}] [${l.level}] ${l.message}`).join('\n')
                ].join('\n');

                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(summary).then(() => this.showToast('Отчет скопирован!'));
                } else {
                    prompt('Отчет диагностики:', summary);
                }
            }
        };
    }
</script>

</body>
</html>
