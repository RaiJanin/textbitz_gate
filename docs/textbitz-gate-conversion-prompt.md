# Converting TextBitz → TextBitz Gate
### Detailed engineering prompt for repurposing the existing codebase into a school RFID turnstile attendance-notification platform

---

## 0. How to use this document

Both repos are already cloned locally with dependencies installed — this is a direct execution brief, not a setup guide. Hand it straight to a developer or an AI coding agent (e.g. Claude Code) working against:

- **`textbitz_server`** — currently the central Laravel server for the SMS blast platform
- **`textbitz`** — currently the NativePHP (Android) client app

No environment setup, `composer install`, or `npm install` steps are included below — start directly at §3 (what to strip) once you've confirmed both projects still boot locally against their existing `.env` / dev config.

Treat every "Reuse" item below as a real instruction to adapt existing code, not rewrite it — the whole point of this conversion is that TextBitz already solved the hard offline-first, dual-auth, real-time-sync problems. Only the **domain** is changing (SMS blasts → attendance taps); the **architecture pattern** mostly isn't.

---

## 1. Product summary

**TextBitz Gate** is a school attendance notification system built on RFID turnstile hardware at school entrances/exits. When a student taps their RFID-enabled ID at a turnstile, the event is captured, sent up to a server, and pushed in real time to the parent's (and optionally the student's own) mobile app as a notification: *"Diana tapped IN at Main Gate — 7:42 AM."*

This is **not** a school-operated admin dashboard. The primary user is the **parent/guardian**, monitoring their own child(ren)'s comings and goings. A secondary, optional user is the **student themselves**, viewing their own attendance in a lighter, more personal framing. Both roles live in the same app, switchable per logged-in account.

---

## 2. System architecture

```
[RFID Turnstile Hardware]
        │  (tap event: card UID, direction, timestamp)
        ▼
[School's Local Server]   ← runs on-prem at each school (this is what
        │                    textbitz_server becomes / is repurposed into,
        │                    OR a lightweight ingest service the school
        │                    server talks to — see Open Question 1 below)
        │  (validates tap, resolves UID → student, applies cutoff-time
        │   rules, determines IN/OUT, persists TapEvent)
        ▼
[Push/Broadcast Layer]    ← Laravel Reverb (already built for TextBitz;
        │                    repoint from "blast delivery status" channels
        │                    to "tap event" channels) + FCM for background
        │                    push (new requirement, see §6)
        ▼
[Parent/Student Mobile App]  ← NativePHP Android app (textbitz repo),
                                re-skinned as TextBitz Gate
```

Key point carried over from the existing architecture: **the mobile app does not talk to the turnstile hardware directly.** It only ever talks to the school's server, exactly like the current TextBitz client never talks to the SMS gateway directly — it goes through the central server. This lets us reuse almost the entire `RemoteApiClient` / `RemoteAuthService` / `ServerConnectivityService` / `BroadcastAuthProxyController` stack as-is, just repointed at new endpoints and channel names.

### Open Question 1 — who receives the raw turnstile signal?
The turnstile hardware itself (RFID reader) needs *something* listening on the LAN at the school. Two options — pick one before implementation starts:
- **(a)** A small on-prem bridge/agent (could be an ESP8266/Arduino-adjacent device or a lightweight local service) that reads the RFID reader's output and forwards it via HTTPS to the school's TextBitz Gate server instance.
- **(b)** The turnstile controller itself has network/webhook capability and posts directly to the school server's ingest endpoint.
Either way, the **school server's job starts at "I received a tap event via HTTPS POST"** — everything below assumes that.

---

## 3. What to strip out of the existing codebase

Remove entirely (or leave dormant behind a feature flag if a hard cutover is risky):
- SMS blast sending pipeline: `SMSBlastService`, `SMSBlastRecipients` trait, `SendBlastJob`, `PushBlastToServerJob` / `PushBlastRecipientsJob` chain
- Contacts module: `ContactRequest`, contacts CRUD, tag system, the Vue contacts module (search/filter/infinite-scroll/multi-select)
- Templates module: template CRUD, `findByHashId`, most-used ordering
- Blast-related DB tables: `blasts`, `blast_recipients`, `contacts`, `templates` (or migrate/rename if any of this data has reuse value — it doesn't here)
- Mobile UI: New Blast screen, Contacts screen, Templates screen, and the center "compose" nav action

**Do not remove** (these are the reusable skeleton):
- Dual-auth architecture (Breeze local + Sanctum remote, bridged at login/reconnect)
- `RemoteApiClient` and its `RESULT_SUCCESS/RETRY/FAILED/UNAUTHORIZED` pattern
- `ServerConnectivityService`, `RemoteAuthService`, `BlastSyncService`'s retry pattern (rename, keep the mechanism)
- `ServerConnectionRestored` / `SyncPendingClientData` / `ServerConnectionLost` event-driven reconnect flow
- Laravel Reverb + Echo real-time integration, and the `BroadcastAuthProxyController` proxy-auth pattern
- NativePHP platform detection (`PlatformService::detect()`), `useNativeLoader.js`, `crossPlatformToast`
- Deep-link flow infrastructure (can be reused for guardian-invite/child-link deep links — see §5)
- Deployment pipeline: Dokploy + Railpack + Traefik + the `CONTAINER_ROLE` switch (`app`/`queue`/`reverb`/`scheduler`) — just needs a new Dockerfile stage if an ingest worker is split out

---

## 4. New domain model

Replace blast/contact/template tables with:

| Table | Key fields |
|---|---|
| `schools` | id, name, timezone, attendance_cutoff_time, ingest_token (for hardware/bridge auth) |
| `gates` | id, school_id, name (e.g. "Main Gate", "Side Gate"), status (online/offline), last_seen_at |
| `students` | id, school_id, full_name, grade, section, rfid_uid, avatar/initials |
| `guardians` | id, name, email/phone, auth credentials (this is the "parent" account) |
| `guardian_student` | pivot: guardian_id, student_id, relationship label ("Mom", "Dad", "Guardian") |
| `student_accounts` | id, student_id, auth credentials (optional — only if student self-login is enabled) |
| `tap_events` | id, student_id, gate_id, direction (in/out), tapped_at, is_late (bool), synced_at |
| `notification_preferences` | owner (guardian or student), arrival, departure, late_alert, weekly_summary — per-toggle booleans |

Notes:
- `is_late` is computed server-side at ingest time against `schools.attendance_cutoff_time` — don't push that logic to the client.
- An "absent" state (as shown in the History calendar mockups) is **derived**, not stored: end-of-day job flags any enrolled student with zero `tap_events` for a school day as absent, and that triggers the "was marked absent — contact the school if this doesn't look right" alert.
- Keep `remote_token` / `remote_id` / `remote_synced_at` pattern from the existing users table for guardian/student accounts — it's the exact mechanism the offline-first sync already depends on.

---

## 5. API surface to build

**Ingest (school server, called by the turnstile bridge/controller):**
```
POST /api/ingest/tap
Headers: Authorization: Bearer {school.ingest_token}
Body: { rfid_uid, gate_id, timestamp }
```
Resolves UID → student, computes direction (toggle from last known state) and lateness, persists `TapEvent`, then broadcasts on `student.{student_id}` and dispatches push (see §6).

**Guardian↔student linking:**
```
POST /api/link/request   { student_id or school-issued link code }
```
Reuse the existing deep-link notification infrastructure for this — a school could hand a parent a link code or QR at enrollment, and tapping it in-app (or a deep link) completes the link, the same way the password-reset deep link flow already works.

**Mobile app read endpoints:**
```
GET /api/students/{id}/status        → today's timeline (arrived/dismissed)
GET /api/students/{id}/history       → month calendar + daily records
GET /api/students/{id}/alerts        → alerts feed
GET/PUT /api/notification-preferences
```
All of these are read/config only from the mobile side — the app never writes attendance data, only preferences and links.

**Real-time channels (Reverb):**
- `private-student.{student_id}` — broadcast every new `TapEvent`, subscribed to by every linked guardian + the student's own account if self-login is enabled
- `private-gate.{gate_id}` — device online/offline status, if you want gate-health visibility anywhere (parent app doesn't need this; a future school-admin view would)

---

## 6. Real-time delivery — new requirement beyond what TextBitz already does

The existing Reverb/Echo setup pushes updates **while the app is open and connected**. A parent needs to know their kid tapped in **even if the app is closed** — this is the whole value proposition. That means:

- Add **Firebase Cloud Messaging (FCM)** push notifications alongside Reverb. Reverb keeps the in-app live feed instant; FCM is what wakes a background/killed app.
- On tap-event ingest, after broadcasting to Reverb, also enqueue a push job per linked guardian (and student account, if enabled) — respecting each recipient's `notification_preferences` (don't push an arrival notification to someone who's turned arrival notifications off, etc.).
- NativePHP's mobile-device package integration (already diagnosed once for `System::isAndroid()`) will need extending to register/refresh FCM tokens per device and store them against the guardian/student account.

---

## 7. Mobile app — screens to build

Reskin `textbitz` into four screens, each behaving differently by role (parent vs student), based on the mockups already produced for this project:

1. **Home** — status hero (At School / not yet arrived), today's timeline (Arrived → Dismissal), weekly on-time stat, recent notifications feed. Parent view includes a child switcher if more than one linked student; student view is single-self, first-person copy, and a "your guardian was notified" transparency note.
2. **History** — month calendar (on-time/late/absent color coding) + a scrollable list of daily IN/OUT records.
3. **Alerts** — late arrivals, absence flags, weekly summary digest. Student view adds positive-reinforcement cards (on-time streaks) that don't exist on the parent side.
4. **Settings** — parent: linked children (+ link another via code), notification toggles, school contact info, sign out. Student: linked guardians (view-only), notification toggles, school contact info, sign out.

Global: a role switcher only shown if an account has both a guardian and student login for demo/dev purposes — in production this is likely just "which account did you log into," not an in-app toggle, unless you want to support a single login seeing both (worth deciding explicitly, see Open Question 2).

### Open Question 2
Should a parent who is *also* enrolled as a student-account holder (unlikely, but e.g. an older sibling with their own account) be able to switch roles inside one login, or should parent and student always be fully separate accounts/logins? The mockups built an in-app switcher for demo convenience; decide if that's real product behavior or just a way to review both designs in one file.

---

## 8. Privacy & data handling — treat as first-class, not an afterthought

This system handles **children's location/movement data** (a student's physical arrival/departure from a specific place at a specific time), which is materially more sensitive than SMS blast contact lists:

- Store the minimum needed: `tap_events` needs student, gate, timestamp — resist the urge to log anything richer (no GPS, no device fingerprinting beyond what's needed for FCM delivery).
- Guardian-student linking must be verifiable (school-issued code, not open self-service) so a stranger can't link themselves to a child's attendance feed.
- Depending on your jurisdiction, this kind of system may fall under child-data-protection rules (e.g. the Philippines' Data Privacy Act, or FERPA/COPPA-equivalent regimes elsewhere) — worth a compliance review with the school before rollout, not something to bolt on later. This document isn't legal advice; flagging it so it's on the plan.
- Decide a retention policy for `tap_events` up front (e.g. keep 1 school year, then archive/purge) rather than growing the table indefinitely.

---

## 9. Suggested phased build order

1. **Strip & scaffold** — with both repos already cloned and dependencies installed, start by removing blast/contacts/templates (§3) and adding new migrations (§4)
2. **Ingest pipeline** — build `/api/ingest/tap`, lateness/direction logic, persist `TapEvent` (test with curl/Postman before any hardware exists)
3. **Real-time + push** — repoint Reverb channels, add FCM job on ingest (§6)
4. **Mobile read APIs** — status/history/alerts/preferences endpoints (§5)
5. **Mobile UI** — four screens × two roles, reusing NativePHP shell, toast/loader composables, and offline-cache patterns from the old blast list views
6. **Guardian-student linking flow** — school-issued codes + deep link (§5)
7. **Hardware bridge decision** — resolve Open Question 1, build/adapt the bridge or controller integration
8. **Privacy pass + retention policy** — before any real student data touches the system (§8)
9. **Deploy** — extend the existing Dokploy/Railpack pipeline; likely just a new `CONTAINER_ROLE` for any ingest-specific worker

---

## 10. Explicitly out of scope (unless you want to add it back in)

- Any school-admin dashboard (device fleet health, enrollment management, staff accounts) — the four mockup screens covered *parent/student only*. A school-facing admin app is a separate, larger piece of work reusing the same server.
- SMS delivery of any kind — Gate is push-notification only.
- Anything resembling the old Contacts/Templates modules — there's no "audience" to manage in this product; recipients are determined entirely by the guardian_student graph.
