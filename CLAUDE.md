# e-garage Backend API — Project Context

## Project Overview
E-commerce API for electric bicycle parts and accessories shop (Uzbekistan market).
- **10–50 products**, mobile-first, RU/UZ bilingual
- **Target:** launch in 3–5 weeks, small business

## Tech Stack
- **Laravel 13**, PHP 8.4
- **SQLite** (local dev), MySQL (production)
- **JWT** via `php-open-source-saver/jwt-auth`
- **Architecture:** Layered (Controller → Service → Model). NO modules, NO CMS.

## Project Structure
```
/var/www/e-garage/backend/
├── app/
│   ├── Enums/
│   │   └── UserRole.php          ← Admin, Manager
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── Auth/
│   │   │   │   ├── CustomerAuthController.php
│   │   │   │   └── AdminAuthController.php
│   │   └── Requests/Auth/
│   │       ├── CustomerRegisterRequest.php
│   │       ├── CustomerLoginRequest.php
│   │       └── AdminLoginRequest.php
│   ├── Models/
│   │   ├── Customer.php          ← buyers (phone/telegram login)
│   │   └── User.php              ← admins/managers (email login)
│   └── Services/Auth/
│       ├── CustomerAuthService.php
│       └── AdminAuthService.php
├── bootstrap/
│   └── app.php                   ← API routes registered here, 401 JSON handler
├── config/
│   ├── auth.php                  ← two guards: 'api' (users) + 'customer' (customers)
│   └── jwt.php                   ← ttl: 15min, refresh_ttl: 7 days
├── database/
│   ├── migrations/
│   │   └── xxxx_create_customers_table.php
│   └── seeders/
│       └── AdminUserSeeder.php   ← seeds super admin (email: superadmin@mail.ru)
└── routes/
    └── api.php                   ← all routes here, prefix /api/v1/
```

## Authentication Architecture
Two independent user types via **multi-guard JWT**:

### Guards (config/auth.php)
```php
'api'      → driver: jwt, provider: users      → App\Models\User
'customer' → driver: jwt, provider: customers  → App\Models\Customer
```

### JWT Claims
- Customer token: `{ type: "customer", sub: id, prv: hash(Customer) }`
- Admin token:    `{ type: "admin", role: "admin"|"manager", sub: id, prv: hash(User) }`

### Models
**Customer** — buyers
- Fields: `id, name, phone(nullable,unique), password(nullable), telegram_id(nullable,unique), telegram_username(nullable), locale(default:uz), balance(decimal 12,2 default:0), timestamps`
- Casts: `password => hashed`, `balance => decimal:2`
- Implements: `JWTSubject`

**User** — admins/managers
- Fields: `id, name, email, password, role(enum), timestamps`
- Casts: `password => hashed`, `role => UserRole::class`
- Enum `UserRole`: `Admin = 'admin'`, `Manager = 'manager'`
- Implements: `JWTSubject`
- **No public register** — super admin created via seeder, others via future `AdminUserController`

## API Routes
```
# Customer Auth (public)
POST   /api/v1/auth/customer/register
POST   /api/v1/auth/customer/login
POST   /api/v1/auth/customer/refresh

# Customer Auth (protected: auth:customer)
POST   /api/v1/auth/customer/logout
GET    /api/v1/auth/customer/me

# Admin Auth (public)
POST   /api/v1/auth/admin/login
POST   /api/v1/auth/admin/refresh

# Admin Auth (protected: auth:api)
POST   /api/v1/auth/admin/logout
GET    /api/v1/auth/admin/me

# Catalog (public, localized via Accept-Language: ru|uz, default uz)
GET    /api/v1/categories
GET    /api/v1/products              ?category=slug&min_price&max_price&sort=newest|price_asc|price_desc&per_page
GET    /api/v1/products/search       ?q=
GET    /api/v1/products/{slug}

# Customer orders (auth:customer) — cart lives on the frontend
POST   /api/v1/cart/checkout         {items:[{product_id,qty}], delivery:{name,phone,method:courier|pickup,address,comment}, payment_method:click|payme|cash}
GET    /api/v1/user/orders
GET    /api/v1/user/orders/{id}

# Admin (auth:api + role:admin,manager)
CRUD   /api/v1/admin/categories
CRUD   /api/v1/admin/products
POST   /api/v1/admin/products/{id}/images    (multipart images[])
DELETE /api/v1/admin/products/{id}/images    (body: path)
GET    /api/v1/admin/orders                  ?status&payment_status&customer_id
GET    /api/v1/admin/orders/{id}
PUT    /api/v1/admin/orders/{id}/status      {status}

# Staff (auth:api + role:admin)
GET|POST /api/v1/admin/users, PUT|DELETE /api/v1/admin/users/{id}  (DELETE = deactivate)
```

Other conventions: business-rule violations in services throw `App\Exceptions\BusinessRuleException` (renders JSON 422/409/404). `role:` middleware = `App\Http\Middleware\EnsureUserHasRole` (also rejects inactive staff). `UserRole` enum lives at `App\UserRole`; newer enums (OrderStatus, PaymentMethod, PaymentStatus, DeliveryMethod) are in `App\Enums`.

## Key Conventions
1. **Controllers** — only: receive request → call service → return response. NO business logic.
2. **Services** — ALL business logic lives here.
3. **FormRequest** — ALL validation here, never in controllers or services.
4. **Guards** — always specify explicitly: `auth()->guard('customer')` or `auth()->guard('api')`
5. **API versioning** — always `/api/v1/` prefix. Never break existing clients.
6. **No redirect** — pure JSON API. `bootstrap/app.php` handles `AuthenticationException` → 401 JSON.

## bootstrap/app.php Key Config
```php
->withRouting(api: __DIR__.'/../routes/api.php', ...)
->withMiddleware(fn($m) => $m->redirectGuestsTo(fn() => null))
->withExceptions(function($e) {
    $e->render(function(AuthenticationException $ex, Request $req) {
        return response()->json(['message' => 'Пользователь не авторизован.'], 401);
    });
})
```

## Running Locally
```bash
cd /var/www/e-garage/backend
php artisan serve --port=8080

# Seed super admin (first time only):
php artisan db:seed --class=AdminUserSeeder
# Credentials: superadmin@mail.ru / admin1234
```

## What's Done ✅
- JWT multi-guard authentication (Customer + Admin)
- PHP Enum for UserRole
- Service Layer architecture
- FormRequest validation
- Super admin seeder
- Clean 401 JSON responses (no redirects)
- All auth endpoints tested in Postman
- Catalog: categories/products (RU/UZ `*_ru`/`*_uz` columns), public endpoints, admin CRUD + image upload, `CatalogSeeder`
- Staff management (`AdminUserController`), role middleware, `users.is_active`
- Orders: checkout (DB prices, stock locked + decremented), customer history, admin list + status transitions (`OrderService`)
- Feature tests: `tests/Feature/Catalog`, `tests/Feature/Admin`, `tests/Feature/Order`

## What's NOT Done Yet ❌
- Payments (Click/Payme), pay from balance (`balance_transactions`)
- Telegram login for customers
- Vue 3 frontend (separate project at /var/www/e-garage/frontend)
