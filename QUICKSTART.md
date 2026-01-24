# Quick Start: Using Swagger UI

## 🚀 Get Started in 3 Steps

### Step 1: Start the Server

```bash
composer start
```

Server runs at: `http://localhost:8080`

### Step 2: Open Swagger UI

Visit: `http://localhost:8080/swagger`

### Step 3: Test the API

#### Example: Login

1. Find **Authentication** → **POST /auth/login**
2. Click "Try it out"
3. Enter credentials:
   ```json
   {
     "email": "john@example.com",
     "password": "password"
   }
   ```
4. Click "Execute"
5. Copy the `token` from response

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

| Issue                     | Solution                                                        |
| ------------------------- | --------------------------------------------------------------- |
| Swagger UI not loading    | Ensure server is running on port 8080                           |
| "Cannot read spec"        | Check browser console, verify `/api/swagger.json` is accessible |
| Authorization not working | Verify token format: `Bearer <token>` (with space)              |
| Token expired             | Get a new token from `/auth/login`                              |

---

## 💡 Pro Tips

1. **Export Requests**: Right-click request to copy as cURL
2. **Share API**: Send Swagger URL to teammates
3. **Import to Postman**: Use `/api/swagger.json` URL
4. **Generate Code**: Use OpenAPI generators for your language

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
