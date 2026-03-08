# API Documentation

Base URL: `http://localhost:8000/api`

All authenticated requests require an `Authorization: Bearer <token>` header.  
All requests should include `Accept: application/json`.

---

## Authentication

### Register
`POST /api/register`

**Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Response `201`:**
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com", "role": null, "created_at": "..." },
  "token": "1|abc123..."
}
```

---

### Login
`POST /api/login`

**Body:**
```json
{
  "email": "john@example.com",
  "password": "password"
}
```

**Response `200`:**
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com", "role": null, "created_at": "..." },
  "token": "1|abc123..."
}
```

---

### Logout *(Auth required)*
`POST /api/logout`

**Response `200`:**
```json
{ "message": "Logged out successfully." }
```

---

### Get Current User *(Auth required)*
`GET /api/me`

**Response `200`:**
```json
{ "id": 1, "name": "John Doe", "email": "john@example.com", "role": null, "created_at": "..." }
```

---

## Categories

### List all categories
`GET /api/categories`

### Get a category by slug
`GET /api/categories/{slug}`

---

## Products

### List all products
`GET /api/products`

Query params: `category`, `search`, `sort`, `page`

### Get a product by slug
`GET /api/products/{slug}`

---

## Reviews

### List reviews for a product
`GET /api/products/{productId}/reviews`

### Add a review *(Auth required)*
`POST /api/products/{productId}/reviews`

**Body:**
```json
{ "rating": 5, "comment": "Great product!" }
```

### Update a review *(Auth required)*
`PUT /api/reviews/{id}`

### Delete a review *(Auth required)*
`DELETE /api/reviews/{id}`

---

## Cart *(Auth required)*

### Get cart
`GET /api/cart`

### Add item to cart
`POST /api/cart/items`

**Body:**
```json
{ "product_id": 1, "quantity": 2 }
```

### Update cart item quantity
`PUT /api/cart/items/{productId}`

**Body:**
```json
{ "quantity": 3 }
```

### Remove a cart item
`DELETE /api/cart/items/{productId}`

### Clear cart
`DELETE /api/cart`

---

## Wishlist *(Auth required)*

### Get wishlist
`GET /api/wishlist`

### Toggle wishlist item
`POST /api/wishlist`

**Body:**
```json
{ "product_id": 1 }
```

### Remove from wishlist
`DELETE /api/wishlist/{productId}`

---

## Coupons *(Auth required)*

### Validate a coupon
`POST /api/coupons/validate`

**Body:**
```json
{ "code": "SAVE10" }
```

---

## Orders *(Auth required)*

### Place an order
`POST /api/orders`

**Body:**
```json
{
  "coupon_code": "SAVE10",
  "shipping_address": { "line1": "123 Main St", "city": "Cairo", "country": "EG" }
}
```

### List orders
`GET /api/orders`

### Get order by number
`GET /api/orders/{orderNumber}`

### Cancel an order
`PATCH /api/orders/{orderNumber}/cancel`

---

## Usage Examples

### JavaScript `fetch`

```js
const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api';

// Helper
async function apiFetch(path, options = {}, token = null) {
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };
  const res = await fetch(`${BASE_URL}${path}`, { ...options, headers });
  if (!res.ok) {
    const error = await res.json().catch(() => ({}));
    throw new Error(error.message ?? `HTTP ${res.status}`);
  }
  return res.json();
}

// Register
const { user, token } = await apiFetch('/register', {
  method: 'POST',
  body: JSON.stringify({ name: 'John', email: 'john@example.com', password: 'password', password_confirmation: 'password' }),
});

// Login
const { user, token } = await apiFetch('/login', {
  method: 'POST',
  body: JSON.stringify({ email: 'john@example.com', password: 'password' }),
});

// Authenticated request
const cart = await apiFetch('/cart', {}, token);
```

### Axios

```js
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
});

// Add token to every request (store token in localStorage or a state manager)
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Register
const { data } = await api.post('/register', {
  name: 'John',
  email: 'john@example.com',
  password: 'password',
  password_confirmation: 'password',
});
localStorage.setItem('token', data.token);

// Login
const { data } = await api.post('/login', { email: 'john@example.com', password: 'password' });
localStorage.setItem('token', data.token);

// Authenticated request
const { data: cart } = await api.get('/cart');
```

---

## CORS

The backend is configured to accept requests from `FRONTEND_URL` (default `http://localhost:3000`).  
Set this environment variable on the server for production deployments.

```env
FRONTEND_URL=https://your-frontend-domain.com
SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
```
