# API Token Management for Admin Users

This document explains how to generate and manage API tokens for admin users in the Smart Guard system.

## Overview

Admin users can now generate Bearer tokens to access the full API system without needing to maintain a web session. This is particularly useful for:

- Programmatic access to the API
- Integration with other systems
- Command-line tools and scripts
- Mobile applications

## Prerequisites

- User must have role 'ADMIN'
- User must be active

## Artisan Commands

### 1. Create API Token

Generate a new API token for an admin user:

```bash
php artisan app:create-api-token {email} {--name=token-name}
```

**Example:**
```bash
# Basic usage (auto-generates token name)
php artisan app:create-api-token admin@example.com

# With custom token name
php artisan app:create-api-token admin@example.com --name="Production API Token"
```

**Output:**
```
API token created successfully!

User: Admin User (admin@example.com)
Token Name: Admin API Token - 2025-11-30 14:30:45
Token ID: 1

=== API TOKEN (keep this secure) ===
1|y3x7l9k2m5p8q1w4e6r0t3u7i9o2p5a8s1d2f3g4h5j6k7l8z9x0c1v2b3n4m5q
=====================================

Usage example:
curl -H 'Authorization: Bearer 1|y3x7l9k2m5p8q1w4e6r0t3u7i9o2p5a8s1d2f3g4h5j6k7l8z9x0c1v2b3n4m5q' http://localhost/api/users
```

### 2. List API Tokens

View all tokens for admin users:

```bash
# List all admin tokens
php artisan app:list-api-tokens

# Filter by specific user
php artisan app:list-api-tokens --user=admin@example.com
```

**Output:**
```
+------------------+---------------------+-------------------------+----------+----------------------+-----------+
| User             | Email               | Token Name              | Token ID | Created              | Last Used |
+------------------+---------------------+-------------------------+----------+----------------------+-----------+
| Admin User       | admin@example.com   | Production API Token    | 1        | 2025-11-30 14:30:45  | Never     |
| Admin User       | admin@example.com   | Testing Token           | 2        | 2025-11-30 13:15:22  | 2 hours   |
| System Admin     | system@example.com  | No tokens               | -        | -                    | -         |
+------------------+---------------------+-------------------------+----------+----------------------+-----------+
Total tokens: 2
```

### 3. Revoke API Tokens

Remove tokens that are no longer needed:

```bash
# Revoke specific token by ID
php artisan app:revoke-api-token --id=1

# Revoke all tokens for a specific user
php artisan app:revoke-api-token --user=admin@example.com

# Revoke ALL admin tokens (with confirmation)
php artisan app:revoke-api-token --all
```

## Using API Tokens

Once you have a token, use it in your HTTP requests:

### cURL Example
```bash
curl -X GET http://localhost/api/users \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### JavaScript Example
```javascript
fetch('http://localhost/api/users', {
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN_HERE'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## API Endpoints for Token Management

In addition to the Artisan commands, you can manage tokens via the API:

### Create Token (POST)
```
POST /api/tokens
Content-Type: application/json
Authorization: Bearer [session_token or existing_token]

{
  "token_name": "My API Token"
}
```

### List Tokens (GET)
```
GET /api/tokens
Authorization: Bearer [existing_token]
```

### Revoke Token (DELETE)
```
DELETE /api/tokens/{tokenId}
Authorization: Bearer [existing_token]
```

## Security Best Practices

1. **Keep Tokens Secure**: Treat tokens like passwords - never share them publicly or commit them to repositories
2. **Use Descriptive Names**: Use meaningful token names to easily identify their purpose
3. **Regular Cleanup**: Revoke tokens that are no longer needed
4. **Monitor Usage**: Check token usage regularly in your application logs
5. **Limit Permissions**: Currently admin tokens have full access ('*' abilities). Consider creating more granular permissions in production

## Troubleshooting

### Common Issues

1. **"User is not an admin"**: Ensure the user has role 'ADMIN' in the database
2. **"User is not active"**: Check the 'active' field is set to true for the user
3. **Token not working**: Verify the token is correctly copied without extra spaces or characters
4. **401 Unauthorized**: Check that the Authorization header is correctly formatted: `Bearer 1|token...`

### Getting Help

Run the help command for any of these commands:
```bash
php artisan help app:create-api-token
php artisan help app:list-api-tokens
php artisan help app:revoke-api-token
```