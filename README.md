
# 🎬 CineWave — Cinema Ticket & Booking Management System

CineWave is a full-stack, web-based cinema ticket and seat booking application. It provides moviegoers with an interactive interface to browse movies, select showtimes, reserve seats, and pre-order food & beverages, while equipping cinema administrators and managers with role-based dashboards for halls, showtimes, promotions, rewards, and analytical reporting.

🌐 **Live Demo:** [https://cinewave-cinema-management-system.onrender.com](https://cinewave-cinema-management-system.onrender.com)

---

## ✨ Key Features

### 🍿 Customer Experience
- **Movie Catalogue & Discovery:** Browse currently showing movies and view detailed information pages (`index.php`, `movie_catalogue.php`, `cinewave_info_page.php`).
- **Interactive Booking Flow:** Select preferred showtimes and pick exact seats via an interactive seating layout (`showtime_selection.php`, `movie_booking.php`, `seat.php`).
- **Group Bookings:** Dedicated workflow for reserving multiple seats or group packages (`groupbooking.php`).
- **Food & Beverage (F&B) Pre-Ordering:** Add snacks, drinks (e.g., popcorn, hotdogs, nachos, beverages), and value combos directly to a booking (`fnb_order.php`).
- **Promotions & Loyalty Rewards:** Apply special offers (such as Tuesday Specials, Student Time, Senior Discounts, and Birthday Month perks) and redeem loyalty rewards (`promotion.php`, `reward.php`).
- **Digital Ticketing & Checkout:** Seamless payment processing and instant digital e-ticket generation with QR code support (`payment.php`, `ticket.php`).
- **User Accounts:** Customer authentication, personal profile management, and about/info pages (`login.php`, `profile_edit.php`, `aboutme.php`).

### 🛠️ Staff & Management Portal (Role-Based Access Control)
- **Movie Management (Admin):** Add new titles and update or remove existing movie details (`movie_management.php`, `add_movie.php`, `movie_edit.php`, `movie_delete.php`).
- **Hall & Showtime Scheduling (Admin):** Configure cinema halls and schedule, modify, or remove movie showtimes (`hall_management.php`, `add_hall.php`, `hall_edit.php`, `hall_delete.php`, `showtime_management.php`, `add_showtime.php`, `showtime_edit.php`, `showtime_delete.php`).
- **Booking & F&B Operations (Admin):** Monitor customer reservations and manage concession menu items (`booking_management.php`, `fnb_management.php`, `add_fnb.php`, `fnb_edit.php`, `fnb_delete.php`).
- **Promotions & Rewards Control (Admin):** Create, update, and remove promotional campaigns and loyalty rewards (`promotion_management.php`, `add_promotion.php`, `promotion_edit.php`, `promotion_delete.php`, `reward_management.php`, `add_reward.php`, `reward_edit.php`, `reward_delete.php`).
- **Sales & Operations Reporting (Manager):** Generate analytical reports to track monthly/annual revenue, popular movies, ticket categories, and F&B sales (`report.php`).

---

## 🧰 Tech Stack

- **Backend:** PHP (PDO)
- **Database:** PostgreSQL / Supabase (`config/database.php`)
- **Frontend:** HTML5, CSS3 (`css/styles.css`, `header.php`, `footer.php`)
- **Hosting:** Render

---

## 📁 Project Structure

```text
cinewave2/
├── asset/                  # Static media & visual assets
│   ├── combo/              # F&B combo promotional graphics
│   ├── customer/           # Customer profile assets
│   ├── fnb/                # Individual snack & drink images
│   ├── movie/              # Movie posters & banners
│   ├── promotion/          # Promotional campaign banners
│   └── ticket/             # E-ticket & QR code assets
├── config/
│   └── database.php        # PostgreSQL PDO connection configuration
├── css/
│   └── styles.css          # Main application stylesheet
├── index.php               # Landing page / Home
├── header.php              # Reusable navigation header
├── footer.php              # Reusable page footer
├── login.php               # Customer, Admin & Manager authentication
├── movie_catalogue.php     # Movie listings
├── showtime_selection.php  # Date & time slot selector
├── movie_booking.php       # Ticket booking handler
├── seat.php                # Interactive hall seat map
├── groupbooking.php        # Group reservation module
├── fnb_order.php           # Concession & combo ordering
├── payment.php             # Checkout & payment processing
├── ticket.php              # Digital ticket & QR display
├── promotion.php           # Public promotions page
├── reward.php              # Loyalty rewards page
├── profile_edit.php        # User profile settings
├── report.php              # Manager analytics & revenue reporting
└── *_management.php        # Admin CRUD modules (Movies, Halls, Showtimes, F&B, Promotions, Rewards)

```

---

## 🚀 Getting Started

### Live Access

You can try out the hosted web version directly here:

👉 **[CineWave Live Web App](https://cinewave-cinema-management-system.onrender.com)**

*(Note: Since the app is hosted on Render's free tier, the initial page load may take around 30–50 seconds to spin up if the server has been inactive.)*

#### Demo Account Credentials

Use the following test accounts on `login.php` to explore each user role:

| Role | Email | Password | Access Scope |
| --- | --- | --- | --- |
| **Customer** | `weiting.tan@yahoo.com` | `cust123` | Browse movies, book seats, order F&B, redeem rewards |
| **Admin** | `dillon.robbins@cinewave.com` | `admin123` | Full CRUD access to Movies, Halls, Showtimes, F&B, Promotions & Rewards |
| **Manager** | `wille.burke@cinewave.com` | `admin123` | View-only management tables and full access to Analytical Sales Reports (`report.php`) |

---

### Local Installation & Setup

#### Prerequisites

* PHP 8.1+ with the `pdo_pgsql` extension enabled
* A PostgreSQL database (local PostgreSQL or a cloud instance such as **Supabase**)
* A web browser

#### Steps

1. **Clone the Repository**
```bash
git clone [https://github.com/](https://github.com/)<your-username>/<your-repo-name>.git
cd <your-repo-name>

```


2. **Configure the Database**
* Create a PostgreSQL database and run your SQL schema dump to create the tables and seed data.
* Open `config/database.php` and update your database connection credentials (`host`, `port`, `dbname`, `user`, `password`).


3. **Run the Application**
* Start PHP's built-in development server (or place the project in your local web server root):


```bash
php -S localhost:8000

```


* Open your browser and navigate to:


```text
http://localhost:8000/index.php

```
