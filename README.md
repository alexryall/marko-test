# Marko Shop

A proof of concept ecommerce store built with the [Marko Framework](https://marko.build) — a modular PHP 8.5+ framework combining Magento's extensibility with Laravel's developer experience.

**Demo**: https://marko-test.fly.dev

## Tech Stack

- **Framework**: Marko (PHP 8.5+)
- **Server**: OpenSwoole (persistent HTTP server — boots once, handles requests from memory)
- **Database**: PostgreSQL
- **Templates**: Latte 3
- **Deployment**: Fly.io via GitHub Actions

## Features

- Product catalog with category browsing
- Product detail pages with size/color selection
- Shopping cart with session persistence
- Checkout flow with order confirmation
- Database-backed sessions (Swoole multi-worker compatible)

## Local Development

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Set up the database
createdb marko_shop
php bin/setup-db.php

# Start the server
marko up
```

Visit http://localhost:8000

## Project Structure

```
app/
  shop/       # Ecommerce module (controllers, entities, views)
  swoole/     # OpenSwoole HTTP server module
bin/
  swoole-server.php   # Server entry point
  setup-db.php        # Database setup and seeder
config/               # Application configuration
public/               # Static assets
```

## Deployment

Pushes to `main` trigger automatic deploys to Fly.io via GitHub Actions. See [.claude/deployment.md](.claude/deployment.md) for setup details.
