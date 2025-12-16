# HRMS Backend - Core PHP

This is a core PHP implementation of the HRMS (Human Resource Management System) API, migrated from Laravel.

## Features

- ✅ RESTful API with JSON responses
- ✅ Token-based authentication (compatible with Laravel Sanctum tokens)
- ✅ PDO database wrapper with prepared statements
- ✅ Custom routing with middleware support
- ✅ Request validation
- ✅ CORS support
- ✅ MD5 legacy password support + bcrypt

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Apache with mod_rewrite enabled
- PDO MySQL extension

## Installation

1. **Copy environment file**
```bash
cp .env.example .env
```

2. **Configure database in .env**
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. **Point Apache to public directory**
   - Document root should be: `backend-php/public/`
   - Or access via: `http://localhost/backend-php/public/`

4. **Ensure mod_rewrite is enabled**
```bash
# On Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## API Endpoints

### Authentication
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout (requires auth)
- `GET /api/v1/auth/me` - Get current user (requires auth)

### Employees
- `GET /api/v1/employees` - List all employees
- `GET /api/v1/employees/{id}` - Get employee details
- `POST /api/v1/employees` - Create employee
- `PUT /api/v1/employees/{id}` - Update employee
- `DELETE /api/v1/employees/{id}` - Delete employee

### Departments
- `GET /api/v1/departments` - List all departments
- `GET /api/v1/departments/{id}` - Get department details
- `POST /api/v1/departments` - Create department
- `PUT /api/v1/departments/{id}` - Update department
- `DELETE /api/v1/departments/{id}` - Delete department

### Health Check
- `GET /health` - API health status

## Usage Example

### Login
```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "token": "your-access-token",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

### Get Employees (with authentication)
```bash
curl -X GET http://localhost/api/v1/employees \
  -H "Authorization: Bearer your-access-token"
```

## Project Structure

```
backend-php/
├── app/
│   ├── Controllers/      # API controllers
│   ├── Models/          # Database models
│   ├── Middleware/      # Custom middleware
│   └── Helpers/         # Helper functions
├── config/              # Configuration files
├── core/                # Core framework classes
├── public/              # Web root (index.php)
├── routes/              # API routes definition
└── storage/
    └── logs/            # Application logs
```

## Core Classes

### Database
PDO wrapper with query builder methods:
- `select()` - Execute SELECT query
- `insert()` - Insert data
- `update()` - Update data
- `delete()` - Delete data
- Transaction support

### Router
Request routing with middleware:
- Route registration (GET, POST, PUT, DELETE)
- Route parameters `{id}`
- Route grouping with prefix
- Middleware support

### Auth
Token-based authentication:
- Compatible with Laravel Sanctum tokens
- Token generation and validation
- User session management

### Request
HTTP request handling:
- `all()` - Get all input data
- `input($key)` - Get specific input
- `bearerToken()` - Extract Bearer token
- `user()` - Get authenticated user

### Response
JSON response formatting:
- `json()` - Send JSON response
- `success()` - Success response
- `error()` - Error response
- `unauthorized()`, `notFound()`, etc.

### Validator
Input validation:
- Rule-based validation
- Validation rules: required, email, min, max, numeric, integer, date, in
- Automatic error responses

## Migration Status

### ✅ Completed Modules
- Core Foundation (Database, Router, Request, Response, Auth)
- Authentication (Login, Logout, User info)
- Employee Management (Full CRUD)
- Department Management (Full CRUD)

### 🔄 In Progress
- Leave Management
- Attendance Tracking
- Payroll System

### ⏳ Pending Modules
- Recruitment
- Notices
- Reports
- Accounting (COA, Vouchers, Ledgers)
- Financial Reports
- Expenses & Income
- Loans
- Assets
- Banks
- Tax Management
- Awards
- Templates

## Development

### Error Logging
Errors are logged to `storage/logs/php-errors.log`

### Debug Mode
Set in .env:
```
APP_DEBUG=true
```

This will show detailed error messages in API responses.

## Notes

- This implementation maintains compatibility with the existing Laravel HRMS database
- Same table structure, column names, and relationships
- Tokens created by Laravel Sanctum are compatible with this implementation
- Supports both MD5 legacy passwords and bcrypt passwords for backward compatibility

## License

MIT License

