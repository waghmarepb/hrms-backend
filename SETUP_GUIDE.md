# Core PHP Backend - Setup Guide

## ✅ Configuration Complete!

Your `.env` file has been configured with the following settings:

```
APP_ENV=production
APP_DEBUG=false

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms
DB_USERNAME=root
DB_PASSWORD=

CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://localhost:8080
```

## 🚀 Quick Start Steps

### 1. Start XAMPP Services

**Option A: Using XAMPP Control Panel**
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

**Option B: Using Command Line**
```bash
# Start Apache
c:\xampp\apache\bin\httpd.exe

# Start MySQL
c:\xampp\mysql\bin\mysqld.exe
```

### 2. Verify Database Connection

Run the test script:
```bash
cd c:\xampp\htdocs\hrms\new-backend\backend-php
c:\xampp\php\php.exe test-connection.php
```

Expected output:
```
=================================
DATABASE CONNECTION TEST
=================================

Testing database connection...
✓ Database connection successful!

Testing query execution...
✓ Query executed successfully!

Connection Details:
-------------------
Database: hrms
Server Time: 2025-12-16 14:30:00

Testing table access...
✓ Users table accessible! Found X users
✓ Employee table accessible! Found X employees

=================================
ALL TESTS PASSED! ✓
=================================
```

### 3. Access Your API

**Base URL:**
```
http://localhost/new-backend/backend-php/public/api/v1/
```

**Health Check:**
```
GET http://localhost/new-backend/backend-php/public/health
```

**Login Endpoint:**
```
POST http://localhost/new-backend/backend-php/public/api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "your_password"
}
```

## 📋 Apache Configuration (Optional)

For cleaner URLs, you can configure a virtual host:

### Create Virtual Host

1. Edit `c:\xampp\apache\conf\extra\httpd-vhosts.conf`

2. Add this configuration:

```apache
<VirtualHost *:80>
    ServerName hrms-api.local
    DocumentRoot "c:/xampp/htdocs/hrms/new-backend/backend-php/public"
    
    <Directory "c:/xampp/htdocs/hrms/new-backend/backend-php/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/hrms-api-error.log"
    CustomLog "logs/hrms-api-access.log" common
</VirtualHost>
```

3. Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator)

Add:
```
127.0.0.1 hrms-api.local
```

4. Restart Apache

5. Access API at: `http://hrms-api.local/api/v1/`

## 🧪 Testing with Postman

### Import Collection

Create a new Postman collection with these endpoints:

#### 1. Health Check
```
GET http://localhost/new-backend/backend-php/public/health
```

#### 2. Login
```
POST http://localhost/new-backend/backend-php/public/api/v1/auth/login
Content-Type: application/json

{
  "email": "your_email@example.com",
  "password": "your_password"
}
```

Save the `token` from response.

#### 3. Get Current User (Protected)
```
GET http://localhost/new-backend/backend-php/public/api/v1/auth/me
Authorization: Bearer YOUR_TOKEN_HERE
```

#### 4. Get Employees (Protected)
```
GET http://localhost/new-backend/backend-php/public/api/v1/employees
Authorization: Bearer YOUR_TOKEN_HERE
```

## 📁 Project Structure

```
backend-php/
├── .env                    ← Your database configuration
├── test-connection.php     ← Database test script
├── public/
│   └── index.php          ← Entry point
├── core/                  ← Core framework classes
├── app/
│   ├── Models/            ← 23 database models
│   ├── Controllers/       ← 19 API controllers
│   ├── Middleware/        ← Auth & CORS middleware
│   └── Helpers/           ← Helper functions
├── config/                ← Configuration files
└── routes/
    └── api.php            ← All API routes (100+ endpoints)
```

## 🔧 Troubleshooting

### Issue: "Database connection failed"
**Solution:**
1. Start XAMPP MySQL service
2. Verify database exists: `mysql -u root -e "SHOW DATABASES;"`
3. Check credentials in `.env` file

### Issue: "404 Not Found"
**Solution:**
1. Ensure Apache mod_rewrite is enabled
2. Check `.htaccess` files exist in `public/` and root
3. Verify DocumentRoot points to `public/` directory

### Issue: "CORS Error"
**Solution:**
1. Update `CORS_ALLOWED_ORIGINS` in `.env`
2. Add your frontend URL (e.g., `http://localhost:3000`)

### Issue: "Token not provided"
**Solution:**
1. Add `Authorization: Bearer YOUR_TOKEN` header
2. Ensure token is valid and not expired

## 📊 Available Modules

All 18 modules are ready to use:

1. ✅ Authentication
2. ✅ Employees
3. ✅ Departments
4. ✅ Leave Management
5. ✅ Attendance
6. ✅ Payroll
7. ✅ Notices
8. ✅ Recruitment
9. ✅ Reports
10. ✅ Expenses
11. ✅ Income
12. ✅ Loans
13. ✅ Assets
14. ✅ Banks
15. ✅ Tax
16. ✅ Awards
17. ✅ Templates
18. ✅ (Optional: Accounting)

## 🔐 Security Notes

**For Production:**
1. Change `APP_DEBUG=false` in `.env`
2. Set strong database password
3. Enable HTTPS
4. Restrict CORS origins
5. Set proper file permissions
6. Enable PHP error logging

## 📞 Support

If you encounter any issues:
1. Check `storage/logs/php-errors.log`
2. Review Apache error logs
3. Verify database connectivity
4. Check PHP version (8.0+ required)

## 🎉 You're All Set!

Your Core PHP HRMS backend is configured and ready to use!

**Next Steps:**
1. Start XAMPP services
2. Run test-connection.php
3. Test API with Postman
4. Connect your frontend

Happy coding! 🚀

