<?php

declare(strict_types=1);
?><!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AE Accounts - Xerox Shop Dashboard</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="page">
        <header class="hero">
            <div>
                <p class="badge">PHP + SQLite + JSON API</p>
                <h1>Xerox / Print / Lamination Shop Accounts</h1>
                <p class="lead">Xerox, scan, printout, lamination, photo frame, spiral binding, mobile accessories மாதிரி items எல்லாத்தையும் ஒரு dashboard-ல account பாக்கலாம்.</p>
            </div>
            <div class="hero-card">
                <h2>API Endpoints</h2>
                <ul>
                    <li><code>GET /api.php?path=dashboard</code></li>
                    <li><code>GET /api.php?path=products</code></li>
                    <li><code>POST /api.php?path=products</code></li>
                    <li><code>POST /api.php?path=transactions</code></li>
                </ul>
            </div>
        </header>

        <section class="stats" id="summaryCards"></section>

        <section class="grid">
            <div class="panel">
                <div class="panel-head">
                    <h2>Products</h2>
                    <span class="muted">Stock + selling price</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
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
                    <h2>Add Product</h2>
                    <span class="muted">Frontend → API → Backend</span>
                </div>
                <form id="productForm" class="form-grid">
                    <input name="name" placeholder="Product name" required>
                    <input name="category" placeholder="Category" required>
                    <input name="unit" placeholder="Unit (piece/pages/book)" required>
                    <input name="sell_price" type="number" min="0" step="0.01" placeholder="Sell price" required>
                    <input name="stock" type="number" min="0" step="1" placeholder="Opening stock" required>
                    <button type="submit">Save Product</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Add Sale / Purchase</h2>
                    <span class="muted">Daily accounts entry</span>
                </div>
                <form id="transactionForm" class="form-grid">
                    <select name="product_id" id="productSelect" required></select>
                    <select name="type" required>
                        <option value="sale">Sale</option>
                        <option value="purchase">Purchase</option>
                        <option value="service">Service</option>
                    </select>
                    <input name="quantity" type="number" min="1" step="1" placeholder="Quantity" required>
                    <input name="amount" type="number" min="0" step="0.01" placeholder="Amount" required>
                    <input name="note" placeholder="Note (optional)">
                    <button type="submit">Save Entry</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2>Recent Transactions</h2>
                    <span class="muted">Latest sales and services</span>
                </div>
                <div id="transactionsList" class="transactions"></div>
            </div>
        </section>
    </div>

    <script>
        const dashboardUrl = './api.php?path=dashboard';
        const productUrl = './api.php?path=products';
        const transactionUrl = './api.php?path=transactions';

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
                ['Products', summary.product_count],
                ['Stock Value', formatCurrency(summary.stock_value)],
                ['Today Sales', formatCurrency(summary.today_sales)],
                ['Month Sales', formatCurrency(summary.month_sales)],
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
                    <td>${product.unit}</td>
                    <td>${product.stock}</td>
                    <td>${formatCurrency(product.sell_price)}</td>
                </tr>
            `).join('');

            document.getElementById('productSelect').innerHTML = products.map((product) => `
                <option value="${product.id}">${product.name} (${product.stock})</option>
            `).join('');
        }

        function renderTransactions(transactions) {
            document.getElementById('transactionsList').innerHTML = transactions.map((transaction) => `
                <article class="txn-card">
                    <div>
                        <strong>${transaction.product_name}</strong>
                        <p>${transaction.type.toUpperCase()} • Qty: ${transaction.quantity}</p>
                    </div>
                    <div>
                        <strong>${formatCurrency(transaction.amount)}</strong>
                        <p>${transaction.note ?? ''}</p>
                    </div>
                </article>
            `).join('');
        }

        async function loadDashboard() {
            const data = await request(dashboardUrl);
            renderSummary(data.summary);
            renderProducts(data.products);
            renderTransactions(data.transactions);
        }

        document.getElementById('productForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(event.currentTarget);
            const payload = Object.fromEntries(formData.entries());
            await request(productUrl, { method: 'POST', body: JSON.stringify(payload) });
            event.currentTarget.reset();
            await loadDashboard();
        });

        document.getElementById('transactionForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(event.currentTarget);
            const payload = Object.fromEntries(formData.entries());
            await request(transactionUrl, { method: 'POST', body: JSON.stringify(payload) });
            event.currentTarget.reset();
            await loadDashboard();
        });

        loadDashboard().catch((error) => {
            alert(error.message);
        });
    </script>
</body>
</html>
