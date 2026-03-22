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
                created_at TEXT NOT NULL
            )'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("sale", "purchase", "service")),
                quantity INTEGER NOT NULL,
                amount REAL NOT NULL,
                note TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY(product_id) REFERENCES products(id)
            )'
        );
    }

    private function seed(): void
    {
        $productCount = (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

        if ($productCount > 0) {
            return;
        }

        $products = [
            ['Xerox B/W', 'Xerox', 'pages', 2.00, 500],
            ['Colour Print', 'Printout', 'pages', 10.00, 150],
            ['Lamination A4', 'Lamination', 'sheet', 35.00, 80],
            ['Photo Frame 8x10', 'Photo Frame', 'piece', 250.00, 25],
            ['Spiral Binding', 'Binding', 'book', 60.00, 40],
            ['Mobile Charger', 'Mobile Accessories', 'piece', 450.00, 18],
        ];

        $insertProduct = $this->pdo->prepare(
            'INSERT INTO products (name, category, unit, sell_price, stock, created_at) VALUES (:name, :category, :unit, :sell_price, :stock, :created_at)'
        );

        $now = date(DATE_ATOM);
        foreach ($products as [$name, $category, $unit, $price, $stock]) {
            $insertProduct->execute([
                ':name' => $name,
                ':category' => $category,
                ':unit' => $unit,
                ':sell_price' => $price,
                ':stock' => $stock,
                ':created_at' => $now,
            ]);
        }

        $insertTransaction = $this->pdo->prepare(
            'INSERT INTO transactions (product_id, type, quantity, amount, note, created_at) VALUES (:product_id, :type, :quantity, :amount, :note, :created_at)'
        );

        $transactions = [
            [1, 'sale', 120, 240.00, 'Morning Xerox orders'],
            [3, 'sale', 8, 280.00, 'Certificate lamination'],
            [5, 'service', 5, 300.00, 'Project binding'],
            [6, 'sale', 2, 900.00, 'Accessories counter'],
        ];

        foreach ($transactions as [$productId, $type, $quantity, $amount, $note]) {
            $insertTransaction->execute([
                ':product_id' => $productId,
                ':type' => $type,
                ':quantity' => $quantity,
                ':amount' => $amount,
                ':note' => $note,
                ':created_at' => $now,
            ]);
        }
    }
}
