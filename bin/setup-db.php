<?php

declare(strict_types=1);

/**
 * Database setup and seeder for the Marko Shop PoC.
 * Usage: php bin/setup-db.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Marko\Env\EnvLoader;

(new EnvLoader())->load(dirname(__DIR__));

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '5432');
$database = env('DB_DATABASE', 'marko_shop');
$username = env('DB_USERNAME', 'marko');
$password = env('DB_PASSWORD', 'marko');

$dsn = "pgsql:host={$host};port={$port};dbname={$database}";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    echo "Connection failed: {$e->getMessage()}\n";
    echo "Make sure PostgreSQL is running and the database '{$database}' exists.\n";
    echo "Create it with: createdb {$database}\n";
    exit(1);
}

echo "Connected to database.\n";

// Drop existing tables
$pdo->exec('DROP TABLE IF EXISTS order_items CASCADE');
$pdo->exec('DROP TABLE IF EXISTS orders CASCADE');
$pdo->exec('DROP TABLE IF EXISTS products CASCADE');
$pdo->exec('DROP TABLE IF EXISTS sessions CASCADE');

echo "Creating tables...\n";

$pdo->exec('
    CREATE TABLE products (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        price INTEGER NOT NULL,
        category VARCHAR(100) NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        sizes TEXT NOT NULL DEFAULT \'[]\',
        colors TEXT NOT NULL DEFAULT \'[]\'
    )
');

$pdo->exec('
    CREATE TABLE orders (
        id SERIAL PRIMARY KEY,
        reference VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        shipping_method VARCHAR(50) NOT NULL,
        shipping_cost INTEGER NOT NULL DEFAULT 0,
        subtotal INTEGER NOT NULL,
        tax INTEGER NOT NULL DEFAULT 0,
        total INTEGER NOT NULL,
        created_at VARCHAR(30) NOT NULL
    )
');

$pdo->exec('
    CREATE TABLE order_items (
        id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        product_id INTEGER NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        price INTEGER NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        size VARCHAR(50) NOT NULL DEFAULT \'One Size\',
        color VARCHAR(50) NOT NULL DEFAULT \'Default\'
    )
');

$pdo->exec('
    CREATE TABLE sessions (
        id VARCHAR(255) PRIMARY KEY,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    )
');

echo "Tables created.\n";
echo "Seeding products...\n";

$products = [
    [
        'name' => 'Structured Wool Overcoat',
        'slug' => 'structured-wool-overcoat',
        'description' => 'Meticulously structured from 100% heavy-weight Italian wool, the Architectural Overcoat defines the Marko Shop silhouette. It features a concealed button placket, silk-lined interior, and hand-finished lapels.',
        'price' => 85000,
        'category' => 'Outerwear',
        'image_url' => 'https://picsum.photos/seed/overcoat/800/1000',
        'sizes' => '["46","48","50","52","54"]',
        'colors' => '["Slate Steel","Midnight Black","Sandstone"]',
    ],
    [
        'name' => 'Architectural Blazer',
        'slug' => 'architectural-blazer',
        'description' => 'A masterclass in modern tailoring. Cut from premium Italian wool blend with a structured shoulder and slim silhouette. Features hand-stitched lapels and hidden interior pocket.',
        'price' => 120000,
        'category' => 'Tailoring',
        'image_url' => 'https://picsum.photos/seed/blazer/800/1000',
        'sizes' => '["46","48","50","52","54"]',
        'colors' => '["Slate Steel","Deep Charcoal"]',
    ],
    [
        'name' => 'Pure Cashmere Knit',
        'slug' => 'pure-cashmere-knit',
        'description' => 'Ultra-soft 100% Mongolian cashmere turtleneck. A timeless essential featuring ribbed cuffs and hemline with a relaxed yet refined fit.',
        'price' => 49500,
        'category' => 'Knitwear',
        'image_url' => 'https://picsum.photos/seed/cashmere/800/1000',
        'sizes' => '["XS","S","M","L","XL"]',
        'colors' => '["Natural","Slate Steel","Midnight Black"]',
    ],
    [
        'name' => 'Japanese Selvedge Denim',
        'slug' => 'japanese-selvedge-denim',
        'description' => 'Crafted from 14oz Japanese selvedge denim woven on vintage shuttle looms. Features a slim tapered fit, leather patch, and signature selvedge detailing.',
        'price' => 32000,
        'category' => 'Essentials',
        'image_url' => 'https://picsum.photos/seed/denim/800/1000',
        'sizes' => '["28","30","32","34","36"]',
        'colors' => '["Indigo","Midnight Black"]',
    ],
    [
        'name' => 'The Atelier Tote',
        'slug' => 'the-atelier-tote',
        'description' => 'Full-grain vegetable-tanned leather tote with brass hardware. Handmade in our atelier with unlined interior, magnetic closure, and reinforced handles.',
        'price' => 155000,
        'category' => 'Accessories',
        'image_url' => 'https://picsum.photos/seed/tote/800/1000',
        'sizes' => '["One Size"]',
        'colors' => '["Cognac","Midnight Black"]',
    ],
    [
        'name' => 'Merino Utility Cardigan',
        'slug' => 'merino-utility-cardigan',
        'description' => 'Extra-fine merino wool cardigan with utility-inspired patch pockets. Lightweight yet warm, with mother-of-pearl buttons and ribbed trim.',
        'price' => 42000,
        'category' => 'Knitwear',
        'image_url' => 'https://picsum.photos/seed/cardigan/800/1000',
        'sizes' => '["XS","S","M","L","XL"]',
        'colors' => '["Slate Steel","Sandstone"]',
    ],
];

$stmt = $pdo->prepare('
    INSERT INTO products (name, slug, description, price, category, image_url, sizes, colors)
    VALUES (:name, :slug, :description, :price, :category, :image_url, :sizes, :colors)
');

foreach ($products as $product) {
    $stmt->execute($product);
    echo "  Seeded: {$product['name']}\n";
}

echo "\nDone! {$database} is ready.\n";
