# iOS Release Gate & Sign-Off Checklist

## Release
| Field | Value |
|---|---|
| App version |  |
| Build number |  |
| Release date |  |
| QA owner |  |
| Engineering owner |  |

## Go/No-Go Criteria
| Gate | Requirement | Result |
|---|---|---|
| G1 | All P1 test cases pass |  |
| G2 | No open Sev-1 defects |  |
| G3 | No open Sev-2 defects without accepted risk waiver |  |
| G4 | Push notification matrix completed on all iOS target devices |  |
| G5 | Auth/session expiry flow verified |  |
| G6 | Leave attachment end-to-end verified |  |
| G7 | Dashboard API de-dup verification complete |  |
| G8 | Profile read-only accuracy verified |  |

## Mandatory Feature Sign-Off
| Feature | Owner | Status | Notes |
|---|---|---|---|
| Authentication (login/logout/session expiry) |  |  |  |
| Dashboard core actions |  |  |  |
| Attendance (online/offline sync) |  |  |  |
| Leaves (including attachment) |  |  |  |
| Payslips & certificates |  |  |  |
| Profile (read-only) |  |  |  |
| Push notifications (delivery + deep link) |  |  |  |

## Open Defects Summary
| Severity | Count | Blocker? |
|---|---:|---|
| Sev-1 |  |  |
| Sev-2 |  |  |
| Sev-3 |  |  |
| Sev-4 |  |  |

## Risk Register
| ID | Risk | Impact | Mitigation | Owner | Status |
|---|---|---|---|---|---|
| R-01 |  |  |  |  |  |
| R-02 |  |  |  |  |  |

## Test Execution Coverage
| Area | iPhone 16 Pro (iOS 18) | iPhone 15 (iOS 17) | iPhone SE (iOS 17/18) |
|---|---|---|---|
| P1 Regression |  |  |  |
| Core employee flows |  |  |  |
| Supervisor flows |  |  |  |
| Manager flows |  |  |  |
| Push foreground/background/terminated |  |  |  |
| UX small-screen checks |  |  |  |

## Final Decision
- Decision: `GO / NO-GO`
- Decision timestamp:
- Approved by:
- Comments:

## Post-Release Monitoring (First 24 Hours)
| Check | Owner | Status | Notes |
|---|---|---|---|
| Crash-free sessions |  |  |  |
| Push delivery success rate |  |  |  |
| Authentication error rate |  |  |  |
| Leave submission failures |  |  |  |
| Payslip download failures |  |  |  |
