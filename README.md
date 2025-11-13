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
