<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ИИ-Диагностика уровня ЦТ/ЦЭ 2026 — Edusfera</title>
    <meta name="description" content="Бесплатная интеллектуальная диагностика готовности к ЦЭ/ЦТ 2026. Точное выявление пробелов спецификации РИКЗ и прогноз баллов за 4 минуты.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --ed-lime: #C6FF33;
            --ed-lime-glow: rgba(198, 255, 51, 0.35);
            --ed-violet: #7D39EB;
            --ed-violet-glow: rgba(125, 57, 235, 0.25);
            --ed-bg: #010101;
        }

        body.nexum-body {
            background-color: var(--ed-bg) !important;
            color: #ffffff;
            font-family: 'Geist', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif !important;
        }

        /* Tactical Range Slider styling */
        input[type=range].ed-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 9999px;
            background: #14161f;
            outline: none;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        input[type=range].ed-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #C6FF33;
            border: 3px solid #010101;
            box-shadow: 0 0 18px rgba(198, 255, 51, 0.75);
            cursor: grab;
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }
        input[type=range].ed-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
        input[type=range].ed-slider::-moz-range-thumb {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #C6FF33;
            border: 3px solid #010101;
            box-shadow: 0 0 18px rgba(198, 255, 51, 0.75);
            cursor: grab;
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }
        input[type=range].ed-slider::-moz-range-thumb:hover {
            transform: scale(1.2);
        }

        /* Radar animations */
        @keyframes radar-sweep {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .radar-spinner {
            animation: radar-sweep 3.5s linear infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.08); opacity: 0.25; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }
        .pulse-ambient {
            animation: pulse-ring 3s ease-in-out infinite;
        }
    </style>
    <script>
        function diagnosticTrainer() {
            return {
                phase: 'setup', // 'setup' | 'testing' | 'analyzing'
                selectedSubject: @json($subject ?? 'Математика'),
                selectedExam: @json($examType ?? 'ЦТ 2026'),
                targetScore: @json($targetScore ?? 85),
                currentIndex: 0,
                answers: [],
                analysisPercent: 0,
                currentStatusText: 'Инициализация нейросетевого анализатора...',

                // Curated RIKZ tasks per subject
                questionsDb: {
                    'Математика': [
                        {
                            code: 'РИКЗ А3',
                            theme: 'Свойства корней и степеней',
                            text: 'Вычислите точное значение выражения:',
                            codeBlock: '√[3](54) · √[3](4)  -  √50 / √2',
                            options: ['1', '6', '4', '2'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Ошибка в свойствах корней одинаковой степени или извлечении корня из частного.'
                        },
                        {
                            code: 'РИКЗ А7',
                            theme: 'Логарифмические уравнения (ловушка ОДЗ)',
                            text: 'Укажите все действительные корни уравнения:',
                            codeBlock: 'log₂(x² - 3x) = 2',
                            options: ['x = 4 и x = -1', 'x = 4', 'x = -1', 'x = 2'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Потеря отрицательного корня из-за ложного предположения, что аргумент логарифма не может содержать отрицательный x.'
                        },
                        {
                            code: 'РИКЗ А12',
                            theme: 'Планиметрия: прямоугольный треугольник',
                            text: 'В прямоугольном треугольнике гипотенуза равна 20, а sin α = 0.6. Найдите длину катета, прилежащего к углу α.',
                            codeBlock: 'c = 20, sin α = 0.6  →  Найти: прилежащий катет b',
                            options: ['12', '16', '14', '8'],
                            correct: 1,
                            gapLoss: 4,
                            mistake: 'Путаница между синусом (противолежащий катет) и косинусом (прилежащий катет = 20 · 0.8 = 16).'
                        },
                        {
                            code: 'РИКЗ Б2',
                            theme: 'Тригонометрические уравнения (отбор корней)',
                            text: 'Сколько корней уравнения cos(2x) - sin(x) = 0 принадлежит отрезку [0; π]?',
                            codeBlock: 'cos(2x) - sin(x) = 0,  x ∈ [0; π]',
                            options: ['1 корень', '2 корня', '3 корня', '4 корня'],
                            correct: 1,
                            gapLoss: 6,
                            mistake: 'Неверное разложение cos(2x) = 1 - 2sin²(x) или включение постороннего корня 3π/2, лежащего вне отрезка [0; π].'
                        },
                        {
                            code: 'РИКЗ Б5',
                            theme: 'Показательные и логарифмические неравенства',
                            text: 'Решите неравенство со сменой знака основания:',
                            codeBlock: 'log₀.₅(2x - 6) ≥ -2',
                            options: ['(3; 5]', '[3; 5]', '(-∞; 5]', '(3; +∞)'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Забыта проверка ОДЗ (2x - 6 > 0 => x > 3), что приводит к грубейшей потере баллов на ЦТ/ЦЭ.'
                        },
                        {
                            code: 'РИКЗ Б10',
                            theme: 'Стереометрия: расстояния в пространстве',
                            text: 'В правильной четырехугольной призме со стороной основания 4 и высотой 6 найдите расстояние от вершины основания до плоскости диагонального сечения.',
                            codeBlock: 'a = 4, h = 6  →  d(A, BDD₁B₁) = ?',
                            options: ['2√2', '4√2', '4', '2√3'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Неверное построение перпендикуляра из вершины квадрата к его диагонали (половина диагонали квадрата: 4√2 / 2 = 2√2).'
                        }
                    ],
                    'Русский язык': [
                        {
                            code: 'РИКЗ А2',
                            theme: 'Орфография: Слитное и раздельное написание НЕ',
                            text: 'В каком варианте НЕ пишется раздельно со словом?',
                            codeBlock: '1) (не)прочитанная вовремя книга\n2) (не)годующий взгляд\n3) (не)высокий, но крутой холм\n4) крайне (не)осмотрительно',
                            options: ['(не)прочитанная вовремя книга', '(не)годующий взгляд', '(не)высокий, но крутой холм', 'крайне (не)осмотрительно'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Невнимательность к зависимому слову «вовремя» при полном причастии, требующему раздельного написания.'
                        },
                        {
                            code: 'РИКЗ А5',
                            theme: 'Пунктуация в сложносочиненном предложении',
                            text: 'Укажите предложение, в котором запятая перед союзом И НЕ ставится:',
                            codeBlock: '1) В саду пахло яблоками и тихо шумел ветер.\n2) Пошел дождь и мы побежали домой.\n3) Солнце село и на небе зажглись звезды.\n4) Урок окончился и дети выбежали в коридор.',
                            options: ['В саду пахло яблоками и тихо шумел ветер.', 'Пошел дождь и мы побежали домой.', 'Солнце село и на небе зажглись звезды.', 'Урок окончился и дети выбежали в коридор.'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Пропуск общего второстепенного члена («В саду»), отменяющего запятую перед И в ССП.'
                        },
                        {
                            code: 'РИКЗ А10',
                            theme: 'Правописание корней с чередованием',
                            text: 'В каком слове на месте пропуска пишется буква А?',
                            codeBlock: '1) зам..реть\n2) прик..саться\n3) непром..каемый\n4) расст..лать',
                            options: ['прик..саться', 'зам..реть', 'непром..каемый', 'расст..лать'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Путаница между правилом суффикса -А- (кас/кос) и смысловыми корнями (мак/мок).'
                        },
                        {
                            code: 'РИКЗ Б1',
                            theme: 'Орфоэпические нормы (ударение)',
                            text: 'Укажите слово с верным ударением по нормам РИКЗ 2026:',
                            codeBlock: '1) блеклО\n2) жалюзИ\n3) включИт\n4) слИвовый',
                            options: ['жалюзИ', 'блеклО', 'включИт', 'слИвовый'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Французское происхождение слова жалюзи фиксирует ударение исключительно на последний слог.'
                        },
                        {
                            code: 'РИКЗ Б4',
                            theme: 'Синтаксические нормы (деепричастный оборот)',
                            text: 'Укажите грамматически правильное продолжение предложения:',
                            codeBlock: 'Возвращаясь вечером домой, ...',
                            options: ['я встретил старого школьного друга.', 'пошел сильный проливной дождь.', 'мне стало очень грустно.', 'ветер срывал последние листья.'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Субъект действия деепричастия обязан совпадать с подлежащим предложения.'
                        },
                        {
                            code: 'РИКЗ Б8',
                            theme: 'Сложные случаи Н и НН в суффиксах',
                            text: 'В каком слове пишется удвоенная НН?',
                            codeBlock: '1) плете..ая корзина\n2) кова..ый сундук\n3) ране..ый в плечо боец\n4) сви..ой окорок',
                            options: ['ране..ый в плечо боец', 'плете..ая корзина', 'кова..ый сундук', 'сви..ой окорок'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Наличие зависимого слова («в плечо») превращает отглагольное прилагательное в причастие с НН.'
                        }
                    ],
                    'Белорусский язык': [
                        {
                            code: 'РИКЗ А1',
                            theme: 'Правапіс галосных О, Э, А',
                            text: 'Адзначце слова, у якім на месцы пропуску пішацца літара А:',
                            codeBlock: '1) ш..калад\n2) р..монт\n3) кр..вавы\n4) б..тон',
                            options: ['кр..вавы', 'ш..калад', 'р..монт', 'б..тон'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Памылка ў правіле акання: пад уплывам націску ў корані (кроў -> крывавы/крававы).'
                        },
                        {
                            code: 'РИКЗ А4',
                            theme: 'Правапіс падоўжаных зычных',
                            text: 'У якім слове пішацца падаўжэнне зычных?',
                            codeBlock: '1) мыш..у\n2) насен..е\n3) ліс..е\n4) суц..е',
                            options: ['насен..е', 'мыш..у', 'ліс..е', 'суц..е'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Блытаніна паміж падоўжанымі зычнымі і апострафам/раздзяляльным знакам.'
                        },
                        {
                            code: 'РИКЗ Б2',
                            theme: 'Правапіс прыназоўнікаў і злучнікаў',
                            text: 'Адзначце правільны варыянт напісання згодна з новай рэдакцыяй правіл:',
                            codeBlock: '1) на працягу дня\n2) на працязе дня\n3) цягам дня\n4) у працягу дня',
                            options: ['цягам дня', 'на працягу дня', 'на працязе дня', 'у працягу дня'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Калька з рускай мовы («на протяжении дня» -> літаратурна «цягам дня»).'
                        },
                        {
                            code: 'РИКЗ Б5',
                            theme: 'Сінтаксіс: аднародныя члены сказа',
                            text: 'Адзначце сказ з правільнай пастаноўкай знакаў прыпынку:',
                            codeBlock: '1) I лес, і луг, і рэчка — усё дыхала спакоем.\n2) I лес і луг і рэчка ўсё дыхала спакоем.\n3) Усё лес, луг і рэчка дыхалі спакоем.\n4) Лес, луг, рэчка, усё дыхала спакоем.',
                            options: ['I лес, і луг, і рэчка — усё дыхала спакоем.', 'I лес і луг і рэчка ўсё дыхала спакоем.', 'Усё лес, луг і рэчка дыхалі спакоем.', 'Лес, луг, рэчка, усё дыхала спакоем.'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Правіла абагульняльнага слова пасля аднародных членаў сказа (патрабуецца працяжнік).'
                        }
                    ],
                    'Физика': [
                        {
                            code: 'РИКЗ А2',
                            theme: 'Кинематика: равноускоренное движение',
                            text: 'Автомобиль, двигаясь равноускоренно из состояния покоя, проходит путь 50 м за 5 с. Какова скорость автомобиля в конце этого пути?',
                            codeBlock: 'v₀ = 0, S = 50 м, t = 5 с  →  Найти: v',
                            options: ['20 м/с', '10 м/с', '25 м/с', '15 м/с'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Забыто ускорение: a = 2S/t² = 100/25 = 4 м/с², тогда v = at = 4·5 = 20 м/с.'
                        },
                        {
                            code: 'РИКЗ А6',
                            theme: 'Законы сохранения в механике',
                            text: 'Тело массой 2 кг падает с высоты 10 м без начальной скорости. Чему равна кинетическая энергия тела в момент удара о землю? (g = 10 м/с²)',
                            codeBlock: 'm = 2 кг, h = 10 м  →  Найти: E_k',
                            options: ['200 Дж', '100 Дж', '400 Дж', '50 Дж'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'По закону сохранения энергии: E_k = mgh = 2 · 10 · 10 = 200 Дж.'
                        },
                        {
                            code: 'РИКЗ Б1',
                            theme: 'Термодинамика: уравнение Менделеева-Клапейрона',
                            text: 'При изохорном нагревании идеального газа его абсолютная температура увеличилась в 1.5 раза. Начальное давление было 120 кПа. Каково конечное давление?',
                            codeBlock: 'V = const, T₂ = 1.5 T₁, p₁ = 120 кПа  →  Найти: p₂',
                            options: ['180 кПа', '160 кПа', '240 кПа', '80 кПа'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'При изохорном процессе p/T = const, следовательно p₂ = 1.5 · 120 = 180 кПа.'
                        },
                        {
                            code: 'РИКЗ Б4',
                            theme: 'Электродинамика: закон Ома для полной цепи',
                            text: 'Источник тока с ЭДС 12 В и внутренним сопротивлением 1 Ом подключен к резистору 5 Ом. Какова сила тока в цепи?',
                            codeBlock: 'E = 12 В, r = 1 Ом, R = 5 Ом  →  Найти: I',
                            options: ['2 А', '2.4 А', '1.2 А', '3 А'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Закон Ома для полной цепи: I = E / (R + r) = 12 / (5 + 1) = 2 А.'
                        }
                    ],
                    'Английский язык': [
                        {
                            code: 'РИКЗ А3',
                            theme: 'Conditionals & Mixed Conditionals',
                            text: 'Choose the correct form to complete the sentence:',
                            codeBlock: 'If he _____ the train yesterday, he would be in Minsk right now.',
                            options: ['had not missed', 'did not miss', 'would not miss', 'has not missed'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Смешанный тип условных предложений: условие в прошлом (Past Perfect) и следствие в настоящем.'
                        },
                        {
                            code: 'РИКЗ А8',
                            theme: 'Prepositions after Adjectives',
                            text: 'Fill in the correct preposition according to formal academic norms:',
                            codeBlock: "The entire staff is highly dedicated _____ improving students' test results.",
                            options: ['to', 'with', 'for', 'at'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Путаница в зависимых предлогах: прилагательное dedicated всегда требует предлога to.'
                        },
                        {
                            code: 'РИКЗ Б1',
                            theme: 'Word Formation (Prefixes & Suffixes)',
                            text: 'Form the correct antonym of the word in capitals:',
                            codeBlock: 'The unexpected results proved to be completely _____ (EXPECTED).',
                            options: ['unexpected', 'unexpecting', 'non-expected', 'inexpected'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Ошибочный выбор префикса (non-/in-) вместо нормативного un- для причастия expected.'
                        },
                        {
                            code: 'РИКЗ Б4',
                            theme: 'Subject-Verb Agreement (Proximity Rule)',
                            text: 'Identify the grammatically correct sentence according to RIKZ specification:',
                            codeBlock: 'Neither the head tutor nor the high school students _____ present at the conference.',
                            options: ['were', 'was', 'is', 'has been'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Правило близости при neither... nor: глагол согласуется с ближайшим подлежащим (students were).'
                        }
                    ]
                },

                init() {
                    this.resetTest();
                },

                get currentQuestions() {
                    return this.questionsDb[this.selectedSubject] || this.questionsDb['Математика'];
                },

                get currentQuestion() {
                    return this.currentQuestions[this.currentIndex] || this.currentQuestions[0];
                },

                get targetScoreStatus() {
                    const s = this.targetScore;
                    if (s >= 95) return { label: 'Топ вузов (95-100)', color: 'border-[#C6FF33] text-[#C6FF33] bg-[#C6FF33]/15' };
                    if (s >= 85) return { label: 'Бюджет БГУ / БГУИР (85-94)', color: 'border-emerald-400/40 text-emerald-400 bg-emerald-500/10' };
                    if (s >= 75) return { label: 'Уверенный результат (75-84)', color: 'border-cyan-400/40 text-cyan-400 bg-cyan-500/10' };
                    return { label: 'Базовый порог (60-74)', color: 'border-amber-400/40 text-amber-400 bg-amber-500/10' };
                },

                selectSubject(sub) {
                    this.selectedSubject = sub;
                    this.resetTest();
                },

                resetTest() {
                    this.currentIndex = 0;
                    this.answers = new Array(this.currentQuestions.length).fill(undefined);
                },

                startDiagnostic() {
                    this.resetTest();
                    this.phase = 'testing';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                confirmExit() {
                    if (confirm('Сбросить текущее тестирование и вернуться к выбору параметров?')) {
                        this.phase = 'setup';
                    }
                },

                selectOption(optIndex) {
                    this.answers[this.currentIndex] = optIndex;
                },

                prevQuestion() {
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                    }
                },

                nextQuestion() {
                    if (this.currentIndex < this.currentQuestions.length - 1) {
                        this.currentIndex++;
                    }
                },

                handleKey(e) {
                    if (this.phase !== 'testing') return;
                    if (['1', '2', '3', '4'].includes(e.key)) {
                        const optIndex = parseInt(e.key, 10) - 1;
                        if (optIndex < this.currentQuestion.options.length) {
                            this.selectOption(optIndex);
                        }
                    } else if (e.key === 'Enter') {
                        if (this.answers[this.currentIndex] !== undefined) {
                            if (this.currentIndex === this.currentQuestions.length - 1) {
                                this.finishAndAnalyze();
                            } else {
                                this.nextQuestion();
                            }
                        }
                    }
                },

                finishAndAnalyze() {
                    this.phase = 'analyzing';
                    this.analysisPercent = 0;
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    const statuses = [
                        { at: 10, text: 'Проверка ловушек РИКЗ и спецификации 2026...' },
                        { at: 35, text: 'Анализ вычислительных паттернов и типовых ошибок...' },
                        { at: 65, text: 'Сравнение с базой 10 000+ сдавших ЦЭ/ЦТ...' },
                        { at: 88, text: 'Построение индивидуального трека подготовки...' }
                    ];

                    const duration = 2600;
                    const interval = 40;
                    const step = 100 / (duration / interval);

                    const timer = setInterval(() => {
                        this.analysisPercent = Math.min(100, Math.round(this.analysisPercent + step));

                        for (const s of statuses) {
                            if (this.analysisPercent >= s.at) {
                                this.currentStatusText = s.text;
                            }
                        }

                        if (this.analysisPercent >= 100) {
                            clearInterval(timer);
                            this.compileAndSubmit();
                        }
                    }, interval);
                },

                compileAndSubmit() {
                    let correctCount = 0;
                    let totalGapLoss = 0;
                    const weakTopics = [];
                    const skillGaps = [];

                    this.currentQuestions.forEach((q, idx) => {
                        const userAns = this.answers[idx];
                        if (userAns === q.correct) {
                            correctCount++;
                        } else {
                            totalGapLoss += q.gapLoss;
                            weakTopics.push(q.theme);
                            skillGaps.push({
                                code: q.code,
                                topic: q.theme,
                                loss: q.gapLoss,
                                mistake: q.mistake,
                                criticality: q.gapLoss >= 6 ? 'Критический пробел' : 'Требует закрепления'
                            });
                        }
                    });

                    let predictedScore = Math.max(48, Math.min(98, 100 - Math.round(totalGapLoss * 1.5)));
                    if (correctCount === this.currentQuestions.length) {
                        predictedScore = 96;
                    }

                    const payload = {
                        step: 3,
                        subject: [this.selectedSubject],
                        examType: this.selectedExam,
                        currentScore: predictedScore,
                        targetScore: this.targetScore,
                        weakTopics: weakTopics.slice(0, 4),
                        skillGaps: skillGaps,
                        answers: this.answers
                    };

                    fetch('{{ route("diagnostic.submit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(() => {
                        window.location.href = '{{ route("diagnostic.finish") }}?subject=' + encodeURIComponent(this.selectedSubject) + 
                                               '&exam_type=' + encodeURIComponent(this.selectedExam) + 
                                               '&current_score=' + predictedScore + 
                                               '&target_score=' + this.targetScore;
                    })
                    .catch(() => {
                        window.location.href = '{{ route("diagnostic.finish") }}?subject=' + encodeURIComponent(this.selectedSubject) + 
                                               '&exam_type=' + encodeURIComponent(this.selectedExam) + 
                                               '&current_score=' + predictedScore + 
                                               '&target_score=' + this.targetScore;
                    });
                }
            };
        }

        window.diagnosticTrainer = diagnosticTrainer;
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                window.Alpine.data('diagnosticTrainer', diagnosticTrainer);
            }
        });
    </script>
</head>
<body class="nexum-body min-h-screen bg-[#010101] text-white selection:bg-[#C6FF33] selection:text-black overflow-x-hidden antialiased flex flex-col justify-between">

    {{-- Subtle Ambient Glows & Grid (Edusfera Platform Aesthetics) --}}
    <div class="fixed top-0 left-1/4 -translate-x-1/2 -top-40 w-[600px] h-[600px] bg-[#7D39EB]/12 rounded-full blur-[140px] pointer-events-none z-0"></div>
    <div class="fixed top-28 right-1/4 translate-x-1/3 w-[550px] h-[550px] bg-[#C6FF33]/08 rounded-full blur-[150px] pointer-events-none z-0"></div>
    <div class="fixed inset-0 bg-[radial-gradient(rgba(255,255,255,0.03)_1px,transparent_1px)] [background-size:32px_32px] pointer-events-none z-0"></div>

    <div x-data="diagnosticTrainer()" x-init="init()" class="relative z-10 min-h-screen flex flex-col justify-between" @keydown.window="handleKey($event)">

        {{-- ─── CANONICAL HEADER (Liquid Glass Island) ─── --}}
        <header class="sticky top-0 w-full z-40 bg-gradient-to-b from-[#010101]/90 via-[#010101]/75 to-[#010101]/30 backdrop-blur-2xl border-b border-white/[0.08] py-3.5 sm:py-4">
            <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-12 flex items-center justify-between">
                
                {{-- Brand Mark --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white transition-colors group">
                        <svg width="28" height="28" viewBox="0 0 64 64" class="w-7 h-7 rounded-lg shadow-sm group-hover:scale-105 transition-transform">
                            <rect width="64" height="64" rx="14" fill="#7D39EB" />
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                        <span class="text-xl font-bold tracking-tight text-white uppercase font-rimma">edusfera</span>
                    </a>

                    {{-- Liquid Glass Badge: ИИ-Диагностика --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-violet-500/20 via-white/[0.08] to-[#C6FF33]/15 border border-white/15 text-[#C6FF33] font-bold text-[11px] uppercase tracking-wider backdrop-blur-xl shadow-inner">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-pulse"></span>
                        ИИ-Диагностика
                    </span>
                </div>

                {{-- Center Live Mode Indicator --}}
                <div class="hidden md:flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/[0.05] border border-white/10 text-xs font-semibold text-neutral-300 backdrop-blur-xl shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]">
                    <span class="w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                    <span>Спецификация РИКЗ 2026</span>
                    <span class="text-neutral-500">•</span>
                    <span class="text-[#C6FF33] font-black uppercase text-[11px] tracking-wider">0 BYN Бесплатно</span>
                </div>

                {{-- Right Profile Link / Exit --}}
                <div class="flex items-center gap-3">
                    <template x-if="phase === 'testing'">
                        <button @click="confirmExit()" class="text-xs font-bold text-neutral-400 hover:text-white px-3.5 py-1.5 rounded-full border border-white/10 hover:bg-white/10 transition cursor-pointer">
                            Сбросить
                        </button>
                    </template>
                    @auth
                        <a href="/admin" class="flex items-center gap-2.5 bg-gradient-to-b from-white/[0.15] to-white/[0.05] border border-white/20 pl-2.5 pr-4 py-1.5 rounded-full hover:border-white/30 transition-all text-white backdrop-blur-xl">
                            <span class="w-6 h-6 rounded-full bg-[#C6FF33] text-black font-black flex items-center justify-center text-[10px]">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </span>
                            <span class="text-xs font-bold text-white">{{ auth()->user()->name }}</span>
                        </a>
                    @else
                        <a href="/login" class="rounded-full bg-gradient-to-b from-white/[0.15] to-white/[0.05] hover:from-white/[0.22] hover:to-white/[0.1] text-white font-medium text-xs sm:text-sm px-4 sm:px-5 py-2 border border-white/20 backdrop-blur-xl transition-all flex items-center gap-2">
                            <span>Войти</span>
                            <span class="text-neutral-400">→</span>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- ─── MAIN VIEWPORT ─── --}}
        <main class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-8 py-8 sm:py-12 max-w-5xl mx-auto w-full">

            {{-- ========================================================== --}}
            {{-- PHASE 1: SETUP (Выбор предмета, экзамена, целевого балла) --}}
            {{-- ========================================================== --}}
            <div x-show="phase === 'setup'" class="space-y-8 sm:space-y-10 transition-opacity duration-300">
                
                {{-- Hero Section (Breathing Room & Scale Contrast) --}}
                <div class="text-center space-y-4 max-w-2xl mx-auto">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/25 text-[#C6FF33] text-[11px] font-black uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-ping"></span>
                        <span>Спецификация РИКЗ 2026 · Нейросетевой скан</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-[3.2rem] font-black tracking-tight text-white leading-[1.08]">
                        Нейросетевая диагностика <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-neutral-100 to-[#C6FF33]">
                            готовности к ЦЭ и ЦТ
                        </span>
                    </h1>

                    <p class="text-neutral-400 text-sm sm:text-base max-w-lg mx-auto leading-relaxed">
                        Выявите скрытые ловушки РИКЗ, узнайте свой реальный прогнозный балл и получите персональную стратегию за 4 минуты.
                    </p>
                </div>

                {{-- Interactive Master Card (Liquid Glass Surface) --}}
                <div class="bg-[#0b0c10]/80 backdrop-blur-2xl border border-white/[0.1] rounded-[32px] p-6 sm:p-10 space-y-8 shadow-[0_20px_50px_rgba(0,0,0,0.8),inset_0_1px_1px_rgba(255,255,255,0.1)]">
                    
                    {{-- 1. Предмет подготовки --}}
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-neutral-400">
                                    01. Выберите предмет подготовки
                                </span>
                            </div>
                            <span class="text-xs text-[#C6FF33] font-bold" x-text="selectedSubject">{{ $subject ?? 'Математика' }}</span>
                        </div>

                        {{-- Subject Items Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                            
                            {{-- 1. Математика --}}
                            <button type="button"
                                    @click="selectSubject('Математика')"
                                    :class="selectedSubject === 'Математика'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_25px_rgba(198,255,51,0.18)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white hover:bg-white/[0.04]'"
                                    class="flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border text-center transition-all duration-200 group relative cursor-pointer">
                                
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2.5 transition-transform group-hover:scale-110"
                                     :class="selectedSubject === 'Математика' ? 'bg-[#C6FF33]/20 text-[#C6FF33]' : 'bg-white/5 text-neutral-300'">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="3 3 21 21 3 21 3 3"/>
                                        <line x1="9" y1="21" x2="9" y2="17"/>
                                        <line x1="13" y1="21" x2="13" y2="15"/>
                                        <line x1="17" y1="21" x2="17" y2="19"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white tracking-tight">Математика</span>
                                <span class="text-[11px] text-neutral-400 mt-1 font-mono">38 заданий РИКЗ</span>
                                <span x-show="selectedSubject === 'Математика'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                            </button>

                            {{-- 2. Русский язык --}}
                            <button type="button"
                                    @click="selectSubject('Русский язык')"
                                    :class="selectedSubject === 'Русский язык'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_25px_rgba(198,255,51,0.18)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white hover:bg-white/[0.04]'"
                                    class="flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border text-center transition-all duration-200 group relative cursor-pointer">
                                
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2.5 transition-transform group-hover:scale-110"
                                     :class="selectedSubject === 'Русский язык' ? 'bg-[#C6FF33]/20 text-[#C6FF33]' : 'bg-white/5 text-neutral-300'">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                        <path d="M6 6h10"/>
                                        <path d="M6 10h10"/>
                                        <path d="M6 14h6"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white tracking-tight">Русский язык</span>
                                <span class="text-[11px] text-neutral-400 mt-1 font-mono">40 заданий РИКЗ</span>
                                <span x-show="selectedSubject === 'Русский язык'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                            </button>

                            {{-- 3. Белорусский язык --}}
                            <button type="button"
                                    @click="selectSubject('Белорусский язык')"
                                    :class="selectedSubject === 'Белорусский язык'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_25px_rgba(198,255,51,0.18)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white hover:bg-white/[0.04]'"
                                    class="flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border text-center transition-all duration-200 group relative cursor-pointer">
                                
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2.5 transition-transform group-hover:scale-110"
                                     :class="selectedSubject === 'Белорусский язык' ? 'bg-[#C6FF33]/20 text-[#C6FF33]' : 'bg-white/5 text-neutral-300'">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                                        <line x1="12" y1="7" x2="12" y2="13"/>
                                        <line x1="9" y1="10" x2="15" y2="10"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white tracking-tight">Белорусский</span>
                                <span class="text-[11px] text-neutral-400 mt-1 font-mono">40 заданий РИКЗ</span>
                                <span x-show="selectedSubject === 'Белорусский язык'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                            </button>

                            {{-- 4. Физика --}}
                            <button type="button"
                                    @click="selectSubject('Физика')"
                                    :class="selectedSubject === 'Физика'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_25px_rgba(198,255,51,0.18)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white hover:bg-white/[0.04]'"
                                    class="flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border text-center transition-all duration-200 group relative cursor-pointer">
                                
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2.5 transition-transform group-hover:scale-110"
                                     :class="selectedSubject === 'Физика' ? 'bg-[#C6FF33]/20 text-[#C6FF33]' : 'bg-white/5 text-neutral-300'">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(30 12 12)"/>
                                        <ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(-30 12 12)"/>
                                        <circle cx="12" cy="12" r="2" fill="currentColor"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white tracking-tight">Физика</span>
                                <span class="text-[11px] text-neutral-400 mt-1 font-mono">38 заданий РИКЗ</span>
                                <span x-show="selectedSubject === 'Физика'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                            </button>

                            {{-- 5. Английский язык --}}
                            <button type="button"
                                    @click="selectSubject('Английский язык')"
                                    :class="selectedSubject === 'Английский язык'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_25px_rgba(198,255,51,0.18)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white hover:bg-white/[0.04]'"
                                    class="flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border text-center transition-all duration-200 group relative cursor-pointer">
                                
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2.5 transition-transform group-hover:scale-110"
                                     :class="selectedSubject === 'Английский язык' ? 'bg-[#C6FF33]/20 text-[#C6FF33]' : 'bg-white/5 text-neutral-300'">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="2" y1="12" x2="22" y2="12"/>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white tracking-tight">Английский</span>
                                <span class="text-[11px] text-neutral-400 mt-1 font-mono">40 заданий РИКЗ</span>
                                <span x-show="selectedSubject === 'Английский язык'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                            </button>

                        </div>
                    </div>

                    {{-- 2. Тип экзамена (ЦЭ 2026 / ЦТ 2026) --}}
                    <div class="space-y-3.5 pt-4 border-t border-white/[0.08]">
                        <label class="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-neutral-400">
                            02. Тип экзамена
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            {{-- ЦЭ 2026 --}}
                            <button type="button"
                                    @click="selectedExam = 'ЦЭ 2026'"
                                    :class="selectedExam === 'ЦЭ 2026'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_20px_rgba(198,255,51,0.12)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white'"
                                    class="p-4 sm:p-5 rounded-2xl border text-left transition-all duration-200 flex items-center justify-between group cursor-pointer">
                                <div>
                                    <div class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                                        <span>ЦЭ 2026</span>
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/10 text-neutral-300 font-semibold">11 КЛАСС</span>
                                    </div>
                                    <div class="text-xs text-neutral-400 mt-1">Централизованный экзамен (выпускной в школе)</div>
                                </div>
                                <div class="w-6 h-6 rounded-full border flex items-center justify-center text-xs shrink-0 transition-all"
                                     :class="selectedExam === 'ЦЭ 2026' ? 'border-[#C6FF33] bg-[#C6FF33] text-black font-black' : 'border-neutral-700 text-transparent'">
                                    ✓
                                </div>
                            </button>

                            {{-- ЦТ 2026 --}}
                            <button type="button"
                                    @click="selectedExam = 'ЦТ 2026'"
                                    :class="selectedExam === 'ЦТ 2026'
                                        ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white shadow-[0_0_20px_rgba(198,255,51,0.12)] ring-1 ring-[#C6FF33]'
                                        : 'border-white/10 bg-white/[0.02] text-neutral-400 hover:border-white/20 hover:text-white'"
                                    class="p-4 sm:p-5 rounded-2xl border text-left transition-all duration-200 flex items-center justify-between group cursor-pointer">
                                <div>
                                    <div class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                                        <span>ЦТ 2026</span>
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/10 text-neutral-300 font-semibold">ПОСТУПЛЕНИЕ</span>
                                    </div>
                                    <div class="text-xs text-neutral-400 mt-1">Централизованное тестирование (вступительное)</div>
                                </div>
                                <div class="w-6 h-6 rounded-full border flex items-center justify-center text-xs shrink-0 transition-all"
                                     :class="selectedExam === 'ЦТ 2026' ? 'border-[#C6FF33] bg-[#C6FF33] text-black font-black' : 'border-neutral-700 text-transparent'">
                                    ✓
                                </div>
                            </button>

                        </div>
                    </div>

                    {{-- 3. Интерактивный тактильный слайдер целевого балла --}}
                    <div class="space-y-4 pt-4 border-t border-white/[0.08]">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <label class="text-[11px] font-mono font-bold uppercase tracking-[0.2em] text-neutral-400">
                                    03. Ваш целевой балл
                                </label>
                                <p class="text-xs text-neutral-400 mt-0.5">Укажите желаемую цель для точной калибровки заданий</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="px-3.5 py-1 rounded-full text-xs font-bold border transition-colors backdrop-blur-md"
                                      :class="targetScoreStatus.color"
                                      x-text="targetScoreStatus.label">
                                </span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl sm:text-4xl font-black text-[#C6FF33] tracking-tight font-mono" x-text="targetScore"></span>
                                    <span class="text-xs font-bold text-neutral-500 font-mono">/ 100</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2.5 pt-2">
                            <input type="range" min="60" max="100" step="1"
                                   x-model.number="targetScore"
                                   class="ed-slider">
                            
                            <div class="flex justify-between text-[11px] font-mono text-neutral-400 px-1">
                                <span>60 (Порог)</span>
                                <span>75 (Уверенный)</span>
                                <span>85 (Бюджет БГУ/БГУИР)</span>
                                <span>100 (Топ вузов)</span>
                            </div>
                        </div>
                    </div>

                    {{-- Action Button & Stats Ribbon --}}
                    <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-5 border-t border-white/[0.08]">
                        <div class="flex items-center gap-3 text-xs text-neutral-400">
                            <div class="w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-[#C6FF33] shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-white font-bold">6 диагностических заданий</span> РИКЗ<br>
                                <span class="text-neutral-400">Оценочное время прохождения: ~4 минуты</span>
                            </div>
                        </div>

                        <button type="button"
                                @click="startDiagnostic()"
                                class="w-full sm:w-auto px-9 py-4 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-black text-sm uppercase tracking-wider shadow-[0_0_30px_rgba(198,255,51,0.35)] hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 cursor-pointer">
                            <span>Начать ИИ-диагностику (0 BYN)</span>
                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>

                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- PHASE 2: TESTING (Интерактивный адаптивный тренажер РИКЗ)  --}}
            {{-- ========================================================== --}}
            <div x-show="phase === 'testing'" x-cloak class="space-y-6 max-w-3xl mx-auto w-full transition-opacity duration-300">
                
                {{-- Step Header & Progress Bar --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-[#C6FF33]/15 text-[#C6FF33] font-black border border-[#C6FF33]/30 font-mono">
                                Задание <span x-text="currentIndex + 1"></span> из <span x-text="currentQuestions.length"></span>
                            </span>
                            <span class="text-neutral-400 hidden sm:inline" x-text="selectedSubject"></span>
                            <span class="text-neutral-600 hidden sm:inline">•</span>
                            <span class="text-neutral-400 hidden sm:inline" x-text="selectedExam"></span>
                        </div>
                        <div class="text-neutral-400 font-mono text-xs">
                            Прогресс: <span class="text-white font-black" x-text="Math.round(((currentIndex) / currentQuestions.length) * 100) + '%'"></span>
                        </div>
                    </div>

                    {{-- Progress Track --}}
                    <div class="w-full h-2 rounded-full bg-white/5 overflow-hidden border border-white/10">
                        <div class="h-full bg-gradient-to-r from-[#7D39EB] to-[#C6FF33] transition-all duration-300 ease-out shadow-[0_0_15px_#C6FF33]"
                             :style="'width: ' + (((currentIndex + 1) / currentQuestions.length) * 100) + '%'"></div>
                    </div>
                </div>

                {{-- Question Card --}}
                <div class="bg-[#0b0c10]/85 backdrop-blur-2xl border border-white/[0.1] rounded-[32px] p-6 sm:p-9 space-y-6 shadow-[0_20px_50px_rgba(0,0,0,0.8),inset_0_1px_1px_rgba(255,255,255,0.1)] relative">
                    
                    {{-- Spec Badge & Topic --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/[0.08] pb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="px-3 py-1 rounded-full bg-violet-500/20 border border-violet-400/30 text-violet-300 text-xs font-mono font-bold"
                                  x-text="currentQuestion.code">
                            </span>
                            <span class="text-xs font-semibold text-neutral-300" x-text="currentQuestion.theme"></span>
                        </div>
                        <div class="text-[11px] font-bold text-amber-400 flex items-center gap-1.5 font-mono">
                            <span>⚡ Ловушка РИКЗ</span>
                            <span class="text-neutral-600">•</span>
                            <span class="text-neutral-400">Потеря до <span x-text="currentQuestion.gapLoss"></span> баллов</span>
                        </div>
                    </div>

                    {{-- Question Text --}}
                    <div class="space-y-3.5">
                        <h2 class="text-lg sm:text-xl font-bold text-white leading-relaxed" x-text="currentQuestion.text"></h2>
                        
                        <template x-if="currentQuestion.codeBlock">
                            <div class="bg-black/60 border border-white/10 rounded-2xl p-4 sm:p-5 font-mono text-sm text-[#C6FF33] whitespace-pre-wrap leading-relaxed shadow-inner"
                                 x-text="currentQuestion.codeBlock">
                            </div>
                        </template>
                    </div>

                    {{-- Options List --}}
                    <div class="space-y-3 pt-2">
                        <template x-for="(opt, oIndex) in currentQuestion.options" :key="oIndex">
                            <div @click="selectOption(oIndex)"
                                 :class="answers[currentIndex] === oIndex 
                                    ? 'border-[#C6FF33] bg-[#C6FF33]/10 text-white ring-1 ring-[#C6FF33] shadow-[0_0_20px_rgba(198,255,51,0.15)]' 
                                    : 'border-white/10 bg-white/[0.02] text-neutral-300 hover:border-white/25 hover:bg-white/[0.04]'"
                                 class="p-4 sm:p-5 rounded-2xl border cursor-pointer transition-all duration-150 flex items-center justify-between group">
                                
                                <div class="flex items-center gap-3.5 pr-4">
                                    <span class="w-7 h-7 rounded-xl border flex items-center justify-center text-xs font-mono font-bold shrink-0 transition-colors"
                                          :class="answers[currentIndex] === oIndex 
                                            ? 'bg-[#C6FF33] border-[#C6FF33] text-black font-black' 
                                            : 'border-neutral-700 bg-neutral-800 text-neutral-400 group-hover:border-neutral-500 group-hover:text-white'">
                                        <span x-text="oIndex + 1"></span>
                                    </span>
                                    <span class="text-sm sm:text-base font-medium leading-relaxed" x-text="opt"></span>
                                </div>

                                <div class="w-6 h-6 rounded-full border flex items-center justify-center text-xs shrink-0 transition-all"
                                     :class="answers[currentIndex] === oIndex 
                                        ? 'bg-[#C6FF33] border-[#C6FF33] text-black font-black shadow-[0_0_10px_#C6FF33]' 
                                        : 'border-neutral-700 text-transparent'">
                                    ✓
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Navigation Buttons --}}
                    <div class="flex items-center justify-between pt-5 border-t border-white/[0.08] gap-3">
                        <button type="button"
                                @click="prevQuestion()"
                                :disabled="currentIndex === 0"
                                class="px-5 py-3 rounded-full border border-white/10 text-xs font-bold text-neutral-400 hover:text-white hover:border-white/25 disabled:opacity-30 disabled:pointer-events-none transition cursor-pointer">
                            ← Назад
                        </button>

                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline text-xs text-neutral-500 font-mono">Клавиши 1-4, Enter</span>
                            
                            <template x-if="currentIndex < currentQuestions.length - 1">
                                <button type="button"
                                        @click="nextQuestion()"
                                        :disabled="answers[currentIndex] === undefined"
                                        class="px-7 py-3 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(198,255,51,0.25)] disabled:opacity-30 disabled:pointer-events-none transition flex items-center gap-2 cursor-pointer">
                                    <span>Далее</span>
                                    <span>→</span>
                                </button>
                            </template>

                            <template x-if="currentIndex === currentQuestions.length - 1">
                                <button type="button"
                                        @click="finishAndAnalyze()"
                                        :disabled="answers[currentIndex] === undefined"
                                        class="px-8 py-3.5 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-black text-xs uppercase tracking-wider shadow-[0_0_25px_rgba(198,255,51,0.35)] disabled:opacity-30 disabled:pointer-events-none transition flex items-center gap-2 cursor-pointer">
                                    <span>Завершить и рассчитать балл</span>
                                    <span>🚀</span>
                                </button>
                            </template>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- PHASE 3: ANALYZING (Кинематографичный радар & нейро-скан)  --}}
            {{-- ========================================================== --}}
            <div x-show="phase === 'analyzing'" x-cloak class="max-w-xl mx-auto w-full text-center space-y-8 py-8 transition-opacity duration-300">
                
                {{-- Radar Container --}}
                <div class="relative w-64 h-64 mx-auto flex items-center justify-center">
                    
                    {{-- Outer Glowing Concentric Rings --}}
                    <div class="absolute inset-0 rounded-full border border-violet-500/25 pulse-ambient"></div>
                    <div class="absolute -inset-4 rounded-full border border-[#C6FF33]/20 animate-pulse"></div>
                    <div class="absolute -inset-10 rounded-full border border-dashed border-white/10"></div>
                    
                    {{-- Rotating Radar Line --}}
                    <div class="absolute inset-0 rounded-full radar-spinner pointer-events-none">
                        <div class="w-1/2 h-1/2 bg-gradient-to-br from-[#C6FF33]/35 via-transparent to-transparent rounded-tl-full"></div>
                    </div>

                    {{-- Neural Core --}}
                    <div class="w-36 h-36 rounded-full bg-[#0b0c10] border-2 border-[#C6FF33] shadow-[0_0_50px_rgba(198,255,51,0.45)] flex flex-col items-center justify-center z-10 relative">
                        <div class="text-3xl font-black text-white tracking-tight font-mono" x-text="analysisPercent + '%'"></div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-[#C6FF33] mt-1 font-mono">EDUSFERA AI</div>
                    </div>

                </div>

                {{-- Dynamic Status Messaging --}}
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-neutral-300">
                        <span class="w-2 h-2 rounded-full bg-[#C6FF33] animate-ping"></span>
                        <span>Модель РИКЗ-Скан v2.6</span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight min-h-12 flex items-center justify-center"
                        x-text="currentStatusText">
                    </h2>

                    <p class="text-xs text-neutral-400 font-mono">
                        Сопоставление ответов со спецификацией ЦЭ/ЦТ 2026 и базой 10 000+ абитуриентов...
                    </p>
                </div>

                {{-- Linear Progress Bar --}}
                <div class="max-w-md mx-auto space-y-1.5">
                    <div class="w-full h-1.5 rounded-full bg-white/5 overflow-hidden border border-white/10">
                        <div class="h-full bg-gradient-to-r from-violet-500 via-[#C6FF33] to-[#C6FF33] transition-all duration-150 shadow-[0_0_10px_#C6FF33]"
                             :style="'width: ' + analysisPercent + '%'"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-neutral-500 font-mono">
                        <span>0% ЗАПУСК</span>
                        <span>СИНТЕЗ КАРТЫ ЗНАНИЙ</span>
                        <span>100% ФИНИШ</span>
                    </div>
                </div>

            </div>

        </main>

        {{-- ─── FOOTER ─── --}}
        @include('partials.site-footer')

    </div>

</body>
</html>
