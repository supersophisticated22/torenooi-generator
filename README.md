# Toernooigenerator

## Local demo data

This project includes a complete demo seeder stack for a realistic multi-tenant setup.

### Seed command

```bash
php artisan migrate:fresh --seed --no-interaction
```

Or seed demo data into an existing local schema:

```bash
php artisan db:seed --class=Database\\Seeders\\Demo\\DemoSeeder --no-interaction
```

### Demo login users

- `organizer@demo.test` / `password`
- `coach@demo.test` / `password`

### What gets generated

- 1 demo organization
- 2 demo users linked to the organization
- sports, categories, teams, players, venue, and fields
- 2 realistic tournaments:
  - Amsterdam Football Cup 2026
  - Amsterdam Basketball Cup 2026
- tournament entries, generated matches, and partially completed match results
