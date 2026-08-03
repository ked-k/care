# Radminly — Modern Laravel 12 Admin Template

> **Ship beautiful admin panels, faster.**

Radminly is a premium **Laravel 12** admin template with a modern **Tailwind CSS v4 + Alpine.js** interface — pure Blade, **no jQuery or Bootstrap**. It ships role & permission management, a REST API, 25+ reusable Blade components, dark mode, a live theme customizer and fully-designed modules (Dashboard, Inventory, POS, Accounting, Reports, Settings, Users).

- 🔐 **Roles & permissions** (Spatie) with a scalable permission picker
- 🧩 **25+ Blade components** — tables, drawers, modals, tabs, forms, stat cards…
- 📊 **Smart data table** — search, export (CSV/Excel/PDF/Copy), print, pagination, bulk actions
- 🌗 **Dark mode** + **live theme customizer** (8 presets, saved per browser)
- 🧱 **Ready-made modules** — Inventory, POS, Accounting, Reports, Settings
- 🔌 **REST API** powered by Laravel Passport
- 🐳 **Docker** setup for a one-command local environment

**Tech stack:** Laravel 12 · PHP 8.2+ · Tailwind CSS v4 · Alpine.js · Vite · Spatie Laravel Permission · Laravel Passport · MySQL 8.

---

## Requirements

- **PHP** >= 8.2 (with `pdo_mysql`, `mbstring`, `openssl`, `gd`)
- **Composer** 2.x
- **Node.js** >= 18 & npm
- **MySQL** 8 (or MariaDB 10.6+)
- **Docker** & Docker Compose — optional, recommended

---

## 🐳 Quick start with Docker (recommended)

```bash
# 1 · Environment (pre-configured for the Docker stack)
cp .env.docker.example .env

# 2 · Build & start the containers (php, nginx, mysql)
docker compose up -d --build

# 3 · Install PHP dependencies
docker compose exec php composer install

# 4 · Generate the application key
docker compose exec php php artisan key:generate

# 5 · Run migrations + seeders (creates demo data & the admin user)
docker compose exec php php artisan migrate --seed

# 6 · Build front-end assets
npm install && npm run build
```

Open **http://localhost:8900**

> **Note:** the `php` container loads `.env` via `env_file`, so values become real OS
> environment variables at container creation. If you change `APP_NAME` (or any env var)
> afterwards, recreate the container so it is picked up:
> `docker compose up -d --force-recreate php`

---

## ⚙️ Manual installation (without Docker)

```bash
# 1 · Dependencies
composer install
npm install

# 2 · Environment
cp .env.example .env
php artisan key:generate

# 3 · Configure your database in .env
#     DB_HOST=127.0.0.1
#     DB_DATABASE=radminly
#     DB_USERNAME=root
#     DB_PASSWORD=secret

# 4 · Database schema + demo data
php artisan migrate --seed
#   (or import database/database.sql manually)

# 5 · Build assets (use `npm run dev` while developing)
npm run build

# 6 · Serve
php artisan serve
```

Open **http://127.0.0.1:8000**

---

## 🔑 Default login

| Email | Password |
| --- | --- |
| `admin@test.com` | `1234` |

> Change these credentials before deploying to production.

---

## 🎨 Branding & theming

- **App name & tagline** — set in `.env` (`APP_NAME`, `APP_TAGLINE`) and surfaced everywhere via `config('app.name')` / `config('app.tagline')`.
- **Logo** — swap the `<x-brand-logo>` component and `public/img/radminly-mark.svg` (also the favicon).
- **Colors** — edit the Tailwind v4 `@theme` tokens in `resources/css/app.css` (`--color-primary-*`, sidebar/topbar tokens, dark-mode overrides). There is **no** `tailwind.config.js`.
- **Live customizer** — open it from the droplet icon in the header; choices are stored in `localStorage` (no rebuild required).

---

## 📦 Building assets

```bash
npm run dev      # hot-reloading dev server
npm run build    # optimized production build → public/build
```

---

## 🗂 Project structure

```
app/Http/Controllers   # Roles, Permission, User, Dashboard, API…
config/menu.php         # sidebar + workspace navigation (single source)
resources/css/app.css   # Tailwind v4 @theme tokens
resources/js/app.js     # Alpine + plugins + global stores
resources/views/
  layouts/main.blade.php # authenticated app shell
  include/               # head, header, sidebar, footer
  components/            # 25+ <x-...> Blade components
docs/                    # landing page + documentation + release notes
docker-compose.yml       # php, nginx, mysql
```

---

## 📚 Documentation

Full end-to-end docs (installation, configuration, theming, components, roles & permissions, REST API, deployment) ship in the **`docs/`** folder:

- `docs/index.html` — product landing page
- `docs/documentation.html` — complete documentation
- `docs/release-notes.html` — version history & changelog

Open them directly in a browser, or host them under your domain.

---

## 🚀 Deployment

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Set `APP_ENV=production` and `APP_DEBUG=false`, point the web root at `/public`, and ensure `storage/` and `bootstrap/cache/` are writable.

---

## 👤 Author & credits

- **Author:** Md. Rakibul Islam · [rakib1708@gmail.com](mailto:rakib1708@gmail.com)
- **Studio:** [Themicly](https://themicly.com)

Built with Laravel, Tailwind CSS, Alpine.js, Spatie Laravel Permission, Laravel Passport and IconKit.

## License

Released under the [MIT License](https://opensource.org/licenses/MIT).
