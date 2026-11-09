# Lunglow Backend (Laravel 12 + Sanctum API)

REST API for the Lunglow React SPA. MySQL on XAMPP.

## Requirements
- PHP 8.2+, Composer, XAMPP MySQL running on `127.0.0.1:3306`

## Setup
```bash
cd Backend
composer install
cp .env.example .env   # or keep the provided .env
php artisan key:generate
# create DB:  CREATE DATABASE lunglow_db CHARACTER SET utf8mb4;
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8001
```

## Database config (`.env`)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lunglow_db
DB_USERNAME=root
DB_PASSWORD=
```
Session/cache/queue use the `file`/`sync` drivers — no extra tables.

## Seeded accounts
| Role   | Email                  | Password   |
|--------|------------------------|------------|
| Admin  | admin@lunglow.dev      | admin123   |
| Member | heinhtet@lunglow.dev   | password   |

## Frontend
Set `Frontend/.env`:
```
VITE_API_URL=http://127.0.0.1:8001/api
```
> Port **8001** is used because an older copy of this backend may already
> occupy `8000`. If `8000` is free you may serve there and point the env at it.

Auth is Sanctum **bearer token** (localStorage `lunglow_token`) — no cookies /
stateful CSRF needed. `supports_credentials` + permissive CORS are enabled for
local dev in `config/cors.php`.

## Conventions
- Ids returned to the SPA are **prefixed** (`post-1`, `blog-2`, `ch-3`, `u-4`,
  `req-5`, `pcom-6`, `bcom-7`) to match the app's mock data contract.
- Comments are polymorphic across posts & blogs.
- The 7×6 craving heatmap derives each cell via `MAX(intensity)` of logged
  `craving_logs` events.
- `POST /challenges/{id}/log-day` enforces one clean-day log per calendar day.
- File uploads (avatar, post/blog images, challenge cover) go to
  `storage/app/public` and are served via the `public/storage` symlink.

## Route groups
- Public: `POST /register|/login|/admin/login`, `GET /posts|/blogs|/challenges|/broadcast`
- Member (`auth:sanctum`): profile, settings, smoke-logs, goals, reminders,
  heatmap, checkins, posts/blogs/comments, challenges join/log, reports, requests,
  `GET /notifications/due` (real-time peak-craving alerts, polled every ~30s)
- Admin (`auth:sanctum` + `admin`): `/admin/...` users, reports, requests,
  challenges CRUD, broadcast publish/clear, analytics overview

## Real-time peak-craving notifications
When a user marks a heatmap cell as **Peak Craving** (`POST /heatmap/toggle` or
`/heatmap/log` with `intensity=2`), a recurring weekly `peak_craving_alerts`
row is armed for that `day_index`/`slot_index`. `GET /notifications/due` (polled
by the SPA) delivers one `user_notifications` row the moment that window's start
hour arrives (slot `0`=6 AM … `5`=9 PM), then marks it `fetched_at` so it fires
once. A `Schedule::call(...)->everyMinute()` closure in `routes/console.php`
provides a fallback delivery path; `php artisan schedule:work` enables it during
dev. Timezone is `Asia/Yangon` (`APP_TIMEZONE`); grace window is
`PEAK_CRAVING_GRACE_MINUTES` (default 10).
