# Backend Installation Guide

This guide will walk you through installing and setting up the Smart Guard API backend.

## Prerequisites

- Docker and Docker Compose installed
- Git installed

## Step 1: Clone the Repository

If you haven't already, clone the repository:

```bash
cd ~
git clone https://github.com/tekabu/smart-guard-v2.0-backend.git
cd smart-guard-v2.0-backend
```

## Step 2: Environment Configuration

1. Copy the example environment file for the backend:

```bash
cp src/.env.example src/.env
```

2. Create a root environment file for Docker Compose:

```bash
cp .env.example .env
```

3. Set the ADMIN_API_TOKEN in the root .env file (you can generate a secure token):
   - Refer to [API Token Management Guide](src/docs/API_TOKEN_MANAGEMENT.md) for instructions on generating secure tokens

3. Edit the environment files as needed:
   - `src/.env` contains backend-specific configuration
   - `.env` contains Docker Compose, service configuration, and ADMIN_API_TOKEN

## Step 3: Build and Start the Services

1. Build and start all services in detached mode:

```bash
docker compose up -d --build
```

2. To view the logs of all services:

```bash
docker compose logs -f
```

3. To view logs of a specific service:

```bash
docker compose logs -f <service-name>
```

## Step 4: Setup Laravel Application

1. Access the PHP container:

```bash
docker exec -it smart-guard-php bash
```

2. Install PHP dependencies:

```bash
composer install
```

3. Update dependencies:

```bash
composer update
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Run database migrations:

```bash
php artisan migrate
```

6. Exit the container:

```bash
exit
```

## Step 5: Verify Installation

1. Check that all containers are running:

```bash
docker compose ps
```

2. If the API service is running, you can test it by visiting:

```
http://localhost:<port>
```

Replace `<port>` with the port specified in your configuration.

## Common Commands

- Stop all services:
  ```bash
  docker compose down
  ```

- Restart a specific service:
  ```bash
  docker compose restart <service-name>
  ```

- Access a running container:
  ```bash
  docker compose exec <service-name> bash
  ```

## Troubleshooting

- If containers fail to start, check the logs for errors:
  ```bash
  docker compose logs <service-name>
  ```

- If you need to rebuild from scratch:
  ```bash
  docker compose down -v
  docker compose up -d --build
  ```

- Ensure all environment variables are properly configured in both `.env` files.

## Additional Resources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Project Documentation](docs/)