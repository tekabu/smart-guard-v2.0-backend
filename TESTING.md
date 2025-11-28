# Smart Guard API Testing Documentation

Comprehensive unit tests for all API endpoints using PHPUnit and Laravel testing features.

## Test Suite Overview

We have created complete test coverage for all 10 API controllers:

1. **UserControllerTest** - User management tests
2. **DeviceControllerTest** - Device management tests
3. **RoomControllerTest** - Room management tests
4. **SubjectControllerTest** - Subject management tests
5. **UserFingerprintControllerTest** - Fingerprint registration tests
6. **UserRfidControllerTest** - RFID card registration tests
7. **ScheduleControllerTest** - Schedule management tests
8. **SchedulePeriodControllerTest** - Schedule period tests
9. **UserAccessLogControllerTest** - Access log tests
10. **UserAuditLogControllerTest** - Audit log tests

## Running Tests

### Run All Tests

```bash
docker exec smart-guard-php php artisan test
```

### Run Specific Test File

```bash
# Test Users API
docker exec smart-guard-php php artisan test --filter UserControllerTest

# Test Devices API
docker exec smart-guard-php php artisan test --filter DeviceControllerTest

# Test Rooms API
docker exec smart-guard-php php artisan test --filter RoomControllerTest
```

### Run Specific Test Method

```bash
docker exec smart-guard-php php artisan test --filter test_can_create_user
```

### Run Tests with Coverage

```bash
docker exec smart-guard-php php artisan test --coverage
```

## Test Structure

Each test file includes tests for:

- **List (Index)** - GET request to retrieve all records
- **Create (Store)** - POST request with valid data
- **Show** - GET request for a specific record
- **Update** - PUT request to modify a record
- **Delete (Destroy)** - DELETE request to remove a record
- **Validation** - Testing required fields and validation rules
- **Edge Cases** - Duplicate values, invalid data, etc.

## Example Test Cases

### UserControllerTest

```php
✓ test_can_list_users
✓ test_can_create_user
✓ test_can_show_user
✓ test_can_update_user
✓ test_can_delete_user
✓ test_cannot_create_user_with_duplicate_email
✓ test_cannot_create_user_with_invalid_role
✓ test_requires_name_email_password_role
```

### DeviceControllerTest

```php
✓ test_can_list_devices
✓ test_can_create_device
✓ test_can_show_device
✓ test_can_update_device
✓ test_can_delete_device
✓ test_cannot_create_device_with_duplicate_device_id
✓ test_requires_device_id
```

### ScheduleControllerTest

```php
✓ test_can_list_schedules
✓ test_can_create_schedule
✓ test_can_show_schedule
✓ test_can_update_schedule
✓ test_can_delete_schedule
✓ test_cannot_create_schedule_with_invalid_day
✓ test_requires_all_fields
```

## Model Factories

All models have factories for test data generation:

- **UserFactory** - Generates users with all roles
- **DeviceFactory** - Generates devices with unique IDs
- **RoomFactory** - Generates rooms
- **SubjectFactory** - Generates subjects
- **UserFingerprintFactory** - Generates fingerprint records
- **UserRfidFactory** - Generates RFID records
- **ScheduleFactory** - Generates schedules
- **SchedulePeriodFactory** - Generates time periods
- **UserAccessLogFactory** - Generates access logs
- **UserAuditLogFactory** - Generates audit logs

## Test Database

Tests use the RefreshDatabase trait which:
- Creates a fresh database for each test
- Rolls back all changes after each test
- Ensures test isolation

## Continuous Integration

### Run Tests Before Commit

```bash
# Run all tests
docker exec smart-guard-php php artisan test

# Run with output
docker exec smart-guard-php php artisan test --verbose
```

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: |
          docker-compose up -d
          docker exec smart-guard-php composer install
          docker exec smart-guard-php php artisan migrate
          docker exec smart-guard-php php artisan test
```

## Test Coverage

### View Coverage Report

```bash
docker exec smart-guard-php php artisan test --coverage --min=80
```

### Generate HTML Coverage Report

```bash
docker exec smart-guard-php vendor/bin/phpunit --coverage-html coverage
```

## Writing New Tests

When adding new features, follow this pattern:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\YourModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_records()
    {
        YourModel::factory()->count(3)->create();
        $response = $this->getJson('/api/your-endpoint');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_record()
    {
        $data = ['field' => 'value'];
        $response = $this->postJson('/api/your-endpoint', $data);
        $response->assertStatus(201);
        $this->assertDatabaseHas('your_table', $data);
    }
}
```

## Debugging Failed Tests

### View detailed output

```bash
docker exec smart-guard-php php artisan test --verbose
```

### Run single failing test

```bash
docker exec smart-guard-php php artisan test --filter test_name
```

### Check test database

```bash
docker exec smart-guard-php php artisan tinker
>>> \App\Models\User::count()
```

## Best Practices

1. **Test Isolation** - Each test should be independent
2. **Descriptive Names** - Use clear test method names
3. **Arrange-Act-Assert** - Structure tests clearly
4. **Test Edge Cases** - Cover validation and errors
5. **Use Factories** - Generate test data with factories
6. **Clean Database** - Always use RefreshDatabase trait

## Common Assertions

```php
// Status codes
$response->assertStatus(200);
$response->assertStatus(201);
$response->assertStatus(422);

// JSON structure
$response->assertJson(['key' => 'value']);
$response->assertJsonFragment(['key' => 'value']);
$response->assertJsonCount(3);

// Database
$this->assertDatabaseHas('table', ['field' => 'value']);
$this->assertDatabaseMissing('table', ['field' => 'value']);

// Validation errors
$response->assertJsonValidationErrors(['field']);
```

## Troubleshooting

### Tests fail with database errors

```bash
# Reset test database
docker exec smart-guard-php php artisan migrate:fresh
```

### Factory errors

```bash
# Clear cache
docker exec smart-guard-php php artisan config:clear
docker exec smart-guard-php composer dump-autoload
```

### Permission errors

```bash
# Fix permissions
docker exec smart-guard-php chown -R www-data:www-data storage bootstrap/cache
```

## Next Steps

1. Run the full test suite
2. Review test coverage
3. Add integration tests for complex workflows
4. Set up CI/CD pipeline
5. Add performance tests

## Support

For test failures or questions:
- Check test output for specific errors
- Review the API documentation
- Check model factories for correct data
- Verify database migrations are up to date

Run `docker exec smart-guard-php php artisan test --help` for more options.
