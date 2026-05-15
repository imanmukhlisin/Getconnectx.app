<p align="center">
  <h1 align="center">ConnectX Backend API</h1>
</p>

<p align="center">
  <strong>The official backend service for ConnectX, powering matchmaking, real-time discovery, and seamless networking.</strong>
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
* **Fully Serverless Ready:** Architected specifically to achieve 100% compatibility with the Vercel Serverless ecosystem.

## 🛠 Tech Stack

* **Framework:** [Laravel 11](https://laravel.com/) (PHP)
* **Database:** PostgreSQL (hosted on [Supabase Cloud](https://supabase.com/))
* **Real-time Engine:** Supabase Realtime
* **API Documentation:** OpenAPI 3.0 via [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)
* **Deployment:** Vercel (Serverless Functions)

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

## ☁️ Deployment (Vercel)

This application has been meticulously modified to bypass traditional VPS limitations and run seamlessly on **Vercel Serverless Functions**. It handles Read-Only File System (EROFS) restrictions, temporary session storage, and routing conflict mitigation.

👉 **IMPORTANT:** Before deploying, please read the [Vercel Architecture & Setup Guide](./VERCEL_DEPLOYMENT_GUIDE.md).

## 📚 Documentation

The backend provides an interactive Swagger UI for testing endpoints and reviewing the API contracts.

* **Local Sandbox:** `http://localhost:8000/api/documentation`
* **Production Sandbox:** `https://your-vercel-domain.vercel.app/api/documentation`

For detailed technical notes on the initial authentication flow, refer to the [Auth Technical Guide](./README-AUTH.md).

---
*Built with ❤️ by the ConnectX Engineering Team.*
