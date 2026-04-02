# Shop PoC (Proof of Concept)

A fully functional ecommerce storefront built as a Marko module to demonstrate the framework's capabilities.

## Purpose

Demonstrate Marko's module system, routing, templating, database, and session packages working together in a realistic application. Designed to be deployable on fly.io with PostgreSQL.

## Module: `app/shop`

Namespace: `App\Shop`

### Tech Stack

- **Database**: PostgreSQL 17 (installed via Homebrew locally)
- **Templates**: Latte 3 (via `marko/view-latte`)
- **Session**: Database-backed (via `marko/session-database`, PostgreSQL `sessions` table)
- **Styling**: Tailwind CSS (CDN), Material Symbols icons, Manrope + Inter fonts
- **Images**: Placeholder images from picsum.photos
- **Payments**: None (free payment method — PoC only)

### Framework Packages Used

- `marko/view` + `marko/view-latte` — template rendering
- `marko/database` + `marko/database-pgsql` — PostgreSQL via Repository/Entity pattern
- `marko/session` + `marko/session-database` — session-based cart (PostgreSQL)

### Directory Structure

```
app/shop/
├── composer.json                    # Module definition
├── src/
│   ├── Controller/
│   │   ├── HomepageController.php   # GET /
│   │   ├── CategoryController.php   # GET /collections
│   │   ├── ProductController.php    # GET /product/{slug}
│   │   ├── CartController.php       # GET /cart, POST /cart/add, /cart/update, /cart/remove
│   │   ├── CheckoutController.php   # GET /checkout, POST /checkout/place-order
│   │   └── OrderController.php      # GET /order/{id}
│   ├── Entity/
│   │   ├── Product.php              # products table
│   │   ├── Order.php                # orders table
│   │   └── OrderItem.php            # order_items table
│   ├── Repository/
│   │   ├── ProductRepository.php
│   │   ├── OrderRepository.php
│   │   └── OrderItemRepository.php
│   └── Service/
│       └── CartService.php          # Session-based cart
└── resources/views/
    ├── layout/
    │   └── base.latte               # Shared layout (nav, minicart JS, footer)
    ├── components/
    │   └── minicart.latte           # Slide-out cart drawer
    └── pages/
        ├── homepage.latte           # Hero + new arrivals bento grid
        ├── collections.latte        # Product grid
        ├── product.latte            # PDP with color/size selectors
        ├── checkout.latte           # Shipping + order summary + place order
        └── order-complete.latte     # Thank you / confirmation page
```

### Routes

| Method | Path                    | Controller              | Description                     |
|--------|-------------------------|-------------------------|---------------------------------|
| GET    | `/`                     | HomepageController      | Hero + featured products        |
| GET    | `/collections`          | CategoryController      | All products grid               |
| GET    | `/product/{slug}`       | ProductController       | Product detail page             |
| GET    | `/cart`                 | CartController          | Cart JSON API                   |
| POST   | `/cart/add`             | CartController          | Add item (returns JSON)         |
| POST   | `/cart/update`          | CartController          | Update quantity (returns JSON)  |
| POST   | `/cart/remove`          | CartController          | Remove item (returns JSON)      |
| GET    | `/checkout`             | CheckoutController      | Checkout form (redirects if empty) |
| POST   | `/checkout/place-order` | CheckoutController      | Creates order, redirects        |
| GET    | `/order/{id}`           | OrderController         | Order confirmation page         |

### Database Schema

Four tables created by `bin/setup-db.php`:

- **products** — id, name, slug, description, price (cents), category, image_url, sizes (JSON string), colors (JSON string)
- **orders** — id, reference, email, first_name, last_name, shipping_method, shipping_cost, subtotal, tax, total (all money in cents), created_at
- **order_items** — id, order_id (FK), product_id, product_name, price, quantity, size, color
- **sessions** — id (PK), payload, last_activity

### Seed Data (6 products)

| Product                    | Price    | Category    |
|---------------------------|----------|-------------|
| Structured Wool Overcoat  | $850.00  | Outerwear   |
| Architectural Blazer      | $1,200.00| Tailoring   |
| Pure Cashmere Knit        | $495.00  | Knitwear    |
| Japanese Selvedge Denim   | $320.00  | Essentials  |
| The Atelier Tote          | $1,550.00| Accessories |
| Merino Utility Cardigan   | $420.00  | Knitwear    |

### Cart

Session-based via `CartService`. Cart items keyed by `{productId}-{size}-{color}`. All cart API endpoints return JSON with `items`, `count`, `subtotal`, `formattedSubtotal`.

The mini cart is a slide-out drawer rendered in `base.latte` with vanilla JS (fetch API). Uses `n:syntax="off"` on the script block to prevent Latte from parsing JS template literals.

### Checkout Flow

1. Cart must have items (empty cart redirects to `/collections`)
2. Form collects: email, first name, last name, shipping method (standard free / express $25)
3. Tax calculated at 8.5%
4. "Free payment method" (no real payment processing)
5. Creates Order + OrderItems, clears cart, redirects to `/order/{id}`

### Configuration Files

- `config/database.php` — PostgreSQL connection (reads from `.env`)
- `config/session.php` — Database driver (PostgreSQL `sessions` table)
- `config/view.php` — Latte cache in `storage/views`, auto_refresh enabled

### Environment Variables (`.env`)

```
APP_ENV=local
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=marko_shop
DB_USERNAME=marko
DB_PASSWORD=marko
```

### Setup Commands

```bash
# Install dependencies
composer install

# Create and seed database
createdb marko_shop    # if database doesn't exist
php bin/setup-db.php

# Start dev server
vendor/bin/marko up     # runs on http://localhost:8000
```

### Known Latte Gotchas

- **JS in templates**: Latte parses `{...}` inside `<script>` tags. Use `n:syntax="off"` on script blocks containing vanilla JS with template literals (`${var}`).
- **Block content types**: Don't override a `{block}` that lives inside `<script>` with Latte expressions — causes content type mismatch. Instead, use inline `<script n:syntax="off">` within `{block content}` and pass data via `data-*` attributes.
- **Dynamic URLs in onclick**: Latte's context-aware escaping wraps `{$var}` in quotes inside HTML attributes like `onclick="..."`, producing `%22slug%22` in URLs. Use `<a href>` tags instead of `onclick="window.location=..."`.
- **Entity IDs**: Auto-increment `$id` properties must be `?int = null` (not `int`) so the framework's `extract()` can handle them before insert.

### Removed/Disabled UI Elements

The following design elements were removed because they had no backend functionality:

- Nav search box and person icon
- "Atelier" nav link (dead href)
- Homepage category cards section and newsletter form
- Collections page sidebar (search, filters, size buttons, price range) and sort dropdown
- Footer "Information" links (About Us, Contact, Privacy) and newsletter section
