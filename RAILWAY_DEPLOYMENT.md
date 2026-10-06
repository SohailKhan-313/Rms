# 🚀 Deploying RMS (Restaurant Management System) to Railway

This document guides you through deploying your **Restaurant Management System (RMS)** application to **[Railway.app](https://railway.app/)**.

All required configuration files have been prepared and tested for zero-configuration, production-ready cloud deployment.

---

## 📦 Prepared Deployment Files

| File | Purpose |
| :--- | :--- |
| **`Dockerfile`** | Production container using **PHP 8.2 + Apache**, with all required extensions (`mysqli`, `pdo_mysql`, `gd`, `zip`), Composer dependencies, Apache `mod_rewrite`, and symlink for `/RMS`. |
| **`docker-entrypoint.sh`** | Configures Apache to dynamically bind to Railway's `$PORT`, ensures URL routing compatibility, and triggers automatic database migrations. |
| **`railway.json`** | Declares build configuration for Railway to automatically detect the Dockerfile and restart policies. |
| **`.dockerignore`** | Prevents log files, development scratchpads, and git files from bloating the Docker build image. |
| **`schema.sql`** | Full database dump with all tables (`orders`, `order_items`, `expenses`, `menue`, `category`, `customers`, `suppliers`, `login`, `restaurant_tables`, `restaurant_floors`), pre-seeded with food menu dishes and categories. |
| **`config/database.php`** | Smart multi-environment connector that automatically reads Railway environment variables (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQL_URL`) while keeping local development intact. |
| **`config/init_db.php`** | Automated database initializer that seeds tables and default admin accounts into your Railway MySQL database on initial launch. |
| **`index.php` & `.htaccess`** | Root entry point routing traffic from `https://your-app.up.railway.app/` directly to `/RMS/public/index.php`. |

---

## 🛠️ Step-by-Step Deployment Guide

### Step 1: Push Code to GitHub

Make sure your repository has all files pushed to GitHub:
```bash
git init
git add .
git commit -m "Add Railway deployment files and configuration"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/RMS.git
git push -u origin main
```

---

### Step 2: Create a New Project on Railway

1. Go to [railway.app](https://railway.app/) and sign in with your GitHub account.
2. Click **"+ New Project"**.
3. Select **"Deploy from GitHub repo"** and choose your `RMS` repository.

---

### Step 3: Add a MySQL Database Service

1. Inside your Railway project canvas, click **"+ Create"** or **"+ New"**.
2. Select **"Database"** ➡️ **"Add MySQL"**.
3. Railway will provision a dedicated MySQL instance in seconds.

---

### Step 4: Connect Web Service to MySQL

Railway automatically injects database credentials into linked services:

1. Click on your **RMS Web Service** box on the Railway canvas.
2. Go to the **"Variables"** tab.
3. Click **"+ New Variable"** ➡️ **"Add Reference"** (or Railway will prompt you to connect to the MySQL service).
4. Select the MySQL service so the following environment variables become available:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`
   - `MYSQL_URL`

*(Your `config/database.php` will automatically detect any of these variables!)*

---

### Step 5: Generate a Public Domain

1. In your **RMS Web Service**, go to the **"Settings"** tab.
2. Under **"Networking"**, click **"Generate Domain"** (e.g. `rms-production-xxxx.up.railway.app`).
3. Click on the domain link to launch the application.

---

## 🔑 Default Login Credentials

Once deployed, the database is automatically seeded by `config/init_db.php`. You can log in using:

- **Email:** `SOHAIL@gmail.com`
- **Password:** `123`

*(Alternative Administrator Account)*:
- **Email:** `admin@rms.com`
- **Password:** `admin123`

---

## 🔍 How Railway Handles Routing

- **Root Access:** Visiting `https://your-domain.railway.app/` automatically forwards to `/RMS/public/index.php`.
- **Paths & Assets:** The Docker image creates a symlink `RMS -> .` inside Apache's document root, ensuring every existing link (`/RMS/public/...`, `/RMS/views/...`, `/RMS/app/controller/...`) and all CSS/JS/images load seamlessly without modifying code.
- **Dynamic Port:** Railway injects the `$PORT` environment variable at boot time. `docker-entrypoint.sh` automatically binds Apache to this port.
