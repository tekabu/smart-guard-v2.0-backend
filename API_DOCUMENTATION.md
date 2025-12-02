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

### Health Check

Monitor API health and database connectivity.

- `GET /api/health` - Check API health status

**Response (200 OK):**
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok"
  }
}
```

**Response (503 Service Unavailable):**
```json
{
  "status": "unhealthy",
  "checks": {
    "database": "error"
  }
}
```

### Count Endpoints

Get total counts of records in the system. All require authentication.

- `GET /api/users/count` - Get total number of users
- `GET /api/user-fingerprints/count` - Get total number of fingerprint registrations
- `GET /api/user-rfids/count` - Get total number of RFID registrations
- `GET /api/devices/count` - Get total number of devices
- `GET /api/device-boards/count` - Get total number of device boards
- `GET /api/rooms/count` - Get total number of rooms
- `GET /api/subjects/count` - Get total number of subjects
- `GET /api/schedules/count` - Get total number of schedules
- `GET /api/schedules/by-subject` - Get schedules grouped by subject
- `GET /api/schedule-periods/count` - Get total number of schedule periods
- `GET /api/class-sessions/count` - Get total number of class sessions
- `GET /api/student-schedules/count` - Get total number of student schedules
- `GET /api/schedule-sessions/count` - Get total number of schedule sessions
- `GET /api/schedule-sessions/overview` - Get overview of schedule sessions
- `GET /api/schedule-attendance/count` - Get total number of attendance records
- `GET /api/schedule-attendance/overview` - Get overview of attendance records
- `GET /api/user-access-logs/count` - Get total number of access logs
- `GET /api/user-audit-logs/count` - Get total number of audit logs

**Response (GET /api/{resource}/count):**
```json
{
  "status": true,
  "data": {
    "count": 42
  }
}
```

**Response (GET /api/schedules/by-subject):**
```json
{
  "status": true,
  "data": [
    {
      "subject": "Computer Programming",
      "count": 3
    },
    {
      "subject": "Data Structures",
      "count": 2
    }
  ]
}
```

### Authentication

Manage user authentication sessions.

- `POST /api/login` - Authenticate user and create session
- `POST /api/logout` - Logout user (requires authentication)
- `GET /api/user` - Get current authenticated user (requires authentication)

**Request Body (POST /api/login):**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Validation Rules (POST /api/login):**
- `email` - required, valid email format
- `password` - required, string

**Response (POST /api/login):**
```json
{
  "status": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
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
}
```

**Error Response (401 Unauthorized):**
```json
{
  "status": false,
  "message": "The provided credentials are incorrect."
}
```

**Error Response (403 Forbidden):**
```json
{
  "status": false,
  "message": "Your account is inactive."
}
```

**Response (POST /api/logout):**
```json
{
  "status": true,
  "data": {
    "message": "Successfully logged out"
  }
}
```

**Response (GET /api/user):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
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

### Users

Manage system users with role-based access (Admin, Staff, Student, Faculty).

- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

#### User Roles and Required Fields

The system uses four distinct user roles with specific required fields for each:

**ADMIN Role (Required Fields):**
- `name` - Full name
- `email` - Unique email address
- `password` - Minimum 8 characters
- `role` - Must be "ADMIN"
- `active` - Boolean (default: true)

**STAFF Role (Required Fields):**
- `name` - Full name
- `email` - Unique email address
- `password` - Minimum 8 characters
- `role` - Must be "STAFF"
- `active` - Boolean (default: true)

**STUDENT Role (Required Fields):**
- `name` - Full name
- `email` - Unique email address
- `password` - Minimum 8 characters
- `role` - Must be "STUDENT"
- `student_id` - Unique student identifier (e.g., "STU-2024001")
- `active` - Boolean (default: true)
- Optional: course, year_level, attendance_rate, department

**FACULTY Role (Required Fields):**
- `name` - Full name
- `email` - Unique email address
- `password` - Minimum 8 characters
- `role` - Must be "FACULTY"
- `faculty_id` - Unique faculty identifier (e.g., "FAC-2024001")
- `active` - Boolean (default: true)
- Optional: department

**Shared Features:**
- Passwords are automatically hashed and never returned in API responses
- STUDENT and FACULTY users can register multiple fingerprints and RFID cards for access control
- ADMIN and STAFF users typically use system authentication (email/password) rather than biometric/RFID
- Inactive users cannot authenticate

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
  "department": "Engineering",
  "clearance": false
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
  "department": "Engineering",
  "clearance": true
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
- `clearance` - optional, boolean (default: false)

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
    "clearance": false,
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
      "clearance": false,
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

Manage fingerprint registrations for STUDENT and FACULTY users. Includes user relationship data.

- `GET /api/user-fingerprints` - List all fingerprints (includes user data)
- `POST /api/user-fingerprints` - Register fingerprint for student/faculty
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

Manage RFID card registrations for STUDENT and FACULTY users. Includes user relationship data.

- `GET /api/user-rfids` - List all RFID cards (includes user data)
- `POST /api/user-rfids` - Register RFID card for student/faculty
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

### Device Boards

Manage ESP32 boards associated with devices. Includes device relationship data.

- `GET /api/device-boards` - List all device boards (includes device data)
- `POST /api/device-boards` - Register new device board
- `GET /api/device-boards/{id}` - Get device board details (includes device data)
- `PUT /api/device-boards/{id}` - Update device board
- `DELETE /api/device-boards/{id}` - Delete device board

**Query Parameters (GET /api/device-boards):**
- `device_id` - Filter by device ID
- `board_type` - Filter by board type (FINGERPRINT, RFID, LOCK, CAMERA, DISPLAY)
- `active` - Filter by active status (true/false)

**Request Body (POST):**
```json
{
  "device_id": 1,
  "board_type": "FINGERPRINT",
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "firmware_version": "v1.2.3",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "device_id": 2,
  "board_type": "RFID",
  "mac_address": "11:22:33:44:55:66",
  "firmware_version": "v2.0.1",
  "active": false
}
```

**Validation Rules (POST):**
- `device_id` - required, must exist in devices table
- `board_type` - required, must be one of: FINGERPRINT, RFID, LOCK, CAMERA, DISPLAY
- `mac_address` - required, string, must be unique
- `firmware_version` - optional, string
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `device_id` - optional, must exist in devices table
- `board_type` - optional, must be one of: FINGERPRINT, RFID, LOCK, CAMERA, DISPLAY
- `mac_address` - optional, string, must be unique (excludes current record)
- `firmware_version` - optional, string
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "device_id": 1,
  "board_type": "FINGERPRINT",
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "firmware_version": "v1.2.3",
  "active": true,
  "last_seen_at": "2025-11-28T10:00:00.000000Z",
  "last_ip": "192.168.1.100",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "device": {
    "id": 1,
    "device_id": "DEV-001",
    "door_open_duration_seconds": 5,
    "active": true,
    "last_accessed_by_user_id": 1,
    "last_accessed_at": "2025-11-28T10:00:00.000000Z",
    "last_accessed_used": "FINGERPRINT"
  }
}
```

**Board Types:**
- `FINGERPRINT` - Fingerprint scanner board
- `RFID` - RFID card reader board
- `LOCK` - Door lock control board
- `CAMERA` - Camera board for surveillance
- `DISPLAY` - Display board for information display

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

### Sections

Manage academic sections or classes.

- `GET /api/sections` - List all sections
- `POST /api/sections` - Create new section
- `GET /api/sections/{id}` - Get section details
- `PUT /api/sections/{id}` - Update section
- `DELETE /api/sections/{id}` - Delete section

**Request Body (POST):**
```json
{
  "section": "SECTION A",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "section": "SECTION B",
  "active": false
}
```

**Validation Rules (POST):**
- `section` - required, string, must be unique
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `section` - optional, string, must be unique (excludes current record)
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "section": "SECTION A",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z"
}
```

### Section Subjects

Manage subjects assigned to sections with faculty assignments. Includes section, subject, and faculty relationships.

- `GET /api/section-subjects` - List all section subjects (includes relationships)
- `POST /api/section-subjects` - Create new section subject assignment
- `GET /api/section-subjects/{id}` - Get section subject details (includes relationships)
- `PUT /api/section-subjects/{id}` - Update section subject assignment
- `DELETE /api/section-subjects/{id}` - Delete section subject assignment
- `GET /api/section-subjects/options` - Get formatted options for dropdown/select lists

**Query Parameters (GET /api/section-subjects):**
- `section_id` - Filter by section ID
- `subject_id` - Filter by subject ID

**Request Body (POST):**
```json
{
  "section_id": 1,
  "subject_id": 1,
  "faculty_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "section_id": 2,
  "subject_id": 2,
  "faculty_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `section_id` - required, must exist in sections table
- `subject_id` - required, must exist in subjects table
- `faculty_id` - required, must exist in users table with FACULTY role
- `active` - optional, boolean (default: true)
- **Unique combination check:** The combination of section_id and subject_id must be unique

**Validation Rules (PUT):**
- `section_id` - optional, must exist in sections table
- `subject_id` - optional, must exist in subjects table
- `faculty_id` - optional, must exist in users table with FACULTY role
- `active` - optional, boolean
- **Unique combination check:** The combination of section_id and subject_id must be unique (excludes current record)

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "section_id": 1,
  "subject_id": 1,
  "faculty_id": 1,
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "section": {
    "id": 1,
    "section": "SECTION A",
    "active": true
  },
  "subject": {
    "id": 1,
    "subject": "Computer Programming",
    "active": true
  },
  "faculty": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "FACULTY"
  }
}
```

**Response (GET /api/section-subjects/options):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "label": "SECTION A - Computer Programming - John Doe"
    }
  ]
}
```

### Section Subject Schedules

Manage schedule templates for section-subject combinations. Includes day/time patterns and faculty assignments.

- `GET /api/section-subject-schedules` - List all section subject schedules (includes relationships)
- `POST /api/section-subject-schedules` - Create new section subject schedule
- `GET /api/section-subject-schedules/{id}` - Get schedule details (includes relationships)
- `PUT /api/section-subject-schedules/{id}` - Update section subject schedule
- `DELETE /api/section-subject-schedules/{id}` - Delete section subject schedule

**Request Body (POST):**
```json
{
  "section_subject_id": 1,
  "day_of_week": "MONDAY",
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "room_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "day_of_week": "TUESDAY",
  "start_time": "10:00:00",
  "end_time": "11:30:00",
  "room_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `section_subject_id` - required, must exist in section_subjects table
- `day_of_week` - required, must be one of: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY
- `start_time` - required, must be in H:i:s format
- `end_time` - required, must be in H:i:s format, must be after start_time
- `room_id` - required, must exist in rooms table
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `section_subject_id` - optional, must exist in section_subjects table
- `day_of_week` - optional, must be one of: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY
- `start_time` - optional, must be in H:i:s format
- `end_time` - optional, must be in H:i:s format, must be after start_time
- `room_id` - optional, must exist in rooms table
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "section_subject_id": 1,
  "day_of_week": "MONDAY",
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "room_id": 1,
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "sectionSubject": {
    "id": 1,
    "section_id": 1,
    "subject_id": 1,
    "faculty_id": 1,
    "section": {
      "id": 1,
      "section": "SECTION A",
      "active": true
    },
    "subject": {
      "id": 1,
      "subject": "Computer Programming",
      "active": true
    },
    "faculty": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "FACULTY"
    }
  },
  "room": {
    "id": 1,
    "room_number": "101",
    "device_id": 1,
    "active": true
  }
}
```

### Section Subject Students

Manage student enrollments in section subjects.

- `GET /api/section-subject-students` - List all section subject students (includes relationships)
- `POST /api/section-subject-students` - Enroll student in section subject
- `GET /api/section-subject-students/{id}` - Get enrollment details (includes relationships)
- `PUT /api/section-subject-students/{id}` - Update enrollment
- `DELETE /api/section-subject-students/{id}` - Remove student from section subject

**Request Body (POST):**
```json
{
  "section_subject_id": 1,
  "student_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "section_subject_id": 2,
  "student_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `section_subject_id` - required, must exist in section_subjects table
- `student_id` - required, must exist in users table with STUDENT role
- `active` - optional, boolean (default: true)
- **Unique combination check:** The combination of section_subject_id and student_id must be unique

**Validation Rules (PUT):**
- `section_subject_id` - optional, must exist in section_subjects table
- `student_id` - optional, must exist in users table with STUDENT role
- `active` - optional, boolean
- **Unique combination check:** The combination of section_subject_id and student_id must be unique (excludes current record)

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "section_subject_id": 1,
  "student_id": 1,
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "sectionSubject": {
    "id": 1,
    "section_id": 1,
    "subject_id": 1,
    "faculty_id": 1
  },
  "student": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "STUDENT",
    "student_id": "2024-001"
  }
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

### Schedule Sessions

Manage actual instances of scheduled classes with real dates and times. Includes start/end control and overview functionality.

- `GET /api/schedule-sessions` - List all schedule sessions
- `POST /api/schedule-sessions` - Create new schedule session
- `POST /api/schedule-sessions/create` - Auto-create session from schedule template (with optional `start=1` query parameter)
- `GET /api/schedule-sessions/{id}` - Get session details
- `PUT /api/schedule-sessions/{id}` - Update session
- `DELETE /api/schedule-sessions/{id}` - Delete session
- `POST /api/schedule-sessions/{id}/start` - Start a session (sets start date/time)
- `POST /api/schedule-sessions/{id}/close` - Close a session (sets end date/time)
- `GET /api/schedule-sessions/count` - Get total count of sessions
- `GET /api/schedule-sessions/overview` - Get overview with filters

**Query Parameters (GET /api/schedule-sessions/overview):**
- `section_id` - Filter by section ID
- `subject_id` - Filter by subject ID
- `faculty_id` - Filter by faculty ID
- `day_of_week` - Filter by day of week
- `start_date` - Filter by start date
- `has_class` - Filter by sessions that have class (1) or don't have class (0)

**Request Body (POST /api/schedule-sessions):**
```json
{
  "section_subject_schedule_id": 1,
  "faculty_id": 1,
  "day_of_week": "MONDAY",
  "room_id": 1,
  "start_date": "2025-01-01",
  "start_time": "08:00:00",
  "end_date": "2025-01-01",
  "end_time": "09:30:00",
  "active": true
}
```

**Request Body (POST /api/schedule-sessions/create):**
```json
{
  "section_subject_schedule_id": 1
}
```

**Query Parameters for POST /api/schedule-sessions/create:**
- `start=1` - Auto-set start_date and start_time to current date/time (only if current day matches schedule and within time window)

**Request Body (PUT):**
```json
{
  "faculty_id": 2,
  "day_of_week": "TUESDAY",
  "end_time": "10:30:00"
}
```

**Validation Rules (POST):**
- `section_subject_schedule_id` - required, must exist in section_subject_schedules table
- `faculty_id` - required, must exist in users table
- `day_of_week` - required, must be one of: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY
- `room_id` - required, must exist in rooms table
- `start_date` - required, date, must be today's date
- `start_time` - required, must be in H:i:s format
- `end_date` - optional, date, must be same or after start_date
- `end_time` - optional, must be in H:i:s format, must be after start_time if both present
- `active` - optional, boolean (default: true)
- **Unique combination check:** The combination of section_subject_schedule_id and start_date must be unique

**Validation Rules (POST /api/schedule-sessions/create):**
- `section_subject_schedule_id` - required, must exist in section_subject_schedules table
- **Additional validation:** Current day must match the schedule's day_of_week when `start=1` is used
- **Time window validation:** Current time must be within the schedule's time window when `start=1` is used

**Validation Rules (PUT):**
- All fields are optional (uses `sometimes` validation)
- `end_time` must be after `start_time` if both are provided

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "section_subject_schedule_id": 1,
  "faculty_id": 1,
  "day_of_week": "MONDAY",
  "room_id": 1,
  "start_date": "2025-01-01",
  "start_time": "08:00:00",
  "end_date": "2025-01-01",
  "end_time": "09:30:00",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "sectionSubjectSchedule": {
    "id": 1,
    "section_subject_id": 1,
    "day_of_week": "MONDAY",
    "start_time": "08:00:00",
    "end_time": "09:30:00",
    "room_id": 1
  },
  "faculty": {
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
  }
}
```

**Response (POST /api/schedule-sessions/{id}/start):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "start_date": "2025-01-01",
    "start_time": "08:30:00",
    "end_date": null,
    "end_time": null
  }
}
```

**Response (POST /api/schedule-sessions/{id}/close):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "start_date": "2025-01-01",
    "start_time": "08:00:00",
    "end_date": "2025-01-01",
    "end_time": "09:30:00"
  }
}
```

**Response (GET /api/schedule-sessions/overview):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "section": "SECTION A",
      "subject": "Computer Programming",
      "faculty": "John Doe",
      "day_of_week": "MONDAY",
      "start_date": "2025-01-01",
      "start_time": "08:00:00",
      "end_time": "09:30:00",
      "room_number": "101"
    }
  ]
}
```

### Schedule Attendance

Manage attendance records for schedule sessions. Includes student information and validation.

- `GET /api/schedule-attendance` - List all attendance records
- `POST /api/schedule-attendance` - Create new attendance record
- `GET /api/schedule-attendance/{id}` - Get attendance record details
- `PUT /api/schedule-attendance/{id}` - Update attendance record
- `DELETE /api/schedule-attendance/{id}` - Delete attendance record
- `GET /api/schedule-attendance/count` - Get total count of attendance records
- `GET /api/schedule-attendance/overview` - Get overview with filters

**Query Parameters (GET /api/schedule-attendance/overview):**
- `section_id` - Filter by section ID
- `subject_id` - Filter by subject ID
- `faculty_id` - Filter by faculty ID
- `student_id` - Filter by student ID
- `date_in` - Filter by check-in date

**Request Body (POST):**
```json
{
  "schedule_session_id": 1,
  "student_id": 1,
  "date_in": "2025-01-01",
  "time_in": "08:05:00",
  "date_out": "2025-01-01",
  "time_out": "09:30:00",
  "attendance_status": "PRESENT"
}
```

**Request Body (PUT):**
```json
{
  "attendance_status": "LATE",
  "date_out": "2025-01-01",
  "time_out": "09:45:00"
}
```

**Validation Rules (POST):**
- `schedule_session_id` - required, must exist in schedule_sessions table
- `student_id` - required, must exist in users table with STUDENT role
- `date_in` - required, date format
- `time_in` - optional, time format (H:i:s)
- `date_out` - optional, date format
- `time_out` - optional, time format (H:i:s), must be after time_in if both present
- `attendance_status` - required, must be one of: PRESENT, ABSENT, LATE, EXCUSED
- **Active session validation:** The schedule session must be active
- **Unique combination check:** The combination of student_id, schedule_session_id, and date_in must be unique

**Validation Rules (PUT):**
- `attendance_status` - optional, must be one of: PRESENT, ABSENT, LATE, EXCUSED
- `time_out` - optional, must be after time_in if time_in is present
- **Active session validation:** The schedule session must be active

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "schedule_session_id": 1,
  "student_id": 1,
  "date_in": "2025-01-01",
  "time_in": "08:05:00",
  "date_out": "2025-01-01",
  "time_out": "09:30:00",
  "attendance_status": "PRESENT",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "scheduleSession": {
    "id": 1,
    "section_subject_schedule_id": 1,
    "faculty_id": 1,
    "day_of_week": "MONDAY",
    "room_id": 1,
    "start_date": "2025-01-01"
  },
  "student": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "STUDENT",
    "student_id": "STU-001"
  }
}
```

**Response (GET /api/schedule-attendance/overview):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "section": "SECTION A",
      "subject": "Computer Programming",
      "faculty": "John Doe",
      "student": "Jane Smith",
      "student_id": "STU-001",
      "date_in": "2025-01-01",
      "time_in": "08:05:00",
      "attendance_status": "PRESENT"
    }
  ]
}
```

### Student Schedules

Manage student enrollment in scheduled classes. Links students to specific schedule periods.

- `GET /api/student-schedules` - List all student schedules (includes relationships)
- `POST /api/student-schedules` - Create new student schedule
- `GET /api/student-schedules/{id}` - Get student schedule details (includes relationships)
- `PUT /api/student-schedules/{id}` - Update student schedule
- `DELETE /api/student-schedules/{id}` - Delete student schedule
- `GET /api/student-schedules/count` - Get total count of student schedules

**Request Body (POST):**
```json
{
  "student_id": 1,
  "subject_id": 1,
  "schedule_id": 1,
  "schedule_period_id": 1,
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "subject_id": 2,
  "schedule_id": 2,
  "schedule_period_id": 2,
  "active": false
}
```

**Validation Rules (POST):**
- `student_id` - required, must exist in users table with STUDENT role
- `subject_id` - required, must exist in subjects table
- `schedule_id` - required, must exist in schedules table
- `schedule_period_id` - required, must exist in schedule_periods table
- `active` - optional, boolean (default: true)
- **Unique combination check:** The combination of student_id and schedule_period_id must be unique

**Validation Rules (PUT):**
- `student_id` - optional, must exist in users table with STUDENT role
- `subject_id` - optional, must exist in subjects table
- `schedule_id` - optional, must exist in schedules table
- `schedule_period_id` - optional, must exist in schedule_periods table
- `active` - optional, boolean
- **Unique combination check:** The combination of student_id and schedule_period_id must be unique (excludes current record)

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "student_id": 1,
  "subject_id": 1,
  "schedule_id": 1,
  "schedule_period_id": 1,
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "student": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "STUDENT",
    "student_id": "STU-001"
  },
  "subject": {
    "id": 1,
    "subject": "Computer Programming",
    "active": true
  },
  "schedule": {
    "id": 1,
    "user_id": 2,
    "day_of_week": "MONDAY",
    "room_id": 1,
    "subject_id": 1,
    "active": true
  },
  "schedulePeriod": {
    "id": 1,
    "schedule_id": 1,
    "start_time": "08:00:00",
    "end_time": "09:30:00",
    "active": true
  }
}
```

### Class Sessions

Manage actual class sessions that occur based on schedules. Can be created by devices or manually.

- `GET /api/class-sessions` - List all class sessions
- `POST /api/class-sessions` - Create new class session
- `GET /api/class-sessions/{id}` - Get class session details
- `PUT /api/class-sessions/{id}` - Update class session
- `DELETE /api/class-sessions/{id}` - Delete class session
- `POST /api/class-sessions/{class_session}/close` - Close a class session
- `GET /api/class-sessions/count` - Get total count of class sessions

**Request Body (POST):**
```json
{
  "schedule_period_id": 1,
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "active": true
}
```

**Request Body (PUT):**
```json
{
  "end_time": "10:00:00",
  "active": false
}
```

**Request for POST /api/class-sessions/{class_session}/close:**
No request body required. Sets end_time to current time.

**Validation Rules (POST):**
- `schedule_period_id` - required, must exist in schedule_periods table
- `start_time` - optional, time format (H:i:s)
- `end_time` - optional, time format (H:i:s)
- `active` - optional, boolean (default: true)

**Validation Rules (PUT):**
- `schedule_period_id` - optional, must exist in schedule_periods table
- `start_time` - optional, time format (H:i:s)
- `end_time` - optional, time format (H:i:s)
- `active` - optional, boolean

**Response (GET, POST, PUT):**
```json
{
  "id": 1,
  "schedule_period_id": 1,
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "active": true,
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z",
  "schedulePeriod": {
    "id": 1,
    "schedule_id": 1,
    "start_time": "08:00:00",
    "end_time": "09:30:00",
    "active": true
  }
}
```

**Response (POST /api/class-sessions/{class_session}/close):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "schedule_period_id": 1,
    "start_time": "08:00:00",
    "end_time": "09:30:00",
    "active": true
  }
}
```

### Device Communication

Manage communication between ESP32 device boards and API. Requires device board authentication.

**Authentication:** Uses Bearer token specific to each device board (not user tokens).

- `POST /api/device-communications/heartbeat` - Send heartbeat from device board
- `GET /api/device-communications/me` - Get device board profile
- `POST /api/device-communications/validate-card` - Validate RFID card
- `POST /api/device-communications/validate-fingerprint` - Validate fingerprint
- `POST /api/device-communications/scan-card` - Scan RFID card
- `POST /api/device-communications/scan-fingerprint` - Scan fingerprint
- `POST /api/device-communications/class-sessions/from-card` - Create class session using RFID card
- `POST /api/device-communications/class-sessions/from-fingerprint` - Create class session using fingerprint

**Request Body (POST /api/device-communications/heartbeat):**
```json
{
  "firmware_version": "v2.1.0"
}
```

**Request Body (POST /api/device-communications/validate-card):**
```json
{
  "card_id": "ABC123XYZ"
}
```

**Request Body (POST /api/device-communications/validate-fingerprint):**
```json
{
  "fingerprint_id": "12345"
}
```

**Request Body (POST /api/device-communications/scan-card):**
```json
{
  "card_id": "CARD-123"
}
```

**Request Body (POST /api/device-communications/scan-fingerprint):**
```json
{
  "fingerprint_id": "FP-123"
}
```

**Request Body (POST /api/device-communications/class-sessions/from-card):**
```json
{
  "card_id": "ABC123XYZ"
}
```

**Request Body (POST /api/device-communications/class-sessions/from-fingerprint):**
```json
{
  "fingerprint_id": "12345"
}
```

**Validation Rules (POST /api/device-communications/heartbeat):**
- `firmware_version` - optional, string

**Validation Rules (POST /api/device-communications/validate-card):**
- `card_id` - required, string

**Validation Rules (POST /api/device-communications/validate-fingerprint):**
- `fingerprint_id` - required, string or integer

**Validation Rules (POST /api/device-communications/scan-card):**
- `card_id` - required, string

**Validation Rules (POST /api/device-communications/scan-fingerprint):**
- `fingerprint_id` - required, string or integer

**Validation Rules for class session creation:**
- `card_id` / `fingerprint_id` - required
- User must be FACULTY role
- Must have active schedule period for current day/time

**Response (POST /api/device-communications/heartbeat):**
```json
{
  "status": true,
  "data": {
    "board": {
      "id": 1,
      "device_id": 1,
      "board_type": "FINGERPRINT",
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "firmware_version": "v2.1.0",
      "active": true,
      "last_seen_at": "2025-01-01T08:30:00.000000Z",
      "last_ip": "192.168.1.100"
    }
  }
}
```

**Response (GET /api/device-communications/me):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "device_id": 1,
    "board_type": "FINGERPRINT",
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "device": {
      "id": 1,
      "device_id": "DEV-001",
      "door_open_duration_seconds": 5,
      "active": true
    }
  }
}
```

**Response (POST /api/device-communications/validate-card):**
```json
{
  "status": true,
  "data": {
    "valid": true,
    "user_id": 1,
    "attendance_recorded": true
  }
}
```

**Response (POST /api/device-communications/validate-fingerprint):**
```json
{
  "status": true,
  "data": {
    "valid": true,
    "user_id": 1,
    "attendance_recorded": false
  }
}
```

**Response (POST /api/device-communications/scan-card):**
```json
{
  "status": true,
  "data": {
    "scanned": true,
    "card_id": "CARD-123"
  }
}
```

**Response (POST /api/device-communications/scan-fingerprint):**
```json
{
  "status": true,
  "data": {
    "scanned": true,
    "fingerprint_id": "FP-123"
  }
}
```

**Response (POST /api/device-communications/class-sessions/from-card):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "schedule_period_id": 1,
    "start_time": "08:00:00",
    "end_time": null,
    "active": true
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

**Error Response (403 Forbidden):**
```json
{
  "status": false,
  "message": "This action is unauthorized."
}
```

**Error Response (422) - Invalid Card/Fingerprint:**
```json
{
  "status": false,
  "message": "The provided card is not registered."
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

The API includes authentication for protecting endpoints. To use:

1. Get authentication token by logging in: `POST /api/login`
2. Include token in requests: `Authorization: Bearer {token}`
3. Logout when done: `POST /api/logout`

Note: Some endpoints may require authentication. Check individual endpoint documentation for requirements.

### Token Management API (Admin Only)

Admin users can manage API tokens for programmatic access.

- `POST /api/tokens` - Create new API token (admin only)
- `GET /api/tokens` - List all API tokens (admin only)
- `DELETE /api/tokens/{tokenId}` - Revoke API token (admin only)

**Request Body (POST /api/tokens):**
```json
{
  "token_name": "My API Token"
}
```

**Validation Rules (POST /api/tokens):**
- `token_name` - required, string, max 255 characters

**Response (POST /api/tokens):**
```json
{
  "status": true,
  "data": {
    "token": "1|abcdef123456789...",
    "abilities": ["*"]
  }
}
```

**Response (GET /api/tokens):**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "My API Token",
      "abilities": ["*"],
      "created_at": "2025-11-28T10:00:00.000000Z",
      "last_used_at": null
    }
  ]
}
```

**Response (DELETE /api/tokens/{tokenId}):**
```json
{
  "status": true,
  "data": {
    "message": "Token revoked successfully"
  }
}
```

**Error Response (403 Forbidden):**
```json
{
  "status": false,
  "message": "Only admin users can create API tokens."
}
```

## Database Schema

See migrations in `/src/database/migrations/` for complete schema.

### Key Tables

- **users** - System users with roles (includes clearance field for access permissions)
- **user_fingerprints** - Fingerprint registrations
- **user_rfids** - RFID card registrations
- **devices** - Door lock devices
- **device_boards** - ESP32 boards associated with devices (includes API token authentication)
- **rooms** - Rooms with access control
- **subjects** - Academic subjects
- **schedules** - Faculty teaching schedules
- **schedule_periods** - Time periods for schedules
- **sections** - Academic sections or classes
- **section_subjects** - Subject assignments to sections with faculty
- **section_subject_schedules** - Schedule templates for section-subject combinations
- **section_subject_students** - Student enrollments in section subjects
- **student_schedules** - Student enrollment in scheduled classes
- **schedule_sessions** - Actual instances of scheduled classes with real dates
- **schedule_attendance** - Attendance records for schedule sessions
- **student_attendance** - Legacy attendance records for class sessions
- **user_access_logs** - Door access history
- **user_audit_logs** - User activity audit trail

### Important Field Additions

**Users Table:**
- `clearance` - Boolean field for special access permissions (default: false)

**Device Boards Table:**
- `api_token` - Token for device board authentication
- `last_seen_at` - Timestamp of last heartbeat
- `last_ip` - IP address of last connection

**Schedule Sessions Table:**
- `start_date`, `start_time` - When class actually starts
- `end_date`, `end_time` - When class actually ends
- Supports auto-creation from schedule templates
- Time window validation for starting sessions

**Schedule Attendance Table:**
- `date_in`, `time_in` - Check-in timestamp
- `date_out`, `time_out` - Check-out timestamp
- `attendance_status` - PRESENT, ABSENT, LATE, EXCUSED

## Testing the API

### Using cURL

```bash
# Health check
curl http://localhost:8021/api/health

# Login
curl -X POST http://localhost:8021/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"password123"}'

# Get current user (requires authentication)
curl -H "Authorization: Bearer {token}" http://localhost:8021/api/user

# Logout (requires authentication)
curl -X POST -H "Authorization: Bearer {token}" http://localhost:8021/api/logout

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

# List device boards with filters
curl "http://localhost:8021/api/device-boards?device_id=1&board_type=FINGERPRINT"

# Create device board
curl -X POST http://localhost:8021/api/device-boards \
  -H "Content-Type: application/json" \
  -d '{"device_id":1,"board_type":"FINGERPRINT","mac_address":"AA:BB:CC:DD:EE:FF"}'
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
- **Device Boards:** Includes `device` data
- **Rooms:** Includes `device`, `lastOpenedByUser`, and `lastClosedByUser` data
- **Subjects:** No automatic relationships
- **Schedules:** Includes `user`, `room`, `subject`, and `periods` data
- **Schedule Periods:** Includes `schedule` data
- **Sections:** No automatic relationships
- **Section Subjects:** Includes `section`, `subject`, and `faculty` data
- **Section Subject Schedules:** Includes `sectionSubject` (with nested relationships) and `room` data
- **Section Subject Students:** Includes `sectionSubject` and `student` data
- **Student Schedules:** Includes `student`, `subject`, `schedule`, and `schedulePeriod` data
- **Schedule Sessions:** Includes `sectionSubjectSchedule`, `faculty`, and `room` data
- **Schedule Attendance:** Includes `scheduleSession` and `student` data
- **Class Sessions:** Includes `schedulePeriod` data
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

### Device Board Authentication

The API includes a special authentication mechanism for ESP32 device boards using API tokens:

1. **Token Generation:** Device boards are automatically assigned API tokens in the database
2. **Authentication Method:** Use `Authorization: Bearer {device_board_api_token}` header
3. **Middleware Protection:** Device communication endpoints use `EnsureDeviceBoard` middleware
4. **Heartbeat Updates:** Devices send periodic heartbeats to update `last_seen_at` and `last_ip`

**Example Device Request:**
```bash
curl -H "Authorization: Bearer 12345abcdef..." \
  -X POST http://localhost:8021/api/device-communications/heartbeat \
  -d '{"firmware_version": "v2.1.0"}'
```

**Device-Specific Endpoints:**
- `/api/device-communications/*` - All endpoints require device board authentication
- Automatically prevents regular users from accessing device-specific functions
- Enables automatic attendance recording for student scans during active classes

## Support

For issues or questions, refer to the main README.md file.
