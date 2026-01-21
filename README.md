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
   composer migrate
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
```

The API will be available at `http://localhost:8080`

### Run tests

```bash
composer test
```

## API Endpoints

### Health Check

- `GET /health` - Check API status

### Authentication

- All endpoints except `/health` require a Bearer token in `Authorization` header.
- Tokens are stored in the `users.api_token` column. Seed data includes:
  - alice → `token-alice-123`
  - bob → `token-bob-456`
  - charlie → `token-charlie-789`
- Use HTTPS in production to protect tokens in transit.

### Groups

- `POST /groups` (auth) — Create public/private groups (`is_private` boolean). Creator is stored as admin.
- `POST /groups/{groupId}/join` (auth) — Join a group. For private groups, pass `invite_token` in the JSON body.
- `POST /groups/{groupId}/invite` (auth, admin only) — Issue invite tokens for private access.

### Messages

- `POST /groups/{groupId}/messages` (auth, group member) — Send a message to the group.
- `GET /groups/{groupId}/messages?page=1&page_size=20` (auth, group member) — Paginated message listing (page_size capped at 100). Poll periodically to fetch new messages.

## Project Structure

```
chat-gateway-php/
├── public/              # Web root
│   └── index.php       # Application entry point
├── src/                # Application source code
│   ├── Controllers/    # Request handlers
│   │   └── HealthController.php
│   ├── Middleware/     # HTTP middleware
│   │   ├── AuthMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   └── JsonBodyParserMiddleware.php
│   ├── Models/         # Data models (to be added)
│   ├── Services/       # Business logic
│   │   └── Database.php
│   └── Repositories/   # Data access layer (to be added)
├── config/             # Configuration files
├── database/           # Database files and migrations
├── logs/               # Application logs
├── tests/              # Test files
└── .github/            # GitHub Actions workflows
```

## Roadmap

- [x] Project setup and structure
- [x] GitHub Actions CI/CD
- [x] Database schema
- [x] Authentication implementation
- [ ] Group management
- [ ] Messaging system
- [ ] API documentation (OpenAPI/Swagger)
- [ ] Comprehensive test coverage
- [ ] Docker support
- [ ] WebSocket support for real-time messaging
