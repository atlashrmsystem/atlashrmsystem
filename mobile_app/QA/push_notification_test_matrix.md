# Push Notification Test Matrix (iOS)

## Assumptions
- Provider: FCM -> APNs delivery for iOS
- Environment: iOS sandbox
- Notification classes tested:
  - Leave status updates
  - Leave approval pending
  - Payslip ready
  - Salary certificate status updates

## Payload Contract (Proposed Minimal)
```json
{
  "type": "leave_status_updated",
  "title": "Leave Request Update",
  "body": "Your leave request was approved.",
  "route": "/leaves",
  "entity_id": "123",
  "meta": {
    "workflow_status": "approved"
  }
}
```

## Route Mapping Validation Table
| Event Type | Expected Route | Fallback |
|---|---|---|
| `leave_status_updated` | `/leaves` | `/` |
| `leave_approval_pending` | `/team-leave-queue` | `/` |
| `payslip_ready` | `/payslips` | `/` |
| `salary_certificate_updated` | `/payslips` | `/` |

## Device Matrix
| Device | OS | Priority |
|---|---|---|
| iPhone 16 Pro | iOS 18 | P0 |
| iPhone 15 | iOS 17 | P1 |
| iPhone SE | iOS 17/18 | P1 |

---

## A) Permission & Registration
| ID | Scenario | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| N-PERM-01 | First launch permission allow | Fresh install -> grant notifications | Permission granted; token registered server-side |  |  |  |
| N-PERM-02 | First launch permission deny | Fresh install -> deny notifications | App handles gracefully; no crash; shows guidance |  |  |  |
| N-PERM-03 | Re-enable from Settings | Deny then enable in iOS Settings | Notifications received after re-enable |  |  |  |

---

## B) Delivery by App State
| ID | App State | Notification Type | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|---|
| N-STATE-01 | Foreground | Leave update | Send push while app active | In-app banner/local display; no duplicate nav |  |  |  |
| N-STATE-02 | Background | Leave update | Send push, tap from tray | App opens route `/leaves` |  |  |  |
| N-STATE-03 | Terminated | Leave update | Kill app, send push, tap | Cold start then opens `/leaves` |  |  |  |
| N-STATE-04 | Background | Payslip ready | Send push, tap | Opens `/payslips` |  |  |  |
| N-STATE-05 | Terminated | Approval pending | Send push, tap as supervisor/manager | Opens `/team-leave-queue` |  |  |  |

---

## C) Deep-Link Correctness & Safety
| ID | Scenario | Payload | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| N-LINK-01 | Valid route + entity id | complete payload | Correct target screen opens |  |  |  |
| N-LINK-02 | Missing route | no `route` key | Safe fallback to `/` |  |  |  |
| N-LINK-03 | Unknown route | `route=/unknown` | Safe fallback to `/` |  |  |  |
| N-LINK-04 | Missing entity id | no `entity_id` | List screen opens without crash |  |  |  |
| N-LINK-05 | Stale entity id | deleted/invalid id | Screen loads graceful error/empty state |  |  |  |

---

## D) Concurrency / Reliability
| ID | Scenario | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| N-REL-01 | Burst messages | Send 5 notifications quickly | No crash; order acceptable; taps resolve correctly |  |  |  |
| N-REL-02 | Duplicate payload | Send same notification twice | No broken state; duplication handled predictably |  |  |  |
| N-REL-03 | Network transition | Receive during weak network | App remains stable; eventual route handling |  |  |  |

---

## E) In-App Inbox (Minimal, if enabled in release)
| ID | Scenario | Steps | Expected | Status | Evidence | Notes |
|---|---|---|---|---|---|---|
| N-INBOX-01 | List load | Open inbox | Notifications listed desc by time |  |  |  |
| N-INBOX-02 | Unread badge/count | Receive new push | Unread count increments |  |  |  |
| N-INBOX-03 | Mark single read | Open item | Item read state updates |  |  |  |
| N-INBOX-04 | Mark all read | Use mark-all action | Unread count reset |  |  |  |
| N-INBOX-05 | Offline inbox open | Disable network then open inbox | Graceful cached/empty/error handling |  |  |  |

---

## Defect Logging
| Bug ID | Severity | Device/OS | Test ID | Summary | Repro Steps | Actual vs Expected | Attachments |
|---|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |  |
