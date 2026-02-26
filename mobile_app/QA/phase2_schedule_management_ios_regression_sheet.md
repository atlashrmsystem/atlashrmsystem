# ATLAS ESS - Phase 2 iOS Schedule Management Regression Sheet

## Scope
- Feature: Supervisor Schedule Management (Create, Edit Published, Update, Publish).
- Backend coverage: `/schedules/week-status`, `/schedules`, `/schedules/publish`, `/shifts`.
- Devices in scope:
  - iPhone 16 Pro (primary, iOS 18+)
  - iPhone 15 (iOS 17/18)
  - iPhone SE (iOS 17/18, small screen readability)

## Build + Environment
- App name: `ATLAS ESS`
- API base URL must be reachable from phone Wi-Fi network.
- Test user role: Supervisor (or Super Admin for fallback endpoint validation).

## Execution Matrix

| Test ID | Test Scenario | iPhone 16 Pro | iPhone 15 | iPhone SE |
|---|---|---|---|---|
| SCH-IOS-01 | Launch app and login successfully |  |  |  |
| SCH-IOS-02 | Open Schedule Management screen without crash |  |  |  |
| SCH-IOS-03 | Store selector loads and week navigation works (prev/today/next) |  |  |  |
| SCH-IOS-04 | Shift time chips/buttons open picker and save valid `H:i` times |  |  |  |
| SCH-IOS-05 | Create New Schedule CTA visible in draft week |  |  |  |
| SCH-IOS-06 | Bulk Select -> select multiple cells -> Apply shift |  |  |  |
| SCH-IOS-07 | Publish Week with incomplete coverage returns warning (not crash) |  |  |  |
| SCH-IOS-08 | Week status badge updates correctly (`Draft/Published/Changes Pending Publish`) |  |  |  |
| SCH-IOS-09 | Published week becomes read-only until `Edit Published Schedule` |  |  |  |
| SCH-IOS-10 | Edit Published Schedule unlocks cell edit + shift timing edit |  |  |  |
| SCH-IOS-11 | After edit, CTA shows `Update Changes` and publish flow works |  |  |  |
| SCH-IOS-12 | Coverage summary values and week health warning render correctly |  |  |  |
| SCH-IOS-13 | Grid cell tap/long-press disabled while week is locked |  |  |  |
| SCH-IOS-14 | App remains stable after pull-to-refresh on this screen |  |  |  |
| SCH-IOS-15 | Navigation away/back preserves expected week state (no stale lock) |  |  |  |

## iPhone SE Readability Focus

| Test ID | Small-Screen UX Check | Expected |
|---|---|---|
| SCH-SE-01 | Header row and status badge fit without overlap | No clipping or overflow |
| SCH-SE-02 | Workflow hint container wraps text cleanly | Full text visible |
| SCH-SE-03 | Bulk Tools expansion and chips wrap lines properly | No horizontal clipping |
| SCH-SE-04 | Shift timing row (AM/MID/PM) controls stay tappable | Buttons fully visible |
| SCH-SE-05 | Coverage summary text wraps and remains readable | No truncated critical info |

## Focused API Smoke (Reference)
- Login: `PASS` with `superadmin@atlas.org`.
- Week status endpoint: `PASS`.
- Schedule fetch endpoint: `PASS`.
- Publish endpoint: `PASS` (returns validation warning when week incomplete, expected).

## Defect Log Template

| Bug ID | Device | Scenario | Steps | Expected | Actual | Severity | Priority | Status |
|---|---|---|---|---|---|---|---|---|
| BUG- |  |  |  |  |  |  |  |  |

