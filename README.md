# 🛒 apiecomerce — Laravel E-Commerce REST API

> **الـ Backend الكامل** لمشروع متجر إلكتروني مبني على Laravel 12، بيوفر API شاملة لكل العمليات من Authentication لحد Orders.

---

## 🧱 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 12+ (PHP 8.2+) |
| **Database** | MySQL |
| **Authentication** | Laravel Sanctum (Bearer Token) |
| **Authorization (Roles)** | Spatie Laravel Permission |
| **File Storage** | Local Disk (Images & Videos) |
| **Testing** | PHPUnit 11 |
| **Code Style** | Laravel Pint + StyleCI |

---

## 🗂️ Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php       # Register, Login, Logout, Me
│   │       ├── CategoryController.php   # CRUD للكاتيجوريز
│   │       ├── ProductController.php    # CRUD للمنتجات + رفع صور وفيديوهات
│   │       ├── CartController.php       # إدارة السلة
│   │       ├── WishlistController.php   # قائمة المفضلة
│   │       ├── ReviewController.php     # التقييمات والريفيوهات
│   │       ├── CouponController.php     # الكوبونات
│   │       └── OrderController.php      # الطلبات
│   ├── Requests/                        # Form Request Validation
│   └── Resources/                       # API Resources (JSON Transformers)
├── Models/                              # Eloquent Models
├── Services/
│   └── FileUploadService.php           # رفع الملفات وحذفها
└── Providers/

database/
└── migrations/                          # كل الـ DB Tables

routes/
└── api.php                              # كل الـ API Routes
```

---

## 🗄️ Database Schema

الجداول الموجودة في الـ Database:

| Table | Description |
|-------|------------|
| `users` | المستخدمين |
| `roles` / `permissions` | أدوار المستخدمين (Spatie) |
| `categories` | الأقسام (تدعم Parent/Child + عربي/إنجليزي) |
| `products` | المنتجات (مع slug, SKU, سعر قديم وجديد, كمية) |
| `product_images` | صور المنتجات (primary image support) |
| `product_videos` | فيديوهات المنتجات |
| `carts` / `cart_items` | سلة التسوق |
| `wishlists` | قائمة المفضلة |
| `reviews` | التقييمات (rating 1-5, comment, is_approved) |
| `coupons` | الكوبونات (fixed/percentage, usage limit, date range) |
| `orders` | الطلبات (مع shipping info و GPS coordinates) |
| `order_items` | عناصر الطلب (snapshot للمنتج وقت الشراء) |

---

## 🔐 Authentication & Authorization

### نظام الأدوار (3 Roles)

| Role | Permissions |
|------|------------|
| **customer** | تسوق، cart، wishlist، orders، reviews |
| **vendor** | إدارة منتجاته الخاصة فقط |
| **admin** | كل شيء |

### Auth Flow (Sanctum)
```
POST /api/register  → { user, token }
POST /api/login     → { user, token }
POST /api/logout    → revokes token
GET  /api/me        → current user info
```

---

## 🚀 Setup & Installation

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations
php artisan migrate

# 4. Link storage (for images/videos)
php artisan storage:link

# 5. Run dev server
php artisan serve
```

---

## 📡 API Endpoints

### 🔓 Public Routes (بدون Auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | كل الكاتيجوريز الأساسية مع الـ children |
| GET | `/api/categories/{slug}` | كاتيجوري واحدة مع منتجاتها |
| GET | `/api/products` | قائمة المنتجات (مع فلاتر وباجينيشن) |
| GET | `/api/products/{slug}` | تفاصيل منتج واحد |
| GET | `/api/products/{id}/reviews` | تقييمات منتج |

#### Product Filters
| Parameter | Description |
|-----------|-------------|
| `?category={slug}` | فلتر بالكاتيجوري |
| `?search={term}` | بحث في الاسم والوصف |
| `?min_price=` / `?max_price=` | فلتر بالسعر |
| `?featured=1` | المنتجات المميزة فقط |
| `?vendor={id}` | منتجات بائع معين |
| `?sort_by=price\|name\|created_at` | ترتيب |
| `?per_page=15` | عدد النتائج (max 100) |

---

### 🔒 Authenticated Routes (تحتاج Bearer Token)

#### 🛒 Cart
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cart` | عرض السلة |
| POST | `/api/cart/items` | إضافة منتج |
| PUT | `/api/cart/items/{productId}` | تعديل الكمية |
| DELETE | `/api/cart/items/{productId}` | حذف منتج |
| DELETE | `/api/cart` | تفريغ السلة |

#### ❤️ Wishlist
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/wishlist` | عرض المفضلة |
| POST | `/api/wishlist` | إضافة/إزالة (toggle) |
| DELETE | `/api/wishlist/{productId}` | إزالة منتج |

#### ⭐ Reviews
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/products/{id}/reviews` | إضافة تقييم |
| PUT | `/api/reviews/{id}` | تعديل تقييم |
| DELETE | `/api/reviews/{id}` | حذف تقييم |

#### 🎟️ Coupons
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/coupons/validate` | التحقق من كوبون |

#### 📦 Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/orders` | إنشاء طلب من السلة |
| GET | `/api/orders` | طلباتي |
| GET | `/api/orders/{orderNumber}` | تفاصيل طلب |
| PATCH | `/api/orders/{orderNumber}/cancel` | إلغاء طلب (pending فقط) |

---

### 🏪 Vendor Routes (`role: vendor|admin`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/vendor/products` | إضافة منتج |
| PUT | `/api/vendor/products/{id}` | تعديل منتج |
| DELETE | `/api/vendor/products/{id}` | حذف منتج |
| POST | `/api/vendor/products/{id}/images` | إضافة صور |
| DELETE | `/api/vendor/products/{id}/images/{imageId}` | حذف صورة |
| POST | `/api/vendor/products/{id}/videos` | إضافة فيديوهات |
| GET | `/api/vendor/orders` | طلبات الفيندور |

---

### 👑 Admin Routes (`role: admin`)

| Resource | Endpoints |
|----------|-----------|
| Categories | POST/PUT/DELETE `/api/admin/categories` |
| Coupons | GET/POST/PUT/DELETE `/api/admin/coupons` |
| Orders | GET/PATCH `/api/admin/orders` (مع فلاتر شاملة) |
| Reviews | GET/PATCH `/api/admin/reviews` (approve/disapprove) |

---

## 📁 File Storage

الملفات بتتحفظ في `storage/app/public/` وبتتقدم عبر symlink:

```
storage/app/public/categories/images/
storage/app/public/categories/videos/
storage/app/public/products/images/
storage/app/public/products/videos/
```

- **Images:** jpg, jpeg, png, webp — max **2MB**
- **Videos:** mp4, mov, avi — max **50MB**
- **Max per product:** 10 صور + 5 فيديوهات

---

## 📊 Order Lifecycle

```
pending → confirmed → processing → shipped → delivered
                                           ↘ cancelled | refunded
```

---

## 🔗 Related Project

- **Frontend:** [`Eslamawd/Ecommerce`](https://github.com/Eslamawd/Ecommerce) — Next.js 16 + TypeScript
