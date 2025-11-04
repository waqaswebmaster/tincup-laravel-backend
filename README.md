# TinCup Backend - PHP API

A lightweight PHP API for TinCup mobile app, designed to work on cPanel hosting.

## Features

- ✅ User authentication (register, login, logout)
- ✅ JWT token-based authentication
- ✅ User profile management
- ✅ MySQL database integration
- ✅ CORS enabled for mobile app
- ✅ Works on cPanel shared hosting

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PDO extension enabled

## Installation on cPanel

### 1. Upload Files

Upload all files to your cPanel account:
```
/home/hummelec/api.ibextools.com/tincup-laravel-backend/
```

### 2. Configure Environment

1. Copy `.env.example` to `.env`
2. Update database credentials in `.env` (already configured for your cPanel):
```env
DB_DATABASE=hummelec_tincup_db
DB_USERNAME=hummelec_tincup_user
DB_PASSWORD=5[KZllJi%Cu{M}xX
```

### 3. Database Setup

The database tables (`users` and `organizations`) are already created in your MySQL database.

### 4. Configure Subdomain

Point `api.ibextools.com` document root to:
```
/home/hummelec/api.ibextools.com/tincup-laravel-backend/public
```

### 5. Test the API

Visit: `https://api.ibextools.com/api/auth/health`

Expected response:
```json
{
  "success": true,
  "message": "Auth service is healthy",
  "timestamp": "2025-11-04T..."
}
```

## API Endpoints

### Public Endpoints

- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/refresh-token` - Refresh access token
- `GET /api/auth/health` - Health check

### Protected Endpoints (require Bearer token)

- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update user profile
- `POST /api/auth/logout` - Logout user

## Example Usage

### Register
```bash
curl -X POST https://api.ibextools.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "firstName": "John",
    "lastName": "Doe"
  }'
```

### Login
```bash
curl -X POST https://api.ibextools.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Get Profile
```bash
curl -X GET https://api.ibextools.com/api/auth/profile \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## File Structure

```
tincup-laravel-backend/
├── app/
│   ├── Controllers/
│   │   └── AuthController.php
│   └── Models/
│       └── User.php
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   └── Database.php
├── public/
│   └── index.php
├── .env.example
└── README.md
```

## Deployment via Git

```bash
git init
git add .
git commit -m "Initial commit - PHP backend"
git branch -M main
git remote add origin https://github.com/waqaswebmaster/tincup-laravel-backend.git
git push -u origin main
```

## Security Notes

- Passwords are hashed with bcrypt
- JWT tokens for authentication
- SQL injection protection via PDO prepared statements
- XSS protection via input validation
- CORS configured for mobile app access

## License

MIT
