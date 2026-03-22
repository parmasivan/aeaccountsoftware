# AE Accounts Shop Project

இது Xerox shop / printout center / lamination / photo frame / spiral binding / mobile accessories shop க்கு ஒரு practical PHP accounts project.

## இப்போ என்னென்ன இருக்கு?

- Frontend dashboard (`public/index.php`)
- Backend business logic (`src/`)
- JSON API (`public/api.php`)
- SQLite **அல்லது** MySQL / phpMyAdmin database use பண்ணலாம்
- Product master add பண்ணலாம்
- Daily sale / service / stock purchase entry add பண்ணலாம்
- Shop expense add பண்ணலாம்
- Customer name, payment mode, bill number save பண்ணலாம்
- Low stock alert பார்க்கலாம்
- Income / expense / purchase ledger பார்க்கலாம்
- Today net / month net summary பார்க்கலாம்

## Project structure

- `public/index.php` - full dashboard UI + fetch calls
- `public/api.php` - API endpoints
- `src/Database.php` - SQLite / MySQL setup, migration, seed
- `src/ShopRepository.php` - backend account logic
- `config/database.php` - database config loader
- `database/mysql_schema.sql` - phpMyAdmin import SQL file
- `storage/data.sqlite` - generated SQLite file (SQLite mode மட்டும்)

## Run locally with SQLite

```bash
php -S 127.0.0.1:8000 -t public
```

பிறகு browser-ல் open பண்ணவும்:

- `http://127.0.0.1:8000`

## phpMyAdmin / MySQL database add பண்ணுவது எப்படி?

### Method 1: phpMyAdmin-ல் database create பண்ணி project connect பண்ணுவது

1. XAMPP / WAMP / Laragon start பண்ணவும்.
2. Browser-ல் `http://localhost/phpmyadmin` open பண்ணவும்.
3. **New** click பண்ணவும்.
4. Database name-ஆ `ae_accounts` enter பண்ணவும்.
5. Collation-ஆ `utf8mb4_unicode_ci` select பண்ணவும்.
6. **Create** click பண்ணவும்.
7. Project root-ல் `.env.example`-ஐ copy பண்ணி `.env` create பண்ணவும்.
8. `.env` file-ல் இந்த values set பண்ணவும்:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ae_accounts
DB_USERNAME=root
DB_PASSWORD=
```

9. இப்போ project run பண்ணவும்:

```bash
php -S 127.0.0.1:8000 -t public
```

10. First request வந்தவுடன் app MySQL database-க்கு connect ஆகி tables create பண்ணும்.

### Method 2: phpMyAdmin Import use பண்ணுவது

1. phpMyAdmin-ல் `ae_accounts` database open பண்ணவும்.
2. **Import** tab-க்கு போங்க.
3. இந்த file select பண்ணவும்:

- `database/mysql_schema.sql`

4. **Go** click பண்ணவும்.
5. அதன் பிறகு `.env` file-ல் MySQL settings set பண்ணி app run பண்ணவும்.

## Important files for MySQL setup

- `.env.example` - sample MySQL config
- `config/database.php` - app எந்த database use பண்ணணும் என்று decide பண்ணும் file
- `src/bootstrap.php` - `.env` load பண்ணும் file
- `src/Database.php` - SQLite / MySQL connect + migrate + seed logic
- `database/mysql_schema.sql` - phpMyAdmin import file

## API endpoints

### Dashboard

```bash
curl http://127.0.0.1:8000/api.php?path=dashboard
```

### Ledger மட்டும் பார்க்க

```bash
curl http://127.0.0.1:8000/api.php?path=ledger
```

### Product add

```bash
curl -X POST http://127.0.0.1:8000/api.php?path=products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "USB Cable",
    "category": "Mobile Accessories",
    "unit": "piece",
    "sell_price": 120,
    "stock": 20,
    "reorder_level": 5
  }'
```

### Daily sale / service / purchase add

```bash
curl -X POST http://127.0.0.1:8000/api.php?path=transactions \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "type": "sale",
    "quantity": 10,
    "amount": 20,
    "customer_name": "Walk-in customer",
    "payment_mode": "cash",
    "bill_no": "BILL-2001",
    "note": "Xerox counter sale"
  }'
```

### Expense add

```bash
curl -X POST http://127.0.0.1:8000/api.php?path=expenses \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Internet Bill",
    "category": "Utilities",
    "amount": 899,
    "payment_mode": "upi",
    "note": "Monthly broadband"
  }'
```

## Next improvements

- Login / user management
- Customer pending balance
- Supplier ledger
- Invoice / print bill
- GST report / export
