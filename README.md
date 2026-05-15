<p align="center">
  <h1 align="center">ConnectX Backend API</h1>
</p>

<p align="center">
  <strong>The official backend REST API for the ConnectX mobile application, powering matchmaking, real-time discovery, and seamless networking.</strong>
</p>

<p align="center">
  <a href="#-key-features">Key Features</a> •
  <a href="#-tech-stack">Tech Stack</a> •
  <a href="#-getting-started">Getting Started</a> •
  <a href="#-deployment">Deployment</a> •
  <a href="#-documentation">Documentation</a>
</p>

---

## 🚀 Key Features

* **Advanced Authentication:** Secure passwordless login via WhatsApp & Email OTP, alongside standard Social OAuth integrations (Google, Apple, LinkedIn).
* **Dynamic Onboarding Engine:** A robust, sequential 5-stage registration flow ensuring high-quality profile data collection.
* **Matchmaking & Discovery:** A highly optimized swipe-based feed algorithm utilizing Geo-location (Haversine formula), deep Tag/Skill matching, and Role compatibility scoring. Supports transactional *Swipe Right (Connect)* and *Swipe Left (Skip)* actions.
* **AI-Powered Match Analysis:** Automated background queues that generate JSON-based AI compatibility insights whenever a mutual match occurs.
* **Real-time Chat Integration:** 1-on-1 direct messaging capabilities synchronized directly with **Supabase Realtime Broadcast** for instantaneous mutual-match communication.
* **Scalable Architecture:** Architected to handle high-concurrency requests typical of mobile application usage.

## 🛠 Tech Stack

* **Framework:** [Laravel 11](https://laravel.com/) (PHP)
* **Database:** PostgreSQL (hosted on [Supabase Cloud](https://supabase.com/))
* **Real-time Engine:** Supabase Realtime
* **API Documentation:** OpenAPI 3.0 via [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)
* **Deployment:** Linux VPS / Docker (Nginx + PHP-FPM)

## 💻 Getting Started

### Prerequisites

Ensure you have the following installed on your local development machine:

* PHP >= 8.2
* Composer
* Node.js & NPM
* A PostgreSQL database (or a local Supabase instance)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-org/getconnect-x.git
   cd getconnect-x
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment Variables:**
   Copy the example environment file and configure your local settings, especially your database credentials and Supabase keys.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Database Migrations:**
   ```bash
   php artisan migrate
   ```

5. **Serve the Application:**
   ```bash
   php artisan serve
   ```

## ☁️ Deployment (VPS)

This application is designed to be deployed on a standard Linux Virtual Private Server (VPS). 

### Recommended Stack
* **Web Server:** Nginx or Apache
* **PHP:** PHP 8.2+ with PHP-FPM
* **Process Monitor:** Supervisor (for Laravel Queues)
* **SSL:** Let's Encrypt

Ensure you run the standard Laravel deployment commands during your CI/CD pipeline or manual setup:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

## 📚 Documentation

The backend provides an interactive Swagger UI for testing endpoints and reviewing the API contracts for the mobile frontend team.

* **Local Sandbox:** `http://localhost:8000/api/documentation`
* **Production Sandbox:** `https://your-production-domain.com/api/documentation`

For detailed technical notes on the initial authentication flow, refer to the [Auth Technical Guide](./README-AUTH.md).

---
*Built with ❤️ by the ConnectX Engineering Team.*
