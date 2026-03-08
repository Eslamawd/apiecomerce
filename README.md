# E-Commerce API

Laravel-based E-Commerce API with Sanctum authentication supporting both Mobile (Bearer Token) and Web SPA (Cookie/Session) clients.

## Tech Stack
- **Backend:** Laravel 12+ (PHP 8.3)
- **Database:** MySQL
- **Auth:** Laravel Sanctum (Dual Mode)
- **Roles:** Spatie Laravel Permission
- **Storage:** Local (Images/Videos)

## Status
🚧 Under Development — Phase 2 (Products & Categories) completed

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
