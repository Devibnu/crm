# KIT CRM

[![Pipeline Status](https://gitlab.krakatau-it.co.id/ibnuqosim/crm/badges/master/pipeline.svg)](https://gitlab.krakatau-it.co.id/ibnuqosim/crm/-/commits/master)

KIT CRM is a Laravel 13 backend with multiple Vue 3 workspace variants for CRM features. The actively customized frontend in this repository is the TypeScript full-version app under `typescript-version/full-version`, which contains the CRM Sales Enablement flows, Lead Management UI, and Opportunity Board.

## Stack

- Backend: Laravel 13, Sanctum, PHPUnit
- Root frontend assets: Vite
- Main app frontend: Vue 3, TypeScript, Vuetify, Vite, Vitest, pnpm

## Repository Layout

- `app`, `routes`, `database`, `tests`: Laravel API and backend test suite
- `typescript-version/full-version`: active TypeScript Vue CRM frontend
- `typescript-version/starter-kit`: upstream starter template reference
- `javascript-version/full-version`: JavaScript variant of the frontend template
- `documentation.html`: exported project documentation

## Local Setup

### Backend

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
```

The default example environment uses SQLite. To load demo data for QA and local review:

```bash
php artisan db:seed
```

### Root Vite Assets

```bash
npm install
npm run dev
```

### Main CRM Frontend

```bash
cd typescript-version/full-version
corepack enable
pnpm install --frozen-lockfile
pnpm dev
```

## Useful Commands

### Backend

```bash
php artisan serve
php artisan test
php artisan db:seed
```

### Frontend

```bash
cd typescript-version/full-version
pnpm test
pnpm build
```

### Focused Frontend Regression Tests

```bash
cd typescript-version/full-version
pnpm vitest run src/tests/LeadList.spec.ts
pnpm vitest run src/tests/OpportunityBoard.spec.ts
```

## Seed Data

The default database seeder loads several functional areas through `DatabaseSeeder`:

- `UserSeeder`
- `ServiceManagementSeeder`
- `SalesEnablementSeeder`
- `InvoiceSeeder`

The CRM sales seed includes sample leads, opportunities, quotations, and forecast data for local QA.

## Key Routes

Primary frontend routes currently used for CRM flows include:

- `/sales-enablement/dashboard`
- `/sales-enablement/lead-management`
- `/sales-enablement/lead-form`
- `/sales-enablement/opportunity-board`
- `/sales-enablement/opportunity-management`
- `/sales-enablement/pipeline-forecasting`
- `/sales-enablement/quotation-deal`
- `/service-management/ticket-list`
- `/service-management/ticket-form`
- `/service-management/sla-dashboard`

## GitLab CI

This repository includes a GitLab pipeline that runs on merge requests, `master`, and tags.

Current jobs:

- Laravel backend tests
- TypeScript frontend regression tests
- TypeScript frontend production build

Pipeline notes:

- Composer and pnpm dependencies are cached between jobs
- Frontend build depends on frontend regression tests
- Backend jobs only run when Laravel-side files change
- Frontend jobs only run when the TypeScript frontend or CI config changes
- Build artifacts are retained for 7 days

The pipeline definition lives in `.gitlab-ci.yml`.

## Current CRM Areas

- Lead Qualification and Assignment
- Opportunity Board with stage transitions
- Forecast and sales enablement APIs
- CRM dashboard and customer-related backend modules

## Notes

- The pushed default branch is `master`.
- The active Git remote is `https://gitlab.krakatau-it.co.id/ibnuqosim/crm.git`.
- If you rotate GitLab credentials, update your local authentication method before the next push.

## Deployment Notes

This repository does not yet define a production deployment job. The current CI setup focuses on validation only: backend tests, frontend regression tests, and frontend build output.

If deployment is added later, the recommended next step is to split it into a separate protected job that only runs from `master` or from release tags.
