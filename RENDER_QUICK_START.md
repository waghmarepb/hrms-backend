# Render Quick Start Guide

Get your HRMS backend deployed to Render in 10 minutes!

## Prerequisites

✅ Git repository (GitHub/GitLab/Bitbucket)  
✅ Render account (free at [render.com](https://render.com))  
✅ Code pushed to repository

## Step 1: Create Database (2 minutes)

### Option A: Render Managed MySQL
1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click **"New +"** → **"MySQL"**
3. Name: `hrms-database`
4. Region: Choose closest to you
5. Plan: **Free** (or Starter for production)
6. Click **"Create Database"**
7. **Save the connection details** shown after creation

### Option B: Use External Database
Skip this step if you have an existing MySQL database.

## Step 2: Create Web Service (3 minutes)

1. In Render Dashboard, click **"New +"** → **"Web Service"**
2. Click **"Connect a repository"**
3. Select your Git provider and authorize
4. Choose your repository
5. Configure:
   - **Name**: `hrms-backend`
   - **Region**: Same as database
   - **Branch**: `main`
   - **Runtime**: **PHP**
   - **Build Command**: `bash build.sh`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`
   - **Plan**: **Free** (or Starter for production)

## Step 3: Set Environment Variables (5 minutes)

Click **"Advanced"** → **"Add Environment Variable"** and add these:

### Required Variables (Copy & Paste)

```bash
# Application
APP_NAME=HRMS Backend
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Database (from Step 1)
DB_CONNECTION=mysql
DB_HOST=your-db-host.onrender.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Sanctum (your frontend domain)
SANCTUM_STATEFUL_DOMAINS=your-frontend.com
SESSION_DOMAIN=.your-frontend.com

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Important Notes:
- For `DB_*` values, use the **Internal Connection String** from your Render database
- Mark `DB_PASSWORD` as **SECRET** (check the secret checkbox)
- Leave `APP_KEY` blank - it will auto-generate
- Update `APP_URL` after deployment with your actual Render URL
- Update `SANCTUM_STATEFUL_DOMAINS` with your frontend domain

## Step 4: Deploy! (2-5 minutes)

1. Click **"Create Web Service"**
2. Render will automatically:
   - ✅ Install Composer dependencies
   - ✅ Generate application key
   - ✅ Run database migrations
   - ✅ Cache configuration
   - ✅ Start your application

3. Watch the build logs for progress
4. Wait for status to show **"Live"** (green)

## Step 5: Verify Deployment (1 minute)

1. Copy your service URL (e.g., `https://hrms-backend.onrender.com`)
2. Visit: `https://your-app.onrender.com/api/health`
3. You should see:
```json
{
  "success": true,
  "message": "API is running",
  "version": "1.0.0"
}
```

## Step 6: Update Configuration (1 minute)

1. Go to **Environment** tab in your service
2. Update `APP_URL` with your actual Render URL
3. Service will auto-redeploy (takes ~2 minutes)

## 🎉 Done!

Your HRMS backend is now live!

### Next Steps:

1. **Test API**: Visit `/api/documentation` for Swagger UI
2. **Create Admin User**: Use Render Shell to create your first user
3. **Connect Frontend**: Update your frontend to use the new API URL
4. **Custom Domain** (Optional): Add your own domain in service settings

## 📱 Quick Commands

### Access Render Shell
Dashboard → Your Service → **Shell**

### Create Admin User
```bash
php artisan tinker
# Then run your user creation code
```

### Run Migrations Manually
```bash
php artisan migrate --force
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

### Check Logs
Dashboard → Your Service → **Logs**

## 🐛 Troubleshooting

### Build Failed?
- Check build logs for specific error
- Verify `composer.json` is valid
- Ensure PHP 8.0+ compatibility

### Can't Connect to Database?
- Verify database credentials
- Use **Internal Connection String** from Render database
- Ensure database is running

### 500 Error?
- Check logs in Render dashboard
- Verify `APP_KEY` is set
- Ensure all environment variables are configured

### CORS Errors?
- Update `SANCTUM_STATEFUL_DOMAINS` with your frontend domain
- Update `FRONTEND_URL` environment variable
- Ensure `APP_URL` is correct

## 💡 Tips

### Free Tier Limitations
- Service spins down after 15 minutes of inactivity
- First request after spin-down takes ~30 seconds
- Upgrade to Starter ($7/month) for always-on service

### Improve Performance
- Upgrade to Starter plan
- Add Redis for caching ($10/month)
- Use Render's managed database

### Auto-Deploy
Render automatically deploys when you push to your configured branch!

### Monitoring
- Check **Metrics** tab for performance data
- Set up alerts (paid plans)
- Monitor logs regularly

## 📚 More Resources

- **[Full Deployment Guide](RENDER_DEPLOYMENT_GUIDE.md)** - Comprehensive instructions
- **[Environment Variables](RENDER_ENV_VARIABLES.md)** - All variables explained
- **[Deployment Checklist](DEPLOYMENT_CHECKLIST.md)** - Complete checklist

## 🆘 Need Help?

1. Check the [full deployment guide](RENDER_DEPLOYMENT_GUIDE.md)
2. Review [Render documentation](https://render.com/docs)
3. Check [Render community forum](https://community.render.com)

---

**Total Time**: ~10-15 minutes  
**Cost**: Free tier available  
**Difficulty**: Easy ⭐⭐☆☆☆

Happy Deploying! 🚀

