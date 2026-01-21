# Swagger UI Documentation

## Overview

Swagger UI provides an interactive, web-based interface for exploring and testing the Chat Gateway API. The API is fully documented using OpenAPI 3.0 specifications.

## Accessing Swagger UI

### Development Server

```
http://localhost:8080/swagger
```

### API Specification (JSON)

```
http://localhost:8080/api/swagger.json
```

## Features

### 📖 Interactive Documentation

- Browse all available endpoints
- View request/response schemas
- See code examples for different programming languages
- Understand authentication requirements

### 🧪 Try It Out

- Execute API calls directly from the browser
- Test endpoints with real data
- See live responses and error messages
- No external tools needed

### 🔐 Authentication

- Swagger UI supports JWT Bearer token authentication
- Copy your JWT token from the `/auth/login` response
- Click the "Authorize" button to set the token globally
- All protected endpoints will automatically include the token

## Workflow Example

### 1. Login and Get Token

1. Navigate to Swagger UI: `http://localhost:8080/swagger`
2. Find the **Authentication** section
3. Click on **POST /auth/login**
4. Click "Try it out"
5. Enter your credentials:
   ```json
   {
     "email": "user@example.com",
     "password": "password"
   }
   ```
6. Click "Execute"
7. Copy the `token` from the response

### 2. Authorize for Protected Endpoints

1. Click the green "Authorize" button at the top
2. Paste your token in the "Value" field
3. Leave the scheme as "Bearer"
4. Click "Authorize"

### 3. Test Protected Endpoints

1. Navigate to **Users** → **GET /users/me**
2. Click "Try it out"
3. Click "Execute"
4. View your user profile in the response

### 4. Explore Groups

1. Go to **Groups** → **GET /groups**
2. Click "Try it out"
3. Click "Execute"
4. See all groups accessible to you

## API Sections

### Health

- **GET /health** - Service health check (no authentication required)

### Authentication

- **POST /auth/login** - User login (returns JWT token)

### Users

- **GET /users/me** - Get current user profile

### Groups

- **GET /groups** - List accessible groups
- **POST /groups** - Create new group
- **GET /groups/{groupId}** - Get group details with members
- **POST /groups/{groupId}/join** - Join a group
- **POST /groups/{groupId}/invite** - Invite user to group
- **GET /groups/{groupId}/messages** - Get group messages
- **POST /groups/{groupId}/messages** - Send message to group

## Request/Response Examples

### Example: Login

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Response:

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "john_doe",
    "email": "john@example.com"
  }
}
```

### Example: Get User Profile

```bash
curl -X GET http://localhost:8080/api/users/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

Response:

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "username": "john_doe",
  "email": "john@example.com",
  "created_at": "2024-01-15T10:30:00"
}
```

## Error Codes

| Code | Meaning      | Solution                      |
| ---- | ------------ | ----------------------------- |
| 200  | OK           | Request successful            |
| 201  | Created      | Resource created successfully |
| 400  | Bad Request  | Check request parameters      |
| 401  | Unauthorized | Provide valid JWT token       |
| 404  | Not Found    | Resource doesn't exist        |
| 409  | Conflict     | Resource already exists       |
| 500  | Server Error | Contact support               |

## Tips & Tricks

### 1. Save Your Token

Keep your JWT token handy. Copy it immediately after login to avoid needing to re-authenticate.

### 2. Clear Auth

To log out or switch users, click the "Authorize" button again and click "Logout".

### 3. View Raw Responses

Click "Response headers" to see HTTP headers and status codes.

### 4. Download OpenAPI Spec

The OpenAPI specification can be downloaded and imported into other tools:

- Postman
- Insomnia
- API documentation generators
- Code generation tools

### 5. Generate Client Code

Use the OpenAPI spec to generate API client code in multiple languages:

- JavaScript/TypeScript
- Python
- Java
- Go
- Ruby
- PHP

## Generating API Clients

### Using OpenAPI Generator CLI

```bash
# Generate TypeScript client
npx openapi-generator-cli generate \
  -i http://localhost:8080/api/swagger.json \
  -g typescript \
  -o ./api-client

# Generate Python client
openapi-generator-cli generate \
  -i http://localhost:8080/api/swagger.json \
  -g python \
  -o ./api-client
```

## Troubleshooting

### Swagger UI not loading

- Ensure the server is running: `php -S localhost:8080 -t public`
- Check browser console for errors
- Try clearing browser cache

### "Cannot read spec from URL"

- Verify the API spec endpoint is accessible: `http://localhost:8080/api/swagger.json`
- Check network requests in browser DevTools
- Ensure JSON is valid

### Authorization not working

- Verify token is correct and not expired
- Check Authorization header format: "Bearer <token>"
- Try logging in again to get a fresh token

## Further Reading

- [OpenAPI Specification](https://spec.openapis.org/)
- [Swagger UI Documentation](https://swagger.io/tools/swagger-ui/)
- [JWT Authentication](https://jwt.io/introduction)
