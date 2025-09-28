<?php
require __DIR__.'/../bootstrap/autoload.php';

use App\Support\Database;

$pdo = Database::connection();

$queries = [
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        created_at TEXT,
        updated_at TEXT
    )',
    'CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        note TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    )',
    'CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sku TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        note TEXT,
        category_id INTEGER,
        cost_price REAL DEFAULT 0,
        sale_price REAL DEFAULT 0,
        expire_date TEXT,
        reorder_point INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        quantity INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )',
    'CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id)',
    'CREATE INDEX IF NOT EXISTS idx_products_expire ON products(expire_date)',
    'CREATE INDEX IF NOT EXISTS idx_products_reorder ON products(reorder_point)',
    'CREATE TABLE IF NOT EXISTS stock_movements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        amount INTEGER NOT NULL,
        note TEXT,
        actor_id INTEGER,
        happened_at TEXT,
        created_at TEXT,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (actor_id) REFERENCES users(id)
    )',
    'CREATE INDEX IF NOT EXISTS idx_movements_product ON stock_movements(product_id)',
    'CREATE INDEX IF NOT EXISTS idx_movements_happened ON stock_movements(happened_at)'
];

foreach ($queries as $query) {
    $pdo->exec($query);
}

echo "migration completed\n";
