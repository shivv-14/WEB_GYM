<<<<<<< HEAD
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
=======
# WebGym 🏋️‍♂️

### Full Stack Gym Management System

---

## 📌 Overview

**WebGym** is a complete **Gym Management Web Application** built using **PHP, MySQL, and modern frontend technologies**.

It provides a centralized platform for **Gym Members, Gym Owners, and Admin** to manage gym-related activities such as membership requests, profile management, and notifications.

The system follows a **modular MVC-inspired architecture**, ensuring scalability and clean code organization.

---

## 🎯 Objective

To digitize gym operations by:

* Simplifying membership management
* Enabling users to discover and join gyms
* Helping gym owners manage their business efficiently
* Providing a seamless and modern user experience

---

## 👥 User Roles

### 🔹 Gym Members

* Register/Login with authentication system
* Browse gyms with filtering options
* View gym details and location
* Send membership requests with plan selection
* Receive notifications on request status
* Manage personal profile and uploads

---

### 🔹 Gym Owners

* Create and manage gym profiles
* Upload gym images and details
* View and manage membership requests
* Approve/Reject users
* Track gym activity and users

---

### 🔹 Admin

* Manage system-level operations
* Control users and gym listings
* Monitor platform activity

---

## 🧠 Key Features

* 🔐 Authentication system (login/signup/logout)
* 📧 Email notifications system
* 🏋️ Gym discovery & filtering
* 🧾 Membership request handling
* 🔔 Notification system
* 👤 Profile management with image upload
* 🧩 MVC-based modular structure (Controllers + Models)
* 🎨 Responsive UI with custom styling

---

## 🏗️ Tech Stack

* **Backend:** PHP
* **Database:** MySQL / MariaDB
* **Frontend:** HTML, CSS, JavaScript
* **Architecture:** MVC-inspired (Controllers + Models)
* **Email Integration:** PHPMailer
* **Styling:** Custom CSS

---

## 📂 Project Structure

```id="webgymstruct"
webgym/
│
├── .gitignore
├── README.md
├── TODO.md
├── TODO_CLEANUP.md
│
├── index.php
├── login.php
├── signup.php
├── logout.php
├── reset_password.php
├── admin.php
├── userlogin.php
│
├── db_connect.php
├── send_notifications.php
├── question.php
│
├── controllers/
│   ├── AuthController.php
│   ├── GymController.php
│   ├── MembershipController.php
│   ├── NotificationController.php
│   └── ReviewController.php
│
├── models/
│   ├── Gym.php
│   ├── Notification.php
│   └── Review.php
│
├── admin/
│   ├── admin.php
│   ├── db_connect.php
│   └── logout.php
│
├── gym_joiner/
│   ├── user_dashboard.php
│   ├── profile.php
│   ├── notifications.php
│   ├── payment.php
│   └── gym_profile.php
│
├── gym_owner/
│   ├── gym_owner_dashboard.php
│   ├── setup_profile_gym_owner.php
│   ├── profile.php
│   ├── overview.php
│   └── notifications.php
│
├── assets/
│   ├── style1.css
│   └── styles.css
│
├── uploads/
│   └── (user images, gym images)
│
└── PHPMailer/
```

---

## ⚙️ How to Run

### 1. Clone the repository

```bash id="run1"
git clone https://github.com/shivv-14/webgym.git
```

### 2. Move to XAMPP htdocs

Place the project inside:

```id="run2"
C:/xampp/htdocs/
```

### 3. Setup Database

* Open phpMyAdmin
* Create database: `webgym_db`
* Import SQL file

### 4. Configure DB Connection

Edit:

```id="run3"
db_connect.php
```

### 5. Start Server

* Start Apache & MySQL (XAMPP)

### 6. Run Project

```id="run4"
http://localhost/webgym
```

---

## 📊 Current Development Status

* ✅ Authentication system
* ✅ User dashboard
* ✅ Gym listing & filtering
* ✅ Membership request system
* ✅ Notification system
* ✅ Profile management
* 🔄 UI improvements & cleanup in progress

---

## 🚀 Future Enhancements

* 💳 Online payment integration (Razorpay / Stripe)
* 📱 Fully responsive mobile UI
* 📊 Advanced analytics dashboard
* 🌐 Cloud deployment

---

## ⚠️ Notes

* Large upload files are excluded using `.gitignore`
* PHPMailer can be removed if not required
* Database file should be imported manually

---

## 👨‍💻 Author

**Brungi Shiva Ganesh**

---

## 🏷️ Domain

**Full Stack Web Development | Database Management | Web Application**
>>>>>>> cf280474a423c1811b603e5c0db6241f5d3353b1
