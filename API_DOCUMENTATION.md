# Smart Guard API Documentation

Complete REST API for the Smart Guard system with full CRUD operations.

## Base URL

```
http://localhost:8021/api
```

## Setup Instructions

### 1. Run Migrations

```bash
docker exec smart-guard-php php artisan migrate
```

### 2. Clear Cache

```bash
docker exec smart-guard-php php artisan config:cache
docker exec smart-guard-php php artisan route:cache
```

## API Endpoints

**Note:** All successful API responses (GET, POST, PUT) are wrapped in a standard format:
```json
{
  "status": true,
  "data": { ... }  // or [ ... ] for list endpoints
}
```

Error responses use:
```json
{
  "status": false,
  "message": "Error message here"
}
```

### Users

Manage system users (Admin, Staff, Student, Faculty).

- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

**Request Body (POST):**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "STUDENT",
  "active": true,
  "student_id": "2024-001",
  "faculty_id": "F-2024-001",
  "course": "Computer Science",
  "year_level": 4,
  "attendance_rate": 95.5,
  "department": "Engineering"
}
```

**Request Body (PUT):**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "newpassword123",
  "role": "FACULTY",
  "active": false,
  "student_id": "2024-002",
  "faculty_id": "F-2024-002",
  "course": "Computer Engineering",
  "year_level": 3,
  "attendance_rate": 98.7,
  "department": "Engineering"
}
```

**Validation Rules (POST):**
- `name` - required, string, max 255 characters
- `email` - required, valid email format, must be unique
- `password` - required, string, minimum 8 characters (will be hashed)
- `role` - required, must be one of: ADMIN, STAFF, STUDENT, FACULTY
- `active` - optional, boolean (default: true)
- `student_id` - optional, string
- `faculty_id` - optional, string
- `course` - optional, string
- `year_level` - optional, integer
- `attendance_rate` - optional, numeric (stored with 2 decimal places)
- `department` - optional, string

**Validation Rules (PUT):**
- All fields are optional (use `sometimes` validation)
- `email` uniqueness check excludes current user
- `password` will be hashed if provided

**Response (GET single, POST, PUT):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "STUDENT",
    "active": true,
    "student_id": "2024-001",
    "faculty_id": null,
    "course": "Computer Science",
    "year_level": 4,
    "attendance_rate": "95.50",
    "department": "Engineering",
    "last_access_at": null,
    "email_verified_at": null,
    "created_at": "2025-11-28T10:00:00.000000Z",
    "updated_at": "2025-11-28T10:00:00.000000Z"
  }
}
```

**Response (GET list):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "STUDENT",
      "active": true,
      "student_id": "2024-001",
      "faculty_id": null,
      "course": "Computer Science",
      "year_level": 4,
      "attendance_rate": "95.50",
      "department": "Engineering",
      "last_access_at": null,
      "email_verified_at": null,
      "created_at": "2025-11-28T10:00:00.000000Z",
      "updated_at": "2025-11-28T10:00:00.000000Z"
    }
  ]
}
```

**Note:** The `password` field is never returned in responses (hidden for security).

### User Fingerprints

Manage user fingerprint registrations. Includes user relationship data.

- `GET /api/user-fingerprints` - List all fingerprints (includes user data)
- `POST /api/user-fingerprints` - Register fingerprint
- `GET /api/user-fingerprints/{id}` - Get fingerprint details (includes user data)
- `PUT /api/user-fingerprints/{id}` - Update fingerprint
- `DELETE /api/user-fingerprints/{id}` - Delete fingerprint

**Request Body (POST):**
```json
{
  "user_id": 1,
  "fingerprint_id": 12345,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "user_id": 2,
  "fingerprint_id": 54321,
  "active": false
}
```

**Validation Rules (POST):**
- `user_id` - required, must exist in users table
- `fingerprint_id` - required, integer, must be unique
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `user_id` - optional, must exist in users table
- `fingerprint_id` - optional, integer, must be unique (excludes current record)
- `active` - optional, boolean

**Response (GET single, POST, PUT):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "fingerprint_id": 12345,
    "active": true,
    "created_at": "2025-11-28T10:00:00.000000Z",
    "updated_at": "2025-11-28T10:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "STUDENT"
    }
  }
}
```

**Response (GET list):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "fingerprint_id": 12345,
      "active": true,
      "created_at": "2025-11-28T10:00:00.000000Z",
      "updated_at": "2025-11-28T10:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "STUDENT"
      }
    }
  ]
}
```

### User RFIDs

Manage user RFID card registrations. Includes user relationship data.

- `GET /api/user-rfids` - List all RFID cards (includes user data)
- `POST /api/user-rfids` - Register RFID card
- `GET /api/user-rfids/{id}` - Get RFID details (includes user data)
- `PUT /api/user-rfids/{id}` - Update RFID
- `DELETE /api/user-rfids/{id}` - Delete RFID

**Request Body (POST):**
```json
{
  "user_id": 1,
  "card_id": "ABC123XYZ",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "user_id": 2,
  "card_id": "XYZ789ABC",
  "active": false
}
```

**Validation Rules (POST):**
- `user_id` - required, must exist in users table
- `card_id` - required, string, must be unique
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `user_id` - optional, must exist in users table
- `card_id` - optional, string, must be unique (excludes current record)
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "user_id": 1,
  "card_id": "ABC123XYZ",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "STUDENT"
  }
}
```

### Devices

Manage door lock devices. Includes last accessed user and associated rooms.

- `GET /api/devices` - List all devices (includes lastAccessedByUser and rooms)
- `POST /api/devices` - Register new device
- `GET /api/devices/{id}` - Get device details (includes lastAccessedByUser and rooms)
- `PUT /api/devices/{id}` - Update device
- `DELETE /api/devices/{id}` - Delete device

**Request Body (POST):**
```json
{
  "device_id": "DEV-001",
  "door_open_duration_seconds": 5,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "device_id": "DEV-002",
  "door_open_duration_seconds": 10,
  "active": false
}
```

**Validation Rules (POST):**
- `device_id` - required, string, must be unique
- `door_open_duration_seconds` - optional, integer, minimum 1 second
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `device_id` - optional, string, must be unique (excludes current record)
- `door_open_duration_seconds` - optional, integer, minimum 1 second
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "device_id": "DEV-001",
  "door_open_duration_seconds": 5,
  "active": true,
  "last_accessed_by_user_id": 1,
  "last_accessed_at": "2025-11-28T10:00:00.000000Z",
  "last_accessed_used": "FINGERPRINT",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "last_accessed_by_user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "rooms": [
    {
      "id": 1,
      "room_number": "101",
      "device_id": 1,
      "active": true
    }
  ]
}
```

### Rooms

Manage rooms with door access. Includes device and last opened/closed user information.

- `GET /api/rooms` - List all rooms (includes device, lastOpenedByUser, lastClosedByUser)
- `POST /api/rooms` - Create new room
- `GET /api/rooms/{id}` - Get room details (includes device, lastOpenedByUser, lastClosedByUser)
- `PUT /api/rooms/{id}` - Update room
- `DELETE /api/rooms/{id}` - Delete room

**Request Body (POST):**
```json
{
  "room_number": "101",
  "device_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "room_number": "102",
  "device_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `room_number` - required, string, must be unique
- `device_id` - optional, must exist in devices table (nullable)
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `room_number` - optional, string, must be unique (excludes current record)
- `device_id` - optional, must exist in devices table (nullable)
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "room_number": "101",
  "device_id": 1,
  "active": true,
  "last_opened_by_user_id": 1,
  "last_opened_at": "2025-11-28T10:00:00.000000Z",
  "last_closed_by_user_id": 1,
  "last_closed_at": "2025-11-28T11:00:00.000000Z",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "device": {
    "id": 1,
    "device_id": "DEV-001",
    "door_open_duration_seconds": 5,
    "active": true
  },
  "last_opened_by_user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "last_closed_by_user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### Subjects

Manage academic subjects.

- `GET /api/subjects` - List all subjects
- `POST /api/subjects` - Create new subject
- `GET /api/subjects/{id}` - Get subject details
- `PUT /api/subjects/{id}` - Update subject
- `DELETE /api/subjects/{id}` - Delete subject

**Request Body (POST):**
```json
{
  "subject": "Computer Programming",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "subject": "Data Structures and Algorithms",
  "active": false
}
```

**Validation Rules (POST):**
- `subject` - required, string, must be unique
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `subject` - optional, string, must be unique (excludes current record)
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "subject": "Computer Programming",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z"
}
```

### Schedules

Manage faculty teaching schedules. Includes user, room, subject, and periods relationships.

- `GET /api/schedules` - List all schedules (includes user, room, subject, periods)
- `POST /api/schedules` - Create new schedule
- `GET /api/schedules/{id}` - Get schedule details (includes user, room, subject, periods)
- `PUT /api/schedules/{id}` - Update schedule
- `DELETE /api/schedules/{id}` - Delete schedule

**Request Body (POST):**
```json
{
  "user_id": 1,
  "day_of_week": "MONDAY",
  "room_id": 1,
  "subject_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "user_id": 2,
  "day_of_week": "TUESDAY",
  "room_id": 2,
  "subject_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `user_id` - required, must exist in users table
- `day_of_week` - required, must be one of: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY
- `room_id` - required, must exist in rooms table
- `subject_id` - required, must exist in subjects table
- `active` - optional, boolean (default: true)
- **Unique combination check:** The combination of user_id, day_of_week, room_id, and subject_id must be unique

**Validation Rules (PUT):**
- `user_id` - optional, must exist in users table
- `day_of_week` - optional, must be one of: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY
- `room_id` - optional, must exist in rooms table
- `subject_id` - optional, must exist in subjects table
- `active` - optional, boolean
- **Unique combination check:** The combination of user_id, day_of_week, room_id, and subject_id must be unique (excludes current record)

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "user_id": 1,
  "day_of_week": "MONDAY",
  "room_id": 1,
  "subject_id": 1,
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "FACULTY"
  },
  "room": {
    "id": 1,
    "room_number": "101",
    "device_id": 1,
    "active": true
  },
  "subject": {
    "id": 1,
    "subject": "Computer Programming",
    "active": true
  },
  "periods": [
    {
      "id": 1,
      "schedule_id": 1,
      "start_time": "08:00:00",
      "end_time": "09:30:00",
      "active": true
    }
  ]
}
```

**Error Response (422) - Duplicate Combination:**
```json
{
  "status": false,
  "message": "A schedule with the same user, day of week, room, and subject already exists."
}
```

### Schedule Periods

Manage time periods for schedules. Includes schedule relationship and overlap validation.

- `GET /api/schedule-periods` - List all periods (includes schedule data)
- `POST /api/schedule-periods` - Create new period
- `GET /api/schedule-periods/{id}` - Get period details (includes schedule data)
- `PUT /api/schedule-periods/{id}` - Update period
- `DELETE /api/schedule-periods/{id}` - Delete period

**Request Body (POST):**
```json
{
  "schedule_id": 1,
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "schedule_id": 2,
  "start_time": "10:00:00",
  "end_time": "11:30:00",
  "active": false
}
```

**Validation Rules (POST):**
- `schedule_id` - required, must exist in schedules table
- `start_time` - required, must be in H:i:s format (e.g., "08:00:00")
- `end_time` - required, must be in H:i:s format, must be after start_time
- `active` - optional, boolean (default: true)
- **No overlap check:** Validates that the time period does not overlap with existing periods for the same room and day of week (uses NoScheduleOverlap custom validation rule)

**Validation Rules (PUT):**
- `schedule_id` - optional, must exist in schedules table
- `start_time` - optional, must be in H:i:s format
- `end_time` - optional, must be in H:i:s format, must be after start_time
- `active` - optional, boolean
- **No overlap check:** Validates that the time period does not overlap with existing periods (excludes current record)

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "schedule_id": 1,
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "schedule": {
    "id": 1,
    "user_id": 1,
    "day_of_week": "MONDAY",
    "room_id": 1,
    "subject_id": 1,
    "active": true
  }
}
```

**Error Response (422) - Overlapping Period:**
```json
{
  "status": false,
  "message": "The schedule period overlaps with an existing schedule period: 08:00:00 - 09:30:00 on room 1 for day MONDAY."
}
```

### User Access Logs

Track door access events. Includes user, room, and device relationships. **Note:** Update endpoint is not available (immutable logs).

- `GET /api/user-access-logs` - List all access logs (includes user, room, device data)
- `POST /api/user-access-logs` - Create access log entry
- `GET /api/user-access-logs/{id}` - Get log details (includes user, room, device data)
- `DELETE /api/user-access-logs/{id}` - Delete log
- ~~`PUT /api/user-access-logs/{id}`~~ - **Not available** (logs are immutable)

**Request Body (POST):**
```json
{
  "user_id": 1,
  "room_id": 1,
  "device_id": 1,
  "access_used": "FINGERPRINT"
}
```

**Validation Rules (POST):**
- `user_id` - required, must exist in users table
- `room_id` - required, must exist in rooms table
- `device_id` - required, must exist in devices table
- `access_used` - required, must be one of: FINGERPRINT, RFID, ADMIN, MANUAL

**Response (GET, POST):**
```json
{
  "id": 1,
  "user_id": 1,
  "room_id": 1,
  "device_id": 1,
  "access_used": "FINGERPRINT",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "STUDENT"
  },
  "room": {
    "id": 1,
    "room_number": "101",
    "device_id": 1,
    "active": true
  },
  "device": {
    "id": 1,
    "device_id": "DEV-001",
    "door_open_duration_seconds": 5,
    "active": true
  }
}
```

**Access Methods:**
- `FINGERPRINT` - Access via fingerprint scanner
- `RFID` - Access via RFID card
- `ADMIN` - Admin override access
- `MANUAL` - Manual door opening

### User Audit Logs

Track user activity audit trail. Includes user relationship data. **Note:** Update endpoint is not available (immutable logs).

- `GET /api/user-audit-logs` - List all audit logs (includes user data)
- `POST /api/user-audit-logs` - Create audit log entry
- `GET /api/user-audit-logs/{id}` - Get log details (includes user data)
- `DELETE /api/user-audit-logs/{id}` - Delete log
- ~~`PUT /api/user-audit-logs/{id}`~~ - **Not available** (logs are immutable)

**Request Body (POST):**
```json
{
  "user_id": 1,
  "description": "User logged in from web portal"
}
```

**Validation Rules (POST):**
- `user_id` - required, must exist in users table
- `description` - required, string

**Response (GET, POST):**
```json
{
  "id": 1,
  "user_id": 1,
  "description": "User logged in from web portal",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "ADMIN"
  }
}
```

## Response Format

All API responses follow standard JSON format with appropriate HTTP status codes.

### HTTP Status Codes

- `200 OK` - Successful GET, PUT requests
- `201 Created` - Successful POST request (resource created)
- `204 No Content` - Successful DELETE request
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors

### Success Response Examples

**GET Single/PUT Response (200 OK):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-11-28T10:00:00.000000Z",
    "updated_at": "2025-11-28T10:00:00.000000Z"
  }
}
```

**GET List Response (200 OK):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2025-11-28T10:00:00.000000Z",
      "updated_at": "2025-11-28T10:00:00.000000Z"
    }
  ]
}
```

**POST Response (201 Created):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-11-28T10:00:00.000000Z",
    "updated_at": "2025-11-28T10:00:00.000000Z"
  }
}
```

**DELETE Response (204 No Content):**
```
(empty response body)
```

### Error Response Examples

**Validation Error (422 Unprocessable Entity):**
```json
{
  "status": false,
  "message": "The email has already been taken."
}
```

**Note:** Only the first validation error message is returned. If multiple fields fail validation, only the first error is shown.

**Not Found Error (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\User] 999"
}
```

**Note:** 404 Not Found errors maintain Laravel's default format.

## CORS Configuration

CORS is configured to allow all origins for development. Update `/src/config/cors.php` for production:

```php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
```

## Authentication

The API includes Laravel Sanctum for authentication. To use:

1. Get CSRF cookie: `GET /sanctum/csrf-cookie`
2. Login endpoint (implement as needed)
3. Include token in requests: `Authorization: Bearer {token}`

## Database Schema

See migrations in `/src/database/migrations/` for complete schema.

### Key Tables

- **users** - System users with roles
- **user_fingerprints** - Fingerprint registrations
- **user_rfids** - RFID card registrations
- **devices** - Door lock devices
- **rooms** - Rooms with access control
- **subjects** - Academic subjects
- **schedules** - Faculty teaching schedules
- **schedule_periods** - Time periods for schedules
- **user_access_logs** - Door access history
- **user_audit_logs** - User activity audit trail

## Testing the API

### Using cURL

```bash
# List all users
curl http://localhost:8021/api/users

# Create a new user
curl -X POST http://localhost:8021/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@test.com","password":"password123","role":"STUDENT"}'

# Get specific user
curl http://localhost:8021/api/users/1

# Update user
curl -X PUT http://localhost:8021/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane Doe"}'

# Delete user
curl -X DELETE http://localhost:8021/api/users/1
```

### Using Postman

1. Import collection from this documentation
2. Set base URL: `http://localhost:8021/api`
3. Test endpoints with sample requests

## Vue.js Integration

### Axios Example

```javascript
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8021/api';

// List users
const getUsers = async () => {
  const response = await axios.get(`${API_BASE_URL}/users`);
  return response.data;
};

// Create user
const createUser = async (userData) => {
  const response = await axios.post(`${API_BASE_URL}/users`, userData);
  return response.data;
};

// Update user
const updateUser = async (id, userData) => {
  const response = await axios.put(`${API_BASE_URL}/users/${id}`, userData);
  return response.data;
};

// Delete user
const deleteUser = async (id) => {
  await axios.delete(`${API_BASE_URL}/users/${id}`);
};
```

## Important Features

### Eager Loading Relationships

Most GET endpoints automatically include related data via eager loading to reduce database queries:

- **Users:** No automatic relationships (use query parameters if needed)
- **User Fingerprints:** Includes `user` data
- **User RFIDs:** Includes `user` data
- **Devices:** Includes `lastAccessedByUser` and `rooms` data
- **Rooms:** Includes `device`, `lastOpenedByUser`, and `lastClosedByUser` data
- **Subjects:** No automatic relationships
- **Schedules:** Includes `user`, `room`, `subject`, and `periods` data
- **Schedule Periods:** Includes `schedule` data
- **User Access Logs:** Includes `user`, `room`, and `device` data
- **User Audit Logs:** Includes `user` data

### Custom Validation Rules

The API implements custom validation rules for complex business logic:

1. **NoScheduleOverlap Rule** - Prevents overlapping schedule periods for the same room and day
   - Checks if a new schedule period conflicts with existing periods
   - Validates based on room_id and day_of_week from the parent schedule
   - Used in Schedule Period POST and PUT operations

2. **Unique Combination Validation** - Ensures unique schedule combinations
   - Validates that user_id, day_of_week, room_id, and subject_id combination is unique
   - Implemented in ScheduleController for POST and PUT operations
   - Returns custom error message on conflict

### Immutable Logs

Both User Access Logs and User Audit Logs do not support PUT/UPDATE operations to maintain data integrity and audit trail accuracy. Logs can only be created (POST), read (GET), or deleted (DELETE).

### Password Security

- User passwords are automatically hashed using Laravel's `Hash::make()` before storage
- Passwords are never returned in API responses (hidden via model's `$hidden` property)
- Minimum password length: 8 characters

### Boolean Fields

All boolean fields (`active`, etc.) default to `true` if not provided during creation.

## Next Steps

1. Run migrations: `docker exec smart-guard-php php artisan migrate`
2. Seed database with test data: `docker exec smart-guard-php php artisan db:seed`
3. Test endpoints using Postman or cURL
4. Implement authentication if needed
5. Update CORS settings for production
6. Consider adding pagination for large datasets
7. Implement filtering and sorting query parameters as needed

## Support

For issues or questions, refer to the main README.md file.
