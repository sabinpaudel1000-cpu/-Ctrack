# Ctrack Fleet BI — ICT308 Iteration 1

AI-enabled business intelligence prototype for fleet managers. This is a university capstone prototype. **All telematics are synthetic demo records.** They are not Ctrack customer data and not a live Ctrack API feed.

## What Iteration 1 includes

- Login / logout with Admin and Fleet Manager roles
- Dashboard KPIs and charts calculated from MySQL/SQLite
- Vehicle CRUD, search and filter
- Driver list/detail with safety indicators
- Telematics browser
- Analytics (fuel, distance, speed, behaviour)
- Explainable maintenance risk (weighted baseline, not a trained ML model)
- Alerts and recommendations generated from current data

## Stack

Laravel 12, Blade, Tailwind CDN, Chart.js, SQLite by default (MySQL supported via `.env`).

Livewire was considered for search/filter. Iteration 1 uses Blade + query-string filters so the MVC path is easy to explain in Q&A. The domain services are unchanged if Livewire is added later.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Demo accounts:

- Fleet manager: `manager@demo.local` / `password`
- Admin: `admin@demo.local` / `password`

Optional MySQL: set `DB_CONNECTION=mysql` and related variables in `.env`, create database `ctrack_fleet_bi`, then migrate and seed.

Recalculate insights after editing vehicles:

```bash
php artisan fleet:recalculate
```

## Tests

```bash
php artisan test
```

## Risk algorithm (honest description)

`WeightedMaintenanceRiskScorer` combines six 0–100 factor scores:

| Factor | Weight |
|---|---|
| Mileage | 20% |
| Days since maintenance | 20% |
| Vehicle age | 15% |
| 7-day average engine temperature | 15% |
| Harsh events per 100 km (30 days) | 15% |
| Open operational alerts | 15% |

Bands: 0–39 LOW, 40–69 MEDIUM, 70–100 HIGH.

A later iteration can replace this class with a Python ML adapter behind `MaintenanceRiskPredictor`.

## GitHub / Jira

Use real branches (`main`, `develop`, `feature/*`) and the eight Jira epics from the Iteration 1 plan. Do not fabricate history.
