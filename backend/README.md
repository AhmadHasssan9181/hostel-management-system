# Hostel Management System (HMS)

Laravel 13 + PHP 8.3 web application implementing the FYP proposal
(`HMS_fyp (1).pdf`): rooms, bookings, room swaps, complaints, fees, facilities,
inventory, student records, requests and notifications — with a light-themed
Blade frontend and a versioned JSON API.

- **Framework:** Laravel 13.40 / PHP 8.3.36
- **Auth:** session auth for the web app, Sanctum bearer tokens for the API
- **DB:** SQLite for local dev, MySQL-ready migrations
- **Frontend:** Blade + Tailwind 4 + Alpine.js (compiled with Vite)
- **Theme:** light + dark, persisted switch, custom SVG illustrations
- **Tests:** 68 feature tests, `php artisan test`

## Quick start

```powershell
cd backend
npm install
npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Web app: `http://127.0.0.1:8000/login`
API root: `http://127.0.0.1:8000/api/v1`

The frontend assets are prebuilt in `public/build`; rerun `npm run build` only
after changing CSS or JavaScript.

### Printable documents

- Fee voucher / receipt — three copies (student, bank, office) with a PAID watermark
- Batch voucher printing for every unpaid or overdue invoice
- Room allocation slips for approved bookings
- Maintenance complaint tickets with an office-use section

### Photos

Room, block and facility photos are fully editable by staff/admins (including replacement uploads). Bookings, invoices, complaints, rooms, students, inventory, facilities and user accounts all have edit screens.

Room, hostel-block and facility photos are served from `public/images` in the seed
data. Staff can replace any photo from the create/edit forms; uploads are stored on
the public disk under `storage/app/public`. Run `php artisan storage:link` once after
deploying.

### Demo accounts (after seeding)

| Role     | Email               | Password      |
|----------|---------------------|---------------|
| Admin    | admin@hms.test      | Admin@1234    |
| Employee | warden@hms.test     | Staff@1234    |
| Employee | clerk@hms.test      | Staff@1234    |
| Student  | student1@hms.test   | Student@123    |
| Student  | student2@hms.test   | Student@123    |

## Login example

```powershell
$body = '{"email":"student1@hms.test","password":"Student@123"}'
$res = Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/v1/auth/login `
  -ContentType 'application/json' -Body $body
$res.token
Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v1/dashboard `
  -Headers @{ Authorization = "Bearer $($res.token)" }
```

Full endpoint reference: [`API.md`](API.md).

## What is implemented

| Module | Highlights |
|---|---|
| Auth & accounts | Login/logout, me, change password, forgot/reset password, profile updates; accounts are provisioned by admins through user management |
| Users & roles | Admin CRUD for users, role middleware, self-service profile |
| Students | Staff directory with filters, full student record, own profile editing |
| Hostels & rooms | CRUD, bed capacity tracking, `available_beds` computed field, availability filter |
| Bookings | Student request, staff approve/reject, check-in/check-out, automatic bed release |
| Room swaps | Request with reason, approval moves the allocation and adjusts both rooms |
| Complaints | Ticket numbers, priority, photo upload (5 MB), assignment, status workflow, 48 h auto-escalation |
| Fees | Invoice generation, receipt upload, staff verification, waivers, overdue flagging |
| Facilities | CRUD, visibility toggle, operational status, emergency broadcast to students |
| Inventory | Stock tracking, low-stock detection, condition flags, stock adjustments |
| Requests | Leave / room change / facility requests with staff review |
| Notifications | In-app feed, unread counts, mark read |
| Dashboard | Role-aware stats for students and staff |

## Database tables

`users`, `student_profiles`, `hostels`, `rooms`, `bookings`, `room_swap_requests`,
`fees`, `complaints`, `facilities`, `inventory_items`, `student_requests`,
`app_notifications`, plus Laravel's `personal_access_tokens`, cache/jobs/sessions.

## Design notes

- **Bed accounting is transactional.** Approving a booking or a room swap runs inside
  `DB::transaction()` with `lockForUpdate()`, so two concurrent approvals cannot claim
  the same bed. This covers the "Room Full" concurrency requirement in UC-HMS-03.
- **Statuses are PHP enums** (`app/Enums`) cast on models — no magic strings in code.
- **Money is `decimal(10,2)`** and serialized as strings by Eloquent; totals use SQL `SUM`.
- **Complaint SLA:** `php artisan complaints:escalate` runs hourly and escalates tickets
  unassigned for 48 h, notifying all staff.
- **Overdue fees:** `php artisan fees:mark-overdue` runs daily at 00:10.
- **Notifications** are stored in `app_notifications` and pushed from services, so the
  same code path works for the future web/mobile frontend.

### Scheduler

```powershell
php artisan schedule:work   # local
```

On a server, add the standard cron entry:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Switching to MySQL

SQLite is the default for zero-setup development. The migrations are portable:

1. Create a database named `hms` in MySQL (XAMPP/WAMP phpMyAdmin).
2. In `backend/.env` replace the SQLite block with:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hms
DB_USERNAME=root
DB_PASSWORD=
```

3. Run `php artisan migrate:fresh --seed`.

## Tests

```powershell
cd backend
php artisan test          # 74 tests / 348 assertions
vendor\bin\pint          # code style
```

## Frontend contract

- Send `Authorization: Bearer <token>` on every authenticated request.
- Paginated endpoints return Laravel's `{ data, current_page, last_page, total, ... }`.
- Validation failures return `422` with `{ message, errors }`.
- Role failures return `403`; missing auth returns `401`.
- File uploads use `multipart/form-data` (`photo` for complaints, `receipt` for fees).
