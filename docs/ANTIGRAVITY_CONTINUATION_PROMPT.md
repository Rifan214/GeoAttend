# Antigravity — Continue GeoAttend after audit

Read all approved specification documents and `AUDIT_DECISIONS_AND_TESTING.md` before making changes.

## Mandatory constraints
- Do not use Docker.
- Do not use Android Emulator, AVD, or any emulator for mobile testing, verification, screenshots, or acceptance tests.
- Device-dependent mobile behavior must be tested manually on a real Android phone.
- Host-side `flutter test` (unit/widget tests without launching an emulator) and `flutter analyze` are allowed. Mocks do not replace real-device verification.
- If the physical device or required biometric capability is unavailable, mark the relevant test `BLOCKED / NOT VERIFIED`; never substitute an emulator or claim success.
- Do not store raw fingerprint data or claim that `local_auth` provides biometric proof to the backend.
- Do not trust client-provided radius, office coordinates, distance, role, or timestamp.
- Do not overwrite existing user files. Inspect repository and Git status first.

## Resolve audit items
1. Keep PostgreSQL as the target database; check whether installation and PHP extensions are available. Do not install or alter system components without approval. If blocked, provide exact instructions and wait.
2. Keep Blade session auth for the admin website and Sanctum for mobile API authentication. Avoid duplicating business rules between controllers.
3. For MVP, each location has one active default schedule.
4. A check-in without check-out after the scheduled workday is presented as incomplete, with documented timezone behavior.
5. Use `422` for geofence/accuracy validation failures and `409` for attendance-state conflicts; document consistent `401`/`403` handling.
6. Initialize Git, add a comprehensive `.gitignore`, and organize specifications in `docs/` without losing existing files.

## First task
Do not begin feature implementation yet. Inspect the repository and environment, then report:
- current Git and folder status
- PHP/Composer/Flutter/Dart/PostgreSQL status
- whether PostgreSQL install/extensions need attention
- exact file moves/creates planned
- physical Android device test approach, including how the phone will reach the local API via the computer's LAN IP
- any blockers or decisions still needed

Wait for review before implementing. At the end of each phase, report files changed, commands actually run, test results, blockers, and next steps. Never claim a test passed unless it was run and its output inspected.
