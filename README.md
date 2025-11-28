# Smart Guard API

Backend API for the Smart Guard Vue web application, built with Laravel 12.

## Tech Stack

- **PHP**: 8.2+
- **Framework**: Laravel 12
- **Database**: MySQL 8.0
- **Web Server**: Nginx
- **Containerization**: Docker & Docker Compose
- **Testing**: PHPUnit

## Prerequisites

- Docker and Docker Compose installed
- Git

## Project Structure

```
smart-guard-api/
├── src/              # Laravel application source code
├── build/            # Docker build configurations
│   ├── nginx/        # Nginx configuration
│   └── php/          # PHP-FPM configuration
├── data/             # Persistent data volumes
│   └── mysql/        # MySQL data
└── docker-compose.yml
```

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd smart-guard-api
```

### 2. Environment Configuration

Create environment file from example:

```bash
cp .env.example .env
```

Update `.env` with your configuration settings.

### 3. Start Docker Services

```bash
docker-compose up -d
```

This will start:
- **Nginx** on port `8021`
- **PHP-FPM** container
- **MySQL** on port `8022`
- **PhpMyAdmin** on port `8023`

### 4. Install Dependencies

```bash
docker exec -it smart-guard-php composer install
```

### 5. Generate Application Key

```bash
docker exec -it smart-guard-php php artisan key:generate
```

### 6. Run Database Migrations

```bash
docker exec -it smart-guard-php php artisan migrate
```

## Running the Application

### Start All Services

```bash
docker-compose up -d
```

### Stop All Services

```bash
docker-compose down
```

### View Logs

```bash
docker-compose logs -f
```

## API Access

- **API Base URL**: `http://localhost:8021`
- **PhpMyAdmin**: `http://localhost:8023`
  - Server: `mysql`
  - Username: `smart_guard`
  - Password: `smart_guard_password`

## Development

### Running Artisan Commands

```bash
docker exec -it smart-guard-php php artisan <command>
```

### Common Commands

```bash
# Clear cache
docker exec -it smart-guard-php php artisan cache:clear

# Clear config
docker exec -it smart-guard-php php artisan config:clear

# Run migrations
docker exec -it smart-guard-php php artisan migrate

# Rollback migrations
docker exec -it smart-guard-php php artisan migrate:rollback

# Create a new controller
docker exec -it smart-guard-php php artisan make:controller <ControllerName>

# Create a new model
docker exec -it smart-guard-php php artisan make:model <ModelName>

# Create a new migration
docker exec -it smart-guard-php php artisan make:migration <migration_name>
```

### Database Seeding

```bash
docker exec -it smart-guard-php php artisan db:seed
```

## Testing

Run the test suite:

```bash
docker exec -it smart-guard-php php artisan test
```

Or using composer:

```bash
docker exec -it smart-guard-php composer test
```

## Database

### MySQL Connection Details

- **Host**: `localhost` (or `mysql` from within containers)
- **Port**: `8022` (external) / `3306` (internal)
- **Database**: `smart_guard`
- **Username**: `smart_guard`
- **Password**: `smart_guard_password`
- **Root Password**: `root_password`

### Access MySQL CLI

```bash
docker exec -it smart-guard-mysql mysql -u smart_guard -p
```

## API Documentation

API endpoints documentation will be available at:
- `/api/documentation` (when configured)

## Troubleshooting

### Permission Issues

If you encounter permission issues:

```bash
docker exec -it smart-guard-php chmod -R 775 storage bootstrap/cache
docker exec -it smart-guard-php chown -R www-data:www-data storage bootstrap/cache
```

### Clear All Caches

```bash
docker exec -it smart-guard-php php artisan optimize:clear
```

### Rebuild Containers

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Deployment

For production deployment, use:

```bash
docker-compose -f docker-compose.prd.yml up -d
```

## Contributing

1. Create a feature branch
2. Make your changes
3. Run tests
4. Submit a pull request

## License

This project is licensed under the MIT License.
