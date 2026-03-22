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
        $products = $this->products();
        $transactions = $this->transactions();
        $expenses = $this->expenses();

        $summary = [
            'product_count' => count($products),
            'stock_value' => (float) $this->pdo->query('SELECT COALESCE(SUM(stock * sell_price), 0) FROM products')->fetchColumn(),
            'today_income' => $this->sumTransactionsByPeriod('day', ['sale', 'service']),
            'today_expense' => $this->sumExpensesByPeriod('day') + $this->sumTransactionsByPeriod('day', ['purchase']),
            'month_income' => $this->sumTransactionsByPeriod('month', ['sale', 'service']),
            'month_expense' => $this->sumExpensesByPeriod('month') + $this->sumTransactionsByPeriod('month', ['purchase']),
            'low_stock_count' => (int) $this->pdo->query('SELECT COUNT(*) FROM products WHERE stock <= reorder_level')->fetchColumn(),
        ];
        $summary['today_net'] = $summary['today_income'] - $summary['today_expense'];
        $summary['month_net'] = $summary['month_income'] - $summary['month_expense'];

        return [
            'summary' => $summary,
            'products' => $products,
            'transactions' => $transactions,
            'expenses' => $expenses,
            'ledger' => $this->ledger(),
        ];
    }

    public function products(): array
    {
        return $this->pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();
    }

    public function transactions(): array
    {
        return $this->pdo->query(
            'SELECT id, product_id, item_name, category, type, quantity, amount, customer_name, payment_mode, bill_no, note, created_at
             FROM transactions
             ORDER BY datetime(created_at) DESC, id DESC
             LIMIT 12'
        )->fetchAll();
    }

    public function expenses(): array
    {
        return $this->pdo->query(
            'SELECT id, title, category, amount, payment_mode, note, created_at
             FROM expenses
             ORDER BY datetime(created_at) DESC, id DESC
             LIMIT 12'
        )->fetchAll();
    }

    public function ledger(): array
    {
        $statement = $this->pdo->query(
            'SELECT *
             FROM (
                SELECT created_at, "income" AS entry_type, type AS source_type, item_name AS title, category, amount, payment_mode, customer_name AS party_name, bill_no, note
                FROM transactions
                WHERE type IN ("sale", "service")
                UNION ALL
                SELECT created_at, "inventory" AS entry_type, type AS source_type, item_name AS title, category, amount * -1 AS amount, payment_mode, customer_name AS party_name, bill_no, note
                FROM transactions
                WHERE type = "purchase"
                UNION ALL
                SELECT created_at, "expense" AS entry_type, "expense" AS source_type, title, category, amount * -1 AS amount, payment_mode, NULL AS party_name, NULL AS bill_no, note
                FROM expenses
             ) ledger_entries
             ORDER BY datetime(created_at) DESC, title ASC'
        );

        return $statement->fetchAll();
    }

    public function createProduct(array $data): array
    {
        $this->ensureRequired($data, ['name', 'category', 'unit', 'sell_price', 'stock']);

        $statement = $this->pdo->prepare(
            'INSERT INTO products (name, category, unit, sell_price, stock, reorder_level, created_at)
             VALUES (:name, :category, :unit, :sell_price, :stock, :reorder_level, :created_at)'
        );

        $statement->execute([
            ':name' => trim((string) $data['name']),
            ':category' => trim((string) $data['category']),
            ':unit' => trim((string) $data['unit']),
            ':sell_price' => (float) $data['sell_price'],
            ':stock' => (int) $data['stock'],
            ':reorder_level' => isset($data['reorder_level']) && $data['reorder_level'] !== '' ? (int) $data['reorder_level'] : 5,
            ':created_at' => date(DATE_ATOM),
        ]);

        return $this->findProduct((int) $this->pdo->lastInsertId());
    }

    public function createTransaction(array $data): array
    {
        $this->ensureRequired($data, ['type', 'quantity', 'amount']);

        $type = (string) $data['type'];
        $quantity = (int) $data['quantity'];
        $amount = (float) $data['amount'];

        if (!in_array($type, ['sale', 'purchase', 'service'], true)) {
            throw new RuntimeException('Transaction type must be sale, purchase, or service.');
        }

        $productId = null;
        $itemName = trim((string) ($data['item_name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));

        if (isset($data['product_id']) && $data['product_id'] !== '') {
            $product = $this->findProduct((int) $data['product_id']);
            $productId = (int) $product['id'];
            $itemName = $itemName !== '' ? $itemName : (string) $product['name'];
            $category = $category !== '' ? $category : (string) $product['category'];
        }

        if ($itemName === '' || $category === '') {
            throw new RuntimeException('Either choose a product or provide item name and category.');
        }

        if ($quantity <= 0 || $amount < 0) {
            throw new RuntimeException('Quantity must be above 0 and amount cannot be negative.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO transactions (product_id, item_name, category, type, quantity, amount, customer_name, payment_mode, bill_no, note, created_at)
             VALUES (:product_id, :item_name, :category, :type, :quantity, :amount, :customer_name, :payment_mode, :bill_no, :note, :created_at)'
        );
        $insert->execute([
            ':product_id' => $productId,
            ':item_name' => $itemName,
            ':category' => $category,
            ':type' => $type,
            ':quantity' => $quantity,
            ':amount' => $amount,
            ':customer_name' => isset($data['customer_name']) ? trim((string) $data['customer_name']) : null,
            ':payment_mode' => isset($data['payment_mode']) ? trim((string) $data['payment_mode']) : null,
            ':bill_no' => isset($data['bill_no']) ? trim((string) $data['bill_no']) : null,
            ':note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ':created_at' => date(DATE_ATOM),
        ]);

        if ($productId !== null) {
            $stockDelta = match ($type) {
                'purchase' => $quantity,
                'sale' => -$quantity,
                default => 0,
            };

            if ($stockDelta !== 0) {
                $update = $this->pdo->prepare('UPDATE products SET stock = stock + :stock_delta WHERE id = :id');
                $update->execute([
                    ':stock_delta' => $stockDelta,
                    ':id' => $productId,
                ]);
            }
        }

        return $this->findTransaction((int) $this->pdo->lastInsertId());
    }

    public function createExpense(array $data): array
    {
        $this->ensureRequired($data, ['title', 'category', 'amount', 'payment_mode']);

        $amount = (float) $data['amount'];
        if ($amount < 0) {
            throw new RuntimeException('Expense amount cannot be negative.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO expenses (title, category, amount, payment_mode, note, created_at)
             VALUES (:title, :category, :amount, :payment_mode, :note, :created_at)'
        );
        $statement->execute([
            ':title' => trim((string) $data['title']),
            ':category' => trim((string) $data['category']),
            ':amount' => $amount,
            ':payment_mode' => trim((string) $data['payment_mode']),
            ':note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ':created_at' => date(DATE_ATOM),
        ]);

        return $this->findExpense((int) $this->pdo->lastInsertId());
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
            'SELECT id, product_id, item_name, category, type, quantity, amount, customer_name, payment_mode, bill_no, note, created_at
             FROM transactions
             WHERE id = :id'
        );
        $statement->execute([':id' => $id]);
        $transaction = $statement->fetch();

        if ($transaction === false) {
            throw new RuntimeException('Transaction not found.');
        }

        return $transaction;
    }

    private function findExpense(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM expenses WHERE id = :id');
        $statement->execute([':id' => $id]);
        $expense = $statement->fetch();

        if ($expense === false) {
            throw new RuntimeException('Expense not found.');
        }

        return $expense;
    }

    private function ensureRequired(array $data, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data) || trim((string) $data[$key]) === '') {
                throw new RuntimeException(sprintf('%s is required.', $key));
            }
        }
    }

    private function sumTransactionsByPeriod(string $period, array $types): float
    {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $expression = $period === 'day'
            ? "date(created_at) = date('now', 'localtime')"
            : "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now', 'localtime')";

        $statement = $this->pdo->prepare(
            sprintf('SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type IN (%s) AND %s', $placeholders, $expression)
        );
        $statement->execute($types);

        return (float) $statement->fetchColumn();
    }

    private function sumExpensesByPeriod(string $period): float
    {
        $expression = $period === 'day'
            ? "date(created_at) = date('now', 'localtime')"
            : "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now', 'localtime')";

        return (float) $this->pdo->query(sprintf('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE %s', $expression))->fetchColumn();
    }
}
