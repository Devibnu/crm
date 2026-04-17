# Admin CRM Finalization Checklist

Purpose: finalize the internal admin CRM first, before building any customer self-service portal.

Status legend:
- DONE: already operational and validated in current admin workspace
- IN PROGRESS: partially operational, usable, but still needs refinement
- NEXT: not yet complete enough to be treated as finished

## 1. Customer 360

### DONE
- Customer directory, search, and filters are operational.
- Customer detail page at `/pelanggan` acts as an internal admin workspace, not a customer portal.
- Channel identity rendering is available for email, phone, and WhatsApp.
- Customer timeline is backend-driven and merges ticket, email, and WhatsApp activity.
- Timeline supports source filtering and daily grouping.
- Quick actions are aligned to admin work:
  - Create Handling Ticket
  - Open Inbox
  - Start WhatsApp
- Misleading one-to-many action has been removed from Customer 360:
  - Create Broadcast quick action removed

### IN PROGRESS
- Some wording is now clearer, but overall terminology still mixes `ticket`, `case`, and `handling` in several places.
- Customer 360 still needs a final pass for strict language consistency across Indonesian and English UI.

### NEXT
- Standardize final terminology for admin service operations:
  - choose one dominant model: `ticket`, `case`, or `handling ticket`
- Add more explicit admin-only framing in any remaining ambiguous entry points.
- Define whether Customer 360 should show ownership summary such as:
  - assigned agent
  - last handling agent
  - queue state summary

## 2. Ticket Handling

### DONE
- Admin can create tickets from Customer 360 context.
- Ticket creation modal supports:
  - customer selection
  - assignee selection
  - category
  - priority
  - initial internal note
- Ticket recommendation logic exists for:
  - active tickets
  - inbox-sourced customers
  - lead/form/campaign customers
  - assignee history
- Ticket assignment and reassignment are operational.
- Ticket replies and internal notes are operational.
- Ticket activity history is visible.
- Copy now frames ticket creation as admin handling work, not customer self-service.

### IN PROGRESS
- Ticket workspace still needs a final review for end-to-end admin scenarios:
  - create from customer
  - assign
  - reply
  - finish
  - reopen or continue follow-up if needed

### NEXT
- Confirm whether admin ticket lifecycle is final:
  - `baru`
  - `diproses`
  - `selesai`
- Decide whether a reopen state is needed.
- Decide whether internal note and outbound reply should remain the same action or become separate actions.
- Add final UAT checklist specifically for ticket handling as an admin process.

## 3. Omnichannel Inbox

### DONE
- Email inbox uses real backend data.
- Email compose, reply, and forward are operational.
- WhatsApp inbox uses real backend ticket/message data.
- WhatsApp thread actions are operational:
  - assign
  - release
  - mark done
  - reply
- Customer 360 deep links into email and WhatsApp inboxes.
- WhatsApp customer-context flow can create a new outbound thread when no matching thread exists.
- WhatsApp queue state is now server-driven:
  - butuh_respons
  - menunggu_pelanggan
  - selesai
- WhatsApp direction is now visible:
  - inbound
  - outbound

### IN PROGRESS
- Omnichannel still needs a final cross-channel consistency pass for labels, ownership cues, and operational language.
- Email and WhatsApp workspaces are operational, but not yet normalized under one shared admin handling vocabulary.

### NEXT
- Decide whether inbox queue chips should be standardized across channels.
- Decide whether email should also expose a queue-like status model comparable to WhatsApp.
- Review whether thread labels shown to agents should remain raw backend labels or be normalized to user-facing labels.

## 4. SLA And Admin Audit Trail

### DONE
- SLA management module exists.
- Ticket activity and customer timeline already capture key operational events.
- WhatsApp and email actions now contribute to customer history.

### IN PROGRESS
- Audit trail is present, but there is no final admin sign-off yet that it is sufficient for supervision and escalation review.

### NEXT
- Validate whether supervisors can answer these questions quickly:
  - who opened the case
  - who owns the case now
  - what was the latest action
  - whether customer is waiting or agent is waiting
- Review whether SLA watchlist should link more directly back to Customer 360 and inbox context.

## 5. Customer Self-Service Boundary

### CURRENT DECISION
- Customer self-service is not part of the current phase.
- There is no dedicated customer user system yet.
- Admin CRM must be finalized first before any portal/customer-login work begins.

### DO NOT START YET
- customer login
- customer auth roles
- customer portal dashboard
- customer-owned ticket creation inside admin workspace
- customer-only ticket visibility rules

## 6. Execution Order

Recommended next order:

1. Finalize terminology across Customer 360, Ticket Handling, and Inbox.
2. Lock ticket lifecycle and admin handling rules.
3. Run a full admin UAT pass across Customer 360 -> Ticket -> Inbox -> SLA.
4. Fix any remaining operational ambiguity or dead ends.
5. Declare admin CRM phase stable.
6. Only then discuss customer portal scope.

## 7. Definition Of Done For Admin Phase

Admin CRM can be considered ready when:

- admin can open a customer and understand the full context without confusion
- admin can start or continue a case from Customer 360
- inbox flows and ticket flows do not conflict conceptually
- queue states are readable and actionable
- wording consistently signals internal admin work
- no remaining feature on Customer 360 suggests customer self-service behavior
- core backend feature tests and live production checks remain green

## 8. Immediate Next Work

Immediate work recommended after this checklist:

1. final terminology pass
2. admin UAT checklist for Customer 360 + Ticket Handling + Omnichannel
3. close remaining wording and workflow gaps discovered during UAT