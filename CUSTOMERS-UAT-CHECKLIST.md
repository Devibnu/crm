# Customers UAT Checklist

Use this checklist after logging in to `http://kitcrm.id/` and opening the Customers module.

## Preconditions

1. Login with a valid CRM admin account
2. Open the Customers page
3. Ensure at least one customer exists in the database

## Customer List

1. Customers page opens without error
2. Customer list renders name, email, phone, status, and ticket count
3. Searching by name returns the expected customer
4. Searching by email returns the expected customer
5. Searching by phone or WhatsApp returns the expected customer
6. Status filter changes the list correctly
7. Source filter changes the list correctly

## Create Customer

1. Click `Tambah Pelanggan`
2. Fill in name only and save
3. Confirm customer is created successfully
4. Fill in name + email + WhatsApp and save
5. Confirm the created customer appears in the list
6. Confirm the created customer detail panel opens automatically

## Edit Customer

1. Open an existing customer
2. Click `Ubah Profil`
3. Change name, source, status, and notes
4. Save the form
5. Confirm the detail panel shows updated values

## Identity Management

1. Open an existing customer
2. Click `Tambah Identity`
3. Add a WhatsApp identity
4. Confirm identity appears in the identity section
5. Add an email identity
6. Confirm identity appears in the identity section
7. Try adding the same identity value to another customer
8. Confirm duplicate identity is blocked with an error

## Timeline

1. Create a customer and confirm a timeline event appears
2. Edit a customer and confirm an update event appears
3. Add an identity and confirm an identity event appears
4. Timeline timestamps should be readable and ordered newest first

## Ticket Summary

1. Open a customer with tickets
2. Confirm recent tickets render in the detail section
3. Click `Buka tiket` and confirm navigation to the ticket page
4. Click `Balas tiket` and confirm ticket reply flow opens correctly

## Regression

1. Broadcast audience loading still works
2. Ticket page still resolves customer linkage correctly
3. Customers page still supports query param customer deep links

## Exit Criteria

The module is ready to be considered complete for Sprint 1 if:

1. No blocking UI or API error appears during the flows above
2. Customer create and update both work
3. Identity create works and duplicate protection works
4. Timeline events are visible
5. Ticket linkage still works