# Gofix – On-Demand Home Services Booking Platform

A full-featured web-based home service booking and management platform built with PHP and MySQL. Gofix bridges the gap between customers looking for household services (such as Electricians, Plumbers, AC Repair, Cleaning, Appliance Care, etc.) and skilled service providers, backed by a comprehensive administrative management dashboard.

---

## 📌 Features & Architecture

### 1. Customer Portal
- **Service Catalog:** Browse categories including AC Repair, Appliance Repair, Carpenter, Car Wash, Electrician, Home Cleaning, Laundry, Painter, Pest Control, Plumber, Salon at Home, and Sofa Cleaning.
- **Easy Booking System:** Step-by-step service request workflow with address, schedule, and custom requirements.
- **Order Tracking:** Real-time visibility into booking status (`Pending`, `Accepted`, `In Progress`, `Completed`, `Rescheduled`, `Cancelled`).
- **User Authentication:** Registration, login, password recovery (`forgot_password.php`), and profile management.

### 2. Service Provider Portal (`/Provider`)
- **Self-Registration & Verification:** Provider onboarding with government ID documents and profile photo verification.
- **Real-Time Job Dispatching:** Live request polling (`request-polling.js`) for nearby customer service requests.
- **Job Management:** Track and process `new_requests.php`, `accepted_jobs.php`, `rescheduled_jobs.php`, `completed_jobs.php`, and `rejected_jobs.php`.
- **Earnings & Dashboard:** Performance metrics and job status overview.

### 3. Administrator Panel (`/Admin`)
- **Central Analytics Dashboard:** Monitor total bookings, active customers, verified service providers, and platform revenue.
- **Booking Dispatch & Moderation:** Assign jobs to active providers, adjust booking statuses, and manage cancellations.
- **Service Management:** Add, edit, activate, or deactivate service categories and custom pricing.
- **Provider & Customer Management:** Review verification documents, approve or suspend accounts.
- **Financials & Reports:** Payment history tracking and date-range reporting.
- **Contact Desk:** Manage user feedback and customer support inquiries.

---

## 🛠️ Technology Stack

- **Backend:** PHP 8.x
- **Database:** MySQL 8.x (with `mysqli` prepared queries)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Server:** Apache (Laragon / XAMPP)

---

## 💻 Installation & Setup

### Prerequisites
- [Laragon](https://laragon.org/) or [XAMPP](https://www.apachefriends.org/) with PHP 8.0+ and MySQL 5.7+ / 8.0+
- Git installed on your machine

### Steps

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/Gouravsr75/Gofix.git
   ```
   *Or place the project directory inside `laragon/www/Gofix` or `xampp/htdocs/Gofix`.*

2. **Start Web & MySQL Server:**
   - Launch Laragon or XAMPP and start **Apache** and **MySQL**.

3. **Import Database:**
   - Open **phpMyAdmin** or HeidiSQL (`http://localhost/phpmyadmin`).
   - Create a new database named `fixlify_db`.
   - Import the SQL script provided at:
     ```
     database/fixlify_db.sql
     ```

4. **Verify Database Configuration:**
   - Inspect `db.php` in the project root to ensure settings match your local environment:
     ```php
     $host = "localhost";
     $user = "root";
     $password = "";
     $dbname = "fixlify_db";
     ```

5. **Run the Application:**
   - Open your browser and navigate to:
     ```
     http://localhost/Gofix/
     ```
   - Access Provider Portal: `http://localhost/Gofix/Provider/login.php`
   - Access Admin Panel: `http://localhost/Gofix/Admin/login.php`

---

## 📂 Project Structure

```
Gofix/
├── Admin/                     # Administrator management portal
├── Provider/                  # Service provider portal & job processing
├── css/                       # Global stylesheet files
├── js/                        # Global JavaScript scripts
├── image/                     # Service category icons and banners
├── uploads/                   # Uploaded service assets and documents
├── database/
│   └── fixlify_db.sql         # Database schema and seed data
├── index.php                  # Main customer landing page
├── booking.php                # Booking workflow
├── orders.php                 # Customer bookings tracker
├── db.php                     # Central database connection handler
└── README.md                  # Project documentation
```
