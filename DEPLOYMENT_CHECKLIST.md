# VSItoA - Deployment Checklist ✅

## 📋 Pre-Deployment Checklist

### Local Testing ✅
- [ ] Admin login works with password: `admin123`
- [ ] User login works with credentials provided
- [ ] User registration creates new accounts
- [ ] User logout clears session properly
- [ ] Admin logout clears admin session
- [ ] JWT tokens are generated correctly
- [ ] Database connections working
- [ ] No PHP errors in logs
- [ ] All routes accessible

### Database Setup ✅
- [ ] Database created: `vsitoa`
- [ ] Schema imported: `database/schema.sql`
- [ ] Admin user exists in users table
- [ ] All required tables created
- [ ] Sample data inserted
- [ ] Indexes created

### Configuration ✅
- [ ] `.env` file exists with correct values
- [ ] DB_HOST, DB_USERNAME, DB_PASSWORD set
- [ ] DB_DATABASE set to `vsitoa`
- [ ] JWT_SECRET is set to random string
- [ ] APP_ENV set appropriately
- [ ] APP_DEBUG set to false for production
- [ ] APP_BASE_PATH set correctly

### Security ✅
- [ ] Admin password changed from default
- [ ] JWT_SECRET is strong & random (32+ chars)
- [ ] HTTPS enabled (for production)
- [ ] No credentials in git repository
- [ ] .env not committed to git
- [ ] Sensitive data removed from code

---

## 🚀 Deployment to Vercel

### Step 1: Prepare Application
```bash
# 1. Clean up temporary files
rm -f migrate.php migrate_simple.php test_db.php

# 2. Update .env for production
# Set APP_ENV=production
# Set APP_DEBUG=false
# Set database to live database

# 3. Commit changes
git add -A
git commit -m "Prepare for Vercel deployment"
git push
```

### Step 2: Setup Database (PlanetScale)
```
1. Go to: https://planetscale.com
2. Create free account
3. Create new database: vsitoa
4. Get connection credentials
5. Note: host, user, password
```

### Step 3: Setup Vercel
```
1. Go to: https://vercel.com
2. Import from GitHub
3. Select vsitoa repository
4. Add environment variables:
   - DB_HOST (from PlanetScale)
   - DB_PORT (3306)
   - DB_DATABASE (vsitoa)
   - DB_USERNAME (from PlanetScale)
   - DB_PASSWORD (from PlanetScale)
   - JWT_SECRET (generate random)
5. Deploy
```

### Step 4: Initialize Database on Vercel
```bash
# After deployment, run migration on server:
# Visit: your-domain.vercel.app/migrate_simple.php

# Or run manually via SSH if available:
php migrate_simple.php
```

### Verification
- [ ] Application loads on Vercel domain
- [ ] Admin login works
- [ ] User login works
- [ ] Can register new user
- [ ] Database queries work

---

## 🌍 Deployment to Railway.app

### Step 1: Connect Repository
```
1. Go to: https://railway.app
2. Create new project
3. Connect GitHub account
4. Select vsitoa repository
```

### Step 2: Add PostgreSQL Database
```
1. Add new service: PostgreSQL
2. Get connection details
3. Copy environment variables
4. Add to Railway project variables
```

### Step 3: Configure Application
```
Create .env (or set Railway variables):
- DB_HOST
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD
- DB_PORT
- JWT_SECRET
```

### Step 4: Deploy
```
Railway automatically deploys on git push
Check deployment logs for errors
```

---

## 🆔 Deployment to Console.clever

### Step 1: Setup Account
```
1. Go to: https://console.clever-cloud.com
2. Create account
3. Create new Node.js application
```

### Step 2: Connect Repository
```
1. Add GitHub connection
2. Select vsitoa repository
3. Configure build & runtime
```

### Step 3: Add Environment Variables
```
In Console Settings:
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD
- JWT_SECRET
```

### Step 4: Deploy
```
1. Commit changes
2. Push to GitHub
3. Console automatically deploys
4. Check logs for errors
```

---

## 🔍 Post-Deployment Verification

### Test Admin Login
```bash
curl -X POST https://your-domain.com/admin/login \
  -H "Content-Type: application/json" \
  -d '{"password": "admin123"}'

# Should return:
# {"success": true, "message": "Admin login successful", "redirect": "/admin"}
```

### Test User Login
```bash
curl -X POST https://your-domain.com/login \
  -H "Content-Type: application/json" \
  -d '{"email": "worker@vsitoa.com", "password": "admin123"}'

# Should return:
# {"success": true, "message": "Login successful", "redirect": "/dashboard", "token": "..."}
```

### Check Health
```bash
curl https://your-domain.com/
# Should return home page (HTTP 200)
```

---

## 🚨 Troubleshooting Deployment

### Database Connection Fails
```bash
# 1. Verify credentials in environment variables
# 2. Check database host is accessible
# 3. Confirm database created with schema imported
# 4. Test connection manually:
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -e "SELECT COUNT(*) FROM users;"
```

### Admin Login Returns 500 Error
```bash
# 1. Check server logs for error details
# 2. Verify Auth.php is syntactically correct
# 3. Check PHP version supports syntax used
# 4. Run: php -l core/Auth.php
```

### JWT Token Issues
```bash
# 1. Verify JWT_SECRET is set in environment
# 2. Check it's same on all deployments
# 3. Ensure it's 32+ characters
# 4. Don't include quotes in value
```

### Email Verification Not Working
```bash
# 1. Email service not configured yet
# 2. Currently only database table exists
# 3. Configure MAIL_HOST, MAIL_PORT, etc. in .env
```

---

## 📊 Monitoring & Maintenance

### Daily Tasks
- [ ] Check error logs
- [ ] Monitor failed login attempts
- [ ] Check database performance
- [ ] Verify backups completed

### Weekly Tasks
- [ ] Review user registrations
- [ ] Check server resource usage
- [ ] Review security logs
- [ ] Update if patches available

### Monthly Tasks
- [ ] Review admin activity logs
- [ ] Audit user accounts
- [ ] Test disaster recovery
- [ ] Update documentation
- [ ] Performance optimization

---

## 🔐 Security Checklist (Production)

- [ ] HTTPS enabled (SSL certificate)
- [ ] Admin password changed
- [ ] JWT_SECRET is strong (32+ chars)
- [ ] .env file not in git
- [ ] Database backups enabled
- [ ] Firewall rules configured
- [ ] Rate limiting enabled
- [ ] CORS properly configured
- [ ] Input validation enabled
- [ ] SQL injection prevention active
- [ ] XSS protection enabled
- [ ] CSRF tokens validated

---

## 📞 Support During Deployment

### Common Issues

**Issue: Database won't connect**
- Solution: Verify credentials, check firewall, test connection

**Issue: Admin login still fails**
- Solution: Ensure schema imported, check admin user exists

**Issue: Emails not sending**
- Solution: Configure SMTP in .env, test with mail service

**Issue: Slow page loads**
- Solution: Optimize queries, add caching, check server resources

---

## ✅ Final Checklist Before Going Live

### Code Quality
- [ ] No console.log() or debug code
- [ ] No temporary files
- [ ] All PHP files validated
- [ ] No deprecated functions used

### Security
- [ ] No hardcoded credentials
- [ ] All inputs validated
- [ ] HTTPS enabled
- [ ] Strong secrets set

### Testing
- [ ] All routes tested
- [ ] Admin login verified
- [ ] User login verified
- [ ] Registration working
- [ ] Logout working
- [ ] Database working

### Documentation
- [ ] README updated
- [ ] Setup guide provided
- [ ] Emergency contacts listed
- [ ] Backup procedures documented

### Performance
- [ ] Page load time acceptable
- [ ] Database queries optimized
- [ ] Caching enabled (if applicable)
- [ ] CDN configured (if applicable)

### Monitoring
- [ ] Error logging enabled
- [ ] Performance monitoring setup
- [ ] Uptime monitoring enabled
- [ ] Security alerts configured

---

## 🎯 Deployment Status Template

```
Date: ________________
Environment: ________________
Deployed By: ________________
Version: ________________

Pre-Deployment: ✓ Complete
Testing: ✓ Complete
Deployment: ✓ Complete
Verification: ✓ Complete

Issues Found: None / List issues
Resolution: ________________

Sign-off: ________________
```

---

## 📝 Notes

- Database backups are critical - enable them immediately
- Monitor logs regularly for errors
- Update dependencies monthly
- Keep admin password secure
- Document all changes
- Have emergency procedures ready

---

**Last Updated:** 2024
**Deployment Ready:** YES ✅
**Live Service:** Ready for production deployment
