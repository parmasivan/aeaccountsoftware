# AE Accounts Shop Project

இது Xerox shop / printout center / lamination / photo frame / spiral binding / mobile accessories shop க்கு ஒரு practical PHP accounts project.

## இப்போ என்னென்ன இருக்கு?

- Frontend dashboard (`public/index.php`)
- Backend business logic (`src/`)
- JSON API (`public/api.php`)
- SQLite database auto-create + seed data (`storage/data.sqlite`)
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
- `src/Database.php` - SQLite setup, migration, seed
- `src/ShopRepository.php` - backend account logic
- `storage/data.sqlite` - generated database file

## Run locally

```bash
php -S 127.0.0.1:8000 -t public
```

பிறகு browser-ல் open பண்ணவும்:

- `http://127.0.0.1:8000`

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
