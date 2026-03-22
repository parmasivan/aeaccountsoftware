# AE Accounts Shop Project

இது Xerox shop / printout center / lamination / photo frame / spiral binding / mobile accessories shop க்கு ஒரு simple PHP project.

## Features

- Frontend dashboard (`public/index.php`)
- Backend business logic (`src/`)
- JSON API (`public/api.php`)
- SQLite database auto-create + seed data (`storage/data.sqlite`)
- Product add பண்ணலாம்
- Sale / purchase / service entries add பண்ணலாம்
- Stock value, today sales, month sales summary பார்க்கலாம்

## Project structure

- `public/index.php` - UI and frontend fetch calls
- `public/api.php` - API endpoints
- `src/Database.php` - SQLite setup and seed
- `src/ShopRepository.php` - backend CRUD/account logic
- `storage/data.sqlite` - generated database file

## Run locally

```bash
php -S 127.0.0.1:8000 -t public
```

பிறகு browser-ல் open பண்ணவும்:

- `http://127.0.0.1:8000`

## API examples

### Dashboard

```bash
curl http://127.0.0.1:8000/api.php?path=dashboard
```

### Add product

```bash
curl -X POST http://127.0.0.1:8000/api.php?path=products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "USB Cable",
    "category": "Mobile Accessories",
    "unit": "piece",
    "sell_price": 120,
    "stock": 20
  }'
```

### Add transaction

```bash
curl -X POST http://127.0.0.1:8000/api.php?path=transactions \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "type": "sale",
    "quantity": 10,
    "amount": 20,
    "note": "Xerox counter sale"
  }'
```

## Future improvements

- Customer ledger
- Supplier ledger
- Login/authentication
- Daily expense tracking
- GST bill export / invoice print
