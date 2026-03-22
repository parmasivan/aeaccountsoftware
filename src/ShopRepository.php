<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class ShopRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function dashboard(): array
    {
        $products = $this->pdo->query('SELECT * FROM products ORDER BY created_at DESC, id DESC')->fetchAll();
        $transactions = $this->pdo->query(
            'SELECT t.*, p.name AS product_name
             FROM transactions t
             JOIN products p ON p.id = t.product_id
             ORDER BY t.created_at DESC, t.id DESC'
        )->fetchAll();

        $summary = [
            'product_count' => count($products),
            'stock_value' => (float) $this->pdo->query('SELECT COALESCE(SUM(stock * sell_price), 0) FROM products')->fetchColumn(),
            'today_sales' => (float) $this->pdo->query(
                "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type IN ('sale', 'service') AND date(created_at) = date('now', 'localtime')"
            )->fetchColumn(),
            'month_sales' => (float) $this->pdo->query(
                "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type IN ('sale', 'service') AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now', 'localtime')"
            )->fetchColumn(),
        ];

        return [
            'summary' => $summary,
            'products' => $products,
            'transactions' => $transactions,
        ];
    }

    public function createProduct(array $data): array
    {
        $this->ensureRequired($data, ['name', 'category', 'unit', 'sell_price', 'stock']);

        $statement = $this->pdo->prepare(
            'INSERT INTO products (name, category, unit, sell_price, stock, created_at) VALUES (:name, :category, :unit, :sell_price, :stock, :created_at)'
        );

        $statement->execute([
            ':name' => trim((string) $data['name']),
            ':category' => trim((string) $data['category']),
            ':unit' => trim((string) $data['unit']),
            ':sell_price' => (float) $data['sell_price'],
            ':stock' => (int) $data['stock'],
            ':created_at' => date(DATE_ATOM),
        ]);

        return $this->findProduct((int) $this->pdo->lastInsertId());
    }

    public function createTransaction(array $data): array
    {
        $this->ensureRequired($data, ['product_id', 'type', 'quantity', 'amount']);

        $product = $this->findProduct((int) $data['product_id']);
        $type = (string) $data['type'];
        $quantity = (int) $data['quantity'];
        $amount = (float) $data['amount'];

        if (!in_array($type, ['sale', 'purchase', 'service'], true)) {
            throw new RuntimeException('Transaction type must be sale, purchase, or service.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO transactions (product_id, type, quantity, amount, note, created_at) VALUES (:product_id, :type, :quantity, :amount, :note, :created_at)'
        );
        $insert->execute([
            ':product_id' => (int) $product['id'],
            ':type' => $type,
            ':quantity' => $quantity,
            ':amount' => $amount,
            ':note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ':created_at' => date(DATE_ATOM),
        ]);

        $stockDelta = $type === 'purchase' ? $quantity : -$quantity;
        $update = $this->pdo->prepare('UPDATE products SET stock = stock + :stock_delta WHERE id = :id');
        $update->execute([
            ':stock_delta' => $stockDelta,
            ':id' => (int) $product['id'],
        ]);

        return $this->findTransaction((int) $this->pdo->lastInsertId());
    }

    public function products(): array
    {
        return $this->pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();
    }

    private function findProduct(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = :id');
        $statement->execute([':id' => $id]);
        $product = $statement->fetch();

        if ($product === false) {
            throw new RuntimeException('Product not found.');
        }

        return $product;
    }

    private function findTransaction(int $id): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.*, p.name AS product_name
             FROM transactions t
             JOIN products p ON p.id = t.product_id
             WHERE t.id = :id'
        );
        $statement->execute([':id' => $id]);
        $transaction = $statement->fetch();

        if ($transaction === false) {
            throw new RuntimeException('Transaction not found.');
        }

        return $transaction;
    }

    private function ensureRequired(array $data, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data) || $data[$key] === '') {
                throw new RuntimeException(sprintf('%s is required.', $key));
            }
        }
    }
}
