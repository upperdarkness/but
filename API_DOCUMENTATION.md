# BlackNova Traders API Documentation

## Overview

The BlackNova Traders API provides a RESTful interface for accessing game data and performing game actions. The API uses token-based authentication and returns JSON responses.

**Base URL:** `https://yourdomain.com/api/v1`

## Authentication

All API endpoints (except login and register) require authentication using a Bearer token.

### Getting an API Token

1. **Login** - POST `/api/v1/auth/login`
2. **Register** - POST `/api/v1/auth/register`

Both endpoints return a token that should be included in subsequent requests.

### Using the Token

Include the token in the `Authorization` header:
```
Authorization: Bearer <your_token_here>
```

Alternatively, you can use the `X-API-Token` header:
```
X-API-Token: <your_token_here>
```

Tokens expire after 90 days. You can generate a new token by logging in again.

---

## Endpoints

### Authentication

#### POST `/api/v1/auth/login`

Login and receive an API token.

**Request Body:**
```json
{
  "email": "player@example.com",
  "password": "your_password"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "expires_at": "2024-12-31 23:59:59",
    "ship": {
      "ship_id": 1,
      "character_name": "Player Name",
      "sector": 1,
      "credits": 1000,
      "turns": 1200,
      ...
    }
  }
}
```

**Error Responses:**
- `401` - Invalid credentials
- `403` - Ship destroyed (no escape pod)
- `422` - Validation error

---

#### POST `/api/v1/auth/register`

Register a new account and receive an API token.

**Request Body:**
```json
{
  "email": "newplayer@example.com",
  "password": "secure_password",
  "character_name": "New Player",
  "ship_type": "balanced"
}
```

**Ship Types:**
- `scout` - Fast, efficient, small cargo
- `merchant` - Large cargo, slow, weak combat
- `warship` - Strong combat, expensive, small cargo
- `balanced` - Average in all aspects (default)

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "abc123...",
    "expires_at": "2024-12-31 23:59:59",
    "ship": { ... }
  }
}
```

**Error Responses:**
- `409` - Email or character name already exists
- `422` - Validation error

---

#### POST `/api/v1/auth/logout`

Revoke the current API token.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

#### GET `/api/v1/auth/me`

Get current authenticated user information.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "ship": {
      "ship_id": 1,
      "character_name": "Player Name",
      "sector": 1,
      "credits": 1000,
      "turns": 1200,
      ...
    }
  }
}
```

---

### Game Actions

#### GET `/api/v1/game/main`

Get main game screen data (current sector, links, planets, ships).

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "ship": { ... },
    "sector": {
      "sector_id": 1,
      "sector_name": "Alpha",
      "port_type": "ore",
      ...
    },
    "links": [2, 3, 5],
    "planets": [
      {
        "planet_id": 1,
        "planet_name": "Earth",
        "owner": 0,
        ...
      }
    ],
    "ships_in_sector": [
      {
        "ship_id": 2,
        "character_name": "Other Player",
        ...
      }
    ],
    "holds": {
      "max": 100,
      "used": 50,
      "available": 50
    },
    "is_starbase_sector": false
  }
}
```

---

#### POST `/api/v1/game/move/:sector`

Move to a new sector.

**Headers:**
- `Authorization: Bearer <token>`

**URL Parameters:**
- `sector` - Destination sector ID

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "ship": { ... },
    "movement": {
      "sector": 2,
      "turns_used": 1,
      "mine_result": null,
      "fighter_result": null
    }
  }
}
```

**If mines are hit:**
```json
{
  "success": true,
  "data": {
    "ship": { ... },
    "movement": {
      "sector": 2,
      "turns_used": 1,
      "mine_result": {
        "hit": true,
        "damage": 50,
        "message": "You hit a mine!"
      },
      "fighter_result": null
    }
  }
}
```

**Error Responses:**
- `400` - Not enough turns, sectors not linked, or ship destroyed
- `401` - Authentication required

---

#### GET `/api/v1/game/scan`

Get detailed sector scan information.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "ship": { ... },
    "sector": { ... },
    "links": [ ... ],
    "planets": [ ... ],
    "ships_in_sector": [ ... ],
    "defenses": [
      {
        "defence_id": 1,
        "defence_type": "M",
        "quantity": 10,
        "character_name": "Defender Name"
      }
    ]
  }
}
```

---

#### GET `/api/v1/game/status`

Get ship status and statistics.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "ship": { ... },
    "planets": [ ... ],
    "capacities": {
      "holds": 100,
      "energy": 500,
      "fighters": 100,
      "torps": 100
    },
    "score": 5000
  }
}
```

---

#### GET `/api/v1/game/planet/:id`

Get planet information.

**Headers:**
- `Authorization: Bearer <token>`

**URL Parameters:**
- `id` - Planet ID

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "planet": {
      "planet_id": 1,
      "planet_name": "Earth",
      "sector_id": 1,
      "owner": 1,
      ...
    },
    "owner_name": "Player Name",
    "is_owner": true,
    "is_on_planet": false
  }
}
```

**Error Responses:**
- `400` - Wrong sector
- `404` - Planet not found

---

#### POST `/api/v1/game/land/:id`

Land on a planet.

**Headers:**
- `Authorization: Bearer <token>`

**URL Parameters:**
- `id` - Planet ID

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Landed on planet successfully",
  "data": {
    "ship": { ... },
    "planet": { ... }
  }
}
```

**Error Responses:**
- `400` - Invalid planet or wrong sector
- `403` - Planet owned by another player

---

#### POST `/api/v1/game/leave`

Leave current planet.

**Headers:**
- `Authorization: Bearer <token>`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Left planet successfully",
  "data": {
    "ship": { ... }
  }
}
```

**Error Responses:**
- `400` - Not on a planet

---

## Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": { ... }
  }
}
```

### Common Error Codes

- `UNAUTHORIZED` (401) - Authentication required or invalid token
- `FORBIDDEN` (403) - Access denied
- `NOT_FOUND` (404) - Resource not found
- `VALIDATION_ERROR` (422) - Input validation failed
- `INSUFFICIENT_TURNS` (400) - Not enough turns
- `SECTORS_NOT_LINKED` (400) - Sectors are not connected
- `SHIP_DESTROYED` (400/403) - Ship has been destroyed

---

## CORS

The API supports CORS for cross-origin requests. Preflight OPTIONS requests are automatically handled.

---

## Rate Limiting

(To be implemented) API requests may be rate-limited in the future to prevent abuse.

---

## Example Usage

### Swift/iOS Example

```swift
import Foundation

class APIService {
    private let baseURL = "https://yourdomain.com/api/v1"
    private var token: String?
    
    func login(email: String, password: String) async throws -> AuthResponse {
        let url = URL(string: "\(baseURL)/auth/login")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body = ["email": email, "password": password]
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        let (data, _) = try await URLSession.shared.data(for: request)
        let response = try JSONDecoder().decode(AuthResponse.self, from: data)
        
        self.token = response.data.token
        return response
    }
    
    func getMain() async throws -> MainResponse {
        guard let token = token else {
            throw APIError.unauthorized
        }
        
        let url = URL(string: "\(baseURL)/game/main")!
        var request = URLRequest(url: url)
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        
        let (data, _) = try await URLSession.shared.data(for: request)
        return try JSONDecoder().decode(MainResponse.self, from: data)
    }
}
```

### cURL Examples

**Login:**
```bash
curl -X POST https://yourdomain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"player@example.com","password":"password123"}'
```

**Get Main Screen:**
```bash
curl -X GET https://yourdomain.com/api/v1/game/main \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Move to Sector 2:**
```bash
curl -X POST https://yourdomain.com/api/v1/game/move/2 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Notes

- All timestamps are in ISO 8601 format (YYYY-MM-DD HH:MM:SS)
- All numeric IDs are integers
- Boolean values are true/false (not 1/0)
- The API is versioned (v1) - future versions may introduce breaking changes
- Always check the `success` field in responses before accessing `data`
- Store tokens securely (iOS Keychain recommended)

---

## Future Endpoints

Additional endpoints for:
- Port trading
- Combat actions
- Planet management
- Messages
- Teams
- Rankings
- Upgrades
- IBank operations

These will be added in future updates.

