# Smart Guard API - Database Schema Documentation

## Overview

This document describes the database schema for the Smart Guard API, a comprehensive access control and attendance tracking system for educational institutions.

## Table of Contents

- [Core Tables](#core-tables)
  - [users](#users)
  - [user_fingerprints](#user_fingerprints)
  - [user_rfids](#user_rfids)
- [Access Control](#access-control)
  - [devices](#devices)
  - [rooms](#rooms)
- [Scheduling](#scheduling)
  - [subjects](#subjects)
  - [schedules](#schedules)
  - [schedule_periods](#schedule_periods)
- [Logging & Audit](#logging--audit)
  - [user_access_logs](#user_access_logs)
  - [user_audit_logs](#user_audit_logs)
- [Entity Relationship Diagram](#entity-relationship-diagram)

---

## Core Tables

### users

The central table storing all user information including authentication and profile details.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `name` | varchar(255) | NOT NULL | Full name of the user |
| `email` | varchar(255) | UNIQUE, NOT NULL | Email address (used for authentication) |
| `email_verified_at` | timestamp | NULLABLE | Timestamp when email was verified |
| `password` | varchar(255) | NOT NULL | Hashed password |
| `role` | enum | NOT NULL, DEFAULT 'STUDENT' | User role: ADMIN, STAFF, STUDENT, FACULTY |
| `active` | boolean | DEFAULT true | Whether the user account is active |
| `last_accessed_at` | timestamp | NULLABLE | Last time user accessed any room/device |
| `student_id` | varchar(255) | NULLABLE | Student ID number (for STUDENT role) |
| `faculty_id` | varchar(255) | NULLABLE | Faculty ID number (for FACULTY role) |
| `course` | varchar(255) | NULLABLE | Course/program of study (for students) |
| `year_level` | varchar(255) | NULLABLE | Year level (e.g., "1st Year", "2nd Year") |
| `attendance_rate` | decimal(5,2) | NULLABLE | Calculated attendance rate percentage (0.00-100.00) |
| `department` | varchar(255) | NULLABLE | Department affiliation |
| `remember_token` | varchar(100) | NULLABLE | Token for "remember me" functionality |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Usage:**
- Central user management for all system users
- Supports multiple user types (students, faculty, staff, admins)
- Tracks attendance metrics automatically
- Links to biometric authentication methods

**Relationships:**
- Has many `user_fingerprints` (one user can have multiple fingerprints)
- Has many `user_rfids` (one user can have multiple RFID cards)
- Has many `schedules` (teaching/class schedules)
- Has many `user_access_logs` (access history)
- Has many `user_audit_logs` (activity audit trail)
- Referenced by `devices.last_accessed_by_user_id`
- Referenced by `rooms.last_opened_by_user_id` and `rooms.last_closed_by_user_id`

---

### user_fingerprints

Stores fingerprint biometric data associations for users.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `user_id` | bigint unsigned | FOREIGN KEY, NOT NULL | References `users.id` |
| `fingerprint_id` | integer | UNIQUE, NOT NULL | Fingerprint template ID from biometric device |
| `active` | boolean | DEFAULT true | Whether this fingerprint is active/enabled |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE on delete)

**Usage:**
- Links biometric fingerprint data to user accounts
- Each fingerprint gets a unique ID from the biometric scanner
- Supports multiple fingerprints per user for redundancy
- Can be deactivated without deletion for security

**Relationships:**
- Belongs to one `user`

---

### user_rfids

Stores RFID card associations for users.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `user_id` | bigint unsigned | FOREIGN KEY, NOT NULL | References `users.id` |
| `card_id` | varchar(255) | UNIQUE, NOT NULL | RFID card unique identifier |
| `active` | boolean | DEFAULT true | Whether this RFID card is active/enabled |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE on delete)

**Usage:**
- Links RFID cards to user accounts
- Each card has a unique identifier read from the card
- Supports multiple cards per user (e.g., backup cards)
- Can be deactivated if card is lost/stolen

**Relationships:**
- Belongs to one `user`

---

## Access Control

### devices

Hardware devices (fingerprint scanners, RFID readers) that control access.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `device_id` | varchar(255) | UNIQUE, NOT NULL | Device hardware identifier |
| `door_open_duration_seconds` | unsigned integer | DEFAULT 5 | How long the door stays unlocked (in seconds) |
| `active` | boolean | DEFAULT true | Whether the device is active/operational |
| `last_accessed_by_user_id` | bigint unsigned | FOREIGN KEY, NULLABLE | Last user who accessed this device |
| `last_accessed_at` | timestamp | NULLABLE | Last access timestamp |
| `last_accessed_used` | enum | NULLABLE | Method used: FINGERPRINT, RFID, ADMIN, MANUAL |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `last_accessed_by_user_id` → `users.id` (SET NULL on delete)

**Usage:**
- Represents physical access control hardware
- Tracks last access for quick status display
- Configurable door unlock duration
- Supports multiple access methods

**Relationships:**
- May have one `room` (one device per room)
- Has many `user_access_logs` (access history)
- References one `user` (last accessor)

---

### rooms

Physical rooms/spaces with access control.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `room_number` | varchar(255) | NOT NULL | Room identifier/number |
| `device_id` | bigint unsigned | FOREIGN KEY, NULLABLE | Associated access control device |
| `active` | boolean | DEFAULT true | Whether the room is active/in-use |
| `last_opened_by_user_id` | bigint unsigned | FOREIGN KEY, NULLABLE | Last user who opened the room |
| `last_opened_at` | timestamp | NULLABLE | When the room was last opened |
| `last_closed_by_user_id` | bigint unsigned | FOREIGN KEY, NULLABLE | Last user who closed the room |
| `last_closed_at` | timestamp | NULLABLE | When the room was last closed |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `device_id` → `devices.id` (SET NULL on delete)
- `last_opened_by_user_id` → `users.id` (SET NULL on delete)
- `last_closed_by_user_id` → `users.id` (SET NULL on delete)

**Usage:**
- Represents physical classrooms, labs, offices, etc.
- Tracks room access and usage
- Links to access control devices
- Maintains open/close history

**Relationships:**
- May have one `device`
- Has many `schedules` (scheduled classes/usage)
- Has many `user_access_logs` (access history)
- References `user` twice (for open/close tracking)

---

## Scheduling

### subjects

Academic subjects/courses taught at the institution.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `subject` | varchar(255) | UNIQUE, NOT NULL | Subject name/code |
| `active` | boolean | DEFAULT true | Whether the subject is currently offered |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Usage:**
- Master list of subjects/courses
- Referenced by schedules to identify what is being taught
- Can be deactivated for archived courses

**Relationships:**
- Has many `schedules` (scheduled classes)

---

### schedules

Class schedules linking users (teachers/students), rooms, and subjects.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `user_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Teacher/instructor for this class |
| `day_of_week` | enum | NOT NULL | Day: SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY |
| `room_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Room where class is held |
| `subject_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Subject being taught |
| `active` | boolean | DEFAULT true | Whether this schedule is currently active |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE on delete)
- `room_id` → `rooms.id` (CASCADE on delete)
- `subject_id` → `subjects.id` (CASCADE on delete)

**Usage:**
- Defines recurring weekly class schedules
- Links teachers to rooms and subjects
- Used for attendance tracking and access control
- Can be enabled/disabled without deletion

**Relationships:**
- Belongs to one `user` (instructor)
- Belongs to one `room`
- Belongs to one `subject`
- Has many `schedule_periods` (time slots)

---

### schedule_periods

Time periods/slots for scheduled classes.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `schedule_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Parent schedule |
| `start_time` | time | NOT NULL | Class start time (HH:MM:SS) |
| `end_time` | time | NOT NULL | Class end time (HH:MM:SS) |
| `active` | boolean | DEFAULT true | Whether this period is currently active |
| `created_at` | timestamp | NULLABLE | Record creation timestamp |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `schedule_id` → `schedules.id` (CASCADE on delete)

**Usage:**
- Defines specific time ranges for classes
- Allows multiple periods per schedule (e.g., split sessions)
- Used to validate attendance timing
- Supports flexible scheduling patterns

**Relationships:**
- Belongs to one `schedule`

---

## Logging & Audit

### user_access_logs

Comprehensive log of all access events (door openings, room access).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `user_id` | bigint unsigned | FOREIGN KEY, NOT NULL | User who accessed the room |
| `room_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Room that was accessed |
| `device_id` | bigint unsigned | FOREIGN KEY, NOT NULL | Device used for access |
| `access_used` | enum | NOT NULL | Method: FINGERPRINT, RFID, ADMIN, MANUAL |
| `created_at` | timestamp | NULLABLE | When the access occurred |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE on delete)
- `room_id` → `rooms.id` (CASCADE on delete)
- `device_id` → `devices.id` (CASCADE on delete)

**Usage:**
- Complete audit trail of all access events
- Used for attendance tracking and reporting
- Identifies authentication method used
- Critical for security and compliance

**Relationships:**
- Belongs to one `user`
- Belongs to one `room`
- Belongs to one `device`

---

### user_audit_logs

General audit log for user activities and system events.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| `id` | bigint unsigned | PRIMARY KEY | Unique identifier |
| `user_id` | bigint unsigned | FOREIGN KEY, NOT NULL | User who performed the action |
| `description` | text | NOT NULL | Description of the action/event |
| `created_at` | timestamp | NULLABLE | When the action occurred |
| `updated_at` | timestamp | NULLABLE | Record last update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE on delete)

**Usage:**
- Tracks administrative actions and system events
- Provides accountability for sensitive operations
- Free-form description for flexibility
- Used for security audits and compliance

**Relationships:**
- Belongs to one `user`

---

## Entity Relationship Diagram

```
users (1) ──< (many) user_fingerprints
users (1) ──< (many) user_rfids
users (1) ──< (many) schedules
users (1) ──< (many) user_access_logs
users (1) ──< (many) user_audit_logs
users (1) ──< (many) devices.last_accessed_by_user_id
users (1) ──< (many) rooms.last_opened_by_user_id
users (1) ──< (many) rooms.last_closed_by_user_id

devices (1) ──< (1 or 0) rooms
devices (1) ──< (many) user_access_logs

rooms (1) ──< (many) schedules
rooms (1) ──< (many) user_access_logs

subjects (1) ──< (many) schedules

schedules (1) ──< (many) schedule_periods
```

## Access Method Enums

The system supports four access methods:

1. **FINGERPRINT** - Biometric fingerprint authentication
2. **RFID** - RFID card authentication
3. **ADMIN** - Administrative override (manual grant by admin)
4. **MANUAL** - Manual door operation (no authentication)

## User Roles

The system defines four user roles:

1. **ADMIN** - System administrators with full access
2. **STAFF** - Staff members (non-teaching)
3. **STUDENT** - Students attending classes
4. **FACULTY** - Teaching faculty/instructors

## Common Patterns

### Active Flags
Most tables include an `active` boolean field (default `true`) that allows soft-enabling/disabling of records without deletion. This preserves historical data while controlling current functionality.

### Cascade Deletes
- User deletion cascades to their fingerprints, RFIDs, schedules, and logs
- Schedule deletion cascades to schedule periods
- Room/Device deletion cascades to access logs

### Set NULL on Delete
- Device deletion sets `rooms.device_id` to NULL
- User deletion sets last_accessed/opened/closed user references to NULL

### Timestamps
All tables include `created_at` and `updated_at` timestamps managed automatically by Laravel's Eloquent ORM.

## Security Considerations

1. **Passwords** are stored hashed (never plaintext)
2. **Access logs** provide complete audit trail
3. **Biometric data** (fingerprints) stored only as template IDs, actual fingerprint images not in database
4. **RFID cards** can be individually deactivated if compromised
5. **User accounts** can be deactivated while preserving historical data
6. **Cascade deletes** ensure no orphaned sensitive data
