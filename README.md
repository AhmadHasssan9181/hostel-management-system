# Hostel Management System (HMS)

Final Year Project — Department of Computer Science, Federal Urdu University of Arts,
Science & Technology, Islamabad.

- **Student:** Ms. Insha Murtaza — BS CS SP-2023-Eve-0212
- **Supervisor:** Ms. Shamsa Bano
- **Project Coordinator:** Dr. Fakhra Kashif

The FYP proposal is `HMS_fyp (1).pdf`.

---

## Repository layout

```
G:\hms
├── HMS_fyp (1).pdf        proposal document
├── README.md              this file
└── backend\               Laravel 13 REST API (all modules implemented)
    ├── API.md             endpoint reference for the frontend team
    ├── app\Enums\         Role, statuses, priorities as PHP enums
    ├── app\Models\        13 domain models
    ├── app\Services\      booking / swap / complaint / fee business rules
    ├── database\migrations\  domain schema
    └── database\seeders\  DemoSeeder with realistic demo data
```

## Status

| Layer | Status |
|---|---|
| Database schema | Done — 12 domain tables + auth/session tables |
| Auth & roles | Done — Sanctum tokens, `student` / `employee` / `admin` |
| Room allocation & booking | Done — transactional, concurrency-safe |
| Room swap | Done — approval moves the allocation |
| Complaints | Done — tickets, priority, photos, assignment, 48 h escalation |
| Fees | Done — invoices, receipt upload, verification, overdue flagging |
| Facilities | Done — statuses + emergency broadcast |
| Inventory | Done — stock levels, low-stock alerts |
| Student requests | Done — leave / room change / facility |
| Notifications | Done — in-app feed |
| Dashboard | Done — role-aware statistics |
| Web frontend | Done — Blade + Tailwind, light theme, responsive |
| Printable documents | Done — fee vouchers (3 copies), batch vouchers, receipts, allocation slips, complaint tickets |
| Mobile app | Future work |

## Run it

```powershell
cd backend
npm install
npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Open `http://127.0.0.1:8000` and sign in with one of the demo accounts below.
The compiled frontend assets live in `public/build`, so `npm run build` only needs
to run again after CSS or JavaScript changes.

Demo accounts:

| Role | Email | Password |
|---|---|---|
| Admin | admin@hms.test | Admin@1234 |
| Employee | warden@hms.test | Staff@1234 |
| Student | student1@hms.test | Student@123 |

Full setup, module notes and MySQL migration steps: [`backend/README.md`](backend/README.md).
Endpoint reference: [`backend/API.md`](backend/API.md).

## How the proposal gaps were resolved

The original document had inconsistencies; these decisions are now implemented:

1. **Payment flow** — the proposal mixed "upload receipt" with a PCI-DSS card
   gateway. Implemented the manual flow (receipt upload → staff verification),
   which is realistic for a university hostel. A gateway can be added later without
   schema changes.
2. **Room swap** — was a headline feature with no module or table. Now a full module
   (`room_swap_requests`) with approval that updates both rooms atomically.
3. **Admin role** — was referenced but missing from the schema. `users.role` now
   supports `admin`; admin-only routes cover user management and deletions.
4. **Missing tables** — added `facilities`, `inventory_items`, `student_requests`,
   `app_notifications`, `room_swap_requests`; complaints gained `priority`,
   `assigned_to`, `photo_path`, `resolved_at`.
5. **Duplicate use-case ID** — the API now treats each module as an independent
   resource, so the UC numbering issue does not leak into the implementation.
6. **Laravel vs plain PHP** — implemented with Laravel, as listed in §4.6 of the
   proposal.

## Tests

```powershell
cd backend
php artisan test
```

74 feature tests / 348 assertions cover login, password reset, role guards, bookings,
complaints, fees, room swaps, requests, students, blocks, rooms, facilities, inventory,
user provisioning, document printing, photo uploads and API CRUD.
