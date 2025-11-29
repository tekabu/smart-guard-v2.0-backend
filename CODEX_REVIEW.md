# Code Review - Smart Guard API

## Findings

1. **UniqueScheduleCombination rule is a syntax error that cannot be autoloaded**  
   The implementation under `src/app/Rules/UniqueScheduleCombination.php:9-41` only contains bare placeholders (`protected ;`, empty constructor body, missing variable names, etc.). PHP cannot parse this file, so the rule cannot even be instantiated if/when it is referenced. Any attempt to enforce the documented “unique schedule combination” behavior through this rule will immediately fatal. The file needs to be rewritten with actual properties (e.g., `$scheduleId`), constructor arguments, and the intended validation query.

2. **Authentication controller never creates Sanctum tokens**  
   `src/app/Http/Controllers/Api/AuthController.php:20-67` signs users in with `Auth::login($user)` and returns the user record, but never issues a Sanctum personal access token. All protected routes in `routes/api.php` are wrapped in `auth:sanctum`, so real API clients cannot call them after login because they never receive a token/cookie to satisfy that middleware, and logout never revokes tokens either. The controller should call `$user->createToken(...)` (or Sanctum SPA cookie flow) and return the token so clients can authenticate subsequent requests, and `logout` should revoke tokens.

3. **User model uses the wrong column name for last access tracking**  
   The migration adds `last_accessed_at` (`src/database/migrations/2025_11_28_064338_add_user_profile_fields_to_users_table.php:15-24`), factories populate `last_accessed_at` (`src/database/factories/UserFactory.php:18-33`), but `App\Models\User` exposes `last_access_at` in both `$fillable` and `$casts` (`src/app/Models/User.php:20-58`). Because the attribute name does not exist in the table, Laravel silently drops it during mass-assignment and casting, so last access timestamps can never be stored or read correctly. Rename the attribute in the model/casts and everywhere else to match the database column, or alter the schema.

4. **DeviceBoard fillable array references a non-existent column**  
   `src/app/Models/DeviceBoard.php:14-23` includes `board_id`, yet neither migration (`src/database/migrations/2025_11_29_052100_create_device_boards_table.php:14-33` plus `2025_11_29_054500_add_last_ip_to_device_boards_table.php:13-24`) nor any controller validation defines this column. Attempting to mass assign `board_id` will throw a database error, and the attribute can never persist. Remove the field from the model or add the missing column/migration and validation logic.

5. **DeviceBoard factory states generate invalid board_type values**  
   While the default attributes use uppercase values that pass validation, the convenience state helpers (`fingerprint`, `rfid`, `lock`, `camera`, `display`) in `src/database/factories/DeviceBoardFactory.php:40-72` emit lowercase strings. Controllers and database enums only allow uppercase (`FINGERPRINT`, etc.), so any test or seeder that uses these states will instantly fail validation or violate the enum constraint. Update the state values to the uppercase constants used throughout the API.

6. **Schedule period overlap rule ignores updated targets and incomplete payloads**  
   When updating a period, the controller instantiates `new NoScheduleOverlap($id, $record->schedule_id)` (`src/app/Http/Controllers/Api/SchedulePeriodController.php:44-57`). The rule then always uses the injected `currentScheduleId` instead of the `schedule_id` coming from the request (`src/app/Rules/NoScheduleOverlap.php:21-57`). As a result, moving a period to a different schedule/room skips overlap detection for the target schedule entirely. Additionally, the rule reads `request('start_time')` and `request('end_time')` instead of the `$value` being validated, so PATCH requests that only update one bound set the other to `null`, letting overlapping updates slip through. The rule should consume the validated value (`$value`) and merge it with the existing record when the companion field is missing, and it should honor a submitted `schedule_id`.

