## POS API – Quick Start

### Super Admin Access

The seed process now guarantees that a Super Admin user exists so you can immediately reach the admin routes (`/api/admin/*`) guarded by `role:SuperAdmin`.

```
Email:    superadmin@pos.com
Password: password123
```

You can override these defaults by setting `APP_SUPER_ADMIN_EMAIL` and `APP_SUPER_ADMIN_PASSWORD` in your `.env` file before running the seeders.

### Other Seeded Accounts

Restaurant-specific Owner/Manager/Cashier accounts are also created during seeding. Example credentials:

```
Owner:   owner@golden-fork.com / password123
Manager: manager@golden-fork.com / password123
```

Use the Super Admin token for cross-tenant management and the restaurant accounts for daily POS workflows.

### Order workflow updates

Apply the new migrations before deploying the matching frontend:

```bash
docker compose exec app php artisan migrate
```

- Orders accept an omitted or null `table_id`. A supplied table must belong to the current restaurant.
- `discount_amount` is a nonnegative absolute amount supported on create, update, close, and payment. Totals are recalculated on the server; send `0` to remove a discount.
- `GET /api/orders` supports `search` (ID, notes, table number, menu item name), `status=active`, and `per_page=1..100` before pagination.
- `mine=1` restricts orders to the authenticated user's staff record. Waiter-created orders automatically use that staff ID when `waiter_id` is omitted.
- `POST /api/refresh` accepts a bearer JWT within its configured refresh window, including expired access tokens. Invalid, out-of-window, and inactive-account tokens return 401.

The migrations also align payment and table constraints with the existing `mobile` payment method and `out-of-order` table status. Rollback requires first resolving records that use the newly allowed values or null tables; migrations do not rewrite those records.
