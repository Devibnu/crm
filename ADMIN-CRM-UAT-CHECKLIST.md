# Admin CRM & Backoffice UAT Checklist

Use this checklist after logging in to `http://kitcrm.id/` with an admin account.

Scope:
- Customer 360
- Ticket Handling
- Omnichannel Inbox
- Invoice Backoffice
- SLA / audit trail verification

## Preconditions

1. Login with a valid admin account.
2. Open `http://kitcrm.id/pelanggan`.
3. Ensure the database has at least:
   - one customer with email
   - one customer with WhatsApp number
   - one customer with an existing ticket
4. Confirm production is serving the latest frontend bundle before starting UAT.

## Permission Matrix Smoke Test

Use at least these 5 role profiles before full UAT so permission regressions are caught early:

1. Full CRM admin:
   - login: `admin@demo.com / admin`
   - `customers: full`
   - `tickets: full`
   - `inbox: full`
   - `whatsapp: full`
2. Read-only observer:
   - login: `observer@demo.com / observer`
   - `customers: view`
   - `tickets: view`
   - `inbox: view`
   - `whatsapp: view`
3. Inbox operator:
   - login: `inbox-operator@demo.com / inbox`
   - `customers: view`
   - `tickets: handle`
   - `inbox: handle`
   - `whatsapp: handle`
4. Customer admin without ticket handling:
   - login: `customer-admin@demo.com / customer`
   - `customers: manage`
   - `tickets: view`
   - `inbox: view`
   - `whatsapp: view`
   - `invoice: view`
5. Finance admin without CRM write access:
   - login: `finance-admin@demo.com / finance`
   - `customers: view`
   - `tickets: view`
   - `inbox: view`
   - `whatsapp: view`
   - `invoice: manage`

Expected smoke checks for each role:

1. Full CRM admin sees no warning banner in CRM workspaces and can complete all flows below.
2. Read-only observer sees warning/read-only banners in Customer 360, Ticket Handling, Email Inbox, Inbox Overview, WhatsApp Inbox, and Live Chat.
3. Read-only observer cannot see send, assign, status-change, create-ticket, add-customer, or add-identity actions.
4. Inbox operator can reply from inbox workspaces and perform ticket handling actions, but cannot edit customer profile fields.
5. Customer admin without ticket handling can create/edit customer data and identities, but ticket follow-up actions are hidden or disabled.
6. Finance admin can create/edit/delete invoice data, but cannot access admin user management or CRM write flows.

## A. Customer 360

1. Open `http://kitcrm.id/pelanggan`.
2. Confirm the customer list renders without error.
3. Search by customer name and confirm the expected record appears.
4. Search by email and confirm the expected record appears.
5. Search by phone/WhatsApp and confirm the expected record appears.
6. Change status and source filters and confirm the list updates correctly.
7. Open a customer and confirm the detail workspace loads.
8. Confirm the quick actions are now admin-oriented:
   - `Buat Tiket Penanganan`
   - `Buka Inbox`
   - `Mulai WhatsApp`
9. Confirm the old `Create Broadcast` quick action is no longer present.
10. Confirm the page language does not imply customer self-service.

## B. Channel Identities And Timeline

1. Open a customer with multiple identities.
2. Confirm email, phone, and WhatsApp identities render correctly.
3. Confirm primary identity and verification state are visible.
4. Open the customer timeline.
5. Confirm timeline entries are grouped by day.
6. Confirm timeline source filters work:
   - All
   - WhatsApp
   - Email
   - Ticket
   - Profile
7. Confirm timeline entries show meaningful source and direction context.
8. Click a timeline item that opens inbox or ticket and confirm navigation works.

## C. Create Handling Ticket From Customer 360

1. Open a customer in Customer 360.
2. Click `Buat Tiket Penanganan`.
3. Confirm the ticket modal opens.
4. Confirm the wording is admin/internal in meaning:
   - `Buat Tiket Penanganan`
   - `Subjek Case`
   - `Catatan Awal Penanganan`
   - `Agent Penanganan`
5. Confirm customer field is prefilled from the selected customer.
6. Confirm recommendation panel appears when applicable.
7. Create a ticket with:
   - category
   - priority
   - handling note
8. Save the ticket.
9. Confirm the ticket is created successfully.
10. Confirm the ticket appears in the ticket workspace.
11. Return to Customer 360 and confirm the new ticket is reflected in recent tickets and/or timeline.

## D. Ticket Handling Workspace

1. Open `http://kitcrm.id/tiket`.
2. Confirm the ticket list loads correctly.
3. Filter by status and category.
4. Open a ticket created from Customer 360.
5. Confirm customer, priority, SLA, and assignee are visible.
6. Change the handling agent and confirm it persists.
7. Add an internal reply/note and confirm it appears in activity.
8. Change ticket status from `baru` to `diproses`.
9. Confirm the activity log reflects the change.
10. Mark the ticket `selesai` and confirm the status is updated.

## E. Email Inbox

1. From Customer 360, open `Buka Inbox` for a customer whose preferred identity is email.
2. Confirm navigation goes to the email inbox with customer context.
3. Confirm existing email list loads from backend data.
4. Open an email and confirm `Customer 360` CTA is available when appropriate.
5. Send a reply and confirm it succeeds.
6. Forward an email and confirm it succeeds.
7. Return to Customer 360 and confirm email activity appears in the timeline.

## F. WhatsApp Inbox - Existing Thread

1. From Customer 360, open `Buka Inbox` for a customer with an existing WhatsApp thread.
2. Confirm the matching thread is selected.
3. Confirm queue chips and direction are visible.
4. Confirm thread state is understandable:
   - `Butuh respons`
   - `Menunggu pelanggan`
   - `Selesai`
5. Confirm direction label is understandable:
   - `Inbound`
   - `Outbound`
6. Send a reply from the thread.
7. Confirm the queue state updates appropriately.
8. Confirm returning to Customer 360 still works.

## G. WhatsApp Inbox - New Outbound Thread

1. From Customer 360, click `Mulai WhatsApp` for a customer with no matching thread.
2. Confirm the WhatsApp inbox opens in new-conversation mode.
3. Confirm the customer context is visible.
4. Confirm the create-thread panel shows:
   - customer name
   - customer number
   - priority selector
   - opening message field
5. Create the thread.
6. Confirm a new thread appears in the inbox.
7. Confirm the thread is labeled `Outbound`.
8. Confirm the status becomes `Menunggu pelanggan`.
9. Confirm the create intent clears after the thread opens.

## H. SLA And Audit Trail

1. Open SLA Management.
2. Confirm tickets appear in expected SLA buckets.
3. Open a ticket with recent changes.
4. Confirm activity shows who changed status, assignment, or handling notes.
5. Return to Customer 360 and confirm cross-channel activity is visible in the timeline.
6. Validate whether an admin or supervisor can answer quickly:
   - who owns this case now
   - what happened last
   - whether customer is waiting or agent is waiting

## I. Invoice Backoffice

1. Login as `finance-admin@demo.com / finance`.
2. Open the invoice list page.
3. Confirm the invoice list loads and the access banner reflects invoice write access without implying CRM write access.
4. Confirm `Create invoice` is visible.
5. Open an invoice preview and confirm `Send Invoice`, `Edit`, and `Add Payment` are available.
6. Return to the invoice list and confirm delete action is available.
7. Open Customer 360 and Ticket Handling and confirm CRM write actions remain hidden or disabled for this role.
8. Open Pengguna or legacy user admin pages and confirm access is rejected.

## J. Regression Checks

1. Customer list and filters still work after all flows above.
2. Ticket page still accepts deep-link customer context.
3. Email inbox still composes, replies, and forwards correctly.
4. WhatsApp inbox still assigns, releases, replies, and marks done correctly.
5. No Customer 360 action suggests customer self-service inside the admin dashboard.

## K. Permission UX Regression

1. Login as the read-only observer profile.
2. Open Customer 360 and confirm the access banner explains profile/ticket follow-up restrictions.
3. Open Ticket Handling and confirm create/reply/assign actions are hidden or disabled.
4. Open Inbox Overview and confirm the composer is disabled with a clear warning banner.
5. Open Email Inbox and confirm compose or mailbox actions match the profile's create/update permissions.
6. Open WhatsApp Inbox and confirm reply, assign, and outbound-thread actions match the profile's create/update permissions.
7. Open Live Chat and confirm the composer is disabled when inbox update permission is missing.
8. Login as the inbox operator profile and confirm the warning banners change from read-only to partial-access wording where applicable.
9. Login as the customer admin profile and confirm customer profile edit actions return while ticket follow-up actions stay restricted.
10. Login as the finance admin profile and confirm invoice write actions are visible while CRM and admin-user write surfaces stay restricted.

## Exit Criteria

The admin CRM and backoffice phase can move closer to sign-off when:

1. Customer 360 can be used as the main admin context page without confusion.
2. Ticket creation from Customer 360 is clearly an admin handling action.
3. Email and WhatsApp flows work end-to-end from customer context.
4. Queue states and ownership are readable enough for daily operations.
5. No blocking UI/API errors appear during the flows above.
6. Invoice permissions are validated with a finance-focused operator profile.
7. Remaining issues, if any, are wording polish or minor workflow refinements rather than structural gaps.