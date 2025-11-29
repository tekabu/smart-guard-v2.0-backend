# API Routes

## Public Routes
POST /auth/login
POST /auth/test-logout

## Token Management
POST /logout
POST /logout-all
POST /refresh

## Authenticated Routes (api_token, throttle:60,1)
GET /user
PUT /user
PUT /user/password

## CRUD Resources
GET /users
POST /users
GET /users/{id}
PUT /users/{id}
DELETE /users/{id}

GET /user-fingerprints
POST /user-fingerprints
GET /user-fingerprints/{id}
PUT /user-fingerprints/{id}
DELETE /user-fingerprints/{id}

GET /user-rfids
POST /user-rfids
GET /user-rfids/{id}
PUT /user-rfids/{id}
DELETE /user-rfids/{id}

GET /devices
POST /devices
GET /devices/{id}
PUT /devices/{id}
DELETE /devices/{id}

GET /rooms
POST /rooms
GET /rooms/{id}
PUT /rooms/{id}
DELETE /rooms/{id}

GET /subjects
POST /subjects
GET /subjects/{id}
PUT /subjects/{id}
DELETE /subjects/{id}

GET /schedules
POST /schedules
GET /schedules/{id}
PUT /schedules/{id}
DELETE /schedules/{id}

GET /schedule-periods
POST /schedule-periods
GET /schedule-periods/{id}
PUT /schedule-periods/{id}
DELETE /schedule-periods/{id}

GET /user-access-logs
POST /user-access-logs
GET /user-access-logs/{id}
DELETE /user-access-logs/{id}

GET /user-audit-logs
POST /user-audit-logs
GET /user-audit-logs/{id}
DELETE /user-audit-logs/{id}

## Admin/Staff Routes (role:admin,staff)
GET /device-boards
POST /device-boards
GET /device-boards/{id}
PUT /device-boards/{id}
DELETE /device-boards/{id}
POST /device-boards/{id}/generate-token

## Device Routes
POST /device-boards/heartbeat (throttle:1000,1)

## Fallback
Any unmatched route -> 404