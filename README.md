# VSITOA - Measurement Ads Platform

## 📊 Project Overview

VSITOA (Virtual Site Traffic & Online Ads) is a **PHP-based Measurement Ads Platform** where:
- **Advertisers** can create micro-tasks for URL submission
- **Workers** can complete tasks and earn rewards
- **Admin** can manage the platform

This is an **academic project** inspired by **aviso.bz** - a task-based gig marketplace.

---

## 🎯 Features

### ✅ For Advertisers
- Create measurement ads tasks
- Set payment per execution
- Configure target audience (geo-targeting, rating, age, gender)
- Manually verify worker submissions
- View task analytics
- Manage multiple active tasks

### ✅ For Workers
- Browse available tasks
- Complete tasks and submit proof (URL)
- Track submission status (pending/approved/rejected)
- View earnings and history
- Build rating and reputation

### ✅ For Admin
- Manage users
- Monitor all tasks and submissions
- Handle disputes
- Generate reports

---

## 📁 Project Structure

```
vsitoa/
├── app/                          # PHP Classes
│   ├── User.php                  # User management
│   ├── Task.php                  # Task management
│   ├── Submission.php            # Submission handling
│   ├── process_task.php          # Task creation handler
│   └── process_submission.php    # Submission handler
├── config/                       # Configuration files
│   ├── database.php              # Database connection
│   └── constants.php             # App constants
├── database/                     # Database schema
│   └── schema.sql                # MySQL schema
├── views/                        # HTML/PHP views
│   ├── index.php                 # Home page
│   ├── advertiser_dashboard.php  # Advertiser panel with task form
│   ├── worker_dashboard.php      # Worker panel
│   ├── login.php                 # Login page
│   └── register.php              # Registration page
├── assets/                       # Static files
│   ├── css/
│   │   ├── style.css             # Main styles
│   │   └── dashboard.css         # Dashboard styles
│   └── js/
│       └── dashboard.js          # Dashboard scripts
└── README.md                     # This file
```

---

## 🚀 Installation & Setup

### 1. **Create Database**

Import the SQL schema in phpMyAdmin or MySQL:

```bash
mysql -u root -p < database/schema.sql
```

Or manually:
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database: `vsitoa_db`
3. Import `database/schema.sql`

### 2. **Configure Database**

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'vsitoa_db');
```

### 3. **Start Apache & MySQL** (XAMPP)
```bash
# On Windows XAMPP
- Start Apache Module
- Start MySQL Module
```

### 4. **Access the Application**
```
Home:     http://localhost/vsitoa/views/index.php
Advertiser: http://localhost/vsitoa/views/advertiser_dashboard.php
Worker:    http://localhost/vsitoa/views/worker_dashboard.php
```

---

## 🔐 Default Credentials

### Admin
- **Username**: admin
- **Email**: admin@vsitoa.com
- **Password**: (hashed in database)

### Advertiser
- **Username**: advertiser1
- **Email**: advertiser@vsitoa.com

### Worker
- **Username**: worker1
- **Email**: worker@vsitoa.com

---

## 📋 Measurement Ads Task Form

### 🔹 Form Sections

1. **Advertiser Information**
   - Advertiser Name
   - Company / Website Name (Optional)

2. **Advertisement Type**
   - Website Visit
   - Measurement Ads
   - URL Submission Task

3. **Task / Assignment Title**
   - Job title with clear description

4. **Job Category**
   - Website Traffic
   - Measurement Ads
   - Online Promotion

5. **Task Verification Method**
   - ✓ Manual verification by advertiser (fixed)

6. **Task Description**
   - Clear instructions for workers

7. **Proof Requirement**
   - Website URL submission

8. **Target Website URL**
   - Link to be visited (http:// or https://)

9. **Payment & Reward Settings**
   - Payment per execution (min: 0.5 USDT)
   - Currency: USDT (dollars)

10. **Audience of Performers**
    - All registered users

11. **Execution Rules**
    - One user – one execution only
    - Max completion time: 30 minutes

12. **IP & Access Rules**
    - Any IP address allowed
    - Cookie clearing: Not necessary

13. **Performer Restrictions**
    - Rating restriction
    - Registration date
    - Gender restriction
    - Age limit

14. **Geo-Targeting**
    - All countries or selected countries

15. **Cost Summary**
    - Total estimated budget

16. **Advertiser Actions**
    - ✅ Create Advertisement
    - 👁️ Preview Task
    - 🚀 Publish Task
    - 🔄 Reset Form

---

## 🔄 Workflow

### Advertiser Workflow
1. Register as Advertiser
2. Go to "Advertiser Panel"
3. Create "Measurement Ads Task" using the form
4. Fill all required fields
5. Set payment per execution
6. Publish task to make it active
7. Monitor submissions
8. Verify & approve/reject submissions
9. Reward approved submissions

### Worker Workflow
1. Register as Worker
2. Browse available tasks
3. Select a task
4. Visit the target website
5. Submit final URL as proof
6. Wait for advertiser verification
7. Get approved/rejected
8. Receive payment if approved

### Admin Workflow
1. Manage all users
2. Monitor tasks and submissions
3. Handle disputes
4. Generate reports
5. Maintain platform integrity

---

## 💾 Database Schema

### Tables

**users** - User accounts (workers, advertisers, admins)
```sql
id, username, email, password, first_name, last_name, user_type, company_name, 
website_url, profile_image, rating, total_earnings, total_tasks_completed, 
registration_date, is_verified, is_active, last_login
```

**tasks** - Measurement ads & tasks
```sql
id, advertiser_id, title, description, ad_type, category, verification_method,
proof_type, target_website_url, payment_per_execution, total_budget, 
max_executions, current_executions, execution_type, max_completion_time,
ip_restriction, cookie_clearing, min_rating, allowed_countries, 
target_gender, target_age_min, target_age_max, status
```

**submissions** - Worker submissions
```sql
id, task_id, worker_id, submitted_url, submission_text, screenshot_path,
ip_address, user_agent, status, rejection_reason, verified_by, verified_at
```

**transactions** - Payment records
```sql
id, worker_id, advertiser_id, task_id, submission_id, amount, currency,
transaction_type, status
```

**task_analytics** - Task performance metrics
```sql
id, task_id, views, clicks, submissions, approved_submissions, 
rejected_submissions, total_spent
```

---

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (XAMPP)

---

## 📊 Use Cases

### For E-commerce Businesses
- Increase website traffic
- Measure user behavior
- Collect user feedback

### For Marketing Agencies
- Launch cost-effective ad campaigns
- Measure ad effectiveness
- Target specific audiences

### For Research
- Conduct user studies
- Test website features
- Gather user data ethically

---

## 🔐 Security Features

- ✓ Password hashing (bcrypt)
- ✓ SQL injection prevention (prepared statements)
- ✓ User authentication
- ✓ Session management
- ✓ IP tracking
- ✓ User agent logging
- ✓ URL validation

---

## 📈 Future Enhancements

- [ ] Email notifications
- [ ] Payment gateway integration
- [ ] Advanced analytics dashboard
- [ ] Task duplication feature
- [ ] Bulk task creation
- [ ] API integration
- [ ] Mobile app
- [ ] Real-time notifications
- [ ] Dispute resolution system
- [ ] User reviews & ratings

---

## 📝 Project Documentation

### For Academic Submission
- **Project Type**: PHP Web Application
- **Category**: Micro-task / Gig Marketplace Platform
- **Features**: Measurement Ads with Manual Verification
- **Database**: 5 main tables with proper relationships
- **Security**: Prepared statements, password hashing, session management
- **Responsive**: Mobile-friendly dashboard

---

## 🤝 Contributing

This is an academic project. For any improvements or suggestions:
1. Create an issue
2. Submit a pull request
3. Follow the code style

---

## 📄 License

Academic Use Only - 2026

---

## 👨‍💼 Author

**VSITOA Development Team**
- Created for academic purposes
- Based on aviso.bz concept
- Educational platform for learning PHP & MySQL

---

## 🆘 Support

For issues or questions:
1. Check the README
2. Review the code comments
3. Check database schema
4. Verify MySQL connection

---

**Last Updated**: January 27, 2026
**Version**: 1.0 (Beta)
  