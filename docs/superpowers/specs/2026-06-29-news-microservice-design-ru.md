# Дизайн-спецификация: Легковесный модуль новостей (News Service)

Спецификация описывает архитектуру, схему базы данных, интеграцию в панель управления Filament и дизайн публичных страниц для модуля новостей платформы Edusfera.

---

## 1. Архитектура базы данных и подключение

Для изоляции новостного контента от основных данных платформы используется отдельная база данных SQLite.

### 1.1 Настройка подключения в `config/database.php`
В список подключений добавляется новое соединение `news`:
```php
'news' => [
    'driver' => 'sqlite',
    'database' => database_path('news.sqlite'),
    'prefix' => '',
    'foreign_key_constraints' => true,
],
```

### 1.2 Создание файла базы данных
В процессе инициализации проекта создается пустой файл `database/news.sqlite`. Команда `touch database/news.sqlite` интегрируется в `composer.json` в секцию `setup`.

### 1.3 Таблица `news_articles`
Миграция создается по пути `database/migrations/news/2026_06_29_000000_create_news_articles_table.php` и запускается отдельно:
```php
Schema::connection('news')->create('news_articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->string('featured_image')->nullable();
    $table->string('video_url')->nullable();
    $table->string('status')->default('draft'); // draft, published
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

---

## 2. Панель администратора (Filament Resource)

Администрирование новостей будет интегрировано исключительно в административную панель для технического персонала по адресу `/site-admin`.

### 2.1 Ограничение доступа к панели
Для того чтобы ресурс новостей отображался только в `/site-admin`, в классе `NewsArticleResource` переопределяется метод `canAccess`:
```php
public static function canAccess(): bool
{
    return auth()->user()?->role === 'admin' && filament()->getCurrentPanel()?->getId() === 'site-admin';
}
```

### 2.2 Форма создания и редактирования статьи (Form Schema)
Форма содержит следующие компоненты:
* **Заголовок (`title`)**: Текстовое поле, обязательное. При вводе автоматически генерирует `slug` в реальном времени.
* **ЧПУ (`slug`)**: Текстовое поле, обязательное, уникальное.
* **Содержимое (`content`)**: Компонент `RichEditor`, занимающий всю ширину формы. Позволяет форматировать текст, добавлять ссылки, цитаты и загружать изображения (сохраняются в директорию `news-attachments`).
* **Обложка (`featured_image`)**: Компонент `FileUpload` с валидацией на изображения. Картинки загружаются в директорию `news-covers`.
* **Ссылка на видео (`video_url`)**: Текстовое поле для ввода URL-адреса видео (YouTube, Vimeo).
* **Статус (`status`)**: Выпадающий список (`draft` — Черновик, `published` — Опубликовано) с дефолтным значением `draft`.
* **Дата публикации (`published_at`)**: Поле выбора даты и времени. По умолчанию устанавливается текущее время.

### 2.3 Таблица списка статей (Table Schema)
Таблица отображает:
* Миниатюру обложки (`featured_image`).
* Заголовок статьи (`title`) с возможностью поиска.
* Статус публикации (`status`) в виде цветного бейджа (серый для черновиков, зеленый для опубликованных).
* Дату публикации (`published_at`) с возможностью сортировки.

---

## 3. Публичная часть (Frontend & Routes)

### 3.1 Маршруты в `routes/web.php`
```php
use App\Http\Controllers\NewsController;

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
```

### 3.2 Парсинг ссылок на видео
В модели `NewsArticle` будет реализован метод для автоматического преобразования обычных ссылок на YouTube/Vimeo в ссылки для встраивания (`embed`):
* YouTube: `https://www.youtube.com/watch?v=XXXX` или `https://youtu.be/XXXX` -> `https://www.youtube.com/embed/XXXX`
* Vimeo: `https://vimeo.com/XXXX` -> `https://player.vimeo.com/video/XXXX`

Метод модели:
```php
public function getEmbedVideoUrlAttribute(): ?string
{
    if (!$this->video_url) {
        return null;
    }

    // YouTube
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->video_url, $match)) {
        return "https://www.youtube.com/embed/{$match[1]}";
    }

    // Vimeo
    if (preg_match('%vimeo\.com/(?:channels/(?:\w+/)?|groups/([^/]*)/videos/|album/(\d+)/video/|video/|)(\d+)(?:$|/|\?)%i', $this->video_url, $match)) {
        return "https://player.vimeo.com/video/{$match[3]}";
    }

    return null;
}
```

### 3.3 Дизайн страниц новостей

#### 3.3.1 Каталог новостей (`/news`)
* **Сетка карточек**: Плитка новостей с современным адаптивным дизайном.
* **Карточки**: Использование эффекта стекла (glassmorphism), тонкие границы (`border-white/10` на темном фоне или `border-gray-200` на светлом), мягкие тени и микро-анимация при наведении (легкое приподнятие карточки, плавное масштабирование обложки).
* **Контент карточки**: Категория/дата, заголовок, краткое описание статьи (обрезанный текст до 150 символов) и кнопка «Читать далее».

#### 3.3.2 Детальная страница статьи (`/news/{slug}`)
* **Макет**: Центрированная колонка фиксированной ширины (max-w-4xl) для максимального удобства чтения.
* **Заголовок и метаданные**: Крупный заголовок статьи, дата публикации, кнопка «Назад к списку».
* **Плеер видео**: Если добавлена ссылка на видео, оно отображается вверху статьи в красивом адаптивном контейнере:
  ```html
  <div class="aspect-video w-full overflow-hidden rounded-2xl border border-white/10 bg-black shadow-2xl mb-8">
      <iframe class="h-full w-full" src="{{ $article->embed_video_url }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
  </div>
  ```
* **Обложка**: Если видео нет, но есть обложка — выводится стильное изображение во всю ширину.
* **Содержимое статьи**: Текст форматируется с помощью типографического CSS-класса для красивого отображения заголовков, абзацев, списков и изображений, добавленных через RichEditor.

### 3.4 Обновление навигации
Кнопка-ссылка «Новости» будет добавлена:
1. В основной шапке `resources/views/partials/public-header.blade.php`
2. В боковом/альтернативном меню `resources/views/partials/site-nav.blade.php`
3. В инлайновых шапках на страницах `home.blade.php` и `catalog/index.blade.php`

---

## 4. План верификации

### 4.1 Автоматизированные тесты
Будут созданы unit-тесты для верификации парсинга ссылок на видео (`NewsArticleVideoParsingTest`) и интеграционные тесты для проверки доступности страниц каталога и детального просмотра статьи (`NewsControllerTest`).

### 4.2 Ручное тестирование
* Проверка создания новости, сохранения её в базу `news.sqlite` через панель `/site-admin`.
* Проверка встраивания различных форматов ссылок на видео (короткие `youtu.be`, стандартные `youtube.com`).
* Визуальный контроль отображения страниц новостей на мобильных и десктопных экранах.
