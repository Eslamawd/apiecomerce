# E-Commerce API

Laravel-based E-Commerce API with Sanctum authentication supporting both Mobile (Bearer Token) and Web SPA (Cookie/Session) clients.

## Tech Stack
- **Backend:** Laravel 12+ (PHP 8.3)
- **Database:** MySQL
- **Auth:** Laravel Sanctum (Dual Mode)
- **Roles:** Spatie Laravel Permission
- **Storage:** Local (Images/Videos)

## Status
🚧 Under Development — Phase 3 (Reviews, Cart, Wishlist, Orders, Coupons) completed

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

---

## API Endpoints

### Categories (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | List all active parent categories with children |
| GET | `/api/categories/{slug}` | Show single category with its products |

### Categories (Admin — requires `auth:sanctum` + `role:admin`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/categories` | Create a new category |
| PUT | `/api/admin/categories/{id}` | Update an existing category |
| DELETE | `/api/admin/categories/{id}` | Delete a category |

#### Category Request Body (store/update)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | Yes (store) | Arabic name |
| `name_en` | string | Yes (store) | English name |
| `description` | string | No | Arabic description |
| `description_en` | string | No | English description |
| `image` | file | No | jpg, jpeg, png, webp — max 2MB |
| `video` | file | No | mp4, mov, avi — max 50MB |
| `parent_id` | integer | No | ID of parent category |
| `is_active` | boolean | No | Default: true |
| `sort_order` | integer | No | Default: 0 |

---

### Products (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List products with filters and pagination |
| GET | `/api/products/{slug}` | Show a single product with images, videos, category |

#### Product Index Filters

| Parameter | Description |
|-----------|-------------|
| `?category={slug}` | Filter by category slug |
| `?search={term}` | Search name, name_en, description |
| `?min_price={value}` | Minimum price |
| `?max_price={value}` | Maximum price |
| `?featured=1` | Show featured products only |
| `?vendor={id}` | Filter by vendor ID |
| `?sort_by={field}` | Sort field: `price`, `name`, `created_at` |
| `?sort_dir={asc\|desc}` | Sort direction |
| `?per_page={number}` | Results per page (default: 15, max: 100) |

### Products (Vendor/Admin — requires `auth:sanctum` + `role:vendor|admin`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/vendor/products` | Create a new product |
| PUT | `/api/vendor/products/{id}` | Update a product |
| DELETE | `/api/vendor/products/{id}` | Soft delete a product |
| POST | `/api/vendor/products/{id}/images` | Add images to product |
| DELETE | `/api/vendor/products/{id}/images/{imageId}` | Delete a specific image |
| POST | `/api/vendor/products/{id}/videos` | Add videos to product |
| DELETE | `/api/vendor/products/{id}/videos/{videoId}` | Delete a specific video |
| PATCH | `/api/vendor/products/{id}/images/{imageId}/primary` | Set image as primary |

#### Product Request Body (store/update)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | Yes (store) | Arabic name |
| `name_en` | string | Yes (store) | English name |
| `description` | string | Yes (store) | Arabic description |
| `description_en` | string | Yes (store) | English description |
| `price` | numeric | Yes (store) | Min: 0 |
| `old_price` | numeric | No | For discount display |
| `cost_price` | numeric | No | For vendor profit calculation |
| `sku` | string | No | Must be unique |
| `quantity` | integer | Yes (store) | Stock quantity, min: 0 |
| `category_id` | integer | Yes (store) | Must exist in categories |
| `is_active` | boolean | No | Default: true |
| `is_featured` | boolean | No | Default: false |
| `images[]` | file array | No | Max 10 images, jpg/jpeg/png/webp, max 2MB each |
| `videos[]` | file array | No | Max 5 videos, mp4/mov/avi, max 50MB each |

---

## Authorization

- **Vendors** can only manage their **own** products
- **Admins** can manage **all** products and categories

---

## Storage

Files are stored in `storage/app/public/` and served via the public symlink:

```
storage/app/public/categories/images/
storage/app/public/categories/videos/
storage/app/public/products/images/
storage/app/public/products/videos/
```

Run `php artisan storage:link` to create the public symlink.

---

## Phase 3 Endpoints

### Reviews (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products/{productId}/reviews` | List approved reviews for a product (paginated) |

### Reviews (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/products/{productId}/reviews` | Create a review for a product |
| PUT | `/api/reviews/{id}` | Update own review |
| DELETE | `/api/reviews/{id}` | Delete own review (or admin) |

#### Review Request Body (store)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `rating` | integer | Yes | 1–5 |
| `comment` | string | No | Max 1000 chars |

### Reviews (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/reviews` | List all reviews including unapproved |
| PATCH | `/api/admin/reviews/{id}/approve` | Toggle approve/disapprove |

---

### Cart (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cart` | View cart |
| POST | `/api/cart/items` | Add product to cart |
| PUT | `/api/cart/items/{productId}` | Update item quantity |
| DELETE | `/api/cart/items/{productId}` | Remove item from cart |
| DELETE | `/api/cart` | Clear entire cart |

#### Add Item Request Body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `product_id` | integer | Yes | Must exist |
| `quantity` | integer | Yes | Min: 1 |

---

### Wishlist (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/wishlist` | List wishlist products |
| POST | `/api/wishlist` | Toggle add/remove product |
| DELETE | `/api/wishlist/{productId}` | Remove product from wishlist |

#### Toggle Request Body

| Field | Type | Required |
|-------|------|----------|
| `product_id` | integer | Yes |

---

### Coupons (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/coupons/validate` | Validate a coupon code |

#### Validate Request Body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `code` | string | Yes | Coupon code |
| `order_total` | numeric | No | Used to calculate discount |

### Coupons (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/coupons` | List all coupons |
| POST | `/api/admin/coupons` | Create coupon |
| PUT | `/api/admin/coupons/{id}` | Update coupon |
| DELETE | `/api/admin/coupons/{id}` | Delete coupon |

#### Coupon Request Body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `code` | string | Yes | Unique code |
| `type` | enum | Yes | `fixed` or `percentage` |
| `value` | decimal | Yes | Discount value |
| `min_order_amount` | decimal | No | Minimum order total |
| `max_discount` | decimal | No | Max discount for percentage type |
| `usage_limit` | integer | No | Max total uses |
| `starts_at` | datetime | No | Coupon start date |
| `expires_at` | datetime | No | Coupon expiry date |
| `is_active` | boolean | No | Default: true |

---

### Orders (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/orders` | Create order from cart |
| GET | `/api/orders` | My orders (paginated) |
| GET | `/api/orders/{orderNumber}` | Order details |
| PATCH | `/api/orders/{orderNumber}/cancel` | Cancel order (pending only) |

#### Create Order Request Body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `shipping_name` | string | Yes | |
| `shipping_phone` | string | Yes | |
| `shipping_address` | string | Yes | |
| `shipping_city` | string | Yes | |
| `shipping_email` | string | No | |
| `shipping_latitude` | numeric | No | |
| `shipping_longitude` | numeric | No | |
| `payment_method` | enum | Yes | `cash_on_delivery` or `online` |
| `coupon_code` | string | No | Valid coupon code |
| `notes` | string | No | |

### Orders (Vendor)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/vendor/orders` | Orders containing vendor's products |

### Orders (Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/orders` | All orders with filters |
| GET | `/api/admin/orders/{orderNumber}` | Any order details |
| PATCH | `/api/admin/orders/{orderNumber}/status` | Update order status |

#### Admin Order Filters

| Query Param | Description |
|-------------|-------------|
| `?status=` | Filter by order status |
| `?user=` | Filter by user ID |
| `?from=` | Date range start |
| `?to=` | Date range end |
| `?search=` | Search by order number |
| `?payment_status=` | Filter by payment status |
| `?sort_by=` | Sort field (created_at, total, status) |
| `?sort_dir=` | asc or desc |
| `?per_page=` | Results per page (max 100) |

#### Update Status Request Body

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `status` | enum | Yes | pending, confirmed, processing, shipped, delivered, cancelled, refunded |
| `payment_status` | enum | No | pending, paid, failed, refunded |

#### Order Statuses

- `pending` → `confirmed` → `processing` → `shipped` → `delivered`
- `cancelled`, `refunded`
