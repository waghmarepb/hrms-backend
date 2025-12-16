# Render.yaml Configuration Fixes

## ✅ Issues Fixed

### 1. APP_KEY Configuration Error
**Error**: `cannot simultaneously specify fields generateValue and sync`

**Problem**: The APP_KEY variable had both `generateValue: true` and `sync: false`, which are mutually exclusive.

**Fix**: Removed `sync: false` from APP_KEY. Render will auto-generate the key.

```yaml
# Before (❌ WRONG)
- key: APP_KEY
  generateValue: true
  sync: false

# After (✅ CORRECT)
- key: APP_KEY
  generateValue: true
```

### 2. Invalid Runtime Error
**Error**: `invalid runtime php`

**Problem**: Render doesn't have a native `php` runtime identifier.

**Fix**: Changed to `runtime: docker` and added Dockerfile for proper PHP environment.

```yaml
# Before (❌ WRONG)
env: php

# After (✅ CORRECT)
runtime: docker
dockerfilePath: ./Dockerfile
dockerContext: .
```

## 📁 New Files Created

### 1. **Dockerfile**
A proper Docker configuration for PHP 8.1 with all required extensions:
- PHP 8.1 CLI
- MySQL PDO support
- Composer
- All necessary PHP extensions
- Automated startup script

### 2. **.dockerignore**
Optimizes Docker build by excluding unnecessary files:
- Documentation
- Tests
- IDE files
- Development files
- Git files

## 🔧 Updated Configuration

### render.yaml Now Uses:
```yaml
services:
  - type: web
    name: hrms-backend
    runtime: docker              # ✅ Valid runtime
    dockerfilePath: ./Dockerfile # ✅ Specifies Dockerfile
    dockerContext: .             # ✅ Build context
    healthCheckPath: /api/health
    envVars:
      - key: APP_KEY
        generateValue: true      # ✅ No sync conflict
```

## 🚀 Deployment Approaches

### Approach 1: Docker (Current - Recommended)
✅ **Pros**:
- Full control over environment
- Consistent across development and production
- Easier dependency management
- Better for complex PHP applications

❌ **Cons**:
- Slightly longer build times
- Larger deployment size

**Files needed**:
- `render.yaml` (updated)
- `Dockerfile` (new)
- `.dockerignore` (new)

### Approach 2: Native Build (Alternative)
If you prefer not to use Docker, you can use native build commands:

```yaml
services:
  - type: web
    name: hrms-backend
    # NO runtime specified - Render detects from commands
    buildCommand: bash build.sh
    startCommand: php -S 0.0.0.0:$PORT -t public
    healthCheckPath: /api/health
```

This requires Render to have PHP available, which may vary by plan.

## 🎯 What Happens During Deployment

### Docker Build Process:
1. **Build Stage**:
   - Pulls PHP 8.1 CLI image
   - Installs system dependencies
   - Installs PHP extensions (MySQL PDO, etc.)
   - Copies application code
   - Runs `composer install`
   - Sets permissions

2. **Runtime Stage**:
   - Runs database migrations
   - Caches Laravel configuration
   - Creates storage symlink
   - Generates API documentation
   - Starts PHP server on configured PORT

## ✅ Verification Steps

After deployment, verify:

1. **Service Status**: Should show "Live" in Render dashboard
2. **Health Check**: Visit `https://your-app.onrender.com/api/health`
3. **Expected Response**:
   ```json
   {
     "success": true,
     "message": "API is running",
     "version": "1.0.0"
   }
   ```

## 🐛 Troubleshooting

### If Build Fails:
- Check Render build logs for specific errors
- Verify Dockerfile syntax
- Ensure all required files are in repository

### If Container Won't Start:
- Check runtime logs in Render dashboard
- Verify database connection settings
- Ensure PORT environment variable is being used
- Check PHP errors in logs

### If Migrations Fail:
- Verify database credentials in environment variables
- Ensure database is accessible
- Check migration files for errors

## 📋 Environment Variables Still Required

You still need to set these in Render dashboard:

```bash
# Required
APP_URL=https://your-app.onrender.com
DB_HOST=your-db-host.onrender.com
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password  # Mark as SECRET
SANCTUM_STATEFUL_DOMAINS=your-frontend.com
SESSION_DOMAIN=.your-frontend.com

# Optional
FRONTEND_URL=https://your-frontend.com
```

## 🔄 Next Steps

1. **Commit Changes**:
   ```bash
   git add render.yaml Dockerfile .dockerignore
   git commit -m "Fix Render configuration and add Docker support"
   git push
   ```

2. **Deploy on Render**:
   - Follow `RENDER_QUICK_START.md`
   - The updated configuration will be used automatically

3. **Monitor Deployment**:
   - Watch build logs
   - Check for successful Docker build
   - Verify container starts properly

## 📚 Additional Resources

- [Render Docker Documentation](https://render.com/docs/docker)
- [Render YAML Reference](https://render.com/docs/yaml-spec)
- [PHP on Render](https://render.com/docs/deploy-php)

---

**Configuration Status**: ✅ Fixed and Ready for Deployment

**Last Updated**: December 2025

