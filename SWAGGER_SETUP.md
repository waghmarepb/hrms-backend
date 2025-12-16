# Swagger API Documentation Setup

## Overview
Swagger/OpenAPI documentation has been successfully integrated into the HRMS backend API. This provides interactive API documentation that allows you to explore and test all API endpoints.

## Installation

1. **Install Dependencies**
   ```bash
   cd C:\xampp\htdocs\hrms\new-backend
   composer install
   ```

2. **Generate Swagger Documentation**
   ```bash
   php public/swagger-generate.php
   ```
   This will scan your controllers and generate `public/swagger.json`

## Accessing Documentation

### Web UI (Swagger UI)
Open in your browser:
```
http://localhost/hrms/new-backend/public/swagger.php
```

### JSON Specification
Direct access to OpenAPI spec:
```
http://localhost/hrms/new-backend/public/swagger.json
```

## What's Been Added

### 1. **Dependencies** (`composer.json`)
   - Added `zircote/swagger-php` v4.7

### 2. **Configuration** (`config/swagger.php`)
   - API title, description, version
   - Server URLs
   - Scan paths for annotations
   - Output path for generated JSON

### 3. **Generator Script** (`public/swagger-generate.php`)
   - Scans controllers for OpenAPI annotations
   - Generates swagger.json file
   - Run this after adding/updating annotations

### 4. **Swagger UI Viewer** (`public/swagger.php`)
   - Beautiful interactive documentation interface
   - Test endpoints directly from browser
   - Authentication support

### 5. **OpenAPI Annotations**
   Base annotations added to:
   - `public/index.php` - API info, security schemes, tags
   - `AuthController.php` - Login, logout, user profile
   - `EmployeeController.php` - Full CRUD operations
   - `DepartmentController.php` - List departments
   - `AttendanceController.php` - Attendance tracking

## Using Authentication in Swagger UI

1. Login via `/api/v1/auth/login` endpoint
2. Copy the received token
3. Click "Authorize" button (top right)
4. Enter: `Bearer YOUR_TOKEN_HERE`
5. Click "Authorize" and then "Close"
6. All protected endpoints will now include the token

## Adding Documentation to New Endpoints

Add OpenAPI annotations above your controller methods:

```php
/**
 * @OA\Get(
 *     path="/api/v1/your-endpoint",
 *     tags={"YourTag"},
 *     summary="Endpoint description",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success response",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */
public function yourMethod() {
    // ...
}
```

After adding annotations, regenerate:
```bash
php public/swagger-generate.php
```

## Annotation Examples

### GET with Path Parameter
```php
/**
 * @OA\Get(
 *     path="/api/v1/items/{id}",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     )
 * )
 */
```

### POST with Request Body
```php
/**
 * @OA\Post(
 *     path="/api/v1/items",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string")
 *         )
 *     )
 * )
 */
```

### Query Parameters
```php
/**
 * @OA\Get(
 *     path="/api/v1/items",
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="string")
 *     )
 * )
 */
```

## Available Tags
All modules are tagged for organization:
- Authentication
- Employees
- Departments
- Attendance
- Leave
- Payroll
- Recruitment
- Reports
- Expenses
- Income
- Loans
- Assets
- Banks
- Taxes
- Awards
- Accounting
- Financial Reports

## Next Steps

### Recommended: Add Documentation to Remaining Controllers
1. LeaveController
2. PayrollController
3. NoticeController
4. RecruitmentController
5. ReportController
6. ExpenseController
7. IncomeController
8. LoanController
9. AssetController
10. BankController
11. TaxController
12. AwardController
13. ChartOfAccountController
14. VoucherController
15. LedgerController
16. FinancialReportController

Follow the pattern used in `AuthController.php` and `EmployeeController.php`.

## Troubleshooting

### Swagger.json not generating?
- Ensure composer dependencies are installed
- Check PHP errors in storage/logs/php-errors.log
- Verify annotations syntax is correct

### 404 on swagger.php?
- Check XAMPP is running
- Verify file path is correct
- Ensure .htaccess allows PHP files

### Endpoints not showing?
- Regenerate swagger.json
- Clear browser cache
- Check annotation syntax

## Documentation Resources
- [Swagger-PHP Documentation](https://zircote.github.io/swagger-php/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)

## Support
For issues or questions about the API documentation, refer to the examples in the annotated controllers.

