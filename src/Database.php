<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(private readonly string $databasePath)
    {
        $directory = dirname($this->databasePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->pdo = new PDO('sqlite:' . $this->databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $this->migrate();
        $this->seed();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    private function migrate(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                unit TEXT NOT NULL,
                sell_price REAL NOT NULL,
                stock INTEGER NOT NULL DEFAULT 0,
                reorder_level INTEGER NOT NULL DEFAULT 5,
                created_at TEXT NOT NULL
            )'
        );

        $this->ensureColumn('products', 'reorder_level', 'INTEGER NOT NULL DEFAULT 5');

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                item_name TEXT NOT NULL,
                category TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("sale", "purchase", "service")),
                quantity INTEGER NOT NULL,
                amount REAL NOT NULL,
                customer_name TEXT,
                payment_mode TEXT,
                bill_no TEXT,
                note TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )'
        );

        $this->ensureColumn('transactions', 'item_name', 'TEXT');
        $this->ensureColumn('transactions', 'category', 'TEXT');
        $this->ensureColumn('transactions', 'customer_name', 'TEXT');
        $this->ensureColumn('transactions', 'payment_mode', 'TEXT');
        $this->ensureColumn('transactions', 'bill_no', 'TEXT');

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS expenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                category TEXT NOT NULL,
                amount REAL NOT NULL,
                payment_mode TEXT NOT NULL,
                note TEXT,
                created_at TEXT NOT NULL
            )'
        );

        $this->backfillTransactionDetails();
    }

    private function seed(): void
    {
        $productCount = (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        if ($productCount === 0) {
            $products = [
                ['Xerox B/W', 'Xerox', 'pages', 2.00, 500, 100],
                ['Colour Print', 'Printout', 'pages', 10.00, 150, 40],
                ['Lamination A4', 'Lamination', 'sheet', 35.00, 80, 10],
                ['Photo Frame 8x10', 'Photo Frame', 'piece', 250.00, 25, 5],
                ['Spiral Binding', 'Binding', 'book', 60.00, 40, 10],
                ['Mobile Charger', 'Mobile Accessories', 'piece', 450.00, 18, 5],
            ];

            $insertProduct = $this->pdo->prepare(
                'INSERT INTO products (name, category, unit, sell_price, stock, reorder_level, created_at) VALUES (:name, :category, :unit, :sell_price, :stock, :reorder_level, :created_at)'
            );

            $now = date(DATE_ATOM);
            foreach ($products as [$name, $category, $unit, $price, $stock, $reorderLevel]) {
                $insertProduct->execute([
                    ':name' => $name,
                    ':category' => $category,
                    ':unit' => $unit,
                    ':sell_price' => $price,
                    ':stock' => $stock,
                    ':reorder_level' => $reorderLevel,
                    ':created_at' => $now,
                ]);
            }
        }

        $transactionCount = (int) $this->pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        if ($transactionCount === 0) {
            $insertTransaction = $this->pdo->prepare(
                'INSERT INTO transactions (product_id, item_name, category, type, quantity, amount, customer_name, payment_mode, bill_no, note, created_at)
                 VALUES (:product_id, :item_name, :category, :type, :quantity, :amount, :customer_name, :payment_mode, :bill_no, :note, :created_at)'
            );

            $now = date(DATE_ATOM);
            $transactions = [
                [1, 'Xerox B/W', 'Xerox', 'sale', 120, 240.00, 'College Student', 'cash', 'BILL-1001', 'Morning Xerox orders'],
                [3, 'Lamination A4', 'Lamination', 'sale', 8, 280.00, 'Office Staff', 'upi', 'BILL-1002', 'Certificate lamination'],
                [5, 'Spiral Binding', 'Binding', 'service', 5, 300.00, 'Project Team', 'cash', 'BILL-1003', 'Project binding'],
                [6, 'Mobile Charger', 'Mobile Accessories', 'sale', 2, 900.00, 'Walk-in Customer', 'card', 'BILL-1004', 'Accessories counter'],
            ];

            foreach ($transactions as [$productId, $itemName, $category, $type, $quantity, $amount, $customerName, $paymentMode, $billNo, $note]) {
                $insertTransaction->execute([
                    ':product_id' => $productId,
                    ':item_name' => $itemName,
                    ':category' => $category,
                    ':type' => $type,
                    ':quantity' => $quantity,
                    ':amount' => $amount,
                    ':customer_name' => $customerName,
                    ':payment_mode' => $paymentMode,
                    ':bill_no' => $billNo,
                    ':note' => $note,
                    ':created_at' => $now,
                ]);
            }
        }

        $expenseCount = (int) $this->pdo->query('SELECT COUNT(*) FROM expenses')->fetchColumn();
        if ($expenseCount === 0) {
            $insertExpense = $this->pdo->prepare(
                'INSERT INTO expenses (title, category, amount, payment_mode, note, created_at) VALUES (:title, :category, :amount, :payment_mode, :note, :created_at)'
            );

            $now = date(DATE_ATOM);
            $expenses = [
                ['Shop Rent', 'Rent', 5000.00, 'bank', 'Monthly shop rent'],
                ['A4 Paper Bundle', 'Stock Purchase', 1400.00, 'upi', 'Xerox paper stock'],
                ['EB Bill', 'Utilities', 650.00, 'cash', 'Current bill'],
            ];

            foreach ($expenses as [$title, $category, $amount, $paymentMode, $note]) {
                $insertExpense->execute([
                    ':title' => $title,
                    ':category' => $category,
                    ':amount' => $amount,
                    ':payment_mode' => $paymentMode,
                    ':note' => $note,
                    ':created_at' => $now,
                ]);
            }
        }
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $statement = $this->pdo->query(sprintf('PRAGMA table_info(%s)', $table));
        $columns = $statement->fetchAll();

        foreach ($columns as $existingColumn) {
            if (($existingColumn['name'] ?? null) === $column) {
                return;
            }
        }

        $this->pdo->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
    }

    private function backfillTransactionDetails(): void
    {
        $this->pdo->exec(
            'UPDATE transactions
             SET item_name = COALESCE(item_name, (SELECT name FROM products WHERE products.id = transactions.product_id), "General Item")
             WHERE item_name IS NULL OR item_name = ""'
        );

        $this->pdo->exec(
            'UPDATE transactions
             SET category = COALESCE(category, (SELECT category FROM products WHERE products.id = transactions.product_id), "General")
             WHERE category IS NULL OR category = ""'
        );
    }
}
