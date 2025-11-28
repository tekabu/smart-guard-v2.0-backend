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

### Users

Manage system users (Admin, Staff, Student, Faculty).

- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

**Request Body (POST/PUT):**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "STUDENT",
  "active": true,
  "student_id": "2024-001",
  "course": "Computer Science",
  "year_level": "4",
  "attendance_rate": 95.5,
  "department": "Engineering"
}
```

### User Fingerprints

Manage user fingerprint registrations.

- `GET /api/user-fingerprints` - List all fingerprints
- `POST /api/user-fingerprints` - Register fingerprint
- `GET /api/user-fingerprints/{id}` - Get fingerprint details
- `PUT /api/user-fingerprints/{id}` - Update fingerprint
- `DELETE /api/user-fingerprints/{id}` - Delete fingerprint

**Request Body:**
```json
{
  "user_id": 1,
  "fingerprint_id": 12345,
  "active": true
}
```

### User RFIDs

Manage user RFID card registrations.

- `GET /api/user-rfids` - List all RFID cards
- `POST /api/user-rfids` - Register RFID card
- `GET /api/user-rfids/{id}` - Get RFID details
- `PUT /api/user-rfids/{id}` - Update RFID
- `DELETE /api/user-rfids/{id}` - Delete RFID

**Request Body:**
```json
{
  "user_id": 1,
  "card_id": "ABC123XYZ",
  "active": true
}
```

### Devices

Manage door lock devices.

- `GET /api/devices` - List all devices
- `POST /api/devices` - Register new device
- `GET /api/devices/{id}` - Get device details
- `PUT /api/devices/{id}` - Update device
- `DELETE /api/devices/{id}` - Delete device

**Request Body:**
```json
{
  "device_id": "DEV-001",
  "door_open_duration_seconds": 5,
  "active": true
}
```

### Rooms

Manage rooms with door access.

- `GET /api/rooms` - List all rooms
- `POST /api/rooms` - Create new room
- `GET /api/rooms/{id}` - Get room details
- `PUT /api/rooms/{id}` - Update room
- `DELETE /api/rooms/{id}` - Delete room

**Request Body:**
```json
{
  "room_number": "101",
  "device_id": 1,
  "active": true
}
```

### Subjects

Manage academic subjects.

- `GET /api/subjects` - List all subjects
- `POST /api/subjects` - Create new subject
- `GET /api/subjects/{id}` - Get subject details
- `PUT /api/subjects/{id}` - Update subject
- `DELETE /api/subjects/{id}` - Delete subject

**Request Body:**
```json
{
  "subject": "Computer Programming",
  "active": true
}
```

### Schedules

Manage faculty teaching schedules.

- `GET /api/schedules` - List all schedules
- `POST /api/schedules` - Create new schedule
- `GET /api/schedules/{id}` - Get schedule details
- `PUT /api/schedules/{id}` - Update schedule
- `DELETE /api/schedules/{id}` - Delete schedule

**Request Body:**
```json
{
  "user_id": 1,
  "day_of_week": "MONDAY",
  "room_id": 1,
  "subject_id": 1,
  "active": true
}
```

### Schedule Periods

Manage time periods for schedules.

- `GET /api/schedule-periods` - List all periods
- `POST /api/schedule-periods` - Create new period
- `GET /api/schedule-periods/{id}` - Get period details
- `PUT /api/schedule-periods/{id}` - Update period
- `DELETE /api/schedule-periods/{id}` - Delete period

**Request Body:**
```json
{
  "schedule_id": 1,
  "start_time": "08:00:00",
  "end_time": "09:30:00",
  "active": true
}
```

### User Access Logs

Track door access events (read-only for history).

- `GET /api/user-access-logs` - List all access logs
- `POST /api/user-access-logs` - Create access log entry
- `GET /api/user-access-logs/{id}` - Get log details
- `DELETE /api/user-access-logs/{id}` - Delete log

**Request Body:**
```json
{
  "user_id": 1,
  "room_id": 1,
  "device_id": 1,
  "access_used": "FINGERPRINT"
}
```

**Access Methods:** FINGERPRINT, RFID, ADMIN, MANUAL

### User Audit Logs

Track user activity audit trail.

- `GET /api/user-audit-logs` - List all audit logs
- `POST /api/user-audit-logs` - Create audit log entry
- `GET /api/user-audit-logs/{id}` - Get log details
- `DELETE /api/user-audit-logs/{id}` - Delete log

**Request Body:**
```json
{
  "user_id": 1,
  "description": "User logged in from web portal"
}
```

## Response Format

### Success Response

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2025-11-28T10:00:00.000000Z",
  "updated_at": "2025-11-28T10:00:00.000000Z"
}
```

### Error Response

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

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

## Next Steps

1. Run migrations: `docker exec smart-guard-php php artisan migrate`
2. Test endpoints using Postman or cURL
3. Implement authentication if needed
4. Update CORS settings for production
5. Add validation rules as needed
6. Implement additional business logic in controllers

## Support

For issues or questions, refer to the main README.md file.
