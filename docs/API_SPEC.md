# GeoAttend — API Specification (MVP)

Base path: `/api/v1`. Semua endpoint mobile harus menggunakan HTTPS di deployment. Response JSON konsisten dan tidak membocorkan detail internal exception.

## Response conventions
Success:
```json
{
  "data": {},
  "message": "Success"
}
```

Validation/error response mengikuti format Laravel yang terdokumentasi, dengan kode HTTP yang tepat. Jangan mengirim stack trace kepada client.

## Public/auth endpoints
- `POST /api/v1/login`
  - Request: `email`, `password`, `device_name`
  - Response: token, user summary, role.
- `POST /api/v1/logout` — authenticated; revoke current token.
- `GET /api/v1/me` — authenticated; current user and assigned location summary.

## Employee endpoints
- `GET /api/v1/attendance/today`
  - Status presensi hari ini dan status check-in/check-out.
- `POST /api/v1/attendance/check-in`
  - Request: `latitude`, `longitude`, `accuracy_meters`.
  - Server derives timestamp, assigned location, radius, user status, and schedule.
- `POST /api/v1/attendance/check-out`
  - Request: `latitude`, `longitude`, `accuracy_meters`.
  - Server validates that a valid check-in exists and checkout has not already occurred.
- `GET /api/v1/attendance/history`
  - Query: `from`, `to`, optional `page`.
  - Employee only receives their own records.

## Admin endpoints
All admin endpoints require authenticated user with role `admin`.
- `GET /api/v1/admin/users`
- `POST /api/v1/admin/users`
- `GET /api/v1/admin/users/{id}`
- `PATCH /api/v1/admin/users/{id}`
- `DELETE /api/v1/admin/users/{id}` — prefer deactivate/archive instead of destructive deletion when history exists.
- `GET /api/v1/admin/locations`
- `POST /api/v1/admin/locations`
- `GET /api/v1/admin/locations/{id}`
- `PATCH /api/v1/admin/locations/{id}`
- `DELETE /api/v1/admin/locations/{id}` — prevent destructive deletion if referenced; allow deactivation.
- `GET /api/v1/admin/schedules`
- `POST /api/v1/admin/schedules`
- `PATCH /api/v1/admin/schedules/{id}`
- `GET /api/v1/admin/reports/attendance`
  - Query: `from`, `to`, optional `user_id`, `location_id`, `status`, `page`.
- `GET /api/v1/admin/reports/attendance/export`
  - CSV export; validate and cap date ranges.

## Geofence validation
For check-in/check-out, server:
1. Authenticates token and checks active employee.
2. Gets assigned active location and applicable schedule from database.
3. Validates numeric latitude (-90..90), longitude (-180..180), and positive accuracy.
4. Rejects if accuracy exceeds configured `max_accuracy_meters`.
5. Computes Haversine distance server-side from submitted coordinates to stored office coordinates.
6. Rejects if distance exceeds stored radius.
7. Uses server time; never trusts a client timestamp.
8. Creates/updates attendance in a database transaction and handles unique constraint violations.
9. Writes an audit event for accepted/rejected attempts without logging secrets.

## Status and HTTP codes
- `200`: successful read/update
- `201`: created
- `401`: unauthenticated
- `403`: forbidden or business rule denied (choose and document a consistent convention)
- `404`: resource not found
- `409`: duplicate/incompatible attendance state
- `422`: invalid input
- `429`: rate limited

## Important biometric note
The API does not accept or trust a field such as `fingerprint_success: true` as security proof. The local biometric prompt is an app-side confirmation only in MVP. Do not use a client-supplied boolean to authorize a request. If server-verifiable biometric-gated authentication is required later, design a challenge-response flow using a device-bound cryptographic key protected by platform biometrics.
