<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Виртуальный класс — Edusfera</title>
    <meta name="description" content="Виртуальный класс Edusfera — проведение онлайн-урока с видео, доской и чатом">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/classroom.css', 'resources/js/classroom.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="cr-body">

<div x-data="classroom()"
     x-init="init()"
     class="cr-layout"
     id="classroom-root">

    <!-- ═══════════════════════════════════════ -->
    <!-- HEADER                                 -->
    <!-- ═══════════════════════════════════════ -->
    <header class="cr-header">
        <div class="cr-header-left">
            <a href="{{ url('/admin') }}" class="cr-header-back" title="Назад в кабинет">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
            <div class="cr-lesson-info">
                <p class="cr-lesson-title">{{ $lesson->tutor->name ?? 'Преподаватель' }}</p>
                <span class="cr-lesson-subject">{{ $lesson->subject ?? '' }}</span>
            </div>
        </div>

        <div class="cr-header-center">
            <div class="cr-status-badge"
                 :class="isConnecting ? 'cr-status-badge--waiting' : 'cr-status-badge--active'">
                <span class="cr-status-dot"
                      :class="isConnecting ? 'cr-status-dot--waiting' : 'cr-status-dot--active'"></span>
                <span x-text="isConnecting ? 'Подключение...' : 'Урок идёт'"></span>
            </div>
        </div>

        <div class="cr-header-right">
            <button class="cr-theme-toggle" @click="toggleTheme()" title="Переключить тему" x-cloak>
                <template x-if="isLightTheme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </template>
                <template x-if="!isLightTheme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </template>
            </button>
            <div class="cr-timer" x-text="timerDisplay"></div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════ -->
    <!-- CONTENT (Main + Sidebar)               -->
    <!-- ═══════════════════════════════════════ -->
    <div class="cr-content">

        <!-- ─── Main Video / Whiteboard ─────── -->
        <main class="cr-main">

            <!-- Connecting Skeleton -->
            <template x-if="isConnecting">
                <div class="cr-video-grid" data-participants="1">
                    <div class="cr-video-tile cr-connecting" style="min-height: 300px;"></div>
                </div>
            </template>

            <!-- Video Grid Wrapper -->
            <div x-show="!isConnecting && !isWhiteboardFullscreen"
                 class="cr-video-wrapper"
                 :style="(isWhiteboardActive || isWorkspaceActive) ? 'height: ' + videoHeight + 'px; flex: none;' : 'flex: 1; min-height: 100px;'">
                
                <div class="cr-video-grid"
                     data-participants="2"
                     style="height: 100%;">

                    <!-- Tutor Video Tile -->
                    <div class="cr-video-tile" :class="{ 'cr-speaking': tutorPeer ? tutorPeer.speaking : false }">
                        <template x-if="tutorPeer">
                            <div style="width: 100%; height: 100%; position: relative;">
                                <template x-if="tutorPeer.isLocal">
                                    <div style="width: 100%; height: 100%;">
                                        <template x-if="isCameraOn">
                                            <video x-ref="localVideo" autoplay muted playsinline></video>
                                        </template>
                                        <template x-if="!isCameraOn">
                                            <div class="cr-video-avatar">
                                                <div class="cr-video-avatar-circle">
                                                    <span x-text="config.userName?.charAt(0)?.toUpperCase() || 'Р'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!tutorPeer.isLocal">
                                    <div style="width: 100%; height: 100%;">
                                        <template x-if="tutorPeer.video !== false">
                                            <video :id="'video-' + tutorPeer.id" autoplay playsinline x-init="$el.srcObject = peerStreams[tutorPeer.id]"></video>
                                        </template>
                                        <template x-if="tutorPeer.video === false">
                                            <div class="cr-video-avatar">
                                                <div class="cr-video-avatar-circle">
                                                    <span x-text="tutorPeer.name?.charAt(0)?.toUpperCase() || 'Р'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <span class="cr-video-name" x-text="tutorPeer.isLocal ? 'Вы (Преподаватель)' : (tutorPeer.name || 'Преподаватель')"></span>
                                <span class="cr-video-mic" x-show="tutorPeer.isLocal ? !isMicOn : tutorPeer.audio === false" x-cloak>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                        <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path>
                                        <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"></path>
                                        <line x1="12" y1="19" x2="12" y2="23"></line>
                                        <line x1="8" y1="23" x2="16" y2="23"></line>
                                    </svg>
                                </span>
                            </div>
                        </template>
                        <template x-if="!tutorPeer">
                            <div class="cr-video-avatar cr-avatar-waiting">
                                <div class="cr-video-avatar-circle">
                                    <span>Р</span>
                                </div>
                                <span class="cr-video-name">Преподаватель (ожидание...)</span>
                            </div>
                        </template>
                    </div>

                    <!-- Student Video Tile -->
                    <div class="cr-video-tile" :class="{ 'cr-speaking': studentPeer ? studentPeer.speaking : false }">
                        <template x-if="studentPeer">
                            <div style="width: 100%; height: 100%; position: relative;">
                                <template x-if="studentPeer.isLocal">
                                    <div style="width: 100%; height: 100%;">
                                        <template x-if="isCameraOn">
                                            <video x-ref="localVideo" autoplay muted playsinline></video>
                                        </template>
                                        <template x-if="!isCameraOn">
                                            <div class="cr-video-avatar">
                                                <div class="cr-video-avatar-circle">
                                                    <span x-text="config.userName?.charAt(0)?.toUpperCase() || 'У'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!studentPeer.isLocal">
                                    <div style="width: 100%; height: 100%;">
                                        <template x-if="studentPeer.video !== false">
                                            <video :id="'video-' + studentPeer.id" autoplay playsinline x-init="$el.srcObject = peerStreams[studentPeer.id]"></video>
                                        </template>
                                        <template x-if="studentPeer.video === false">
                                            <div class="cr-video-avatar">
                                                <div class="cr-video-avatar-circle">
                                                    <span x-text="studentPeer.name?.charAt(0)?.toUpperCase() || 'У'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <span class="cr-video-name" x-text="studentPeer.isLocal ? 'Вы (Ученик)' : (studentPeer.name || 'Ученик')"></span>
                                <span class="cr-video-mic" x-show="studentPeer.isLocal ? !isMicOn : studentPeer.audio === false" x-cloak>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                        <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path>
                                        <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"></path>
                                        <line x1="12" y1="19" x2="12" y2="23"></line>
                                        <line x1="8" y1="23" x2="16" y2="23"></line>
                                    </svg>
                                </span>
                            </div>
                        </template>
                        <template x-if="!studentPeer">
                            <div class="cr-video-avatar cr-avatar-waiting">
                                <div class="cr-video-avatar-circle">
                                    <span>У</span>
                                </div>
                                <span class="cr-video-name">Ученик (ожидание...)</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Resizer Handle -->
            <div class="cr-resizer"
                 x-show="!isConnecting && !isWhiteboardFullscreen && (isWhiteboardActive || isWorkspaceActive)"
                 @mousedown="startResize($event)"
                 :class="{ 'cr-resizing': isResizing }"
                 x-cloak>
                <div class="cr-resizer-line"></div>
            </div>

            <!-- Whiteboard -->
            <div x-show="isWhiteboardActive"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="cr-whiteboard-container"
                 style="flex: 1; min-height: 0;">

                <!-- Whiteboard Toolbar -->
                <div class="cr-whiteboard-tools">
                    <!-- Drawing Tools -->
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'pen' }"
                            @click="setWbTool('pen')" title="Карандаш">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                        </svg>
                    </button>
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'eraser' }"
                            @click="setWbTool('eraser')" title="Ластик">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 20H7L3 16c-.8-.8-.8-2 0-2.8L14.6 1.6c.8-.8 2-.8 2.8 0L21 5.2c.8.8.8 2 0 2.8L11 18"></path>
                        </svg>
                    </button>
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'text' }"
                            @click="setWbTool('text')" title="Текст">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="4 7 4 4 20 4 20 7"></polyline>
                            <line x1="9" y1="20" x2="15" y2="20"></line>
                            <line x1="12" y1="4" x2="12" y2="20"></line>
                        </svg>
                    </button>

                    <div class="cr-wb-separator"></div>

                    <!-- Shape Tools -->
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'line' }"
                            @click="setWbTool('line')" title="Линия">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <line x1="5" y1="19" x2="19" y2="5"></line>
                        </svg>
                    </button>
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'rect' }"
                            @click="setWbTool('rect')" title="Прямоугольник">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        </svg>
                    </button>
                    <button class="cr-wb-tool" :class="{ 'active': wbTool === 'circle' }"
                            @click="setWbTool('circle')" title="Круг">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                        </svg>
                    </button>

                    <div class="cr-wb-separator"></div>

                    <!-- Colors -->
                    <template x-for="c in wbColors" :key="c">
                        <button class="cr-wb-color"
                                :class="{ 'active': wbColor === c }"
                                :style="'background-color:' + c"
                                @click="wbColor = c; if(whiteboard) whiteboard.color = c"
                                :title="c"></button>
                    </template>

                    <div class="cr-wb-separator"></div>

                    <!-- Line Width -->
                    <button class="cr-wb-width" :class="{ 'active': wbLineWidth === 1 }"
                            @click="setWbWidth(1)" title="Тонкая">
                        <span class="cr-wb-width-dot" style="width:3px;height:3px"></span>
                    </button>
                    <button class="cr-wb-width" :class="{ 'active': wbLineWidth === 3 }"
                            @click="setWbWidth(3)" title="Средняя">
                        <span class="cr-wb-width-dot" style="width:6px;height:6px"></span>
                    </button>
                    <button class="cr-wb-width" :class="{ 'active': wbLineWidth === 5 }"
                            @click="setWbWidth(5)" title="Толстая">
                        <span class="cr-wb-width-dot" style="width:10px;height:10px"></span>
                    </button>

                    <div class="cr-wb-separator"></div>

                    <!-- Actions -->
                    <button class="cr-wb-tool" @click="wbUndo()" title="Отменить">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"></polyline>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                    </button>
                    <button class="cr-wb-tool" @click="wbClear()" title="Очистить">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>

                <canvas x-ref="whiteboard" class="cr-whiteboard"></canvas>
            </div>

            <!-- Interactive Workspace (Kanban Board) -->
            <div x-show="isWorkspaceActive"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="cr-workspace-container"
                 style="flex: 1; min-height: 0;">
                 
                 <div class="cr-workspace-header">
                     <div class="cr-workspace-title-row">
                         <svg class="cr-workspace-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                             <rect x="3" y="3" width="6" height="18" rx="1"></rect>
                             <rect x="15" y="3" width="6" height="18" rx="1"></rect>
                             <rect x="9" y="3" width="6" height="18" rx="1"></rect>
                         </svg>
                         <h3 class="cr-workspace-title">Интерактивное рабочее пространство</h3>
                     </div>
                 </div>

                 <!-- Kanban Board -->
                 <div class="cr-board" id="workspace-board">
                     <!-- Columns -->
                     <template x-for="(column, colIdx) in workspaceBoard.columns" :key="column.id">
                         <div class="cr-board-column" 
                              :data-column-id="column.id"
                              draggable="true"
                              @dragstart="handleColDragStart($event, column.id)"
                              @dragover.prevent
                              @drop="handleColDrop($event, column.id)">
                              
                             <div class="cr-column-header">
                                 <span class="cr-column-title-input" x-text="column.title"></span>
                                 <button class="cr-column-btn" @click="deleteColumn(column.id)" title="Удалить колонку">
                                     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                         <polyline points="3 6 5 6 21 6"></polyline>
                                         <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                     </svg>
                                 </button>
                             </div>

                             <!-- Cards List -->
                             <div class="cr-column-cards"
                                  :data-column-id="column.id"
                                  @dragover.prevent="handleCardDragOver($event)"
                                  @dragleave="handleCardDragLeave($event)"
                                  @drop="handleCardDrop($event, column.id)">
                                  
                                 <template x-for="(card, cardIdx) in column.cards" :key="card.id">
                                     <div class="cr-card" 
                                          :data-card-id="card.id"
                                          :data-column-id="column.id"
                                          draggable="true"
                                          @dragstart="handleCardDragStart($event, card.id, column.id)">
                                          
                                         <div class="cr-card-header">
                                             <span class="cr-card-badge" :class="'cr-card-badge--' + card.type" x-text="getCardTypeName(card.type)"></span>
                                             <div class="cr-card-actions">
                                                 <button class="cr-column-btn" @click="deleteCard(column.id, card.id)" title="Удалить карточку">
                                                     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                         <polyline points="3 6 5 6 21 6"></polyline>
                                                         <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                     </svg>
                                                 </button>
                                             </div>
                                         </div>

                                         <div style="margin-bottom: 8px;">
                                             <input type="text" 
                                                    class="cr-card-title-input" 
                                                    :value="card.title"
                                                    @change="updateCardTitle(card.id, $event.target.value)"
                                                    placeholder="Заголовок карточки">
                                         </div>

                                         <!-- Card Type Specific Layouts -->
                                         
                                         <!-- Note / Code Type -->
                                         <template x-if="card.type === 'note' || card.type === 'code'">
                                             <textarea class="cr-card-textarea" 
                                                       rows="4"
                                                       :value="card.content || ''"
                                                       @change="updateCardContent(card.id, $event.target.value)"
                                                       :placeholder="card.type === 'code' ? 'Вставьте код...' : 'Текст заметки...'"></textarea>
                                         </template>

                                         <!-- Checklist Type -->
                                         <template x-if="card.type === 'checklist'">
                                             <div class="cr-card-checklist">
                                                 <template x-for="(item, itemIdx) in (card.items || [])" :key="item.id">
                                                     <div class="cr-checklist-item">
                                                         <input type="checkbox" 
                                                                class="cr-checklist-checkbox" 
                                                                :checked="item.completed"
                                                                @change="toggleChecklistItem(card.id, item.id, $event.target.checked)">
                                                         <input type="text" 
                                                                class="cr-checklist-text"
                                                                :class="{ 'completed': item.completed }"
                                                                :value="item.text"
                                                                @change="updateChecklistItemText(card.id, item.id, $event.target.value)">
                                                         <button class="cr-checklist-btn-delete" @click="deleteChecklistItem(card.id, item.id)">
                                                             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                 <line x1="18" y1="6" x2="6" y2="18"></line>
                                                                 <line x1="6" y1="6" x2="18" y2="18"></line>
                                                             </svg>
                                                         </button>
                                                     </div>
                                                 </template>
                                                 
                                                 <!-- Add Checklist Item -->
                                                 <div class="cr-checklist-add-row" x-data="{ newItemText: '' }">
                                                     <input type="text" 
                                                            class="cr-checklist-add-input" 
                                                            placeholder="Новый пункт..." 
                                                            x-model="newItemText"
                                                            @keydown.enter="if(newItemText.trim()) { addChecklistItem(card.id, newItemText); newItemText = ''; }">
                                                     <button class="cr-checklist-add-btn" 
                                                             @click="if(newItemText.trim()) { addChecklistItem(card.id, newItemText); newItemText = ''; }">+</button>
                                                 </div>
                                             </div>
                                         </template>

                                         <!-- Timer Type -->
                                         <template x-if="card.type === 'timer'">
                                             <div class="cr-card-timer" x-data="cardTimer(card)">
                                                 <div class="cr-timer-digits" x-text="formatTime()"></div>
                                                 <div class="cr-timer-controls">
                                                     <button class="cr-timer-btn" @click="toggleTimer()" x-text="running ? 'Пауза' : 'Старт'"></button>
                                                     <button class="cr-timer-btn" @click="resetTimer()">Сброс</button>
                                                 </div>
                                             </div>
                                         </template>

                                     </div>
                                 </template>
                             </div>

                             <!-- Add Card Action Menu -->
                             <div class="cr-card-add-menu">
                                 <button class="cr-card-add-type-btn" @click="addCard(column.id, 'note')">+ Заметка</button>
                                 <button class="cr-card-add-type-btn" @click="addCard(column.id, 'checklist')">+ Список</button>
                                 <button class="cr-card-add-type-btn" @click="addCard(column.id, 'timer')">+ Таймер</button>
                             </div>
                         </div>
                     </template>

                     <!-- Add Column Button -->
                     <button class="cr-column-add-btn" @click="addColumn()">
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                             <line x1="12" y1="5" x2="12" y2="19"></line>
                             <line x1="5" y1="12" x2="19" y2="12"></line>
                         </svg>
                         <span>Добавить колонку</span>
                     </button>
                 </div>
            </div>
        </main>

        <!-- ─── Sidebar ─────────────────────── -->
        <aside class="cr-sidebar"
               x-show="isSidebarOpen"
               x-cloak
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 translate-x-4"
               x-transition:enter-end="opacity-100 translate-x-0"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="opacity-100 translate-x-0"
               x-transition:leave-end="opacity-0 translate-x-4"
               style="transform: translateX(0)">

            <!-- Tab Switcher -->
            <div class="cr-sidebar-tabs">
                <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'chat' }"
                        @click="activeSidebarTab = 'chat'">
                    <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Чат</span>
                </button>
                <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'notes' }"
                        @click="activeSidebarTab = 'notes'">
                    <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Заметки</span>
                </button>
                <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'files' }"
                        @click="activeSidebarTab = 'files'">
                    <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Файлы</span>
                </button>
                <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'ai' }"
                        @click="activeSidebarTab = 'ai'">
                    <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <circle cx="12" cy="5" r="2"></circle>
                        <path d="M12 7v4"></path>
                        <line x1="8" y1="16" x2="8" y2="16"></line>
                        <line x1="16" y1="16" x2="16" y2="16"></line>
                    </svg>
                    <span>ИИ</span>
                </button>
                @if(auth()->id() === $lesson->tutor_id)
                    <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'profile' }"
                            @click="activeSidebarTab = 'profile'">
                        <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Профиль</span>
                    </button>
                    <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'report' }"
                            @click="activeSidebarTab = 'report'">
                        <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>Отчёт</span>
                    </button>
                    <button class="cr-sidebar-tab" :class="{ 'active': activeSidebarTab === 'homework' }"
                            @click="activeSidebarTab = 'homework'">
                        <svg class="cr-sidebar-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span>ДЗ</span>
                    </button>
                @endif
            </div>

            <!-- ── Chat Tab ──────────────────── -->
            <div x-show="activeSidebarTab === 'chat'" class="cr-sidebar-content" x-cloak>
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
                    <div x-show="chatMessages.length === 0" class="cr-profile-empty">
                        Сообщений пока нет. Напишите первым!
                    </div>
                </div>
                <div class="cr-chat-input">
                    <input type="text"
                           x-model="newMessage"
                           @keydown.enter.prevent="sendMessage()"
                           placeholder="Написать сообщение..."
                           id="chat-input">
                    <button class="cr-chat-send" @click="sendMessage()" title="Отправить">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ── Notes Tab ─────────────────── -->
            <div x-show="activeSidebarTab === 'notes'" class="cr-sidebar-content" x-cloak>
                <h4 class="cr-sidebar-title">Заметки к уроку</h4>
                <div class="cr-notes-list">
                    <template x-for="note in notes" :key="note.id">
                        <div class="cr-note-item">
                            <p class="cr-note-text" x-text="note.text"></p>
                            <span class="cr-note-time" x-text="note.time"></span>
                        </div>
                    </template>
                    <div x-show="notes.length === 0" class="cr-profile-empty">
                        Заметок пока нет
                    </div>
                </div>
                <div class="cr-note-form">
                    <textarea class="cr-form-textarea" x-model="newNote"
                              placeholder="Добавить заметку..." rows="3"></textarea>
                    <button class="cr-btn-primary" style="margin-top:8px;width:100%"
                            @click="saveNote()" :disabled="!newNote.trim()">Сохранить</button>
                </div>
            </div>

            <!-- ── Files Tab ─────────────────── -->
            <div x-show="activeSidebarTab === 'files'" class="cr-sidebar-content" x-cloak>
                <h4 class="cr-sidebar-title">Файлы</h4>

                <!-- Drop Zone -->
                <div class="cr-file-drop"
                     @dragover.prevent="$el.classList.add('dragover')"
                     @dragleave="$el.classList.remove('dragover')"
                     @drop.prevent="$el.classList.remove('dragover'); handleFileDrop($event)">
                    <div class="cr-file-drop-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <span>Перетащите файлы сюда</span>
                </div>

                <!-- Upload Progress -->
                <div x-show="isUploading" x-cloak class="cr-file-progress">
                    <div class="cr-file-progress-bar" :style="'width:' + uploadProgress + '%'"></div>
                </div>

                <!-- File List -->
                <div class="cr-files-list">
                    <template x-for="file in files" :key="file.id">
                        <a :href="file.url" target="_blank" class="cr-file-item">
                            <div class="cr-file-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div class="cr-file-info">
                                <p class="cr-file-name" x-text="file.name"></p>
                                <p class="cr-file-size" x-text="file.size"></p>
                            </div>
                        </a>
                    </template>
                    <div x-show="files.length === 0 && !isUploading" class="cr-profile-empty">
                        Файлов пока нет
                    </div>
                </div>
            </div>

            <!-- ── AI Tab ────────────────────── -->
            <div x-show="activeSidebarTab === 'ai'" class="cr-sidebar-content" x-cloak>
                <h4 class="cr-sidebar-title">ИИ-Ассистент</h4>
                <div class="cr-chat-messages" x-ref="aiMessages" style="flex: 1; overflow-y: auto; margin-bottom: 8px;">
                    <template x-for="msg in aiMessages" :key="msg.id">
                        <div class="cr-chat-message" :class="{ 'mine': msg.role === 'user' }">
                            <div class="cr-chat-avatar" :style="msg.role === 'assistant' ? 'background: linear-gradient(135deg, #7D39EB, #C6FF33); color: #fff;' : ''" x-text="msg.role === 'user' ? 'Вы' : 'ИИ'"></div>
                            <div class="cr-chat-body">
                                <div class="cr-chat-sender" x-text="msg.role === 'user' ? 'Вы' : 'Ассистент'"></div>
                                <div class="cr-chat-bubble" x-text="msg.text"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="aiMessages.length === 0" class="cr-profile-empty">
                        Спросите ИИ о теме урока или попросите изменить интерактивную доску.
                    </div>
                </div>
                
                <!-- Quick Prompts -->
                <div class="cr-ai-prompts" style="display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; margin-bottom: 8px;">
                    <button class="cr-checklist-add-btn" style="font-size: 11px; padding: 4px 8px; height: auto;" @click="sendAiPrompt('Добавь чек-лист подготовки к экзамену')">📝 Список подготовки</button>
                    <button class="cr-checklist-add-btn" style="font-size: 11px; padding: 4px 8px; height: auto;" @click="sendAiPrompt('Создай колонку План урока')">📊 Создать колонку</button>
                    <button class="cr-checklist-add-btn" style="font-size: 11px; padding: 4px 8px; height: auto;" @click="sendAiPrompt('Добавь таймер на 5 минут')">⏱️ Таймер 5 мин</button>
                </div>

                <div class="cr-chat-input" x-data="{ isSending: false }">
                    <input type="text"
                           x-model="newAiMessage"
                           @keydown.enter.prevent="if(!isSending && newAiMessage.trim()) { isSending = true; sendAiChat().finally(() => isSending = false); }"
                           placeholder="Спросить ИИ..."
                           :disabled="isSending"
                           id="ai-chat-input">
                    <button class="cr-chat-send" @click="if(!isSending && newAiMessage.trim()) { isSending = true; sendAiChat().finally(() => isSending = false); }" :disabled="isSending" title="Отправить">
                        <template x-if="!isSending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </template>
                        <template x-if="isSending">
                            <span class="cr-status-dot cr-status-dot--waiting" style="display: inline-block;"></span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- ── Student Profile Tab (tutor only) ── -->
            @if(auth()->id() === $lesson->tutor_id)
                <div x-show="activeSidebarTab === 'profile'" class="cr-sidebar-content" style="overflow-y:auto" x-cloak>
                    <h4 class="cr-sidebar-title">Профиль ученика</h4>

                    <template x-if="studentProfile">
                        <div>
                            <div class="cr-profile-section">
                                <p class="cr-profile-label">Цели</p>
                                <p class="cr-profile-value" x-text="studentProfile.goals || 'Не указаны'"></p>
                            </div>
                            <div class="cr-profile-section">
                                <p class="cr-profile-label">Прогресс</p>
                                <p class="cr-profile-value" x-text="studentProfile.progress || 'Нет данных'"></p>
                            </div>
                            <div class="cr-profile-section">
                                <p class="cr-profile-label">Пробелы</p>
                                <div class="cr-profile-tags">
                                    <template x-for="gap in (studentProfile.skill_gaps || [])" :key="gap">
                                        <span class="cr-profile-tag" x-text="gap"></span>
                                    </template>
                                </div>
                                <p x-show="!studentProfile.skill_gaps?.length" class="cr-profile-value">Не определены</p>
                            </div>
                            <div class="cr-profile-section">
                                <p class="cr-profile-label">Последняя диагностика</p>
                                <p class="cr-profile-value" x-text="studentProfile.last_diagnostic || 'Не проводилась'"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!studentProfile">
                        <div class="cr-profile-empty">Загрузка профиля...</div>
                    </template>
                </div>

                <!-- ── Report Tab (tutor only) ────── -->
                <div x-show="activeSidebarTab === 'report'" class="cr-sidebar-content" style="overflow-y:auto" x-cloak>
                    <h4 class="cr-sidebar-title">Отчёт по уроку</h4>

                    <div class="cr-form-group">
                        <label class="cr-form-label">Краткое резюме</label>
                        <textarea class="cr-form-textarea" x-model="report.summary"
                                  placeholder="Что было пройдено на уроке..." rows="3"></textarea>
                    </div>
                    <div class="cr-form-group">
                        <label class="cr-form-label">Фокус занятия</label>
                        <input class="cr-form-input" x-model="report.focus"
                               placeholder="Основная тема...">
                    </div>
                    <div class="cr-form-group">
                        <label class="cr-form-label">Следующие шаги</label>
                        <textarea class="cr-form-textarea" x-model="report.next_steps"
                                  placeholder="На что обратить внимание..." rows="3"></textarea>
                    </div>
                    <div class="cr-form-group">
                        <label class="cr-form-label">Оценка (1-10)</label>
                        <select class="cr-form-select" x-model.number="report.score">
                            <template x-for="i in 10" :key="i">
                                <option :value="i" x-text="i"></option>
                            </template>
                        </select>
                    </div>
                    <button class="cr-btn-primary" style="width:100%" @click="submitReport()">
                        Сохранить отчёт
                    </button>
                </div>

                <!-- ── Homework Tab (tutor only) ──── -->
                <div x-show="activeSidebarTab === 'homework'" class="cr-sidebar-content" style="overflow-y:auto" x-cloak>
                    <h4 class="cr-sidebar-title">Домашнее задание</h4>

                    <!-- Existing homework -->
                    <div class="cr-notes-list" style="margin-bottom:16px">
                        <template x-for="hw in existingHomework" :key="hw.id">
                            <div class="cr-hw-item">
                                <p class="cr-hw-title" x-text="hw.title"></p>
                                <div class="cr-hw-meta">
                                    <span class="cr-hw-due" x-text="'Срок: ' + hw.due_date"></span>
                                    <span class="cr-hw-status"
                                          :class="hw.status === 'done' ? 'cr-hw-status--done' : 'cr-hw-status--pending'"
                                          x-text="hw.status === 'done' ? 'Выполнено' : 'В работе'"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="existingHomework.length === 0" class="cr-profile-empty">
                            ДЗ не назначено
                        </div>
                    </div>

                    <!-- Quick assign form -->
                    <div style="border-top: 1px solid var(--cr-border); padding-top: 12px;">
                        <p class="cr-profile-label" style="margin-bottom: 10px;">Назначить ДЗ</p>
                        <div class="cr-form-group">
                            <input class="cr-form-input" x-model="homework.title"
                                   placeholder="Название задания">
                        </div>
                        <div class="cr-form-group">
                            <textarea class="cr-form-textarea" x-model="homework.description"
                                      placeholder="Описание..." rows="3"></textarea>
                        </div>
                        <div class="cr-form-group">
                            <label class="cr-form-label">Срок сдачи</label>
                            <input class="cr-form-input" type="date" x-model="homework.due_date">
                        </div>
                        <button class="cr-btn-primary" style="width:100%" @click="assignHomework()">
                            Назначить
                        </button>
                    </div>
                </div>
            @endif
        </aside>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- TOOLBAR                                -->
    <!-- ═══════════════════════════════════════ -->
    <footer class="cr-toolbar">
        <div class="cr-toolbar-left">
            <!-- Microphone -->
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isMicOn }"
                    @click="toggleMic()"
                    id="btn-mic">
                <span class="cr-toolbar-btn-icon">
                    <template x-if="isMicOn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                    </template>
                    <template x-if="!isMicOn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                            <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path>
                            <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2c0 .76-.13 1.49-.35 2.17"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                    </template>
                </span>
                <span x-text="isMicOn ? 'Микрофон' : 'Выкл'"></span>
            </button>

            <!-- Camera -->
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isCameraOn }"
                    @click="toggleCamera()"
                    id="btn-camera">
                <span class="cr-toolbar-btn-icon">
                    <template x-if="isCameraOn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 7l-7 5 7 5V7z"></path>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                    </template>
                    <template x-if="!isCameraOn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34l1 1L23 7v10"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </template>
                </span>
                <span x-text="isCameraOn ? 'Камера' : 'Выкл'"></span>
            </button>

            <!-- Screen Share -->
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isScreenSharing }"
                    @click="toggleScreenShare()"
                    id="btn-screen">
                <span class="cr-toolbar-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </span>
                <span>Экран</span>
            </button>

            <!-- Whiteboard -->
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isWhiteboardActive }"
                    @click="toggleWhiteboard()"
                    id="btn-whiteboard">
                <span class="cr-toolbar-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                </span>
                <span>Доска</span>
            </button>

            <!-- Workspace -->
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isWorkspaceActive }"
                    @click="toggleWorkspace()"
                    id="btn-workspace">
                <span class="cr-toolbar-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="6" height="18" rx="1"></rect>
                        <rect x="15" y="3" width="6" height="18" rx="1"></rect>
                        <rect x="9" y="3" width="6" height="18" rx="1"></rect>
                    </svg>
                </span>
                <span>Workspace</span>
            </button>
        </div>

        <div class="cr-toolbar-center">
            <button class="cr-toolbar-btn"
                    :class="{ 'cr-toolbar-btn--active': isSidebarOpen }"
                    @click="isSidebarOpen = !isSidebarOpen"
                    id="btn-sidebar">
                <span class="cr-toolbar-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </span>
                <span>Панель</span>
            </button>
        </div>

        <div class="cr-toolbar-right">
            @if(auth()->id() === $lesson->tutor_id)
                <button class="cr-toolbar-btn cr-toolbar-btn--danger"
                        @click="showEndModal = true"
                        id="btn-end-session">
                    <span class="cr-toolbar-btn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path>
                            <line x1="23" y1="1" x2="1" y2="23"></line>
                        </svg>
                    </span>
                    <span>Завершить</span>
                </button>
            @else
                <button class="cr-toolbar-btn cr-toolbar-btn--danger"
                        @click="leaveSession()"
                        id="btn-leave">
                    <span class="cr-toolbar-btn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"></path>
                            <line x1="23" y1="1" x2="1" y2="23"></line>
                        </svg>
                    </span>
                    <span>Выйти</span>
                </button>
            @endif
        </div>
    </footer>

    <!-- ═══════════════════════════════════════ -->
    <!-- END SESSION MODAL                      -->
    <!-- ═══════════════════════════════════════ -->
    <div class="cr-modal-overlay"
         x-show="showEndModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showEndModal = false">
        <div class="cr-modal">
            <h3 class="cr-modal-title">Завершить урок?</h3>
            <p class="cr-modal-text">
                Это действие завершит сессию для всех участников.
                Убедитесь, что вы сохранили заметки и отчёт.
            </p>
            <div class="cr-modal-actions">
                <button class="cr-modal-btn cr-modal-btn--cancel"
                        @click="showEndModal = false">Отмена</button>
                <button class="cr-modal-btn cr-modal-btn--confirm"
                        @click="confirmEndSession()">Завершить</button>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════ -->
<!-- DATA & ALPINE COMPONENT                    -->
<!-- ═══════════════════════════════════════════ -->
<script>
    window.__classroom = {
        lessonId: {{ $lesson->id }},
        roomId: '{{ $session->room_id }}',
        mediaToken: '{{ $token }}',
        mediaServerUrl: '{{ $mediaServerUrl }}',
        iceServers: @json($iceServers),
        userId: {{ auth()->id() }},
        userName: @json(auth()->user()->name),
        userRole: '{{ auth()->id() === $lesson->tutor_id ? "tutor" : "student" }}',
        csrfToken: '{{ csrf_token() }}',
    };

    function classroom() {
        return {
            // ─── UI State ───────────────────
            isMicOn: true,
            isCameraOn: true,
            isScreenSharing: false,
            isWhiteboardActive: false,
            isWhiteboardFullscreen: false,
            isSidebarOpen: true,
            activeSidebarTab: 'chat',
            isConnecting: true,
            isSessionActive: false,
            showEndModal: false,
            timerDisplay: '00:00:00',
            localSpeaking: false,
            videoHeight: 180,
            isResizing: false,
            isLightTheme: false,

            // ─── Data ───────────────────────
            peers: {},
            peerStreams: {},
            chatMessages: [],
            newMessage: '',
            notes: [],
            newNote: '',
            files: [],
            uploadProgress: 0,
            isUploading: false,
            aiMessages: [],
            newAiMessage: '',

            // ─── Tutor-only ─────────────────
            studentProfile: null,
            report: { summary: '', focus: '', next_steps: '', score: 5 },
            homework: { title: '', description: '', due_date: '' },
            existingHomework: [],

            // ─── Whiteboard ─────────────────
            wbTool: 'pen',
            wbColor: '#7D39EB',
            wbColors: ['#7D39EB', '#C6FF33', '#ff4d6a', '#33d17a', '#ffffff', '#ffb800'],
            wbLineWidth: 3,

            // ─── Workspace (Kanban) ──────────
            isWorkspaceActive: false,
            workspaceBoard: { columns: [] },
            wsWorkspace: null,
            draggedCardId: null,
            draggedCardColId: null,
            draggedColId: null,

            // ─── Modules ────────────────────
            wsManager: null,
            rtcManager: null,
            whiteboard: null,
            timer: null,
            fileUploader: null,

            // ─── Config ─────────────────────
            config: window.__classroom,

            // ═══ Computed ═══════════════════
            get participantCount() {
                return Object.keys(this.peers).length + 1;
            },

            get tutorPeer() {
                if (this.config.userRole === 'tutor') {
                    return {
                        id: 'local',
                        name: 'Вы (Репетитор)',
                        isLocal: true,
                        video: this.isCameraOn,
                        audio: this.isMicOn,
                        speaking: this.localSpeaking,
                        role: 'tutor'
                    };
                }
                return Object.values(this.peers).find(p => p.role === 'tutor') || null;
            },

            get studentPeer() {
                if (this.config.userRole === 'student') {
                    return {
                        id: 'local',
                        name: 'Вы (Ученик)',
                        isLocal: true,
                        video: this.isCameraOn,
                        audio: this.isMicOn,
                        speaking: this.localSpeaking,
                        role: 'student'
                    };
                }
                return Object.values(this.peers).find(p => p.role === 'student') || null;
            },

            // ═══ Init ═══════════════════════
            async init() {
                window.classroomApp = this;
                // Wait for modules to be available
                await this._waitForModules();

                this.isLightTheme = localStorage.getItem('cr-theme') === 'light';
                this.applyTheme();

                const { WebSocketManager, WebRTCManager, WhiteboardEngine, SessionTimer, FileUploader }
                    = window.ClassroomModules;

                // ─── WebSocket ──────────────
                const wsUrl = this.config.mediaServerUrl.replace(/^http/, 'ws')
                    + '/ws/' + this.config.roomId
                    + '?token=' + this.config.mediaToken
                    + '&userId=' + this.config.userId
                    + '&userName=' + encodeURIComponent(this.config.userName);

                this.wsManager = new WebSocketManager(wsUrl);

                this.wsManager.on('joined', (data) => this.handleJoined(data));
                this.wsManager.on('peer-joined', (data) => this.handlePeerJoined(data));
                this.wsManager.on('peer-left', (data) => this.handlePeerLeft(data));
                this.wsManager.on('offer', (data) => this.rtcManager.handleOffer(data.fromId, data.sdp, data.role, data.name));
                this.wsManager.on('answer', (data) => this.rtcManager.handleAnswer(data.fromId, data.sdp));
                this.wsManager.on('ice-candidate', (data) => this.rtcManager.handleIceCandidate(data.fromId, data.candidate));
                this.wsManager.on('chat', (data) => this.handleChatMessage(data));
                this.wsManager.on('wb', (data) => this.whiteboard?.applyRemoteAction(data));
                this.wsManager.on('wb-history', (data) => {
                    if (data.events && this.whiteboard) {
                        data.events.forEach(evt => this.whiteboard.applyRemoteAction(evt));
                    }
                });
                this.wsManager.on('wb-toggle', (data) => {
                    if (this.isWhiteboardActive !== data.isActive) {
                        this.toggleWhiteboard(false);
                    }
                });
                this.wsManager.on('media-state', (data) => this.handleMediaState(data));
                this.wsManager.on('speaking', (data) => this.handleSpeakingState(data));
                this.wsManager.on('session-ended', () => this.handleSessionEnded());

                this.wsManager.connect();

                // ─── WebRTC ─────────────────
                this.rtcManager = new WebRTCManager(this.wsManager, this.config.iceServers);

                this.rtcManager.onPeerAdded = (id, data) => {
                    this.peers[id] = { id, name: data.name, role: data.role, audio: true, video: true, speaking: false };
                };

                this.rtcManager.onPeerRemoved = (id) => {
                    delete this.peers[id];
                };

                this.rtcManager.onTrack = (peerId, stream) => {
                    console.log('[Classroom] Remote stream obtained for peer:', peerId);
                    this.peerStreams[peerId] = stream;
                    this.$nextTick(() => {
                        const el = document.getElementById('video-' + peerId);
                        if (el) el.srcObject = stream;
                    });
                };

                this.rtcManager.onLocalStream = (stream) => {
                    this.$nextTick(() => {
                        if (this.$refs.localVideo) {
                            this.$refs.localVideo.srcObject = stream;
                        }
                    });
                };

                try {
                    await this.rtcManager.getLocalMedia();
                } catch (e) {
                    console.error('[Classroom] Media access failed:', e);
                    this.isMicOn = false;
                    this.isCameraOn = false;
                }

                // ─── Timer ──────────────────
                this.timer = new SessionTimer((formatted) => {
                    this.timerDisplay = formatted;
                });
                this.timer.start();

                // ─── File Uploader ──────────
                this.fileUploader = new FileUploader(this.config.lessonId, this.config.csrfToken);

                // ─── Mark connected ─────────
                this.isConnecting = false;
                this.isSessionActive = true;

                // ─── Load data ──────────────
                this.loadChatHistory();
                this.loadNotes();
                this.loadFiles();

                if (this.config.userRole === 'tutor') {
                    this.loadStudentProfile();
                    this.loadExistingHomework();
                }

                // ─── Event listeners ────────
                window.addEventListener('resize', () => this.whiteboard?.resize());
                window.addEventListener('beforeunload', (e) => {
                    if (this.isSessionActive) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            },

            async _waitForModules() {
                let attempts = 0;
                while (!window.ClassroomModules && attempts < 50) {
                    await new Promise((r) => setTimeout(r, 100));
                    attempts++;
                }
                if (!window.ClassroomModules) {
                    throw new Error('[Classroom] Modules failed to load');
                }
            },

            startResize(e) {
                this.isResizing = true;
                const startY = e.clientY;
                const startHeight = this.videoHeight;
                
                const doResize = (moveEvent) => {
                    const deltaY = moveEvent.clientY - startY;
                    this.videoHeight = Math.max(100, Math.min(500, startHeight + deltaY));
                    if (this.whiteboard) {
                        this.whiteboard.resize();
                    }
                };
                
                const stopResize = () => {
                    this.isResizing = false;
                    window.removeEventListener('mousemove', doResize);
                    window.removeEventListener('mouseup', stopResize);
                };
                
                window.addEventListener('mousemove', doResize);
                window.addEventListener('mouseup', stopResize);
            },

            applyTheme() {
                if (this.isLightTheme) {
                    document.body.classList.add('theme-light');
                } else {
                    document.body.classList.remove('theme-light');
                }
            },

            toggleTheme() {
                this.isLightTheme = !this.isLightTheme;
                localStorage.setItem('cr-theme', this.isLightTheme ? 'light' : 'dark');
                this.applyTheme();
            },

            // ═══ Media Controls ═════════════
            toggleMic() {
                this.isMicOn = !this.isMicOn;
                this.rtcManager?.toggleAudio(this.isMicOn);
            },

            toggleCamera() {
                this.isCameraOn = !this.isCameraOn;
                this.rtcManager?.toggleVideo(this.isCameraOn);
            },

            async toggleScreenShare() {
                if (!this.rtcManager) return;

                try {
                    if (this.isScreenSharing) {
                        await this.rtcManager.stopScreenShare();
                        this.isScreenSharing = false;
                    } else {
                        await this.rtcManager.startScreenShare();
                        this.isScreenSharing = true;

                        // Auto-detect when user stops via browser UI
                        this.rtcManager.screenStream?.getVideoTracks()[0]?.addEventListener('ended', () => {
                            this.isScreenSharing = false;
                        });
                    }
                } catch (e) {
                    console.error('[Classroom] Screen share error:', e);
                }
            },

            // ═══ Whiteboard ═════════════════
            toggleWhiteboard(broadcast = true) {
                this.isWhiteboardActive = !this.isWhiteboardActive;

                if (this.isWhiteboardActive && !this.whiteboard) {
                    this.$nextTick(() => {
                        const canvas = this.$refs.whiteboard;
                        if (canvas) {
                            const { WhiteboardEngine } = window.ClassroomModules;
                            this.whiteboard = new WhiteboardEngine(canvas, this.wsManager);
                            this.whiteboard.init();
                        }
                    });
                }

                if (broadcast && this.wsManager?.connected) {
                    this.wsManager.send('wb-toggle', { enabled: this.isWhiteboardActive });
                }
            },

            setWbTool(tool) {
                this.wbTool = tool;
                if (this.whiteboard) this.whiteboard.tool = tool;
            },

            setWbWidth(w) {
                this.wbLineWidth = w;
                if (this.whiteboard) this.whiteboard.lineWidth = w;
            },

            wbUndo() {
                this.whiteboard?.undo();
            },

            wbClear() {
                this.whiteboard?.clear();
            },

            // ═══ Workspace (Kanban Board) ═══
            toggleWorkspace() {
                this.isWorkspaceActive = !this.isWorkspaceActive;
                if (this.isWorkspaceActive) {
                    this.isWhiteboardActive = false;
                    this.connectWorkspaceWS();
                }
            },

            connectWorkspaceWS() {
                if (this.wsWorkspace && (this.wsWorkspace.readyState === WebSocket.OPEN || this.wsWorkspace.readyState === WebSocket.CONNECTING)) {
                    return;
                }

                // WebSocket endpoint via Nginx proxy
                const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
                const wsUrl = protocol + window.location.host + '/ws/workspace/' + this.config.roomId
                    + '?token=' + this.config.mediaToken;

                console.log('[Workspace] Connecting WebSocket:', wsUrl);
                this.wsWorkspace = new WebSocket(wsUrl);

                this.wsWorkspace.onopen = () => {
                    console.log('[Workspace] WebSocket connected');
                };

                this.wsWorkspace.onmessage = (event) => {
                    try {
                        const msg = JSON.parse(event.data);
                        if (msg.event === 'workspace.sync') {
                            this.workspaceBoard = msg.payload || { columns: [] };
                        } else if (msg.event === 'workspace.error') {
                            console.error('[Workspace] Error from server:', msg.payload);
                        }
                    } catch (e) {
                        console.error('[Workspace] Failed to parse message:', e);
                    }
                };

                this.wsWorkspace.onerror = (err) => {
                    console.error('[Workspace] WebSocket error:', err);
                };

                this.wsWorkspace.onclose = (event) => {
                    console.log('[Workspace] WebSocket closed:', event.code, event.reason);
                    // Reconnect if workspace is still active
                    if (this.isWorkspaceActive) {
                        setTimeout(() => this.connectWorkspaceWS(), 3000);
                    }
                };
            },

            sendWorkspaceMsg(event, payload) {
                if (this.wsWorkspace && this.wsWorkspace.readyState === WebSocket.OPEN) {
                    this.wsWorkspace.send(JSON.stringify({ event, payload }));
                } else {
                    console.warn('[Workspace] WebSocket not connected, cannot send:', event);
                }
            },

            getCardTypeName(type) {
                const names = {
                    'note': 'Заметка',
                    'checklist': 'Список',
                    'code': 'Код',
                    'timer': 'Таймер'
                };
                return names[type] || type;
            },

            generateUUID() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            },

            addColumn() {
                const title = prompt('Введите название новой колонки:');
                if (!title || !title.trim()) return;

                const columnId = 'col-' + this.generateUUID();
                this.sendWorkspaceMsg('column.add', {
                    columnId: columnId,
                    title: title.trim()
                });
            },

            deleteColumn(columnId) {
                if (!confirm('Вы уверены, что хотите удалить эту колонку и все её карточки?')) return;
                this.sendWorkspaceMsg('column.delete', { columnId });
            },

            addCard(columnId, type) {
                const title = prompt('Введите название карточки:');
                if (!title || !title.trim()) return;

                const cardId = 'card-' + this.generateUUID();
                const card = {
                    id: cardId,
                    type: type,
                    title: title.trim(),
                    content: '',
                    items: []
                };

                if (type === 'timer') {
                    card.meta = { seconds: 300, running: false, lastUpdated: 0 };
                }

                this.sendWorkspaceMsg('card.add', {
                    columnId: columnId,
                    card: card
                });
            },

            deleteCard(columnId, cardId) {
                if (!confirm('Удалить эту карточку?')) return;
                this.sendWorkspaceMsg('card.delete', { cardId, columnId });
            },

            updateCardTitle(cardId, title) {
                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { title: title.trim() }
                });
            },

            updateCardContent(cardId, content) {
                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { content: content }
                });
            },

            // --- Checklist Specific Operations ---
            addChecklistItem(cardId, text) {
                const card = this.findCard(cardId);
                if (!card) return;

                const items = card.items ? [...card.items] : [];
                items.push({
                    id: 'item-' + this.generateUUID(),
                    text: text.trim(),
                    completed: false
                });

                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { items }
                });
            },

            toggleChecklistItem(cardId, itemId, completed) {
                const card = this.findCard(cardId);
                if (!card) return;

                const items = card.items.map(item => {
                    if (item.id === itemId) {
                        return { ...item, completed };
                    }
                    return item;
                });

                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { items }
                });
            },

            updateChecklistItemText(cardId, itemId, text) {
                const card = this.findCard(cardId);
                if (!card) return;

                const items = card.items.map(item => {
                    if (item.id === itemId) {
                        return { ...item, text: text.trim() };
                    }
                    return item;
                });

                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { items }
                });
            },

            deleteChecklistItem(cardId, itemId) {
                const card = this.findCard(cardId);
                if (!card) return;

                const items = card.items.filter(item => item.id !== itemId);
                this.sendWorkspaceMsg('card.update', {
                    cardId,
                    data: { items }
                });
            },

            findCard(cardId) {
                for (const col of this.workspaceBoard.columns) {
                    for (const card of col.cards) {
                        if (card.id === cardId) return card;
                    }
                }
                return null;
            },

            // --- Drag & Drop Operations ---
            handleColDragStart(event, columnId) {
                this.draggedColId = columnId;
                event.dataTransfer.effectAllowed = 'move';
            },

            handleColDrop(event, targetColumnId) {
                if (!this.draggedColId || this.draggedColId === targetColumnId) return;

                const colIdx = this.workspaceBoard.columns.findIndex(c => c.id === targetColumnId);
                if (colIdx !== -1) {
                    this.sendWorkspaceMsg('column.move', {
                        columnId: this.draggedColId,
                        index: colIdx
                    });
                }
                this.draggedColId = null;
            },

            handleCardDragStart(event, cardId, columnId) {
                this.draggedCardId = cardId;
                this.draggedCardColId = columnId;
                event.dataTransfer.effectAllowed = 'move';
                event.target.classList.add('dragging');
            },

            handleCardDragOver(event) {
                event.currentTarget.classList.add('dragover');
            },

            handleCardDragLeave(event) {
                event.currentTarget.classList.remove('dragover');
            },

            handleCardDrop(event, targetColId) {
                event.currentTarget.classList.remove('dragover');
                document.querySelectorAll('.cr-card.dragging').forEach(el => el.classList.remove('dragging'));

                if (!this.draggedCardId) return;

                // Find card position (index) in target column
                let targetIndex = 0;
                const targetCardsContainer = event.currentTarget;
                const targetCards = Array.from(targetCardsContainer.querySelectorAll('.cr-card:not(.dragging)'));
                
                for (let i = 0; i < targetCards.length; i++) {
                    const rect = targetCards[i].getBoundingClientRect();
                    const midY = rect.top + rect.height / 2;
                    if (event.clientY > midY) {
                        targetIndex = i + 1;
                    }
                }

                this.sendWorkspaceMsg('card.move', {
                    cardId: this.draggedCardId,
                    fromColumnId: this.draggedCardColId,
                    toColumnId: targetColId,
                    index: targetIndex
                });

                this.draggedCardId = null;
                this.draggedCardColId = null;
            },

            // ═══ Chat ═══════════════════════
            async sendMessage() {
                const text = this.newMessage.trim();
                if (!text) return;

                const msg = {
                    id: Date.now(),
                    userId: this.config.userId,
                    userName: this.config.userName,
                    text: text,
                    time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
                };

                this.chatMessages.push(msg);
                this.wsManager.send('chat', msg);
                this.newMessage = '';
                this.scrollChatToBottom();

                try {
                    await this.fetchApi(`/classroom/${this.config.lessonId}/chat`, {
                        method: 'POST',
                        body: JSON.stringify({ message: text }),
                    });
                } catch (e) {
                    console.error('[Classroom] Save chat failed:', e);
                }
            },

            handleChatMessage(data) {
                if (data.userId != this.config.userId) {
                    this.chatMessages.push(data);
                    this.scrollChatToBottom();
                }
            },

            scrollChatToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.chatMessages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            // ═══ Notes ══════════════════════
            async saveNote() {
                const text = this.newNote.trim();
                if (!text) return;

                const note = {
                    id: Date.now(),
                    text: text,
                    time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
                };

                try {
                    await this.fetchApi(`/classroom/${this.config.lessonId}/notes`, {
                        method: 'POST',
                        body: JSON.stringify({ text }),
                    });
                } catch (e) {
                    console.error('[Classroom] Save note failed:', e);
                }

                this.notes.push(note);
                this.newNote = '';
            },

            async loadNotes() {
                try {
                    const data = await this.fetchApi(`/classroom/${this.config.lessonId}/notes`);
                    if (Array.isArray(data)) this.notes = data;
                } catch (e) {
                    console.log('[Classroom] Notes not available');
                }
            },

            // ═══ Files ══════════════════════
            handleFileDrop(event) {
                const files = event.dataTransfer?.files;
                if (files?.length) {
                    for (const file of files) {
                        this.uploadFile(file);
                    }
                }
            },

            async uploadFile(file) {
                if (!this.fileUploader) return;

                this.isUploading = true;
                this.uploadProgress = 0;

                try {
                    const result = await this.fileUploader.upload(file, (pct) => {
                        this.uploadProgress = pct;
                    });

                    this.files.push({
                        id: result?.id || Date.now(),
                        name: file.name,
                        size: this.fileUploader.formatSize(file.size),
                        url: result?.url || '#',
                    });
                } catch (e) {
                    console.error('[Classroom] File upload failed:', e);
                } finally {
                    this.isUploading = false;
                    this.uploadProgress = 0;
                }
            },

            async loadFiles() {
                try {
                    const data = await this.fetchApi(`/classroom/${this.config.lessonId}/files`);
                    if (Array.isArray(data)) this.files = data;
                } catch (e) {
                    console.log('[Classroom] Files not available');
                }
            },

            async loadChatHistory() {
                try {
                    const data = await this.fetchApi(`/classroom/${this.config.lessonId}/chat`);
                    if (Array.isArray(data)) {
                        this.chatMessages = data;
                        this.scrollChatToBottom();
                    }
                } catch (e) {
                    console.log('[Classroom] Chat history not available');
                }
            },

            // ═══ Tutor-only ═════════════════
            async loadStudentProfile() {
                try {
                    const data = await this.fetchApi(`/classroom/${this.config.lessonId}/student-profile`);
                    if (data) this.studentProfile = data;
                } catch (e) {
                    console.log('[Classroom] Student profile not available');
                }
            },

            async loadExistingHomework() {
                try {
                    const data = await this.fetchApi(`/classroom/${this.config.lessonId}/homework`);
                    if (Array.isArray(data)) this.existingHomework = data;
                } catch (e) {
                    console.log('[Classroom] Homework not available');
                }
            },

            async submitReport() {
                try {
                    await this.fetchApi(`/classroom/${this.config.lessonId}/report`, {
                        method: 'POST',
                        body: JSON.stringify(this.report),
                    });
                    alert('Отчёт сохранён');
                } catch (e) {
                    console.error('[Classroom] Report save failed:', e);
                    alert('Ошибка сохранения отчёта');
                }
            },

            async assignHomework() {
                if (!this.homework.title.trim()) return;

                try {
                    const result = await this.fetchApi(`/classroom/${this.config.lessonId}/homework`, {
                        method: 'POST',
                        body: JSON.stringify(this.homework),
                    });

                    this.existingHomework.push({
                        id: result?.id || Date.now(),
                        title: this.homework.title,
                        due_date: this.homework.due_date,
                        status: 'pending',
                    });

                    this.homework = { title: '', description: '', due_date: '' };
                } catch (e) {
                    console.error('[Classroom] Homework assign failed:', e);
                    alert('Ошибка назначения ДЗ');
                }
            },

            // ═══ WebRTC Events ══════════════
            async handleJoined(data) {
                console.log('[Classroom] Joined room:', data);
                this.selfId = data.selfId;
                // Заполняем уже существующих участников
                if (data.participants) {
                    for (const p of data.participants) {
                        this.peers[p.id] = { id: p.id, name: p.name, role: p.role, audio: p.audio, video: p.video, speaking: false };
                    }
                }
                
                // Инициируем соединение с SFU
                await this.rtcManager.connect();
            },

            handlePeerJoined(data) {
                console.log('[Classroom] Peer joined:', data);
                this.peers[data.peerId] = { id: data.peerId, name: data.name, role: data.role, audio: true, video: true, speaking: false };
            },

            handlePeerLeft(data) {
                console.log('[Classroom] Peer left:', data);
                delete this.peers[data.peerId];
            },

            handleMediaState(data) {
                const peer = this.peers[data.peerId];
                if (peer) {
                    if (data.audio !== undefined) peer.audio = data.audio;
                    if (data.video !== undefined) peer.video = data.video;
                }
            },

            handleSpeakingState(data) {
                if (data.peerId === this.selfId) {
                    this.localSpeaking = data.speaking;
                } else {
                    const peer = this.peers[data.peerId];
                    if (peer) {
                        peer.speaking = data.speaking;
                    }
                }
            },

            handleSessionEnded() {
                this.isSessionActive = false;
                this.timer?.stop();
                this.rtcManager?.close();
                this.wsManager?.close();
                alert('Урок завершён преподавателем');
                window.location.href = '/admin';
            },

            // ═══ Session End ════════════════
            async confirmEndSession() {
                this.showEndModal = false;
                this.isSessionActive = false;
                this.timer?.stop();

                try {
                    await this.fetchApi(`/classroom/${this.config.lessonId}/end`, {
                        method: 'POST',
                    });
                } catch (e) {
                    console.error('[Classroom] End session failed:', e);
                }

                this.wsManager.send('session-ended', {});
                this.rtcManager?.close();
                this.wsManager?.close();
                window.location.href = '/admin';
            },

            leaveSession() {
                this.isSessionActive = false;
                this.timer?.stop();
                this.rtcManager?.close();
                this.wsManager?.close();
                window.location.href = '/admin';
            },

            // ═══ AI Assistant ═══════════════
            sendAiPrompt(promptText) {
                this.newAiMessage = promptText;
                this.sendAiChat();
            },

            async sendAiChat() {
                const text = this.newAiMessage.trim();
                if (!text) return;

                this.aiMessages.push({
                    id: Date.now(),
                    role: 'user',
                    text: text
                });
                this.newAiMessage = '';
                this.scrollAiChatToBottom();

                try {
                    const result = await this.fetchApi(`/classroom/${this.config.lessonId}/ai-chat`, {
                        method: 'POST',
                        body: JSON.stringify({ message: text })
                    });

                    if (result && result.reply) {
                        this.aiMessages.push({
                            id: Date.now() + 1,
                            role: 'assistant',
                            text: result.reply
                        });
                        this.scrollAiChatToBottom();
                    }
                } catch (e) {
                    console.error('[AI] Chat request failed:', e);
                    this.aiMessages.push({
                        id: Date.now() + 1,
                        role: 'assistant',
                        text: 'Извините, произошла ошибка соединения с сервером ИИ.'
                    });
                    this.scrollAiChatToBottom();
                }
            },

            scrollAiChatToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.aiMessages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            // ═══ API Helper ═════════════════
            async fetchApi(url, options = {}) {
                const defaults = {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                    },
                };

                const response = await fetch(url, { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } });

                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }

                const text = await response.text();
                return text ? JSON.parse(text) : null;
            },
        };
    }

    function cardTimer(card) {
        return {
            cardId: card.id,
            seconds: 300,
            running: false,
            interval: null,

            init() {
                try {
                    const meta = typeof card.meta === 'string' ? JSON.parse(card.meta) : card.meta;
                    if (meta) {
                        this.seconds = meta.seconds !== undefined ? meta.seconds : 300;
                        this.running = !!meta.running;
                        
                        if (this.running && meta.lastUpdated) {
                            const elapsed = Math.floor((Date.now() - meta.lastUpdated) / 1000);
                            this.seconds = Math.max(0, this.seconds - elapsed);
                        }
                    }
                } catch (e) {
                    console.error('Failed to parse timer meta', e);
                }

                if (this.running) {
                    this.startInterval();
                }

                this.$watch('workspaceBoard', () => {
                    const updatedCard = window.classroomApp.findCard(this.cardId);
                    if (updatedCard) {
                        try {
                            const meta = typeof updatedCard.meta === 'string' ? JSON.parse(updatedCard.meta) : updatedCard.meta;
                            if (meta) {
                                if (meta.running !== this.running || Math.abs((meta.seconds || 300) - this.seconds) > 5) {
                                    this.seconds = meta.seconds || 300;
                                    this.running = meta.running;
                                    
                                    clearInterval(this.interval);
                                    if (this.running) {
                                        this.startInterval();
                                    }
                                }
                            }
                        } catch (e) {}
                    }
                });
            },

            formatTime() {
                const mins = Math.floor(this.seconds / 60);
                const secs = this.seconds % 60;
                return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            },

            startInterval() {
                this.interval = setInterval(() => {
                    if (this.seconds > 0) {
                        this.seconds--;
                    } else {
                        this.running = false;
                        clearInterval(this.interval);
                    }
                }, 1000);
            },

            toggleTimer() {
                this.running = !this.running;
                if (this.running) {
                    this.startInterval();
                } else {
                    clearInterval(this.interval);
                }

                this.syncTimer();
            },

            resetTimer() {
                this.running = false;
                clearInterval(this.interval);
                this.seconds = 300;
                this.syncTimer();
            },

            syncTimer() {
                const meta = {
                    seconds: this.seconds,
                    running: this.running,
                    lastUpdated: Date.now()
                };

                window.classroomApp.sendWorkspaceMsg('card.update', {
                    cardId: this.cardId,
                    data: {
                        meta: meta
                    }
                });
            }
        };
    }
</script>

</body>
</html>
