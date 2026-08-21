# San Jose Del Monte Legislative Management System
## Role-Based Access Control (RBAC) & UI/UX Experience Matrix

**System Name:** Legislative Research, Policy Analysis, and Impact Evaluation System  
**Organization:** City Government of San Jose Del Monte, Bulacan  
**Last Updated:** August 22, 2026  
**Security Standard:** Principle of Least Privilege (PoLP) with Clean Contextual UI/UX  

---

## 1. Role Definitions & Architectural Scope

The system categorizes users into **4 distinct security tiers**, ensuring clear separation of duties across administrative control, policy analysis, data ingestion, and legislative oversight.

```
       ┌─────────────────────────────────────────────────────────┐
       │                ADMIN (Tier 1 - Full Control)            │
       │    User Management, Audit Logs, Delete, Settings        │
       └───────────────────────────┬─────────────────────────────┘
                                   │
       ┌───────────────────────────▼─────────────────────────────┐
       │              RESEARCHER (Tier 2 - Analyst)              │
       │    Policy Authoring, AI Research, Impact KPIs, Matrix   │
       └───────────────────────────┬─────────────────────────────┘
                                   │
       ┌───────────────────────────┴─────────────────────────────┐
       │                                                         │
┌──────▼──────────────────────────┐    ┌─────────────────────────▼─────┐
│  DATA ENCODER (Tier 3 - Ingest) │    │   VIEWER (Tier 4 - Oversight) │
│  Document Upload, Datasets Ingest│   │   Read-Only, Reports & Exports│
└─────────────────────────────────┘    └───────────────────────────────┘
```

| Role Key | Display Name | Target Persona | Scope & Core Responsibilities |
| :--- | :--- | :--- | :--- |
| `admin` | **System Administrator** | IT Admin / Sanggunian Secretary | Full system control, user account provisioning, role assignment, audit log monitoring, system configuration, and irreversible record deletions. |
| `researcher` | **Legislative Researcher** | Senior Policy Analysts / Legal Research Staff | Policy creation, editing, running Gemini AI legal analysis, evaluating impact assessment KPIs, comparative benchmarking matrix scoring, and report drafting. |
| `data_encoder` | **Data Encoder** | Research Assistants / Records Officers | Ingesting raw datasets, municipal surveys, departmental records, and uploading supporting documents to the Data Collection module. |
| `viewer` | **Viewer / Reviewer** | City Councilors, Committee Chairs, Department Liaisons | Read-only access to published policies, validated datasets, impact analytics, visual dashboards, and generating/printing official PDF/Word reports. |

---

## 2. Dynamic UI/UX Experience Matrix (By Role)

Rather than showing disabled buttons or prompting users with "Access Denied" dialogs, **the system automatically and cleanly tailors the navigation, dashboard, and actions to each user's exact role**.

```
┌─────────────────┬───────────────────────────┬────────────────────────────────────────────────────────┐
│ Role Key        │ Visible Sidebar Modules   │ Dashboard Quick Action Cards                           │
├─────────────────┼───────────────────────────┼────────────────────────────────────────────────────────┤
│ 👑 admin        │ • Dashboard               │ 1. User Management (Provision accounts & roles)        │
│                 │ • Policy Research         │ 2. Policy Research (Search, analyze, & manage)         │
│                 │ • Data Collection         │ 3. Data Collection (Review municipal datasets)         │
│                 │ • Impact Assessment       │ 4. Generate Reports (Export official briefs)           │
│                 │ • Benchmarking Analysis   │                                                        │
│                 │ • Report Generation       │                                                        │
│                 │ • Data Visualization      │                                                        │
│                 │ • User Management (Admin) │                                                        │
├─────────────────┼───────────────────────────┼────────────────────────────────────────────────────────┤
│ 🔬 researcher   │ • Dashboard               │ 1. Draft Policy (Create proposal)                      │
│                 │ • Policy Research         │ 2. AI Legal Citations (Gemini analysis)                │
│                 │ • Data Collection         │ 3. Impact KPIs (Evaluate scores)                       │
│                 │ • Impact Assessment       │ 4. Benchmarking (Score comparative matrix)             │
│                 │ • Benchmarking Analysis   │                                                        │
│                 │ • Report Generation       │                                                        │
│                 │ • Data Visualization      │                                                        │
├─────────────────┼───────────────────────────┼────────────────────────────────────────────────────────┤
│ 📥 data_encoder │ • Dashboard               │ 1. Upload Dataset (CSV, Excel, PDF ingestion)          │
│                 │ • Data Collection         │ 2. Dataset Library (Manage collection records)         │
│                 │ • Policy Research         │ 3. Policy Repository (Browse proposal references)      │
│                 │ • Data Visualization      │ 4. Data Visualization (View collection analytics)      │
├─────────────────┼───────────────────────────┼────────────────────────────────────────────────────────┤
│ 👁️ viewer       │ • Dashboard               │ 1. Policy Library (Read ordinances & citations)        │
│                 │ • Policy Research         │ 2. Impact Analytics (Review evaluation scores)         │
│                 │ • Impact Assessment       │ 3. Benchmarking (Examine comparative ordinances)       │
│                 │ • Benchmarking Analysis   │ 4. Export Reports (Download briefs & summaries)        │
│                 │ • Report Generation       │                                                        │
│                 │ • Data Visualization      │                                                        │
└─────────────────┴───────────────────────────┴────────────────────────────────────────────────────────┘
```

---

## 3. Comprehensive Module Permission Matrix

| Module / Feature Area | Specific Action | `admin` | `researcher` | `data_encoder` | `viewer` | Backend Guard |
| :--- | :--- | :---: | :---: | :---: | :---: | :--- |
| **Navigation & Header** | Dynamic Role Label in Header | ✅ System Admin | ✅ Researcher | ✅ Data Encoder | ✅ Reviewer | Dynamic from `$_SESSION['role']` |
| | Administration Menu in Sidebar | ✅ Visible | ❌ Hidden | ❌ Hidden | ❌ Hidden | `isAdmin()` |
| **User Administration** | Access User Management (`admin/users.php`) | ✅ | ❌ | ❌ | ❌ | `requireRole(ROLE_ADMIN)` |
| | Create New User Account | ✅ | ❌ | ❌ | ❌ | `canManageUsers()` |
| | Edit User Details & Roles | ✅ | ❌ | ❌ | ❌ | `canManageUsers()` |
| | Toggle User Status (Active/Inactive) | ✅ | ❌ | ❌ | ❌ | `canManageUsers()` |
| | Delete User Account | ✅ | ❌ | ❌ | ❌ | `canManageUsers()` |
| **Dashboard** | View Statistics & KPI Metrics | ✅ | ✅ | ✅ | ✅ | Read-Only |
| | View Upcoming Deadlines | ✅ | ✅ | ✅ | ✅ | Read-Only |
| | Create / Edit Deadlines | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Toggle Deadline Completion Status | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Delete Deadlines | ✅ | ❌ Hidden | ❌ Hidden | ❌ Hidden | `canDeletePolicy()` |
| | Mark Notifications as Read | ✅ | ✅ | ✅ | ✅ | Global |
| **Policy Research** | View Policy Repository & Details | ✅ | ✅ | ✅ | ✅ | Read-Only |
| | Create New Policy (`modules/add-policy.php`) | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `requireRole(['admin', 'researcher'])` |
| | Edit / Update Existing Policy Document | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `canEditPolicy()` |
| | Generate AI Legal Citations (Gemini API) | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `canRunAI()` |
| | Submit Policy to Data Collection | ✅ | ✅ | ✅ | ❌ Hidden | `canUploadData()` |
| | Print Legal Analysis Document | ✅ | ✅ | ✅ | ✅ | Global |
| | Delete Policy Document | ✅ | ❌ Hidden | ❌ Hidden | ❌ Hidden | `canDeletePolicy()` |
| **Data Collection** | View Datasets & Documents | ✅ | ✅ | ✅ | ❌ Hidden | Read-Only |
| | Upload New Datasets (CSV, PDF, Excel) | ✅ | ✅ | ✅ | ❌ Hidden | `canUploadData()` |
| | Approve / Reject Incoming Datasets | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Trigger Gemini AI Document Framework | ✅ | ✅ | ❌ Hidden | ❌ Hidden | Auto on Approval |
| | Forward to Impact Assessment Phase | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Delete Dataset Records & Files | ✅ | ❌ Hidden | ❌ Hidden | ❌ Hidden | `canDeletePolicy()` |
| **Impact Assessment** | View Assessment Records & Summaries | ✅ | ✅ | ❌ Hidden | ✅ | Read-Only |
| | Edit Assessment Rates & Beneficiaries | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Evaluate KPI Metrics (Effectiveness, Efficiency, Equity) | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Submit Assessment to Benchmarking | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Print Impact Assessment Report | ✅ | ✅ | ❌ Hidden | ✅ | Global |
| **Benchmarking Analysis** | View Comparative Benchmarks | ✅ | ✅ | ❌ Hidden | ✅ | Read-Only |
| | Score 10-Criteria Comparison Matrix | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Update Matrix Recommendations | ✅ | ✅ | ❌ Hidden | ❌ Hidden | `hasRole(['admin', 'researcher'])` |
| | Print & Download Comparative Reports | ✅ | ✅ | ❌ Hidden | ✅ | Global |
| **Report Generation** | View Generated Policy Briefs | ✅ | ✅ | ❌ Hidden | ✅ | Read-Only |
| | Generate & Download Official PDF Reports | ✅ | ✅ | ❌ Hidden | ✅ | `canGenerateReports()` |
| | Export Policy Brief to Word / Text | ✅ | ✅ | ❌ Hidden | ✅ | `canGenerateReports()` |
| **Data Visualization** | View Interactive Analytics & Charts | ✅ | ✅ | ✅ | ✅ | Read-Only |
| | Filter Date Ranges & Metric Categories | ✅ | ✅ | ✅ | ✅ | Read-Only |

---

## 4. Pre-Configured System User Accounts

Four unique user accounts representing each role have been created and verified in the database.

> **Default Password for all accounts:** `admin123`  
> All accounts are active, pre-seeded, and ready for immediate testing.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 TEST USER ACCOUNTS                                     │
├───────────────┬────────────────────────────┬──────────────┬────────────────────────────┤
│ Username      │ Full Name                  │ Role         │ Email                      │
├───────────────┼────────────────────────────┼──────────────┼────────────────────────────┤
│ admin         │ System Administrator       │ admin        │ asierra389@gmail.com       │
│ researcher    │ Senior Legislative Analyst │ researcher   │ researcher@sjdm.gov.ph     │
│ encoder       │ Data Specialist & Records  │ data_encoder │ encoder@sjdm.gov.ph        │
│ viewer        │ Legislative Reviewer       │ viewer       │ viewer@sjdm.gov.ph         │
└───────────────┴────────────────────────────┴──────────────┴────────────────────────────┘
```

### Account Details

#### 1. System Administrator (`admin`)
* **Username:** `admin`
* **Password:** `admin123`
* **Role:** `admin` *(System Administrator)*
* **Email:** `asierra389@gmail.com`
* **Contact Number:** `+63 912 345 6789`
* **Department:** `Information Technology / Office of the Sanggunian Secretary`
* **Status:** `Active`
* **Navbar Badge:** `System Administrator`
* **Access Scope:** Full system control, User Management, Activity Audit Logs, Record Deletion, System Configuration.

#### 2. Legislative Researcher (`researcher`)
* **Username:** `researcher`
* **Password:** `admin123`
* **Role:** `researcher` *(Legislative Researcher)*
* **Email:** `researcher@sjdm.gov.ph`
* **Contact Number:** `+63 920 111 2233`
* **Department:** `Legislative Research and Policy Development Division`
* **Status:** `Active`
* **Navbar Badge:** `Legislative Researcher`
* **Access Scope:** Drafting policy proposals, Gemini AI legal research, data validation, KPI impact scoring, comparative benchmarking matrix, and report authoring.

#### 3. Data Encoder (`encoder`)
* **Username:** `encoder`
* **Password:** `admin123`
* **Role:** `data_encoder` *(Data Encoder)*
* **Email:** `encoder@sjdm.gov.ph`
* **Contact Number:** `+63 930 444 5566`
* **Department:** `Data Ingestion & Records Management Section`
* **Status:** `Active`
* **Navbar Badge:** `Data Encoder`
* **Access Scope:** Uploading datasets, municipal baseline data, surveys, and viewing repositories.

#### 4. Legislative Viewer (`viewer`)
* **Username:** `viewer`
* **Password:** `admin123`
* **Role:** `viewer` *(Viewer / Reviewer)*
* **Email:** `viewer@sjdm.gov.ph`
* **Contact Number:** `+63 940 777 8899`
* **Department:** `Sangguniang Panlungsod Council Liaison / Public Review`
* **Status:** `Active`
* **Navbar Badge:** `Viewer / Reviewer`
* **Access Scope:** Read-only access to all dashboards, validated policies, impact charts, and report generation/printing.

---

## 5. Technical Security & Implementation Details

### A. Role Helper API (`includes/auth_helper.php`)

The application provides standardized security helper functions used across all controllers and templates:

```php
// Role Verification Constants
define('ROLE_ADMIN', 'admin');
define('ROLE_RESEARCHER', 'researcher');
define('ROLE_DATA_ENCODER', 'data_encoder');
define('ROLE_VIEWER', 'viewer');

// Core Authorization Functions
getCurrentUserRole(): string            // Returns the active session user's role
hasRole($roles): bool                   // Checks if current user matches given role(s)
requireRole($allowedRoles): void        // Enforces role requirement; redirects unauthorized users

// Convenience Boolean Guards
isAdmin(): bool                         // Role === 'admin'
isResearcher(): bool                    // Role === 'researcher'
isDataEncoder(): bool                   // Role === 'data_encoder'
isViewer(): bool                        // Role === 'viewer'

// Capability Guards
canManageUsers(): bool                  // Only admin
canDeletePolicy(): bool                 // Only admin
canEditPolicy(): bool                   // Admin, Researcher
canRunAI(): bool                        // Admin, Researcher
canUploadData(): bool                   // Admin, Researcher, Data Encoder
canGenerateReports(): bool              // Admin, Researcher, Viewer
```

### B. Two-Factor Authentication (OTP) Toggle

Two-factor authentication can be enabled or bypassed globally via environment configuration:

* **File:** [`config/.env`](file:///opt/lampp/htdocs/cap2/config/.env)
* **Variable:** `OTP_ENABLED=false` *(or `true` to require email OTP verification)*
* **Mechanism:** Handled via `isOTPEnabled()` in [`includes/auth_helper.php`](file:///opt/lampp/htdocs/cap2/includes/auth_helper.php). When set to `false`, users transition directly from credentials verification to the dashboard with a success toast notification.

### C. Global Toast Notification System

Toast alerts provide instant visual feedback on authentication, role violations, and data mutations:

* **File:** [`includes/toast.php`](file:///opt/lampp/htdocs/cap2/includes/toast.php)
* **Trigger:** Stored in `$_SESSION['toast'] = ['type' => 'success|error|warning|info', 'title' => '...', 'message' => '...']`
* **Display:** Automatically rendered in [`includes/navbar.php`](file:///opt/lampp/htdocs/cap2/includes/navbar.php) with timer progress bar, auto-dismiss, and animations.
