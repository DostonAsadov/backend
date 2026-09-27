# e-garage — Task List

## ✅ DONE

### Backend Foundation
- [x] Laravel 13 project setup (SQLite, JWT, PHP 8.4)
- [x] Multi-guard JWT authentication (Customer + Admin guards)
- [x] Customer model + migration (phone, telegram_id, balance, locale)
- [x] User model + migration (email, role via PHP Enum)
- [x] UserRole Enum (Admin, Manager)
- [x] CustomerAuthService (register, login, logout, refresh, me)
- [x] AdminAuthService (login, logout, refresh, me)
- [x] CustomerAuthController + AdminAuthController
- [x] FormRequest validation (CustomerRegister, CustomerLogin, AdminLogin)
- [x] AdminUserSeeder (super admin: superadmin@mail.ru / admin1234)
- [x] API routes with /api/v1/ prefix
- [x] Clean 401 JSON responses (no redirect to login page)
- [x] Tested all auth endpoints in Postman
- [x] Guard isolation verified (customer token rejected on admin routes)

---

## 🔄 IN PROGRESS

### Frontend
- [ ] Delete React project, create Vue 3 + Vite project
- [ ] Setup project structure (pages, components, composables, stores)

---

## 📋 TODO

### Backend — Admin
- [x] `AdminUserController` — CRUD for admin/manager users (only super admin)
  - POST   /api/v1/admin/users         (create manager/admin)
  - GET    /api/v1/admin/users         (list all staff)
  - PUT    /api/v1/admin/users/{id}    (update role/info)
  - DELETE /api/v1/admin/users/{id}    (deactivate)
- [x] Migration: add `is_active` to `users` (needed for deactivate)
- [x] Decided: any active `admin` manages staff; admin + manager both manage catalog
- [x] Role middleware — restrict endpoints by role (Admin vs Manager)

### Backend — Catalog
- [x] Migration: `categories` (id, name_ru, name_uz, slug, parent_id)
- [x] Migration: `products` (id, category_id, sku, name_ru, name_uz, description_ru, description_uz, slug, price, stock, images JSON, is_active)
- [x] Category model + relationships
- [x] Product model + relationships + slug auto-generation
- [x] ProductService, CategoryService
- [x] Public endpoints:
  - GET /api/v1/categories
  - GET /api/v1/products (filters: category, min_price, max_price, sort)
  - GET /api/v1/products/{slug}
  - GET /api/v1/products/search?q=...  (by name / SKU)
- [x] Admin endpoints (auth:api):
  - POST   /api/v1/admin/products
  - PUT    /api/v1/admin/products/{id}
  - DELETE /api/v1/admin/products/{id}
  - CRUD   /api/v1/admin/categories
  - GET    /api/v1/admin/products (incl. hidden, filters q/category_id/is_active)
  - POST   /api/v1/admin/products/{id}/images, DELETE .../images (max 10, jpg/png/webp ≤ 4MB)
- [x] Image upload (Laravel Storage, local disk → S3 ready via abstraction)
- [x] Product seeder (test data) — `CatalogSeeder`, re-runnable

### Backend — Orders & Cart
- [ ] Migration: `orders` (customer_id, status, total, delivery_address JSON, payment_method, payment_status)
  - status: pending → paid → processing → shipped → completed / cancelled
  - payment_method: click / payme / balance / cash
  - payment_status: pending / paid / failed
  - checkout requires auth:customer (no guest orders)
- [ ] Migration: `order_items` (order_id, product_id, qty, price_snapshot ← important!)
- [ ] OrderService — create order, update status
- [ ] Customer endpoints:
  - POST /api/v1/cart/checkout         (create order)
  - GET  /api/v1/user/orders           (order history)
  - GET  /api/v1/user/orders/{id}      (order details)
- [ ] Admin endpoints:
  - GET /api/v1/admin/orders
  - PUT /api/v1/admin/orders/{id}/status

### Backend — Payments
- [ ] Register merchant: Click + Payme (takes 5–14 days — start ASAP)
- [ ] Migration: `payments` (order_id, provider, external_id, amount, status, payload JSON)
- [ ] PaymentGatewayInterface (contract for Click/Payme)
- [ ] ClickPaymentService implements PaymentGatewayInterface
- [ ] PaymePaymentService implements PaymentGatewayInterface
- [ ] Webhook endpoints:
  - POST /api/v1/payments/click/notify
  - POST /api/v1/payments/payme/notify
- [ ] Queue jobs for webhook processing (Redis)
- [ ] Order status: pending → paid (only after webhook confirmation)

### Backend — Telegram Login
- [ ] Telegram Bot registration (@BotFather)
- [ ] TelegramAuthService — verify Telegram login widget hash
- [ ] Endpoint: POST /api/v1/auth/customer/telegram

### Backend — Notifications
- [ ] Telegram Bot API — notify admin on new order
- [ ] Queue job for notification

### Frontend — Vue 3
- [ ] Project setup (Vue 3 + Vite + Vue Router + Pinia)
- [ ] i18n setup (vue-i18n, ru/uz JSON files, Google Sheet for content)
- [ ] Auth pages: Login, Register (customer)
- [ ] Catalog page: product list, filters, search
- [ ] Product page: details, add to cart
- [ ] Cart: local state → checkout form
- [ ] Customer cabinet: order history, balance
- [ ] Admin panel (separate route /admin):
  - Login
  - Dashboard
  - Products CRUD
  - Orders management
  - Staff management (AdminUserController)

### Infrastructure
- [ ] Buy domain (.uz or .com)
- [ ] Setup VPS (2CPU, 4GB RAM, 40GB SSD)
- [ ] Docker setup for production (nginx, php-fpm, mysql, redis)
- [ ] SSL certificate (Certbot + Let's Encrypt)
- [ ] CI/CD (GitHub Actions → deploy to VPS)

---

## 🗓️ Priority Order (suggested)

```
Week 1:  Catalog (products/categories) + Admin CRUD
Week 2:  Vue frontend — catalog + auth pages
Week 3:  Orders + Cart + Customer cabinet
Week 4:  Payments (Click/Payme) + Telegram login
Week 5:  Deploy to VPS + testing + launch
```
