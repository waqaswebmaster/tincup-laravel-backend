# Deployment Instructions

## Automatic Deployment via GitHub Actions

This repository automatically deploys to cPanel when you push to the `main` branch.

### First-Time Setup on cPanel

After the first deployment, SSH into cPanel and run:

```bash
cd ~/api.ibextools.com/tincup-laravel-backend
cp .env.example .env
```

The `.env` file already contains your database credentials:
- Database: hummelec_tincup_db
- User: hummelec_tincup_user
- Password: 5[KZllJi%Cu{M}xX

### Configure Subdomain

Point `api.ibextools.com` document root to:
```
/home/hummelec/api.ibextools.com/tincup-laravel-backend/public
```

### Test the API

Visit: https://api.ibextools.com/api/auth/health

Expected response:
```json
{
  "success": true,
  "message": "Auth service is healthy",
  "timestamp": "2025-11-04T..."
}
```

## Manual Deployment (if needed)

Upload all files to:
```
/home/hummelec/api.ibextools.com/tincup-laravel-backend/
```

Make sure the document root points to the `public/` folder.
