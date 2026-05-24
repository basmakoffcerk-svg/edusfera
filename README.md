# Edusfera

Edusfera is a Laravel 12 platform for finding tutors, booking lessons, paying through the platform, and continuing communication in a moderated chat.

## Features

- public landing pages
- tutor catalog with filters and tutor profile pages
- slot booking with a 15-minute payment hold
- checkout flow with lesson packages
- mock payment gateway for local development
- moderated chat with contact masking before payment
- tutor and student dashboards (Filament)
- tutor finance tracking and lesson settlement

## Tech Stack

- PHP 8.3
- Laravel 12
- Filament 3
- PostgreSQL 16
- Redis
- Mailhog
- Vite

## Getting Started

1. Install dependencies:

```bash
composer install
npm install
```

2. Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Start infrastructure:

```bash
docker compose up -d
```

4. Run migrations:

```bash
php artisan migrate
```

5. Start development processes:

```bash
composer run dev
```

After startup:

- app: `http://127.0.0.1:8000`
- Vite: `http://localhost:5173`
- Mailhog: `http://127.0.0.1:8025`
- admin login: `http://127.0.0.1:8000/admin/login`
- site admin login: `http://127.0.0.1:8000/site-admin/login`

## Running Tests

```bash
php artisan test
```

The test suite uses SQLite in memory via `phpunit.xml`, while local runtime uses PostgreSQL from `.env`.

## Main Routes

- `/` - landing page
- `/tutors` - tutor catalog
- `/for-tutors` - landing page for tutors
- `/contacts` - support and contacts
- `/offer` - public offer
- `/refund-policy` - refund policy
- `/privacy-policy` - privacy policy

## Documentation

- Product roadmap: `docs/PRODUCT_ROADMAP_90_DAYS.md`
- Russian roadmap: `docs/PRODUCT_ROADMAP_90_DAYS_RU.md`
- UI rules: `docs/UI_RULES.md`

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
