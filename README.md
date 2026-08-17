# LAPIFY — Laptop Marketplace Web Application
**Diploma Final Year Project · PHP 8 + MySQL + Bootstrap 5.3**

Lapify is a direct peer-to-peer laptop marketplace web application that allows users to buy brand new laptops, discover certified pre-owned deals, sell used laptops, and communicate with sellers directly via internal message threads—without intermediate payment gateways or order tracking complexities.

---

## Key Features

- **User Authentication:** Registration, login, session guard, password hashing (`bcrypt`), profile update, avatar upload, and password change.
- **Marketplace Browsing & Search:** Keyword search, multi-criteria sidebar filters (Brand, Price, Type `New`/`Old`, Condition), price sorting, and catalog pagination.
- **Selling & Listings:** Interactive laptop ad publication with image upload validation (MIME, max 2MB), listing edit, and status toggle (`Available`, `Sold`, `Inactive`).
- **Wishlist:** Heart toggle wishlist persistence with AJAX integration and dedicated saved items page.
- **Internal Messaging:** Conversation threads grouped by laptop listing between buyers and sellers.
- **Admin Panel:** Stat cards dashboard, user management (search & cascade delete), laptop moderation, and brand management with referential integrity safeguards.

---

## Tech Stack & Architecture

- **Frontend:** HTML5, CSS3 (Vanilla CSS Design System with custom color tokens), Bootstrap 5.3, Bootstrap Icons, JS form validation, image preview, password toggle.
- **Backend:** PHP 8 (Procedural logic & helper functions, clean includes architecture, no heavy frameworks).
- **Database:** MySQL (via MySQLi extension using prepared statements only). Exactly 5 normalized tables (`users`, `brands`, `laptops`, `wishlist`, `messages`).
- **Server:** Apache + MySQL (via XAMPP).

---

## Folder Structure

```
Lapify/
├── admin/
│   ├── dashboard.php  login.php  logout.php
│   ├── users.php  laptops.php  brands.php
│   └── header.php  sidebar.php  footer.php
├── assets/
│   ├── css/  style.css  responsive.css  dashboard.css
│   ├── js/   main.js  dashboard.js  validation.js
│   ├── images/
│   └── icons/
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── header.php  navbar.php  footer.php
│   ├── auth.php  functions.php
├── uploads/
│   ├── laptops/
│   └── profiles/
├── sql/
│   └── database.sql
├── index.php  login.php  register.php  logout.php
├── profile.php  dashboard.php
├── buy.php  sell.php  laptop-details.php
├── wishlist.php  inbox.php  my-listings.php
├── contact.php  about.php
└── README.md
```

---

## Quick Setup Instructions (XAMPP)

1. **Copy Project Folder:**
   Place the project folder into your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\Lapify` (or rename this directory to `Lapify`).

2. **Start Services:**
   Open XAMPP Control Panel and start both **Apache** and **MySQL**.

3. **Import Database:**
   - Open your browser and navigate to `http://localhost/phpmyadmin/`.
   - Click **Import** tab.
   - Choose the file `sql/database.sql` located inside the project `sql/` folder.
   - Click **Go** to create the `lapify` database and seed initial data.

4. **Run Application:**
   - Open `http://localhost/Lapify/` in your web browser.

---

## Default Login Credentials

### Administrator Account:
- **URL:** `http://localhost/Lapify/admin/login.php`
- **Email:** `admin@lapify.com`
- **Password:** `password123`

### Sample User Accounts:
- **Email:** `alex@example.com` | **Password:** `password123`
- **Email:** `sarah@example.com` | **Password:** `password123`

---

## Database Schema Overview (5 Tables)

1. `users`: Stores user and admin accounts with hashed passwords (`bcrypt`).
2. `brands`: Manufacturer brands (Apple, Dell, HP, Lenovo, Asus, Acer, MSI).
3. `laptops`: Main listing table containing specs, price, condition, type (`New`/`Old`), image path, and seller foreign keys.
4. `wishlist`: Saved listings per user (DB-enforced `unique_wish` constraint preventing duplicates).
5. `messages`: Peer-to-peer buyer-seller conversation messages linked to a specific laptop listing.

---

## Viva Presentation Points

- **Why no Framework?** The project demonstrates core PHP fundamentals, sessions, state management, SQL query preparation, and raw MySQLi database handling without relying on third-party abstractions.
- **Security:** Zero raw SQL concatenation; all user inputs are handled via Prepared Statements (`mysqli_prepare`). Output escaping is enforced with `htmlspecialchars()`. File uploads are checked for size limits and verified against image MIME types server-side.
- **Database Integrity:** Foreign keys with `ON DELETE CASCADE` ensure that deleting a user or laptop cleans up related wishlist items and messages safely.
