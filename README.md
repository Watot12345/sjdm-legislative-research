# 📜 SJDM Legislative Research System

> A web-based legislative research and policy management platform for **San Jose Del Monte City, Bulacan**, built with PHP, MySQL, and AI-powered document analysis (Gemini API).

---

## 📌 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Setup Guide](#setup-guide)
  - [1. Install XAMPP](#1-install-xampp)
  - [2. Clone the Repository](#2-clone-the-repository)
  - [3. Install PHP Dependencies](#3-install-php-dependencies)
  - [4. Configure Environment Variables](#4-configure-environment-variables)
  - [5. Create the Database](#5-create-the-database)
  - [6. Import the SQL Schema](#6-import-the-sql-schema)
  - [7. Set Up File Upload Permissions](#7-set-up-file-upload-permissions)
  - [8. Start XAMPP and Access the App](#8-start-xampp-and-access-the-app)
- [Default Login Credentials](#default-login-credentials)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

---

## Overview

The SJDM Legislative Research System is a full-stack PHP application that helps city legislators, researchers, and staff:

- Manage and analyze policy documents
- Collect and organize legislative data
- Conduct impact assessments
- Perform benchmarking analysis
- Generate research reports
- Visualize data with interactive charts

AI-powered features are provided through the **Google Gemini API** (document summarization, legal citation generation, and research assistance).

---

## Features

| Module | Description |
|---|---|
| 🏠 Dashboard | Real-time stats, recent activity, and quick links |
| 📖 Policy Research | Upload, search, and AI-summarize policy documents |
| 🗄️ Data Collection | Upload and manage CSV, Excel, and document datasets |
| 📊 Impact Assessment | Create and track policy impact evaluations |
| ⚖️ Benchmarking Analysis | Compare local policies with regional/national benchmarks |
| 📄 Report Generation | Generate and export PDF research reports |
| 📈 Data Visualization | Interactive charts and graphs from collected data |
| 👤 User Management | Role-based access control (Admin only) |
| ⚙️ Settings | Personal appearance, notifications, and password settings |
| ❓ Help Center | Built-in documentation and FAQ |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x |
| Database | MySQL / MariaDB (via XAMPP) |
| Frontend | HTML5, Tailwind CSS (CDN), JavaScript |
| Icons | Font Awesome 6 |
| Fonts | Google Fonts – Inter |
| AI | Google Gemini API |
| PDF Parsing | `smalot/pdfparser` (via Composer) |
| Server | Apache (XAMPP) |

---

## Prerequisites

Before setting up, make sure you have the following installed:

- **XAMPP** (v8.x recommended) — https://www.apachefriends.org/download.html
- **Git** — https://git-scm.com/downloads
- **Composer** — https://getcomposer.org/download
- A **Google Gemini API Key** — https://aistudio.google.com/app/apikey

---

## Setup Guide

### 1. Install XAMPP

1. Download and install XAMPP from https://www.apachefriends.org
2. Open **XAMPP Control Panel**
3. Start **Apache** and **MySQL** services

> 💡 **Linux users**: XAMPP is typically installed at `/opt/lampp/`. Start it with:
> ```bash
> sudo /opt/lampp/lampp start
> ```

---

### 2. Clone the Repository

Navigate to the XAMPP web root directory and clone the project:

**Windows:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/Watot12345/sjdm-legislative-research.git cap2
```

**Linux / macOS:**
```bash
cd /opt/lampp/htdocs
git clone https://github.com/Watot12345/sjdm-legislative-research.git cap2
```

The project will be available at: `http://localhost/cap2/`

---

### 3. Install PHP Dependencies

Navigate into the project folder and install Composer packages:

```bash
cd cap2
composer install
```

This installs `smalot/pdfparser` used for reading uploaded PDF documents.

> ⚠️ If Composer is not in your PATH on Windows, use `php composer.phar install` instead.

---

### 4. Configure Environment Variables

Open `config/.env` in any text editor and fill in your values:

```env
# ============================================
# APPLICATION CONFIGURATION
# ============================================
APP_NAME="Legislative Research System"
APP_ENV="development"
APP_DEBUG=true

# ============================================
# DATABASE CONFIGURATION
# ============================================
DB_HOST="localhost"
DB_USERNAME="root"
DB_PASSWORD=""          # Leave blank for default XAMPP MySQL (no password)
DB_NAME="legislative_db"

# ============================================
# GEMINI AI API CONFIGURATION
# ============================================
# Get your key from: https://aistudio.google.com/app/apikey
GEMINI_API_KEY="YOUR_GEMINI_API_KEY_HERE"
GEMINI_MODEL="gemini-2.5-flash"
GEMINI_TEMPERATURE=0.7
GEMINI_MAX_TOKENS=4096

# ============================================
# APPLICATION SETTINGS
# ============================================
UPLOAD_MAX_SIZE=10485760
ALLOWED_FILE_TYPES="csv,xlsx,xls,pdf,doc,docx"
TIMEZONE="Asia/Manila"
```

> 🔑 Replace `YOUR_GEMINI_API_KEY_HERE` with your actual API key from Google AI Studio.

---

### 5. Create the Database

Open your browser and go to **phpMyAdmin**:

```
http://localhost/phpmyadmin
```

1. Click **"New"** in the left sidebar
2. Enter database name: `legislative_db`
3. Set collation: `utf8mb4_general_ci`
4. Click **"Create"**

**Or via MySQL CLI:**

```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE legislative_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Linux
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE legislative_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

---

### 6. Import the SQL Schema

**Via phpMyAdmin (recommended):**

1. In phpMyAdmin, click on `legislative_db` in the left sidebar
2. Click the **"Import"** tab at the top
3. Click **"Choose File"** and select: `database/schema.sql`
4. Click **"Go"** / **"Import"**

**Via CLI:**

```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root legislative_db < database/schema.sql

# Linux
/opt/lampp/bin/mysql -u root legislative_db < database/schema.sql
```

---

### 7. Set Up File Upload Permissions

Make sure the upload directories are writable:

**Linux / macOS:**
```bash
chmod -R 775 /opt/lampp/htdocs/cap2/uploads/
chmod -R 775 /opt/lampp/htdocs/cap2/modules/uploads/
chmod -R 775 /opt/lampp/htdocs/cap2/cache/
chmod -R 775 /opt/lampp/htdocs/cap2/logs/
```

**Windows:** No action needed — XAMPP runs with full permissions by default.

---

### 8. Start XAMPP and Access the App

1. Make sure **Apache** and **MySQL** are running in the XAMPP Control Panel
2. Open your browser and visit:

```
http://localhost/cap2/
```

You should see the **login page**. Use the default credentials below.

---

## Default Login Credentials

After importing the schema, a default admin account is available:

| Field | Value |
|---|---|
| **Username** | `admin` |
| **Password** | `admin123` |
| **Role** | Administrator |

> ⚠️ **Change this password immediately** after first login via **Settings → Change Password**.

---

## Project Structure

```
cap2/
├── admin/                  # Admin-only pages (User Management)
├── api/                    # AJAX / JSON API endpoints
├── cache/                  # AI response cache (auto-generated)
├── config/
│   ├── config.php          # DB connection & app config loader
│   └── .env                # Environment variables (NOT committed to Git)
├── database/
│   └── schema.sql          # Full database schema — import this to set up
├── includes/
│   ├── sidebar.php         # Shared sidebar navigation
│   ├── navbar.php          # Shared top navbar + notifications
│   ├── FileAnalyzer.php    # PDF/document file parser
│   └── gemini_helper.php   # Gemini AI API wrapper
├── modules/
│   ├── policy-research.php
│   ├── data-collection.php
│   ├── impact-assessment.php
│   ├── benchmarking-analysis.php
│   ├── report-generation.php
│   ├── data-visualization.php
│   ├── profile.php         # User profile page
│   ├── settings.php        # Personal settings
│   └── help-center.php     # Documentation & FAQ
├── uploads/
│   ├── datasets/           # Uploaded CSV/Excel files
│   ├── documents/          # Uploaded PDF/Word documents
│   └── reports/            # Generated report files
├── logs/                   # Application logs
├── composer.json           # PHP dependency definition
├── dashboard.php           # Main dashboard
├── login.php               # Authentication page
├── logout.php              # Session termination
└── index.php               # App entry point / redirect
```

---

## User Roles

The system has three role levels with different access:

| Role | Access |
|---|---|
| **Admin** | Full access — all modules + User Management |
| **Researcher** | All core modules — Policy Research, Data Collection, Impact Assessment, Benchmarking, Report Generation, Data Visualization |
| **Viewer** | Read-only access to modules (no create/edit/delete) |

Roles are assigned by the Admin in **User Management** (`admin/users.php`).

---

## Troubleshooting

### ❌ "Connection failed" database error
- Make sure **MySQL is running** in XAMPP Control Panel
- Verify `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD` in `config/.env`
- On Linux, if `localhost` fails, change `DB_HOST` to `127.0.0.1`

### ❌ Blank page or PHP errors
- Set `APP_DEBUG=true` in `config/.env`
- Check Apache error logs:
  - Windows: `C:\xampp\apache\logs\error.log`
  - Linux: `/opt/lampp/logs/error_log`

### ❌ File uploads not working
- Check that `uploads/` directories exist and are writable (see Step 7)
- Verify `UPLOAD_MAX_SIZE` in `.env` matches `upload_max_filesize` in your `php.ini`

### ❌ AI features not working (Gemini)
- Make sure `GEMINI_API_KEY` is correctly set in `config/.env`
- Confirm the key is active at https://aistudio.google.com
- Check your API quota — the free tier has rate limits

### ❌ Composer not found
- Install Composer from https://getcomposer.org
- On Windows, restart your terminal after installation

### ❌ "Permission denied" on Linux
```bash
sudo chown -R daemon:daemon /opt/lampp/htdocs/cap2/uploads/
sudo chmod -R 775 /opt/lampp/htdocs/cap2/uploads/
```

---

## Contributing

1. **Fork** the repository on GitHub
2. **Clone** your fork locally
3. Create a new branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```
4. Make your changes and **commit**:
   ```bash
   git add .
   git commit -m "feat: describe your change"
   ```
5. **Push** to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```
6. Open a **Pull Request** on GitHub against the `master` branch

> 📝 Please test your changes locally before submitting a PR.

---

## Repository

```
https://github.com/Watot12345/sjdm-legislative-research.git
```

---

<div align="center">
  <sub>Built for San Jose Del Monte City, Bulacan · Legislative Research Division</sub>
</div>
