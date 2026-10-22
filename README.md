# ShopFlow E-commerce

ShopFlow is a PHP storefront backed by Supabase PostgreSQL. Customers can register, browse and search products, manage a cart, apply coupons, place demo checkouts, and review order history. Admins manage the product catalog and stock.

## Features

- Session-based customer and admin accounts; passwords use PHP password hashing and state-changing forms use CSRF tokens.
- Product search, derived Shoes/Bags/Other categories, and catalog image selection.
- Persistent carts with stock validation when items are added or quantities change.
- Coupon validation for active dates, minimum order amount, maximum discount, total usage limit, and per-customer usage limit.
- Transactional checkout creates an order and order items, deducts stock, records coupon redemption, and clears the cart.
- Admin product create, edit, and delete operations.
- Firebase Realtime Database listener updates product stock and out-of-stock states in open storefronts.
- Checkout payment selection is a demo only; no payment is collected or card data stored.

## Data Stores

### Supabase PostgreSQL

PostgreSQL is the source of truth for accounts, products, carts, orders, coupons, and inventory. The application uses PDO with `pdo_pgsql` and SSL. Its queries expect these tables:

| Table | Purpose |
| --- | --- |
| `users` | Customer and admin accounts, including password hashes and roles |
| `products` | Product details, price, image, and available stock |
| `carts`, `cart_items` | Per-customer carts and product quantities |
| `orders`, `order_items` | Order totals and purchase-time product, price, and quantity snapshots |
| `coupons`, `coupon_redemptions` | Coupon rules, usage counts, and customer redemptions |

Checkout runs in a PostgreSQL transaction, locks the cart and product rows, validates current stock, and calls `public.deduct_inventory(BIGINT, INTEGER)` for conditional stock deductions. The database schema and SQL definition for this function are not included in this repository; they must already exist in Supabase. No trigger definitions or schema migrations are included here. Product categories are derived from product names and descriptions in PHP, not stored in a category table.

### Firebase Realtime Database

Firebase mirrors inventory for live storefront updates; it does not authorize purchases or replace PostgreSQL's stock checks. Inventory is stored at `inventory/products/{productId}` with `id`, `name`, `stock`, and `updatedAt` fields.

The PHP Admin SDK seeds this path from PostgreSQL when it is empty, then synchronizes product additions, edits, deletions, and stock deductions after successful checkout commits. The browser subscribes to `inventory/products`. `firebase-database.rules.json` permits public reads of inventory and denies client writes; server writes use a Firebase service account.

## Application Map

| Path | Responsibility |
| --- | --- |
| `index.php` | Product catalog, search, and Firebase stock listener configuration |
| `auth/` | Registration, login, and logout |
| `cart.php`, `cart_add.php`, `cart_update.php` | Customer cart display and updates |
| `checkout.php`, `verify_coupon.php`, `orders.php` | Coupon verification, checkout, and order history |
| `admin/products.php` | Admin product and stock management |
| `DB/database.php` | PostgreSQL connection and initial product query |
| `includes/auth.php` | Session roles, access checks, escaping, and CSRF helpers |
| `includes/firebase-inventory.php` | Firebase initialization, initial inventory seed, and server-side sync |
| `JS/firebase-inventory.js` | Realtime inventory subscription and storefront updates |
| `includes/product-image.php` | Product category and image selection helpers |

## Configuration and Deployment

The Docker image uses PHP 8.3 Apache, Composer, PDO PostgreSQL, and the extensions required by the Firebase Admin SDK. Render configuration is in `render.yaml`.

Set these environment variables in Render:

| Variable | Value |
| --- | --- |
| `DB_HOST` | Supabase database hostname only |
| `DB_PORT` | PostgreSQL port, usually `5432` |
| `DB_NAME` | Database name, usually `postgres` |
| `DB_USER` | Supabase database username |
| `DB_PASSWORD` | Supabase database password |
| `DB_SSLMODE` | `require` |
| `FIREBASE_DATABASE_URL` | Realtime Database URL |
| `FIREBASE_SERVICE_ACCOUNT_JSON` | Complete Firebase Admin service-account JSON |

Publish the rules from `firebase-database.rules.json` in Firebase Console. Keep the service-account key out of source control; `.gitignore` excludes local `.env` files and `vendor/`.
