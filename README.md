# WebGym1 - Clean PHP Gym Management System

## 🏋️ Structure (Cleaned & Organized)
```
c:/xampp/htdocs/WebGym1/
├── index.php              # Landing page
├── db_connect.php         # Single DB connection
├── login.php, signup.php  # Auth
├── admin/                 # Admin panel
├── gym_owner/             # Owner dashboard
├── gym_joiner/            # Member dashboard  
├── controllers/           # MVC Controllers (Gym, Review, Notification, Auth...)
├── models/                # MVC Models
├── includes/              # Common (header, footer, auth)
├── assets/                # CSS/JS
├── uploads/               # Profiles/Pics
├── database/schema.sql    # Single DB schema
├── .gitignore             # Clean repo
└── README.md              # This file
```

## 🚀 Quick Start
1. **DB Setup**: Import `database/schema.sql` to phpMyAdmin (DB: webgym)
2. **XAMPP**: Start Apache + MySQL
3. **Access**: http://localhost/WebGym1/
4. **Roles**:
   - Admin: admin login → admin/
   - Gym Owner: → gym_owner/
   - Gym Member: → gym_joiner/

## ✨ Features
- User auth (OTP, reset password)
- Role-based dashboards
- Gym profiles, reviews, notifications
- Payments, memberships
- MVC architecture (PHP)

**Cleaned**: Removed duplicates (WebGym/), tests, CPP, excess SQL/images. No functionality changed.

© 2025 WebGym1
