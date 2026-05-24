# Edusfera

Edusfera is a Laravel 12 platform for finding tutors, booking lessons, paying through the platform, and continuing communication in a moderated chat. The project includes a public catalog, checkout flow, tutor and student dashboards on Filament, and a technical admin panel.

## Stack

- PHP 8.3
- Laravel 12
- Filament 3
- PostgreSQL 16
- Redis
- Mailhog
- Vite

## Main Flows

- public landing pages
- tutor catalog with filters and profile pages
- slot booking with 15-minute payment hold
- checkout with lesson packages
- mock payment gateway for local development
- chat with contact masking before payment
- tutor finance tracking and lesson settlement

## Local Run

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

## Tests

Run:

```bash
php artisan test
```

The test suite uses SQLite in memory via `phpunit.xml`, while local runtime uses PostgreSQL from `.env`.

## Public Pages

- `/` - landing page
- `/tutors` - public tutor catalog
- `/for-tutors` - landing page for tutors
- `/contacts` - support and contact page

## Legal Pages

- `/offer` - public offer
- `/refund-policy` - refund rules
- `/privacy-policy` - privacy policy

## Support

- public support entrypoint: `/contacts`
- default support email: `MAIL_FROM_ADDRESS`

## Project Notes

- Payment processing is mocked in local development.
- Technical admin credentials are configured through `.env`.
- UI rules for all new screens are documented in [`docs/UI_RULES.md`](docs/UI_RULES.md).
# edusfera
