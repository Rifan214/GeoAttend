# GeoAttend — Audit Decisions & Physical-Device Testing Policy

## Approved project constraints
- Portfolio project only; not tied to a specific company or institution.
- Flutter Android app, Laravel backend + Blade/Tailwind admin dashboard, PostgreSQL.
- Local development without Docker.
- Biometric prompt through `local_auth` in the MVP; face verification is deferred.
- **No Android emulator or AVD is to be used for mobile testing.**
- Device-dependent mobile behavior must be tested manually on a real Android phone.

## Decisions resolving the audit
1. **Database:** Keep PostgreSQL as specified. Install PostgreSQL natively on Windows and enable PHP `pdo_pgsql` and `pgsql` in the active XAMPP PHP configuration. Do not install or modify system components without user approval; present exact steps first if approval is needed.
2. **Dashboard/API:** Laravel Blade uses web session authentication. Mobile endpoints use Sanctum tokens. Keep admin API endpoints only if needed by the approved API contract or a documented integration. Business rules must live in shared services, not be duplicated between Blade and API controllers.
3. **Schedule:** For MVP, each work location has one active default schedule. If employee-specific schedules become necessary, request a decision before changing the schema.
4. **Incomplete attendance:** A record with check-in but no check-out after the scheduled workday ends is displayed as incomplete. Document timezone and evaluation rules. No cron/background daemon is required for MVP.
5. **HTTP status:** `422` for invalid coordinates, unacceptable GPS accuracy, or geofence failures; `409` for duplicate check-in, checkout without check-in, or duplicate checkout; `401` for unauthenticated requests; `403` for authenticated users lacking permission. Keep behavior consistent and documented.
6. **Repository/docs:** Initialize Git at the project root, create a comprehensive `.gitignore`, and place specifications under `docs/`. Inspect before moving or overwriting any existing files.

## Testing policy

### Allowed on development computer
- `flutter analyze`
- `flutter test` for unit/widget tests that do not launch an emulator
- Laravel unit/feature tests against the configured test database
- Static analysis, formatting, API contract checks, and security review

### Required manually on a real Android phone
- Install and launch the app
- Runtime location permissions
- GPS acquisition and reported accuracy
- Location services disabled/unavailable
- Biometric prompt success, cancellation, failure, and unavailable/not-enrolled cases
- Secure token storage, session restoration, and logout
- Network access to the local Laravel server
- End-to-end check-in/check-out and user-visible error states
- Physical-device app lifecycle behavior where relevant

**Do not use Android Emulator, AVD, emulator location controls, or emulator-only networking for any mobile verification.** If no suitable physical device is available, mark device-dependent tests `BLOCKED / NOT VERIFIED`.

For physical-device access to the development server, configure the API base URL with the development computer's LAN IP and use a trusted private network. Do not use `localhost` or `10.0.2.2` on the physical phone. Do not expose an insecure development server to public networks.

## Required manual test report fields
For every device-dependent scenario record:
- Physical device model
- Android version
- App build/commit
- Test date
- Preconditions
- Steps
- Expected result
- Actual result
- PASS / FAIL / BLOCKED
- Notes or sanitized evidence

Do not capture fingerprint data, credentials, access tokens, or unnecessary precise location details in screenshots/logs.
