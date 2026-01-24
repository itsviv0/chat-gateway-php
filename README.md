# chat-gateway-php

A production-grade chat application backend built with PHP and Slim Framework.

## Features

- 🔐 **JWT-based Authentication** - Secure token-based user identification
- 👥 **Group Management** - Create public/private chat groups
- 💬 **Messaging System** - Send and retrieve messages with pagination
- 🎫 **Invitation System** - Admin-controlled group access
- 🔒 **Security First** - Input validation, SQL injection prevention, rate limiting
- 📊 **RESTful API** - Clean JSON API over HTTPS
- 🧪 **Fully Tested** - Unit and integration tests with PHPUnit
- 📝 **Well Documented** - Comprehensive API documentation

## Requirements

- PHP 8.1 or higher
- Composer
- SQLite3
- Extensions: PDO, pdo_sqlite, mbstring, json

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/itsviv0/chat-gateway-php.git
   cd chat-gateway-php
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Set up environment**

   ```bash
   cp .env.example .env
   # Edit .env and configure your settings
   ```

4. **Initialize Database**

   ```bash
   # Run migrations
   vendor/bin/phinx migrate

   # Seed test data (5 users, 6 groups, 25 messages)
   vendor/bin/phinx seed:run
   ```

### Core installations (before composer install (In Debian/Ubuntu)):

```bash
sudo apt install php8.1-cli php8.1-sqlite3 php8.1-mbstring php8.1-curl php8.1-xml
```

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Development

### Start the development server

```bash
composer start
# OR manually:
php -S localhost:8080 -t public public/router.php
```

The API will be available at `http://localhost:8080`

### Access Swagger UI

```bash
http://localhost:8080/swagger
```

Interactive API documentation with "Try it out" functionality.

### Run tests

```bash
composer test
```

## API Endpoints

### Authentication

- `POST /auth/login` - Login with email/password, returns JWT token
  ```json
  {
    "email": "alice@example.com",
    "password": "password123"
  }
  ```

### Users

- `GET /users/me` (auth) - Get current user profile

### Groups

- `GET /groups` (auth) - List accessible groups
- `POST /groups` (auth) - Create new group (public/private)
- `GET /groups/{groupId}` (auth) - Get group details with members
- `POST /groups/{groupId}/join` (auth) - Join a group (requires invite_token for private groups)
- `POST /groups/{groupId}/invite` (auth, admin only) - Generate invite token

### Messages

- `POST /groups/{groupId}/messages` (auth, member) - Send message to group
- `GET /groups/{groupId}/messages` (auth, member) - List messages with pagination (page, page_size)

### Health

- `GET /health` - Service health check (no auth required)

### Test Users

All users have password: `password123`

- alice@example.com
- bob@example.com
- charlie@example.com
- david@example.com
- emma@example.com

### Authorization

All protected endpoints require JWT Bearer token:

```
Authorization: Bearer <your-jwt-token>
```

## Project Structure

```
chat-gateway-php/
├── public/              # Web root
│   ├── index.php       # Application entry point
│   └── router.php      # PHP built-in server router
├── src/                # Application source code
│   ├── Controllers/    # Request handlers
│   ├── Middleware/     # HTTP middleware
│   ├── Services/       # Business logic
│   ├── Repositories/   # Data access layer
│   ├── OpenAPI/        # API documentation
│   │   └── OpenAPI.php
│   ├── Utils/          # Utility classes
│   │   └── Validator.php
│   └── Exceptions/     # Custom exceptions
├── config/             # Configuration files
│   ├── container.php   # DI container
│   ├── middleware.php  # Middleware stack
│   └── routes.php      # Route definitions
├── database/           # Database files
│   ├── database.sqlite # SQLite database (UUID primary keys)
│   ├── migrations/     # Phinx migrations
│   └── seeds/          # Database seeders
├── tests/              # PHPUnit tests
│   ├── Unit/          # Unit tests
│   └── Integration/   # Integration tests
├── logs/              # Application logs
└── vendor/            # Composer dependencies
```

## Documentation

- **[QUICKSTART.md](QUICKSTART.md)** - Get started in 4 steps
- **[SWAGGER.md](SWAGGER.md)** - Swagger UI guide
- **OpenAPI Spec** - `http://localhost:8080/swagger.json`

[Screencast from 24-01-26 01:08:25 PM IST.webm](https://github.com/user-attachments/assets/11dd04e4-4ead-4e98-82fa-5c32e4b29a0c)
