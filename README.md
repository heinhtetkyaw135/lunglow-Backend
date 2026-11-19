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

## Seeded accounts
| Role   | Email                  | Password   |
|--------|------------------------|------------|
| Admin  | admin@lunglow.dev      | Admin@2410 |
| Member | heinhtet@lunglow.dev   | password   |

> Use the **Admin** credentials on the admin login page (`POST /admin/login`).
> Use the **Member** credentials on the normal user login (`POST /login`).

## Frontend
Set `Frontend/.env`:
```
VITE_API_URL=http://127.0.0.1:8001/api
```
> Port **8001** is used because an older copy of this backend may already
> occupy `8000`. If `8000` is free you may serve there and point the env at it.

For the full API reference, route groups, and real-time notification details see
[README-API.md](README-API.md).
