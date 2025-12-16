# HRMS Backend API

A comprehensive Human Resource Management System (HRMS) backend built with Laravel 9, featuring complete HR, payroll, accounting, and asset management modules.

## 🚀 Features

### Core HR Modules
- **Employee Management** - Complete employee lifecycle management
- **Department Management** - Organizational structure and hierarchy
- **Leave Management** - Leave applications, approvals, and tracking
- **Attendance** - Clock in/out, attendance reports
- **Payroll** - Salary processing, payslips, tax calculations
- **Recruitment** - Job postings, applications, candidate tracking
- **Notices** - Company-wide announcements and notifications
- **Awards** - Employee recognition and awards management

### Financial Modules
- **Accounts** - Complete double-entry accounting system
  - Chart of Accounts (COA)
  - Vouchers (Debit, Credit, Contra, Journal)
  - General Ledger, Cash Book, Bank Book
  - Financial Reports (Trial Balance, P&L, Balance Sheet, Cash Flow)
- **Expense Management** - Track and categorize expenses
- **Income Management** - Revenue tracking and reporting
- **Loan Management** - Employee loans and installment tracking
- **Tax Management** - Tax brackets, calculations, and collections
- **Bank Management** - Bank account management

### Asset Management
- **Asset Types** - Categorize company assets
- **Asset Tracking** - Track equipment, vehicles, and other assets
- **Asset Assignment** - Assign assets to employees
- **Asset History** - Complete audit trail of asset movements

### Additional Features
- **Template Management** - Dynamic document templates
- **RESTful API** - Complete REST API with Swagger documentation
- **Authentication** - Laravel Sanctum token-based authentication
- **CORS Support** - Configured for frontend integration

## 📋 Requirements

- PHP ^8.0
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Laravel 9.x

## 🛠️ Local Development Setup

1. **Clone the repository**
```bash
git clone <your-repo-url>
cd new-backend
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Update database configuration in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms
DB_USERNAME=root
DB_PASSWORD=
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Start development server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 🌐 Render Deployment

This application is ready for deployment to Render.com. See detailed guides:

- **[Quick Deployment Guide](RENDER_DEPLOYMENT_GUIDE.md)** - Complete step-by-step deployment instructions
- **[Environment Variables](RENDER_ENV_VARIABLES.md)** - All environment variables explained
- **[Deployment Checklist](DEPLOYMENT_CHECKLIST.md)** - Comprehensive deployment checklist

### Quick Deploy to Render

1. Push code to GitHub/GitLab/Bitbucket
2. Create a new Web Service on Render
3. Connect your repository
4. Configure environment variables (see `RENDER_ENV_VARIABLES.md`)
5. Deploy!

Render will automatically:
- Install dependencies
- Run migrations
- Build and deploy your application

## 📚 API Documentation

Interactive API documentation is available via Swagger UI:

- **Local**: `http://localhost:8000/api/documentation`
- **Production**: `https://your-app.onrender.com/api/documentation`

### Health Check
```bash
GET /api/health
```

### Authentication
```bash
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET /api/v1/auth/me
```

All API endpoints are prefixed with `/api/v1/` and require authentication via Sanctum tokens (except login and public job application endpoints).

## 🔐 Authentication

This API uses Laravel Sanctum for token-based authentication.

1. **Login** to get a token:
```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

2. **Use the token** in subsequent requests:
```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

## 🗂️ Project Structure

```
new-backend/
├── app/
│   ├── Http/Controllers/Api/V1/  # API Controllers
│   ├── Models/                    # Eloquent Models
│   └── ...
├── config/                        # Configuration files
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
├── routes/
│   └── api.php                    # API routes
├── storage/                       # File storage
├── render.yaml                    # Render deployment config
├── build.sh                       # Build script for Render
└── Procfile                       # Process file
```

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

## 📝 Available Modules

### HR Management
- Employees, Departments, Positions
- Leave Management with approval workflow
- Attendance tracking with clock in/out
- Payroll generation and processing
- Recruitment and job applications
- Company notices and announcements
- Employee awards and recognition

### Accounting
- Chart of Accounts (hierarchical)
- Voucher entry (Debit, Credit, Contra, Journal)
- General Ledger, Cash Book, Bank Book
- Trial Balance, P&L Statement, Balance Sheet, Cash Flow

### Financial Operations
- Expense tracking with categories
- Income tracking with categories
- Employee loan management with installments
- Tax setup and collection tracking
- Bank account management

### Asset Management
- Asset type categorization
- Asset registration and tracking
- Asset assignment to employees
- Asset return and history tracking

### Templates
- Dynamic template management
- Template rendering with variables

## 🔧 Configuration

### CORS Configuration
Update `config/cors.php` to add your frontend domain:
```php
'allowed_origins' => array_filter([
    'http://localhost:3000',
    env('FRONTEND_URL'),
]),
```

### Sanctum Configuration
Update `.env` with your frontend domain:
```env
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

## 🚀 Deployment Files

- `render.yaml` - Infrastructure as code for Render
- `build.sh` - Automated build script
- `Procfile` - Process definition
- `.renderignore` - Files excluded from deployment
- `RENDER_DEPLOYMENT_GUIDE.md` - Comprehensive deployment guide
- `RENDER_ENV_VARIABLES.md` - Environment variables reference
- `DEPLOYMENT_CHECKLIST.md` - Step-by-step deployment checklist

## 📊 Database Schema

The application includes migrations for all modules. Run migrations to create:
- Users and authentication tables
- HR management tables (employees, departments, leaves, attendance, payroll)
- Recruitment tables (jobs, applications)
- Accounting tables (COA, transactions, vouchers)
- Financial tables (expenses, incomes, loans, taxes)
- Asset management tables
- Bank and award tables
- Template management tables

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For deployment issues:
- Check [RENDER_DEPLOYMENT_GUIDE.md](RENDER_DEPLOYMENT_GUIDE.md)
- Review [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
- Check Render logs in the dashboard

For application issues:
- Check `storage/logs/laravel.log`
- Review API documentation at `/api/documentation`

## 🔗 Links

- [Laravel Documentation](https://laravel.com/docs/9.x)
- [Render Documentation](https://render.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum)
- [Swagger/OpenAPI](https://swagger.io/)

---

**Built with Laravel 9** | **Ready for Render Deployment** | **Production Ready**
