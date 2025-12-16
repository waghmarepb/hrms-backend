# Render Deployment Checklist

Use this checklist to ensure a smooth deployment to Render.

## Pre-Deployment Checklist

### 1. Code Preparation
- [ ] All code committed to Git repository
- [ ] Repository pushed to GitHub/GitLab/Bitbucket
- [ ] Branch for deployment identified (e.g., `main` or `production`)
- [ ] No sensitive data in repository (check `.env` is in `.gitignore`)

### 2. Required Files Present
- [ ] `render.yaml` - Infrastructure configuration
- [ ] `build.sh` - Build script (executable)
- [ ] `Procfile` - Process file
- [ ] `.renderignore` - Files to exclude from deployment
- [ ] `composer.json` - PHP dependencies
- [ ] `composer.lock` - Locked dependency versions

### 3. Configuration Files
- [ ] `config/cors.php` - Updated with environment variable support
- [ ] `config/database.php` - MySQL configuration present
- [ ] `routes/api.php` - Health check endpoint exists (`/api/health`)

### 4. Database Preparation
- [ ] Database service created (Render or external)
- [ ] Database credentials available
- [ ] Database name, username, password noted
- [ ] Connection string tested (if possible)

## Render Setup Checklist

### 1. Create Web Service
- [ ] Logged into [Render Dashboard](https://dashboard.render.com)
- [ ] Clicked "New +" → "Web Service"
- [ ] Connected Git repository
- [ ] Selected correct repository
- [ ] Selected deployment branch

### 2. Service Configuration
- [ ] **Name**: Set to `hrms-backend` (or your preferred name)
- [ ] **Region**: Selected closest to users
- [ ] **Branch**: Set to `main` or production branch
- [ ] **Runtime**: Set to `PHP`
- [ ] **Build Command**: Set to `bash build.sh`
- [ ] **Start Command**: Set to `php -S 0.0.0.0:$PORT -t public`

### 3. Environment Variables - Required
- [ ] `APP_NAME` = HRMS Backend
- [ ] `APP_ENV` = production
- [ ] `APP_DEBUG` = false
- [ ] `APP_KEY` = (auto-generated or from `php artisan key:generate --show`)
- [ ] `APP_URL` = (your Render URL, update after deployment)

### 4. Environment Variables - Database
- [ ] `DB_CONNECTION` = mysql
- [ ] `DB_HOST` = (your database host)
- [ ] `DB_PORT` = 3306
- [ ] `DB_DATABASE` = (your database name)
- [ ] `DB_USERNAME` = (your database username)
- [ ] `DB_PASSWORD` = (your database password) - **Mark as SECRET**

### 5. Environment Variables - Sanctum/CORS
- [ ] `SANCTUM_STATEFUL_DOMAINS` = (your frontend domain)
- [ ] `SESSION_DOMAIN` = (your frontend domain with leading dot)
- [ ] `FRONTEND_URL` = (your frontend URL without trailing slash)
- [ ] `FRONTEND_URL_WWW` = (your www frontend URL, if applicable)

### 6. Environment Variables - Cache/Session
- [ ] `CACHE_DRIVER` = file
- [ ] `SESSION_DRIVER` = file
- [ ] `QUEUE_CONNECTION` = sync
- [ ] `LOG_CHANNEL` = stack
- [ ] `LOG_LEVEL` = error

### 7. Optional Environment Variables
- [ ] Mail settings (if email functionality needed)
- [ ] Redis settings (if using Redis)
- [ ] AWS S3 settings (if using cloud storage)

## Deployment Checklist

### 1. Initial Deployment
- [ ] Clicked "Create Web Service"
- [ ] Deployment started automatically
- [ ] Watched build logs for errors
- [ ] Build completed successfully
- [ ] Service started successfully

### 2. Post-Deployment Verification
- [ ] Service shows as "Live" in dashboard
- [ ] Accessed service URL (e.g., `https://hrms-backend.onrender.com`)
- [ ] Health check endpoint works: `/api/health`
- [ ] Returns: `{"success":true,"message":"API is running","version":"1.0.0"}`

### 3. Database Verification
- [ ] Migrations ran successfully (check build logs)
- [ ] Database tables created
- [ ] Can connect to database from service

### 4. Update Configuration
- [ ] Updated `APP_URL` in environment variables with actual Render URL
- [ ] Updated `SANCTUM_STATEFUL_DOMAINS` if needed
- [ ] Service redeployed automatically after env changes

## Testing Checklist

### 1. API Endpoints
- [ ] Health check: `GET /api/health`
- [ ] Login endpoint: `POST /api/v1/auth/login`
- [ ] Protected endpoint test (with auth token)

### 2. CORS Testing
- [ ] Frontend can make requests to API
- [ ] No CORS errors in browser console
- [ ] Credentials/cookies work properly

### 3. Database Operations
- [ ] Can create records
- [ ] Can read records
- [ ] Can update records
- [ ] Can delete records

### 4. Authentication
- [ ] Can login successfully
- [ ] Token is returned
- [ ] Protected routes require authentication
- [ ] Logout works properly

## Production Readiness Checklist

### 1. Security
- [ ] `APP_DEBUG` is `false`
- [ ] `APP_ENV` is `production`
- [ ] All passwords marked as SECRET in Render
- [ ] `APP_KEY` is properly generated
- [ ] HTTPS is enabled (automatic on Render)
- [ ] Database credentials are secure

### 2. Performance
- [ ] Upgraded from Free tier (if needed for always-on service)
- [ ] Considered adding Redis for caching
- [ ] Optimized database queries
- [ ] Laravel config/route/view caching enabled (in build script)

### 3. Monitoring
- [ ] Checked Render logs for errors
- [ ] Set up log monitoring
- [ ] Verified metrics in Render dashboard
- [ ] Set up alerts (if on paid plan)

### 4. Backup & Recovery
- [ ] Database backup strategy in place
- [ ] Tested rollback procedure
- [ ] Documented recovery steps

### 5. Documentation
- [ ] API documentation accessible (Swagger at `/api/documentation`)
- [ ] Environment variables documented
- [ ] Deployment process documented
- [ ] Team members have access to Render dashboard

## Troubleshooting Checklist

### Build Failures
- [ ] Checked build logs for specific errors
- [ ] Verified `composer.json` is valid
- [ ] Confirmed PHP version compatibility (^8.0)
- [ ] Ensured `build.sh` has correct permissions

### Runtime Errors
- [ ] Checked application logs in Render dashboard
- [ ] Verified all environment variables are set
- [ ] Confirmed `APP_KEY` is generated
- [ ] Checked storage permissions

### Database Connection Issues
- [ ] Verified database credentials
- [ ] Confirmed database is running
- [ ] Checked if using internal connection string (for Render DB)
- [ ] Tested database connectivity

### CORS Errors
- [ ] Updated `SANCTUM_STATEFUL_DOMAINS`
- [ ] Checked `config/cors.php` configuration
- [ ] Verified `APP_URL` is correct
- [ ] Confirmed frontend URL is whitelisted

## Optimization Checklist (Optional)

### 1. Upgrade Plan
- [ ] Evaluated Free tier limitations
- [ ] Upgraded to Starter plan ($7/month) for always-on service
- [ ] Considered database upgrade for better performance

### 2. Add Redis
- [ ] Created Redis service in Render
- [ ] Updated environment variables for Redis
- [ ] Changed `CACHE_DRIVER` to `redis`
- [ ] Changed `SESSION_DRIVER` to `redis`

### 3. Custom Domain
- [ ] Added custom domain in Render settings
- [ ] Updated DNS records
- [ ] SSL certificate provisioned
- [ ] Updated `APP_URL` with custom domain

### 4. Auto-Deploy
- [ ] Enabled auto-deploy from Git branch
- [ ] Tested automatic deployment on push
- [ ] Verified zero-downtime deployment

## Final Checklist

- [ ] All endpoints tested and working
- [ ] Frontend successfully communicates with backend
- [ ] No errors in logs
- [ ] Performance is acceptable
- [ ] Security measures in place
- [ ] Documentation updated
- [ ] Team notified of deployment
- [ ] Monitoring set up
- [ ] Backup strategy implemented

---

## Quick Reference

### Render Dashboard URLs
- **Dashboard**: https://dashboard.render.com
- **Service Logs**: Dashboard > Your Service > Logs
- **Environment**: Dashboard > Your Service > Environment
- **Shell**: Dashboard > Your Service > Shell

### Common Commands (Render Shell)
```bash
# Check Laravel version
php artisan --version

# Run migrations
php artisan migrate --force

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate app key
php artisan key:generate --force

# Check configuration
php artisan config:show

# Run tinker
php artisan tinker
```

### Support Resources
- [Render Documentation](https://render.com/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/9.x/deployment)
- [Render Community Forum](https://community.render.com)

---

**Deployment Date**: _____________

**Deployed By**: _____________

**Service URL**: _____________

**Notes**: 
_____________________________________________
_____________________________________________
_____________________________________________

