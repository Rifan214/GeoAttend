# GeoAttend — Antigravity Implementation Plan

## Instructions for the agent
You are implementing GeoAttend, a portfolio project. Read `PRD.md`, `ARCHITECTURE.md`, `DATABASE.md`, and `API_SPEC.md` before coding. These documents define the approved scope.

### Hard constraints
- Do not use Docker.
- Do not add face recognition, liveness detection, offline sync, payroll, or other out-of-scope features.
- Do not claim `local_auth` proves biometric identity to the backend.
- Never trust client-supplied radius, office coordinates, role, distance, or authoritative timestamp.
- Never store raw fingerprint data or biometric templates.
- Do not commit secrets, `.env`, signing keys, or tokens.
- Do not silently bypass biometric confirmation when it is unavailable.
- Use migrations, validation, authorization, tests, and readable code.
- Do not overwrite unrelated user changes. Inspect repository status before edits.
- If a required decision is ambiguous or conflicts with the specification, stop and report the decision needed rather than silently inventing behavior.

## Phase 0 — Repository inspection and plan
1. Inspect the current workspace and Git status.
2. Identify installed versions of PHP, Composer, Flutter, Dart, and PostgreSQL where possible.
3. Do not install or upgrade system tools without approval.
4. Propose a concrete file-by-file plan and list assumptions.
5. Create the repository structure and documentation only after confirming no existing work will be overwritten.
6. Report environment blockers clearly.

## Phase 1 — Laravel foundation
1. Create/configure Laravel backend in `backend/`.
2. Configure PostgreSQL through `.env.example` (never commit `.env`).
3. Add Sanctum API authentication and role authorization.
4. Add migrations, models, factories, and safe demo seeders.
5. Add Form Requests, API Resources, service/action classes, and route version prefix `/api/v1`.
6. Add tests for login, authorization, validation, and data access isolation.
7. Run migrations and the full backend test suite; fix failures before proceeding.

## Phase 2 — Attendance business logic
1. Implement server-side Haversine calculation.
2. Validate coordinates, accuracy, assigned active location, radius, account status, and schedule.
3. Use server timestamps only.
4. Implement check-in/out in transactions with duplicate-state handling and database constraints.
5. Ensure employees can only access their own records and admins have explicitly authorized access.
6. Add tests for inside/outside radius, poor accuracy, invalid coordinates, inactive users/location, duplicate check-in, checkout without check-in, duplicate checkout, and cross-user access.
7. Report test results and any limitations.

## Phase 3 — Admin dashboard
1. Build Blade/Tailwind admin login and dashboard using Laravel web authentication.
2. Add employee, location, and schedule CRUD.
3. Add attendance report filters and CSV export.
4. Add server-side authorization, validation, pagination, and safe deactivation behavior.
5. Add tests for admin-only access and report filters.
6. Verify dashboard responsiveness and empty/error/loading states.

## Phase 4 — Flutter mobile
1. Create/configure Flutter app in `mobile/`.
2. Use Riverpod, Dio, `geolocator`, `local_auth`, and `flutter_secure_storage`.
3. Implement login/logout, current user, permission handling, location acquisition, biometric prompt, today status, check-in/out, history, and profile.
4. Keep API base URL in environment/build configuration; never hardcode a production secret.
5. Handle emulator (`10.0.2.2`) versus physical-device LAN URL in developer documentation.
6. Implement loading, retry, permission-denied, unavailable biometric, poor GPS accuracy, out-of-radius, and network-error states.
7. Do not show success until backend confirms success.
8. Add unit/widget tests and run `flutter analyze` and `flutter test`.

## Phase 5 — Integration and hardening
1. Verify API contracts match the Flutter models.
2. Test with Android emulator and, for real biometric behavior, a compatible physical device.
3. Test backend authorization independently of UI.
4. Review logs for secrets and unnecessary location exposure.
5. Verify duplicate requests and concurrent attendance submissions.
6. Run all backend and Flutter tests and static analysis.
7. Fix failures rather than suppressing or skipping tests.

## Phase 6 — Documentation and release readiness
1. Complete README with prerequisites, local setup, migrations/seeders, test commands, and run commands.
2. Document demo account setup safely; use local-only seeded credentials and require users to change/remove them before deployment.
3. Add screenshots only after real screens exist.
4. Ensure `.gitignore` excludes `.env`, build output, caches, and credentials.
5. Provide a final report with implemented features, commands run, exact test results, known limitations, and next steps.

## Required reporting format at the end of each phase
- Phase completed
- Files created/changed
- Commands executed
- Tests run and exact pass/fail counts
- Manual verification performed or still required
- Known limitations/blockers
- Recommended next phase

Do not claim a test passed unless it was actually run and its output was inspected.
