# Render Deployment Guide for HRMS Backend

This guide will help you deploy the Laravel HRMS backend to Render.com.

## Prerequisites

- A [Render account](https://render.com) (free tier available)
- A MySQL database (can use Render's managed database or external service)
- Git repository with your code pushed to GitHub/GitLab/Bitbucket

## Quick Deployment Steps

### 1. Prepare Your Repository

Ensure these files are in your repository:
- ✅ `render.yaml` - Infrastructure configuration
- ✅ `build.sh` - Build script
- ✅ `.env.render.example` - Environment variables reference
- ✅ `.gitignore` - Excludes sensitive files

### 2. Create a Database (Choose One Option)

#### Option A: Render Managed MySQL Database
1. Go to Render Dashboard
2. Click "New +" → "MySQL"
3. Choose a name: `hrms-database`
4. Select region and plan (free tier available)
5. Click "Create Database"
6. Save the connection details shown

#### Option B: External Database
Use any MySQL-compatible database (PlanetScale, AWS RDS, DigitalOcean, etc.)

### 3. Create Web Service on Render

1. Go to Render Dashboard
2. Click "New +" → "Web Service"
3. Connect your Git repository
4. Configure the service:
   - **Name**: `hrms-backend`
   - **Region**: Choose closest to your users
   - **Branch**: `main` (or your production branch)
   - **Runtime**: PHP
   - **Build Command**: `bash build.sh`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`

### 4. Configure Environment Variables

In the Render dashboard, go to your service → "Environment" and add these variables:

#### Required Variables:
```bash
APP_NAME=HRMS Backend
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com  # Update after deployment

# Database (from your database service)
DB_CONNECTION=mysql
DB_HOST=your-db-host.onrender.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password  # Mark as SECRET

# Sanctum (your frontend domain)
SANCTUM_STATEFUL_DOMAINS=your-frontend.com
SESSION_DOMAIN=.your-frontend.com

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### Optional Variables:
- **APP_KEY**: If not auto-generated, run `php artisan key:generate --show` locally and paste the result
- **Mail settings**: If you need email functionality
- **Redis**: If you add a Redis service for caching
- **AWS S3**: If you need cloud file storage

### 5. Deploy

1. Click "Create Web Service"
2. Render will automatically:
   - Install dependencies
   - Run migrations
   - Build and deploy your app
3. Monitor the deployment logs for any issues

### 6. Post-Deployment Setup

After successful deployment:

1. **Update APP_URL**: Set it to your actual Render URL in environment variables
2. **Test the API**: Visit `https://your-app.onrender.com/api/health`
3. **Update CORS settings**: Ensure your frontend domain is allowed in `config/cors.php`
4. **SSL**: Automatically provided by Render (free)

## Database Management

### Run Migrations Manually
```bash
# In Render Shell (Dashboard > Your Service > Shell)
php artisan migrate --force
```

### Seed Database
```bash
php artisan db:seed --force
```

### Create Admin User
```bash
php artisan tinker
# Then run your user creation code
```

## Monitoring & Logs

- **Logs**: Dashboard > Your Service > Logs
- **Metrics**: Dashboard > Your Service > Metrics
- **Shell Access**: Dashboard > Your Service > Shell

## Troubleshooting

### Build Fails
- Check build logs for specific errors
- Ensure `composer.json` is valid
- Verify PHP version compatibility (PHP ^8.0)

### Migration Fails
- Verify database credentials
- Check if database is accessible
- Ensure database exists

### 500 Errors
- Check application logs in Render dashboard
- Verify `APP_KEY` is set
- Ensure all required environment variables are configured
- Check `storage/logs/laravel.log` via Shell

### Storage Permissions
- Build script sets permissions automatically
- If issues persist, check `build.sh` execution

### CORS Errors
- Update `SANCTUM_STATEFUL_DOMAINS` with your frontend domain
- Check `config/cors.php` configuration
- Ensure `APP_URL` is correctly set

## Scaling & Performance

### Free Tier Limitations
- Spins down after 15 minutes of inactivity
- First request after spin-down will be slow (~30 seconds)

### Upgrade Options
- **Starter Plan ($7/month)**: Always-on service
- **Redis**: Add for better session/cache performance
- **Managed Database**: Better performance and backups

## Automatic Deployments

Render automatically deploys when you push to your configured branch:
1. Push to GitHub/GitLab/Bitbucket
2. Render detects changes
3. Runs build script
4. Deploys new version
5. Zero-downtime deployment

## Custom Domain

1. Go to Service Settings → Custom Domain
2. Add your domain (e.g., `api.yourdomain.com`)
3. Update DNS records as instructed
4. SSL certificate is automatically provisioned

## Rollback

If a deployment fails:
1. Go to Dashboard > Your Service > Events
2. Click on a previous successful deployment
3. Click "Rollback to this version"

## Health Check

The app includes a health check endpoint at `/api/health` that Render uses to verify your service is running.

## Support

- [Render Documentation](https://render.com/docs)
- [Laravel Deployment Docs](https://laravel.com/docs/9.x/deployment)
- Check Render community forum for common issues

## Cost Estimation

- **Free Tier**: $0/month (with limitations)
- **Starter Web Service**: $7/month
- **Starter MySQL**: $7/month
- **Redis**: $10/month

**Recommended for Production**: Starter Web Service + Starter MySQL = $14/month

## Next Steps

1. Deploy your frontend application
2. Update CORS and Sanctum settings with frontend URL
3. Set up monitoring and alerts
4. Configure backup strategy for database
5. Add custom domain
6. Enable automatic deployments

---

**Need Help?** Check the troubleshooting section or reach out to Render support.

