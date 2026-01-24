# Quick Start: Using Swagger UI

## 🚀 Get Started in 4 Steps

### Step 1: Setup Database

```bash
# Run migrations to create tables
vendor/bin/phinx migrate

# Seed test data (5 users, 6 groups, 25 messages)
vendor/bin/phinx seed:run
```

### Step 2: Start the Server

```bash
composer start
# OR manually:
php -S localhost:8080 -t public public/router.php
```

Server runs at: `http://localhost:8080`

### Step 3: Open Swagger UI

Visit: `http://localhost:8080/swagger`

### Step 4: Test the API

#### Example: Login

1. Find **Authentication** → **POST /auth/login**
2. Click "Try it out"
3. Enter credentials:
   ```json
   {
     "email": "alice@example.com",
     "password": "password123"
   }
   ```
4. Click "Execute"
5. Copy the `token` from response

#### Available Test Users

All users have password: `password123`

- alice@example.com
- bob@example.com
- charlie@example.com
- david@example.com
- emma@example.com

---

## 🔐 Using JWT Tokens

### Authorize

1. Click green "Authorize" button at top
2. Paste: `Bearer eyJhbGc...` (your token)
3. Click "Authorize"

### Now Test Protected Endpoints

- **GET /users/me** - Your profile
- **GET /groups** - Your groups
- **GET /groups/{id}** - Group details with members

---

## 📚 Documentation Links

- **Full Swagger Guide**: [SWAGGER.md](SWAGGER.md)
- **API Reference**: [API_REFERENCE.md](API_REFERENCE.md)
- **Implementation Guide**: [IMPLEMENTATION.md](IMPLEMENTATION.md)

---

## 🐛 Troubleshooting

| Issue                  | Solution                                                          |
| ---------------------- | ----------------------------------------------------------------- |
| Swagger UI not loading | Start server: `php -S localhost:8080 -t public public/router.php` |
| "Cannot read spec"     | Check browser console, verify `/api/swagger.json` is accessible   |
| No data present        | Run: `vendor/bin/phinx migrate && vendor/bin/phinx seed:run`      |
| Token expired          | Get a new token from `/auth/login`                                |

---

## 🎯 Common Workflows

### Create & Join Group

1. **POST /groups** - Create new group
2. **POST /groups/{id}/join** - Join a group
3. **GET /groups/{id}** - View group details

### Send Messages

1. **POST /groups/{id}/messages** - Send a message
2. **GET /groups/{id}/messages** - List group messages

### Check Server Status

**GET /health** - Service health status (no auth needed)

---

Enjoy exploring the API! 🚀
