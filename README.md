# Advanced POS with Inventory & HRM System

## Overview
A unified Point of Sale (POS), Inventory Management, and Human Resource Management system for multi-brand, multi-branch retail/hospitality operations.

The target platform combines:
- Offline-first Windows POS
- Web-based management dashboard
- Mobile employee app (Flutter in current repo)
- Dedicated Windows warehouse/coffee-bean apps
- Shared Laravel API backend

## Goals
- Unify operations with central control and branch-level flexibility
- Ensure business continuity with offline-first clients
- Support advanced inventory and recipe/composite-product tracking
- Streamline logistics with dedicated warehouse workflows
- Enable data-driven decisions with reporting and AI-ready foundations

## Key Differentiators
- Offline-first Windows POS with hardware integration
- Recipe management for composite products
- Multi-location inventory and transfer workflows
- Unified API serving all client applications
- Architecture designed for future analytics/AI enhancements

## Technology Stack
| Component | Technology |
|---|---|
| Backend API | Laravel 11, PHP 8.2+, Sanctum, Spatie, MySQL |
| Web Admin | Vue 3, Vite, Tailwind CSS, Pinia, Vue I18n |
| Windows POS | .NET 8 WPF, SQLite (offline local store) |
| Mobile Employee App | Flutter (current repo), SQLite-capable offline strategy |
| Warehouse Apps | .NET 8 WPF, SQLite |
| Media Storage | Cloudinary |
| Deployment | Docker, Render (API + worker + static site) |
| Real-time (optional) | Laravel Reverb / Pusher |

## Documentation
See:
- `architecture.md`
- `roadmap.md`
- `data-model.md`
- `api-design.md`
- `finance.md`
- `frontend-guidelines.md`
- `windows-app-guidelines.md`
- `mobile-app-guidelines.md`
- `development-rules.md`
- `phase-gate-checklist.md`
- `phase-a0-execution.md`
- `deployment.md`
- `docs/api-change-control.md`
- `docs/sync-protocol-v1.md`
- `docs/permission-matrix.md`
- `docs/permission-map.md`
- `docs/openapi.yaml`
- `docs/gap-matrix.md`
- `docs/performance-budgets.md`
- `docs/backlog-issues.md`
