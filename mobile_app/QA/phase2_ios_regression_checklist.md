# Phase-2 iOS Regression Run Sheet

## Scope
- Platform: iOS only
- Devices:
  - iPhone 16 Pro (iOS 18) - Primary
  - iPhone 15 (iOS 17)
  - iPhone SE (iOS 17/18)
- Roles:
  - Staff/Employee
  - Supervisor
  - Manager

## Test Metadata
| Field | Value |
|---|---|
| Build version |  |
| Backend environment |  |
| QA date |  |
| Tester name |  |

## Pass/Fail Rules
- `Pass`: Expected behavior observed and evidence captured.
- `Fail`: Behavior incorrect or incomplete.
- `Blocked`: Cannot execute due to env/config/data issue.

## Evidence Format
- Screenshot/video filename:
- API log snippet (if needed):
- Device + OS:

---

## A) P1 Regression (Must Pass)
| ID | Test | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| P1-01 | Leave attachment upload | Sick leave >2 days, attach file, submit | Request succeeds; attachment persisted and visible in backend payload/storage |  |  |  |
| P1-02 | Leave validation | Sick leave >2 days without file | Submit blocked with clear message |  |  |  |
| P1-03 | Token expiry logout | Expire/revoke token, perform API action | App clears session and routes to `/login` |  |  |  |
| P1-04 | Post-logout guard | After forced logout, open protected routes | Redirect remains on `/login` until re-auth |  |  |  |

---

## B) Core Employee Flows (Staff)
| ID | Test | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| S-01 | Login valid | Login with staff credentials | Dashboard opens |  |  |  |
| S-02 | Login invalid | Wrong password | Error shown, no login |  |  |  |
| S-03 | Attendance clock-in | Clock in online | Success message; today's status updates |  |  |  |
| S-04 | Attendance clock-out | Clock out online | Confirmation + success; state updated |  |  |  |
| S-05 | Offline attendance queue | Disable network, clock in/out, reconnect | Event queued then synced; no duplicate entries |  |  |  |
| S-06 | Leave request annual | Create annual leave | Appears in leave list with pending state |  |  |  |
| S-07 | Leave request history | Open leaves screen | History/status display is accurate |  |  |  |
| S-08 | Payslip list | Open payslips | Payslip list or correct empty state |  |  |  |
| S-09 | Payslip download | Download and open payslip | File opens successfully |  |  |  |
| S-10 | Profile read-only accuracy | Open profile | Role/store/brand/area fields accurate and read-only |  |  |  |

---

## C) Supervisor Flows
| ID | Test | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| SV-01 | Role navigation | Login as supervisor | Supervisor routes available |  |  |  |
| SV-02 | Leave queue access | Open team leave queue | Pending supervisor items visible |  |  |  |
| SV-03 | Approve leave | Approve from queue | Status moves to pending manager |  |  |  |
| SV-04 | Reject leave | Reject with reason | Rejected status + reason visible |  |  |  |
| SV-05 | Staff list access | Open staff list | Screen loads with authorized data only |  |  |  |

---

## D) Manager Flows
| ID | Test | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| MG-01 | Attendance tab hidden | Login as manager | Bottom nav hides Attendance tab |  |  |  |
| MG-02 | Team attendance | Open team attendance | Data visible, no authorization error |  |  |  |
| MG-03 | Leave queue manager stage | Open leave queue | Items pending manager visible |  |  |  |
| MG-04 | Approve leave | Approve manager item | Status moves to pending HR |  |  |  |

---

## E) Compatibility & UX Spot Checks
| ID | Test | Device | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| UX-01 | Small-screen layout | iPhone SE | No clipped text/buttons in key screens |  |  |  |
| UX-02 | Dynamic text/readability | All | No overlap/truncation in major labels |  |  |  |
| UX-03 | Orientation behavior | All | Supported orientation behaves correctly |  |  |  |
| UX-04 | Error-state consistency | All | Clear actionable messages for failures |  |  |  |

---

## Final Verdict
| Gate | Result |
|---|---|
| P1 tests all passed |  |
| Push tests all passed (see push matrix) |  |
| No Sev-1/Sev-2 open defects |  |
| Ready for release |  |
