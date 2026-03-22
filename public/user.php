<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$user = requirePageAuth(['user', 'admin']);
?><!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AE Accounts User Page</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="page">
        <header class="hero">
            <div>
                <p class="badge">User Dashboard</p>
                <h1>Welcome, <?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="lead">இந்த page user counter use க்கு. Product மற்றும் price add பண்ண முடியாது. Sales, service, expense entry மட்டும் add பண்ணலாம்.</p>
            </div>
            <div class="hero-card">
                <p><strong>Logged in as:</strong> <?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?></p>
                <button id="logoutButton" class="secondary">Logout</button>
            </div>
        </header>

        <section class="stats" id="summaryCards"></section>

        <section class="grid grid-2">
            <div class="panel">
                <div class="panel-head">
                    <h2>User Daily Entry</h2>
                    <span class="muted">Sale / Service only</span>
                </div>
                <form id="transactionForm" class="form-grid cols-2">
                    <select name="product_id" id="productSelect">
                        <option value="">Select product (optional)</option>
                    </select>
                    <input name="item_name" placeholder="Or type item name">
                    <input name="category" placeholder="Category / service type">
                    <select name="type" required>
                        <option value="sale">Sale</option>
                        <option value="service">Service</option>
                    </select>
                    <input name="quantity" type="number" min="1" step="1" placeholder="Quantity" required>
                    <input name="amount" type="number" min="0" step="0.01" placeholder="Amount" required>
                    <input name="customer_name" placeholder="Customer name">
                    <select name="payment_mode">
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank</option>
                    </select>
                    <input name="bill_no" placeholder="Bill number">
                    <input name="note" placeholder="Note">
                    <button type="submit" class="span-2">Save User Entry</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Expense Entry</h2>
                    <span class="muted">Counter expense</span>
                </div>
                <form id="expenseForm" class="form-grid">
                    <input name="title" placeholder="Expense title" required>
                    <input name="category" placeholder="Expense category" required>
                    <input name="amount" type="number" min="0" step="0.01" placeholder="Amount" required>
                    <select name="payment_mode" required>
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                        <option value="bank">Bank</option>
                    </select>
                    <input name="note" placeholder="Note">
                    <button type="submit" class="secondary">Save Expense</button>
                </form>
            </div>
        </section>

        <section class="grid grid-2 top-gap-lg">
            <div class="panel">
                <div class="panel-head">
                    <h2>Product List</h2>
                    <span class="muted">Read only for user</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody id="productsTable"></tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Recent Expenses</h2>
                    <span class="muted">View only</span>
                </div>
                <div id="expensesList" class="stack-list"></div>
            </div>
        </section>

        <section class="panel top-gap-lg">
            <div class="panel-head">
                <h2>Ledger</h2>
                <span class="muted">View income / expense</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Entry</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Party</th>
                            <th>Payment</th>
                            <th>Bill</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTable"></tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        const urls = {
            dashboard: './api.php?path=dashboard',
            transactions: './api.php?path=transactions',
            expenses: './api.php?path=expenses',
            logout: './api.php?path=logout',
        };

        const formatCurrency = (value) => new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 2,
        }).format(Number(value || 0));

        async function request(url, options = {}) {
            const response = await fetch(url, {
                headers: { 'Content-Type': 'application/json' },
                ...options,
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Something went wrong');
            }
            return data;
        }

        function renderSummary(summary) {
            const items = [
                ['Today Income', formatCurrency(summary.today_income)],
                ['Today Expense', formatCurrency(summary.today_expense)],
                ['Today Net', formatCurrency(summary.today_net)],
                ['Month Net', formatCurrency(summary.month_net)],
            ];

            document.getElementById('summaryCards').innerHTML = items.map(([label, value]) => `
                <article class="stat-card">
                    <p>${label}</p>
                    <h3>${value}</h3>
                </article>
            `).join('');
        }

        function renderProducts(products) {
            document.getElementById('productsTable').innerHTML = products.map((product) => `
                <tr>
                    <td>${product.name}</td>
                    <td>${product.category}</td>
                    <td>${product.stock} ${product.unit}</td>
                    <td>${formatCurrency(product.sell_price)}</td>
                </tr>
            `).join('');

            document.getElementById('productSelect').innerHTML = ['<option value="">Select product (optional)</option>'].concat(
                products.map((product) => `<option value="${product.id}">${product.name} (${product.stock})</option>`)
            ).join('');
        }

        function renderExpenses(expenses) {
            document.getElementById('expensesList').innerHTML = expenses.length
                ? expenses.map((expense) => `
                    <article class="list-card">
                        <strong>${expense.title}</strong>
                        <p>${expense.category} • ${formatCurrency(expense.amount)}</p>
                    </article>
                `).join('')
                : '<p class="empty-state">No expenses yet.</p>';
        }

        function renderLedger(entries) {
            document.getElementById('ledgerTable').innerHTML = entries.map((entry) => {
                const amountClass = Number(entry.amount) < 0 ? 'amount-out' : 'amount-in';
                return `
                    <tr>
                        <td>${new Date(entry.created_at).toLocaleString('en-IN')}</td>
                        <td>${entry.entry_type.toUpperCase()}</td>
                        <td>${entry.title}</td>
                        <td>${entry.category}</td>
                        <td>${entry.party_name || '-'}</td>
                        <td>${entry.payment_mode ? entry.payment_mode.toUpperCase() : '-'}</td>
                        <td>${entry.bill_no || '-'}</td>
                        <td class="${amountClass}">${formatCurrency(entry.amount)}</td>
                    </tr>
                `;
            }).join('');
        }

        async function loadDashboard() {
            const data = await request(urls.dashboard);
            renderSummary(data.summary);
            renderProducts(data.products);
            renderExpenses(data.expenses);
            renderLedger(data.ledger);
        }

        document.getElementById('transactionForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
            await request(urls.transactions, { method: 'POST', body: JSON.stringify(payload) });
            event.currentTarget.reset();
            await loadDashboard();
        });

        document.getElementById('expenseForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
            await request(urls.expenses, { method: 'POST', body: JSON.stringify(payload) });
            event.currentTarget.reset();
            await loadDashboard();
        });

        document.getElementById('logoutButton').addEventListener('click', async () => {
            await request(urls.logout, { method: 'POST', body: JSON.stringify({}) });
            window.location.href = './index.php';
        });

        loadDashboard().catch((error) => alert(error.message));
    </script>
</body>
</html>
