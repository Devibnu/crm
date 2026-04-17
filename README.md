# KIT CRM

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
php artisan key:generate
php artisan migrate
```

The default example environment uses SQLite. If needed, create the database file first:

```bash
touch database/database.sqlite
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

## GitLab CI

This repository includes a basic GitLab pipeline that runs:

- Laravel backend tests
- TypeScript frontend regression tests
- TypeScript frontend production build

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
