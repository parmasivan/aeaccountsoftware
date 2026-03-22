# AE Accounts Shop Project

இது Xerox shop / printout center / lamination / photo frame / spiral binding / mobile accessories shop க்கு ஒரு practical PHP accounts project.

## Main features

- Login system with separate **admin page** and **user page**
- Admin மட்டும் product, price, stock master maintain பண்ணலாம்
- User counter page-ல் daily sale / service / expense entries add பண்ணலாம்
- Frontend dashboard (`public/`)
- Backend business logic (`src/`)
- JSON API (`public/api.php`)
- SQLite **அல்லது** MySQL / phpMyAdmin database support
- Low stock alert, ledger, income/expense summary

## Default login

- **Admin login:** `admin` / `admin123`
- **User login:** `staff` / `user123`

## Pages

- `public/index.php` - login page
- `public/admin.php` - admin dashboard
- `public/user.php` - user dashboard

## Database support

- `src/Database.php` - SQLite / MySQL setup, migration, seed
- `config/database.php` - database config loader
- `database/mysql_schema.sql` - phpMyAdmin import SQL file
- `storage/data.sqlite` - generated SQLite file (SQLite mode மட்டும்)

## phpMyAdmin / MySQL database add பண்ணுவது எப்படி?

1. XAMPP / WAMP / Laragon start பண்ணவும்.
2. Browser-ல் `http://localhost/phpmyadmin` open பண்ணவும்.
3. **New** click பண்ணி `ae_accounts` என்ற database create பண்ணவும்.
4. Collation `utf8mb4_unicode_ci` select பண்ணவும்.
5. `.env.example` file-ஐ copy பண்ணி `.env` create பண்ணவும்.
6. `.env` file-ல் கீழே உள்ள values set பண்ணவும்:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ae_accounts
DB_USERNAME=root
DB_PASSWORD=
```

7. விருப்பமிருந்தால் phpMyAdmin-ல் `database/mysql_schema.sql` file import பண்ணலாம்.
8. பிறகு project run பண்ணவும்:

```bash
php -S 127.0.0.1:8000 -t public
```

9. Login page open பண்ணவும்:

- `http://127.0.0.1:8000`

## SQLite mode run

`.env` இல்லாமலே run பண்ணினால் app SQLite use பண்ணும்:

```bash
php -S 127.0.0.1:8000 -t public
```

## Important API endpoints

- `POST /api.php?path=login`
- `POST /api.php?path=logout`
- `GET /api.php?path=me`
- `GET /api.php?path=dashboard`
- `GET /api.php?path=ledger`
- `POST /api.php?path=products` (**admin only**)
- `POST /api.php?path=transactions`
- `POST /api.php?path=expenses`

## Notes

- Admin மட்டும் product மற்றும் price add/update பண்ண வேண்டும்.
- User page-ல் product list read-only.
- Seed data முதல் run-ல் auto insert ஆகும்.
