# HMS API Reference — v1

Base URL: `http://127.0.0.1:8000/api/v1`

All responses are JSON. Authenticated routes need:

```
Authorization: Bearer <token>
Accept: application/json
```

## Conventions

| Case | Response |
|---|---|
| Validation error | `422` `{ "message": "...", "errors": { "field": ["..."] } }` |
| Unauthenticated | `401` `{ "message": "Unauthenticated." }` |
| Wrong role / not owner | `403` `{ "message": "..." }` |
| Not found | `404` |
| Created | `201` |
| Paginated list | `{ "current_page", "data": [...], "total", "last_page", "per_page" }` |

Roles: `student`, `employee`, `admin`. Staff = employee + admin.

Pagination query params: `page`, `per_page` (max 100). Search: `search`.

---

## 1. Auth

| Method | Path | Role | Body / notes |
|---|---|---|---|
| POST | `/auth/login` | public | `email, password, device_name?` → `token, user` |
| POST | `/auth/forgot-password` | public | `email` → generic success message |
| POST | `/auth/reset-password` | public | `email, token, password, password_confirmation` |
| GET | `/auth/me` | any | Current user + profile + active booking |
| POST | `/auth/logout` | any | Revokes current token |
| POST | `/auth/password` | any | `current_password, password, password_confirmation` |
| PATCH | `/auth/profile` | any | `name?, phone?, address?, guardian_name?, guardian_phone?, medical_notes?` |
| PATCH | `/student/profile` | student | Guardian / medical / address fields only |

Password policy: min 8 chars, letters, numbers, symbols.

```json
POST /auth/login
{ "email": "student1@hms.test", "password": "Student@123" }
```

---

## 2. Dashboard

| Method | Path | Role |
|---|---|---|
| GET | `/dashboard` | any (role-aware payload) |

**Student payload**

```json
{
  "role": "student",
  "active_booking": { "id": 1, "room": { "room_number": "101", "hostel": {} } },
  "fees": { "outstanding": 6500, "next_due": { "reference": "FEE-202611-0002" } },
  "complaints": { "open": 1 },
  "pending_room_swap": false,
  "unread_notifications": 2,
  "facilities": [ { "id": 1, "name": "Mess Hall", "status": "operational" } ]
}
```

**Staff payload:** `students.total`, `students.checked_in`, `occupancy { rooms, beds, occupied, free, rate, rooms_available }`,
`bookings { pending, approved }`, `complaints { open, urgent, unassigned }`,
`fees { collected_this_month, outstanding, pending_verification, overdue }`,
`requests { room_swaps_pending, student_requests_pending }`,
`inventory { low_stock, broken }`, `recent_complaints`.

---

## 3. Users (admin only)

| Method | Path | Body |
|---|---|---|
| GET | `/users` | filters: `role`, `status`, `search` |
| POST | `/users` | `name, email, password, role, phone?, status?`; students also need `registration_number, department, semester` |
| GET | `/users/{id}` | — |
| PATCH | `/users/{id}` | `name?, email?, phone?, role?, status?, password?` |
| DELETE | `/users/{id}` | cannot delete self |

## 4. Students (staff)

| Method | Path | Notes |
|---|---|---|
| GET | `/students` | `search`, `hostel_id`, `check_in_status` |
| GET | `/students/{id}` | full record: profile, booking, fees, complaints, requests |
| PATCH | `/students/{id}` | profile fields + `check_in_status` (`pending|checked_in|checked_out`), `discipline_status` (`clear|warning|probation`) |

---

## 5. Hostels & Rooms

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/hostels` | any | `type=boys|girls`, `active_only=1` |
| GET | `/hostels/{id}` | any | includes rooms |
| POST | `/hostels` | staff | `name, type, location?, total_rooms?, description?, is_active?` |
| PATCH | `/hostels/{id}` | staff | same fields, all optional |
| DELETE | `/hostels/{id}` | staff | blocked if residents remain |
| GET | `/rooms` | any | `hostel_id`, `status`, `room_type`, `available_only=1`, `per_page` |
| GET | `/rooms/{id}` | any | includes occupants |
| GET | `/bookings/available-rooms` | any | only rooms with a free bed |
| POST | `/rooms` | staff | `hostel_id, room_number, capacity, room_type?, floor?, monthly_rent?, notes?` |
| PATCH | `/rooms/{id}` | staff | capacity cannot drop below occupied beds |
| DELETE | `/rooms/{id}` | staff | blocked if occupied |

Room object includes computed `available_beds = capacity - occupied_beds`,
`status` (`available|full|maintenance`), `image_url` and `image_urls` (gallery).

---

## 6. Bookings

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/bookings` | staff | `status`, `room_id`, `hostel_id`, `student_id`, `search` |
| GET | `/bookings/mine` | student | own booking history |
| POST | `/bookings` | student | `room_id, requested_from?, note?` |
| POST | `/bookings/{id}/approve` | staff | `review_note?` → allocates bed |
| POST | `/bookings/{id}/reject` | staff | `review_note?` |
| POST | `/bookings/{id}/cancel` | student (owner) | pending only |
| POST | `/bookings/{id}/check-in` | staff | sets profile `check_in_status = checked_in` |
| POST | `/bookings/{id}/check-out` | staff | releases the bed |

Rules enforced:

- one pending/approved booking per student;
- room must exist, not be under maintenance, and have a free bed;
- approval locks the room row, so concurrent approvals cannot overfill it;
- approval auto-rejects the student's other pending requests.

---

## 7. Room swap requests

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/room-swap-requests` | any | students see own; staff `status`, `student_id` |
| GET | `/room-swap-requests/{id}` | owner/staff | — |
| POST | `/room-swap-requests` | student | `requested_room_id, reason` (min 10 chars) |
| POST | `/room-swap-requests/{id}/approve` | staff | moves allocation, updates both rooms |
| POST | `/room-swap-requests/{id}/reject` | staff | `review_note?` |
| POST | `/room-swap-requests/{id}/cancel` | student (owner) | pending only |

Requires an approved allocation; one pending swap at a time.

---

## 8. Complaints

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/complaints` | any | students see own; staff `status`, `priority`, `category`, `assigned_to`, `unassigned=1`, `search` |
| GET | `/complaints/{id}` | owner/staff | — |
| POST | `/complaints` | student | multipart: `category, description, priority?, photo?` |
| POST | `/complaints/{id}/assign` | staff | `assigned_to` (active staff user id) |
| PATCH | `/complaints/{id}/status` | staff | `status, resolution_note?` |
| DELETE | `/complaints/{id}` | admin | deletes photo too |

- `category`: `plumbing | electrical | wifi | cleanliness | furniture | mess | other`
- `priority`: `low | normal | high | urgent`
- `status`: `open → in_progress → resolved → closed` (or `escalated`)
- `photo`: image, max 5 MB. Stored on the `public` disk, exposed as `photo_url`.
- Ticket number auto-generates as `HMS-YYMMDD-XXXXX`.
- Complaints unassigned for 48 h are escalated hourly by the scheduler.

---

## 9. Fees

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/fees` | any | students see own; staff `student_id`, `status`, `type`, `period`, `search` |
| GET | `/fees/{id}` | owner/staff | — |
| POST | `/fees` | staff | `student_id, amount, due_date, type?, period?, fine?, booking_id?` |
| POST | `/fees/{id}/pay` | student (owner) | multipart `payment_method?, receipt?` (jpg/png/pdf, 5 MB) |
| POST | `/fees/{id}/verify` | staff | `approved` (bool), `note?` |
| POST | `/fees/{id}/waive` | staff | `note?` |
| DELETE | `/fees/{id}` | admin | paid invoices cannot be deleted |

- `type`: `hostel_fee | mess_fee | fine | security_deposit | other`
- `status`: `unpaid → pending_verification → paid` (or `overdue`, `waived`)
- Reference auto-generates as `FEE-YYYYMM-XXXXXX`; receipt exposed as `receipt_url`.

---

## 10. Facilities

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/facilities` | any | students only see visible ones; staff `type`, `status`, `visible` |
| POST | `/facilities` | staff | `name, type, description?, location?, opens_at?, closes_at?, capacity?, status?, is_visible?` |
| PATCH | `/facilities/{id}` | staff | same fields, all optional |
| PATCH | `/facilities/{id}/status` | staff | `status` |
| DELETE | `/facilities/{id}` | admin | — |

`status`: `operational | maintenance | emergency | closed`.
Setting **emergency** notifies every active student.

---

## 11. Inventory (staff)

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/inventory` | staff | `hostel_id`, `category`, `condition`, `low_stock=1`, `search` |
| POST | `/inventory` | staff | `name, quantity, hostel_id?, category?, unit?, threshold?, condition?, notes?` |
| PATCH | `/inventory/{id}` | staff | same fields |
| POST | `/inventory/{id}/adjust` | staff | `delta` (non-zero int), `condition?`, `notes?` |
| DELETE | `/inventory/{id}` | admin | — |

`condition`: `good | damaged | broken | lost`. Response includes `low_stock` flag.
Hostel blocks, rooms and facilities all expose `image_url` / `image_urls` in responses.

---

## 12. Student requests

| Method | Path | Role | Notes |
|---|---|---|---|
| GET | `/student-requests` | any | students see own; staff `status`, `type`, `student_id` |
| POST | `/student-requests` | student | `type, subject, details?, from_date?, to_date?` |
| POST | `/student-requests/{id}/review` | staff | `status` (`approved|rejected`), `review_note?` |
| POST | `/student-requests/{id}/cancel` | student (owner) | pending only |

`type`: `leave | room_change | facility | other`.

---

## 13. Notifications

| Method | Path | Notes |
|---|---|---|
| GET | `/notifications` | `unread_only=1`; returns `unread_count` + paginated list |
| POST | `/notifications/{id}/read` | owner only |
| POST | `/notifications/read-all` | owner |
| DELETE | `/notifications/{id}` | owner |

---

## 14. Scheduler / artisan

| Command | Schedule | Purpose |
|---|---|---|
| `complaints:escalate --hours=48` | hourly | escalate unassigned complaints |
| `fees:mark-overdue` | daily 00:10 | flag unpaid past-due invoices |

Run locally: `php artisan schedule:work`
