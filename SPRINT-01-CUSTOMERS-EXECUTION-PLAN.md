# Sprint 1: Customers Execution Plan

## Goal

Finish the `Customers` module as the master customer profile foundation for:

- Ticket Management
- Omnichannel Inbox
- Consent Management
- Campaign audience selection
- Lead capture in later phases

Target sprint duration: `2 weeks`

## Why Customers First

Current repo state already gives this module a usable starting point:

- Frontend page exists in `typescript-version/full-version/src/pages/pelanggan.vue`
- API list and create endpoints exist in `routes/api.php`
- Backend controller exists in `app/Http/Controllers/Api/CrmPelangganController.php`
- Ticket module already references customer IDs and can become the first downstream consumer

This makes `Customers` the safest first module to complete end-to-end and test before building the next dependency-heavy areas.

## Existing Baseline

### Frontend

- Existing page: `typescript-version/full-version/src/pages/pelanggan.vue`
- Already supports:
  - customer list
  - create customer dialog
  - query-based customer selection
  - recent ticket summary

### Backend

- Existing controller: `app/Http/Controllers/Api/CrmPelangganController.php`
- Existing routes:
  - `GET /api/crm/pelanggan`
  - `POST /api/crm/pelanggan`
- Existing table migration:
  - `database/migrations/2026_04_08_110000_create_pelanggan_table.php`

### Current Gap

The current implementation is still too shallow for a real CRM customer master because it does not yet support:

- customer detail endpoint
- update customer endpoint
- multi identity model
- timeline model
- duplicate identity resolution rules
- explicit status and source management

## Sprint Outcome

At the end of Sprint 1, the system must be able to:

1. display a searchable customer list
2. create a customer
3. update a customer
4. manage multiple identities for one customer
5. show customer detail
6. show a customer timeline
7. provide stable IDs and profile structure for ticket and inbox modules

## In Scope

### UI Scope

1. Customer list improvements
2. Search and base filters
3. Create customer flow
4. Edit customer flow
5. Customer detail panel or detail page
6. Identity management
7. Timeline event display

### Backend Scope

1. Extend customer resource shape
2. Add customer detail endpoint
3. Add customer update endpoint
4. Add customer identity create/update endpoints
5. Add timeline event generation for customer actions

### Data Scope

1. Keep current `pelanggan` table as base customer table or evolve it safely
2. Add `customer_identities`
3. Add `customer_timeline_events`

## Out of Scope

Do not include these in Sprint 1:

1. automatic duplicate merge workflow
2. consent preference engine
3. segmentation engine
4. campaign audience engine
5. omnichannel auto-linking logic
6. lead conversion

## Recommended Data Model

### 1. customers

Recommended logical fields:

| Field | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| name | string | required |
| primary_email | string nullable | denormalized primary email |
| primary_phone | string nullable | denormalized main phone |
| primary_whatsapp | string nullable | denormalized WhatsApp |
| status | string | `active`, `inactive` |
| source | string nullable | `manual`, `inbox`, `import`, `form`, `campaign` |
| notes | text nullable | internal note |
| created_by | bigint nullable | user id |
| updated_by | bigint nullable | user id |
| created_at | timestamp | |
| updated_at | timestamp | |

Implementation note:

- Current repo uses `pelanggan` with fields like `nama`, `email`, `no_hp`
- For Sprint 1, prefer incremental evolution instead of big-bang rename
- Keep naming aligned with current Laravel schema first, then normalize naming later only if needed

### 2. customer_identities

Recommended fields:

| Field | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| customer_id | bigint | FK to customer |
| type | string | `email`, `phone`, `whatsapp` |
| value | string | normalized value |
| label | string nullable | `work`, `personal`, `main`, etc |
| is_primary | boolean | one primary per type per customer |
| is_verified | boolean | verified or not |
| created_at | timestamp | |
| updated_at | timestamp | |

Rules:

1. `(type, value)` must be globally unique
2. one customer can have many identities
3. one customer can only have one primary identity per type

### 3. customer_timeline_events

Recommended fields:

| Field | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| customer_id | bigint | FK to customer |
| event_type | string | `customer_created`, `customer_updated`, `identity_added`, `identity_updated` |
| title | string | human-readable event title |
| description | text nullable | event description |
| event_at | timestamp | event time |
| actor_id | bigint nullable | user id |
| meta | json nullable | extra payload |
| created_at | timestamp | |
| updated_at | timestamp | |

## API Contract

### 1. GET /api/crm/pelanggan

Purpose:

- customer list
- search
- filter
- top-level summary for the list view

Recommended query params:

- `q`
- `status`
- `source`
- `has_ticket`
- `page`
- `per_page`

Recommended response:

```json
{
  "data": [
    {
      "id": 12,
      "name": "Budi Santoso",
      "primaryEmail": "budi@email.com",
      "primaryPhone": "08123456789",
      "primaryWhatsapp": "08123456789",
      "status": "active",
      "source": "manual",
      "ticketCount": 4,
      "activeTicketCount": 2,
      "lastActivityAt": "2026-04-09T08:00:00Z",
      "createdAt": "2026-04-01T09:00:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 20,
    "total": 120
  }
}
```

### 2. POST /api/crm/pelanggan

Purpose:

- create new customer

Request:

```json
{
  "name": "Budi Santoso",
  "primaryEmail": "budi@email.com",
  "primaryPhone": "08123456789",
  "primaryWhatsapp": "08123456789",
  "source": "manual",
  "notes": "Customer VIP"
}
```

Response:

```json
{
  "data": {
    "id": 12,
    "name": "Budi Santoso",
    "primaryEmail": "budi@email.com",
    "primaryPhone": "08123456789",
    "primaryWhatsapp": "08123456789",
    "status": "active",
    "source": "manual",
    "ticketCount": 0,
    "activeTicketCount": 0,
    "lastActivityAt": null,
    "createdAt": "2026-04-09T08:00:00Z"
  }
}
```

### 3. GET /api/crm/pelanggan/{id}

Purpose:

- customer detail

Response:

```json
{
  "data": {
    "id": 12,
    "name": "Budi Santoso",
    "status": "active",
    "source": "manual",
    "notes": "Customer VIP",
    "primaryEmail": "budi@email.com",
    "primaryPhone": "08123456789",
    "primaryWhatsapp": "08123456789",
    "identities": [
      {
        "id": 100,
        "type": "email",
        "value": "budi@email.com",
        "label": "main",
        "isPrimary": true,
        "isVerified": true
      }
    ],
    "ticketSummary": {
      "total": 4,
      "active": 2,
      "lastActivityAt": "2026-04-09T08:00:00Z"
    },
    "recentTickets": [],
    "timeline": []
  }
}
```

### 4. PUT /api/crm/pelanggan/{id}

Purpose:

- update customer base profile

Request:

```json
{
  "name": "Budi Santoso Updated",
  "status": "active",
  "source": "manual",
  "notes": "Updated notes"
}
```

### 5. POST /api/crm/pelanggan/{id}/identities

Purpose:

- add customer identity

Request:

```json
{
  "type": "whatsapp",
  "value": "08129876543",
  "label": "secondary",
  "isPrimary": false,
  "isVerified": false
}
```

Validation rules:

1. type required
2. value required
3. duplicate global `(type, value)` is blocked

### 6. PUT /api/crm/customer-identities/{id}

Purpose:

- update label, primary flag, verified flag

### 7. GET /api/crm/pelanggan/{id}/timeline

Purpose:

- fetch customer timeline independently when needed

## Frontend MVP Scope

### List View

Start from `typescript-version/full-version/src/pages/pelanggan.vue`.

Deliver:

1. searchable list
2. selected customer state
3. customer summary cards or richer detail panel
4. filters for status and source
5. empty state and loading state

### Create Customer

Deliver:

1. clean create form
2. validation error display
3. successful create navigates or selects the created customer

### Edit Customer

Deliver:

1. edit action from detail section
2. update profile fields
3. optimistic or immediate refresh after save

### Detail Experience

Deliver:

1. profile summary
2. identity list
3. recent ticket summary
4. customer timeline

## Backend MVP Scope

### Existing Files Likely to Change

- `app/Http/Controllers/Api/CrmPelangganController.php`
- `routes/api.php`
- `app/Models/Pelanggan.php`
- new models for identities and timeline
- new migrations in `database/migrations/`

### Suggested New Backend Files

- `app/Models/CustomerIdentity.php`
- `app/Models/CustomerTimelineEvent.php`
- optional request classes for validation:
  - `app/Http/Requests/Crm/StoreCustomerRequest.php`
  - `app/Http/Requests/Crm/UpdateCustomerRequest.php`
  - `app/Http/Requests/Crm/StoreCustomerIdentityRequest.php`
  - `app/Http/Requests/Crm/UpdateCustomerIdentityRequest.php`

### Suggested Relation Additions

On customer model:

- `identities()`
- `timelineEvents()`
- existing `tiket()` remains downstream dependency

## Implementation Task Breakdown

### Backend Tasks

#### Task Group A: Data Model

1. review current `pelanggan` table and keep backward compatibility
2. add migration for `customer_identities`
3. add migration for `customer_timeline_events`
4. add proper indexes and uniqueness constraints

#### Task Group B: Domain Model

1. extend `Pelanggan` model
2. add identity and timeline models
3. add helper methods for primary identity sync

#### Task Group C: API

1. refactor list response shape
2. add detail endpoint
3. add update endpoint
4. add identity create endpoint
5. add identity update endpoint
6. add timeline endpoint if needed

#### Task Group D: Timeline Logging

1. log customer created
2. log customer updated
3. log identity added
4. log identity updated

### Frontend Tasks

#### Task Group E: Customers Page

1. refactor `pelanggan.vue` types to richer customer resource
2. add status/source filters
3. split dialog mode into create and edit
4. add identity management UI in detail section
5. add timeline section

#### Task Group F: UX and State

1. preserve selected customer after refresh
2. improve query param behavior for deep link to customer
3. improve error state and snackbar handling
4. support duplicate identity error display

## Two-Week Sprint Breakdown

### Week 1

Day 1:

- finalize schema
- confirm naming strategy
- confirm duplicate identity policy

Day 2:

- add migrations
- add models and relations

Day 3:

- add customer detail endpoint
- add update endpoint

Day 4:

- add identity endpoints
- add validation rules

Day 5:

- add timeline write logic
- backend smoke test

### Week 2

Day 6:

- refactor customer list UI
- add filters

Day 7:

- add edit customer flow
- improve create flow

Day 8:

- build identity management section

Day 9:

- build timeline section
- wire detail endpoint fully

Day 10:

- acceptance test
- regression test with ticket module

## Acceptance Criteria

Sprint 1 is complete only if all points below are true:

1. a user can create a customer
2. a user can edit a customer
3. a customer can have multiple identities
4. duplicate identities are blocked
5. customer detail shows identities and timeline
6. customer list supports search and base filters
7. ticket module can safely consume the same customer ID model

## Test Checklist

### Functional

1. create customer without email but with WhatsApp
2. create customer with email only
3. add second WhatsApp identity
4. set a different primary identity
5. update customer name and notes
6. open customer from query parameter
7. verify timeline records create and update actions

### Validation

1. reject duplicate email identity
2. reject duplicate WhatsApp identity
3. reject invalid email format
4. reject empty name

### Regression

1. `Ticket Management` still opens customer-linked ticket flow correctly
2. current broadcast audience fetch from `/crm/pelanggan` still works after response changes or receives a compatibility-safe response

## Key Decisions to Lock Before Implementation

1. Customer may exist without email: `yes`
2. WhatsApp and phone are logically distinct identities: `yes`
3. Duplicate identity across customers is blocked: `yes`
4. Merge workflow is deferred: `yes`
5. Keep current route naming `/crm/pelanggan` for now to reduce frontend churn: `yes`

## Recommended Immediate Next Step

Start implementation with the backend data model first:

1. add `customer_identities`
2. add `customer_timeline_events`
3. extend `CrmPelangganController`
4. only then refactor `pelanggan.vue`

This order minimizes frontend rework and keeps Sprint 1 testable from the API outward.